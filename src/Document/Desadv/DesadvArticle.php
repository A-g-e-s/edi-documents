<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Document\Desadv;

readonly class DesadvArticle
{
    public function __construct(
        public string $gtin,
        public string $name,
        public float $quantity,
        public ?string $supplierArticleId = null,
        public ?string $buyerArticleId = null,
        public string $itemType = 'Z',
        public string $unit = 'KS',
        public float $numberOfUnits = 1.0,
        public ?string $orderNumber = null,
        public ?string $supplierOrderNumber = null,
    ) {
    }
}
