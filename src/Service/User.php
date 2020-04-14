<?php

declare(strict_types=1);

namespace Bank\Commission\Service;


class User
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Transaction[]
     */
    private $transactions;

    /**
     * User constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->transactions = [];
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Transaction[]
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * @param Transaction $transaction
     * @return User
     */
    public function addTransaction(Transaction $transaction): User
    {
        $this->transactions[] = $transaction;
        return $this;
    }
}