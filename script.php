<?php


namespace Bank\Commission\Service;

require_once 'src/Service/Transaction.php';
require_once 'src/Service/BankService.php';

$bankService = new BankService();

$file = fopen('input.csv', 'rb');

while (($line = fgetcsv($file)) !== false) {
    [$dateStr, $userIdentificator, $userType, $operationType, $amount, $currency] = $line;

    $transaction = new Transaction($dateStr, $userIdentificator, $userType, $operationType, $amount, $currency);
    $commission = $bankService->processTransaction($transaction);

    echo $commission . PHP_EOL;
}

fclose($file);