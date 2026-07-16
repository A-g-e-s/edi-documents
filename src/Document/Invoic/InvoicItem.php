<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Document\Invoic;

readonly class InvoicItem
{
    public function __construct(
        public string $gtin,
        public string $name,
        public float $quantity,
        public float $grossPrice,
        public float $netPrice,
        public float $totalPrice,
        public float $vatRate,
        public float $vatTotal,
        public float $totalPriceVat,
        public ?string $supplierArticleId = null,
        public ?string $buyerArticleId = null,
        public string $itemType = 'Z',
        public float $allowanceRate = 0.0,
        public float $allowanceTotal = 0.0,
        public string $unit = 'KS',
        public float $numberOfUnits = 1.0,
        public ?\DateTimeImmutable $deliveryDate = null,
        public ?string $orderNumber = null,
        public ?string $supplierOrderNumber = null,
        public ?string $despatchAdviceNumber = null,
    ) {
    }
}
