<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Sscc;

use Ages\EdiDocuments\Document\Party;

readonly class SsccLabelData
{
    public function __construct(
        public string $sscc,
        public string $recipientName,
        public string $recipientStreet,
        public string $recipientCity,
        public string $recipientZip,
        public string $recipientCountry,
        public string $reference,
        public int $parcelNumber = 1,
        public int $totalParcels = 1,
        public ?float $weightKg = null,
        public ?string $recipientName2 = null,
        public ?string $orderNumber = null,
        public ?Party $sender = null,
        /** Označení jednotky na etiketě, např. „Paleta 2 / 3“. */
        public string $unitLabel = 'Paleta',
    ) {
    }
}
