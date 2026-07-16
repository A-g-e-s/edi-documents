<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Document\Desadv;

use Ages\EdiDocuments\Document\Party;

/**
 * Dodací list (ORION DESADV). Jeden doklad = jedna obalová jednotka s jedním SSCC kódem.
 */
readonly class DesadvDocument
{
    /**
     * @param Party[] $parties
     * @param DesadvArticle[] $articles
     */
    public function __construct(
        public string $docNumber,
        public \DateTimeImmutable $issueDate,
        public string $ssccCode,
        public array $parties,
        public array $articles,
        public ?\DateTimeImmutable $deliveryDate = null,
        public ?string $orderNumber = null,
        public ?string $despatchAdviceNumber = null,
        public ?string $invoiceNumber = null,
        public ?string $receiverGln = null,
    ) {
    }
}
