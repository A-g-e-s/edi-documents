<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Config;

use Ages\EdiDocuments\Document\Party;
use Ages\EdiDocuments\Document\PartyQualifier;
use Ages\EdiDocuments\EdiException;

readonly class EdiConfig
{
    public function __construct(
        public Party $supplier,
        public string $ssccCompanyPrefix,
        public ?BankAccount $bankAccount = null,
        public int $ssccExtensionDigit = 0,
        public string $messageVersion = '1.0.0',
        public bool $testFlag = false,
        public ?string $logoPath = null,
    ) {
        if ($this->supplier->qualifier !== PartyQualifier::Supplier) {
            throw new EdiException('Dodavatel v konfiguraci musí mít kvalifikátor SU.');
        }
        if (!ctype_digit($this->ssccCompanyPrefix)) {
            throw new EdiException('SSCC company prefix musí obsahovat pouze číslice.');
        }
        $serialLength = 16 - strlen($this->ssccCompanyPrefix);
        if ($serialLength < 1) {
            throw new EdiException('SSCC company prefix je příliš dlouhý, nezbývá místo pro sériové číslo.');
        }
        if ($this->ssccExtensionDigit < 0 || $this->ssccExtensionDigit > 9) {
            throw new EdiException('SSCC extension digit musí být 0–9.');
        }
    }
}
