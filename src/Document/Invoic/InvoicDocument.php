<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Document\Invoic;

use Ages\EdiDocuments\Document\Party;

/**
 * Faktura (ORION INVOIC).
 */
readonly class InvoicDocument
{
    /**
     * @param Party[] $parties
     * @param InvoicItem[] $items
     */
    public function __construct(
        public string $docNumber,
        public \DateTimeImmutable $issueDate,
        public \DateTimeImmutable $taxDate,
        public \DateTimeImmutable $dueDate,
        public string $currencyCode,
        public array $parties,
        public array $items,
        public InvoicDocType $docType = InvoicDocType::Invoice,
        public ?\DateTimeImmutable $deliveryDate = null,
        public string $paymentMeans = '42',
        public ?string $registration = null,
        public ?string $regulatoryInformation = null,
        public ?string $orderNumber = null,
        public ?string $despatchAdviceNumber = null,
        public ?string $variableSymbol = null,
        public ?string $constantSymbol = null,
        public float $pricePaid = 0.0,
        public float $roundingDifference = 0.0,
        public ?string $receiverGln = null,
    ) {
    }
}
