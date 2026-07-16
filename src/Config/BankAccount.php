<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Config;

readonly class BankAccount
{
    public function __construct(
        public string $accountNumber,
        public string $bankCode,
        public ?string $bankName = null,
        public ?string $iban = null,
        public ?string $swift = null,
    ) {
    }
}
