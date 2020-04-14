<?php

declare(strict_types=1);

namespace Bank\Commission\Service;

require_once 'User.php';

class BankService
{
    private const CASH_IN_PERCENTAGE = 0.03;

    private const CASH_OUT_PERCENTAGE = 0.3;

    private const MAX_COMMISSION_EURO = 5;

    private const MIN_COMMISSION_EURO = 0.5;

    private const USD_CONVERSION_RATE = 1.1497;

    private const JPY_CONVERSION_RATE = 129.53;

    private const FREE_TRANSACTIONS_COUNT = 3;

    private const FREE_CASH_OUT_LIMIT = 1000;

    private const ROUNDING_FACTOR = 10 ** 2;

    /**
     * @var User[]
     */
    private $users;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * BankService constructor.
     */
    public function __construct()
    {
        $this->users = [];
    }

    /**
     * @param Transaction $transaction
     * @return string
     */
    public function processTransaction(Transaction $transaction): string
    {
        $this->transaction = $transaction;
        // Get user id from current transaction
        $transactionUserId = $transaction->getUserIdentificator();

        if ($this->isUserExists($transactionUserId)) {
            $oldUser = $this->getUserById($transactionUserId);
            $oldUser->addTransaction($transaction);
            $this->users[$transactionUserId] = $oldUser;
        } else {
            $newUser = new User($transactionUserId);
            $newUser->addTransaction($transaction);
            $this->addUser($newUser);
        }

        return $this->calculateCommission();
    }

    /**
     * @return string
     */
    private function calculateCommission(): string
    {
        $commission = $this->transaction->getOperationType() === 'cash_in' ? $this->cashIn() : $this->cashOut();
        return $this->rounder($commission);
    }

    /**
     * @return float
     */
    private function cashIn(): float
    {
        $commission = self::CASH_IN_PERCENTAGE / 100 * $this->transaction->getAmount();
        $commissionEuros = $this->convertCommissionInEuros($commission);

        if ($commissionEuros > self::MAX_COMMISSION_EURO) {
            return $this->convertEuroToInputCurrency(self::MAX_COMMISSION_EURO);
        }

        return $commission;
    }

    /**
     * @return float
     */
    private function cashOut(): float
    {
        $commission = $this->transaction->getUserType() === 'natural' ?
            $this->getNaturalCommission() : $this->getLegalCommission();
        return $commission;
    }

    /**
     * @return float
     */
    private function getNaturalCommission(): float
    {
        // Count of all week transactions of the current user, including the current one
        $countTransactions = $this->countWeekTransactions();
        $amount = $this->transaction->getAmount();

        // Check whether the transaction is the first second or third for the week
        if ($countTransactions <= self::FREE_TRANSACTIONS_COUNT) {
            // Amount of transactions for the current week in Euro
            $totalCashOutEuros = $this->getTotalCashOut();

            // Check if we have exceeded the limit of 1000
            if ($totalCashOutEuros > self::FREE_CASH_OUT_LIMIT) {
                // How much we have exceeded the limit
                $overAllTransactions = $totalCashOutEuros - self::FREE_CASH_OUT_LIMIT;
                $amountInEuros = $this->getAmountInEuros($this->transaction);
                // Check whether the current transaction has exceeded the limit or has already been exceeded
                $isCurrentExceededLimit = $totalCashOutEuros - $amountInEuros <= self::FREE_CASH_OUT_LIMIT;

                if ($isCurrentExceededLimit) {
                    // How much the current transaction has exceeded the limit
                    $overCurrentTransactionEuros = $amountInEuros - self::FREE_CASH_OUT_LIMIT;

                    if ($overCurrentTransactionEuros == 0) {
                        return self::CASH_OUT_PERCENTAGE / 100 * $this->convertEuroToInputCurrency($overAllTransactions);
                    }

                    // We only charge a commission for the amount that we have exceeded the limit
                    return self::CASH_OUT_PERCENTAGE / 100 * $this->convertEuroToInputCurrency($overCurrentTransactionEuros);
                }

                // The limit is NOT exceeded by the current transaction but it is still exceeded so we charge a full commission
                return self::CASH_OUT_PERCENTAGE / 100 * $amount;
            }

            // Free of charge
            return 0;
        }

        // For forth and other operations commission is calculated by default rules
        return self::CASH_OUT_PERCENTAGE / 100 * $amount;
    }

