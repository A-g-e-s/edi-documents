<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Document;

readonly class Party
{
    public function __construct(
        public PartyQualifier $qualifier,
        public string $gln,
        public ?string $name = null,
        public ?string $street = null,
        public ?string $place = null,
        public ?string $postcode = null,
        public ?string $countryCode = null,
        public ?string $countryName = null,
        public ?string $companyId = null,
        public ?string $taxId = null,
        public ?string $vatId = null,
        public ?string $contactName = null,
        public ?string $contactTel = null,
        public ?string $contactEmail = null,
    ) {
    }
}
