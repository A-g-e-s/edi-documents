<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Document\Desadv;

use Ages\EdiDocuments\Document\Party;
use Ages\EdiDocuments\EdiException;

/**
 * Dodací list (ORION DESADV).
 *
 * Obalové jednotky (palety, kartony) se předávají v `packages` – každá má vlastní SSCC a obsah.
 * Zpětně kompatibilně lze místo nich předat jedinou jednotku přes `ssccCode` + `articles`.
 */
readonly class DesadvDocument
{
    /**
     * @param Party[] $parties
     * @param DesadvArticle[] $articles
     * @param DesadvPackage[] $packages
     */
    public function __construct(
        public string $docNumber,
        public \DateTimeImmutable $issueDate,
        public ?string $ssccCode,
        public array $parties,
        public array $articles = [],
        public ?\DateTimeImmutable $deliveryDate = null,
        public ?string $orderNumber = null,
        public ?string $despatchAdviceNumber = null,
        public ?string $invoiceNumber = null,
        public ?string $receiverGln = null,
        public array $packages = [],
    ) {
    }

    /**
     * @return list<DesadvPackage>
     */
    public function packages(): array
    {
        if ($this->packages !== []) {
            return array_values($this->packages);
        }
        if ($this->ssccCode === null) {
            throw new EdiException('Dodací list musí obsahovat alespoň jednu obalovou jednotku (packages nebo ssccCode).');
        }
        return [new DesadvPackage(1, $this->ssccCode, $this->articles)];
    }
}
