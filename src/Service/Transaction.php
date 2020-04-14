<?php

declare(strict_types=1);

namespace Bank\Commission\Service;


class Transaction
{
    /**
     * @var \DateTime $date
     */
    private $date;

    /**
     * @var int $userIdentificator
     */
    private $userIdentificator;

    /**
     * @var string $userType
     */
    private $userType;

    /**
     * @var string $operationType
     */
    private $operationType;

    /**
     * @var float $amount
     */
    private $amount;

    /**
     * @var string $currency
     */
    private $currency;

    /**
     * Transaction constructor.
     * @param string $dateStr
     * @param int $userIdentificator
     * @param string $userType
     * @param string $operationType
     * @param float $amount
     * @param string $currency
     */
    public function __construct(
        string $dateStr,
        int $userIdentificator,
        string $userType,
        string $operationType,
        float $amount,
        string $currency
    )
    {
        $date = \DateTime::createFromFormat('Y-m-d', $dateStr);
        $this->date = $date;
        $this->userIdentificator = $userIdentificator;
        $this->userType = $userType;
        $this->operationType = $operationType;
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @return int
     */
    public function getUserIdentificator(): int
    {
        return $this->userIdentificator;
    }

    /**
     * @return string
     */
    public function getUserType(): string
    {
        return $this->userType;
    }

    /**
     * @return string
     */
    public function getOperationType(): string
    {
        return $this->operationType;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }
}