    /**
     * @return float
     */
    private function getLegalCommission(): float
    {
        $commission = self::CASH_OUT_PERCENTAGE / 100 * $this->transaction->getAmount();
        $commissionEuros = $this->convertCommissionInEuros($commission);

        if ($commissionEuros < self::MIN_COMMISSION_EURO) {
            return $this->convertEuroToInputCurrency(self::MIN_COMMISSION_EURO);
        }

        return $commission;
    }

    /**
     * @param int $userId
     * @return bool
     */
    private function isUserExists(int $userId): bool
    {
        return array_key_exists($userId, $this->users);
    }

    /**
     * @param float $commission
     * @return float
     */
    private function convertCommissionInEuros(float $commission): float
    {
        $currency = $this->transaction->getCurrency();

        if ($currency === 'USD') {
            return $commission / self::USD_CONVERSION_RATE;
        }

        if ($currency === 'JPY') {
            return $commission / self::JPY_CONVERSION_RATE;
        }

        return $commission;
    }

    /**
     * @param float $euros
     * @return float
     */
    private function convertEuroToInputCurrency(float $euros): float
    {
        $currency = $this->transaction->getCurrency();

        if ($currency === 'USD') {
            return $euros * self::USD_CONVERSION_RATE;
        }

        if ($currency === 'JPY') {
            return $euros * self::JPY_CONVERSION_RATE;
        }

        return $euros;
    }

    /**
     * @param User $user
     */
    private function addUser(User $user): void
    {
        $id = $user->getId();
        $this->users[$id] = $user;
    }

    /**
     * @param int $userId
     * @return User
     */
    private function getUserById(int $userId): User
    {
        if ($this->isUserExists($userId)) {
            return $this->users[$userId];
        }
    }

    /**
     * @return int
     */
    private function countWeekTransactions(): int
    {
        $dateCurrentTransaction = $this->transaction->getDate();
        $dateCurrentTransactionStr = $dateCurrentTransaction->format('Y-m-d');
        $dateCurrentTransactionStrToTime = strtotime($dateCurrentTransactionStr);
        $userId = $this->transaction->getUserIdentificator();
        $currentUser = $this->getUserById($userId);
        $userTransactions = $currentUser->getTransactions();
        $count = 0;

        foreach ($userTransactions as $transaction) {
            $dateTransaction = $transaction->getDate();
            $dateTransactionStr = $dateTransaction->format('Y-m-d');
            $dateTransactionStrToTime = strtotime($dateTransactionStr);
            $sameWeekCheck = date('oW', $dateCurrentTransactionStrToTime) ===
                date('oW', $dateTransactionStrToTime);

            if ($sameWeekCheck) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return float
     */
    private function getTotalCashOut(): float
    {
        $dateCurrentTransaction = $this->transaction->getDate();
        $dateCurrentTransactionStr = $dateCurrentTransaction->format('Y-m-d');
        $dateCurrentTransactionStrToTime = strtotime($dateCurrentTransactionStr);
        $userId = $this->transaction->getUserIdentificator();
        $currentUser = $this->getUserById($userId);
        $userTransactions = $currentUser->getTransactions();
        $totalCashOutEuros = 0;

        foreach ($userTransactions as $transaction) {
            $dateTransaction = $transaction->getDate();
            $dateTransactionStr = $dateTransaction->format('Y-m-d');
            $dateTransactionStrToTime = strtotime($dateTransactionStr);
            $sameWeekCheck = date('oW', $dateCurrentTransactionStrToTime) ===
                date('oW', $dateTransactionStrToTime);

            if ($sameWeekCheck && $transaction->getOperationType() === 'cash_out') {
                $totalCashOutEuros += $this->getAmountInEuros($transaction);
            }
        }

        return $totalCashOutEuros;
    }

    /**
     * @param Transaction $transaction
     * @return float
     */
    private function getAmountInEuros(Transaction $transaction): float
    {
        $currency = $transaction->getCurrency();
        $amount = $transaction->getAmount();

        if ($currency === 'USD') {
            return $amount / self::USD_CONVERSION_RATE;
        }

        if ($currency === 'JPY') {
            return $amount / self::JPY_CONVERSION_RATE;
        }

        return $amount;
    }

    /**
     * @param $number
     * @return string
     */
    private function rounder($number): string
    {
        $currency = $this->transaction->getCurrency();

        if ($currency === 'JPY') {
            return (string)ceil($number);
        }

        // Rounds up a float to a specified number of decimal places
        $number = ceil($number * self::ROUNDING_FACTOR) / self::ROUNDING_FACTOR;
        return number_format($number, 2, '.', '');
    }
}

