<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Document\Desadv;

/**
 * Jedna obalová jednotka (paleta, karton) v dodacím listu – vlastní SSCC kód a obsah.
 */
readonly class DesadvPackage
{
    /**
     * @param DesadvArticle[] $articles
     */
    public function __construct(
        public int $number,
        public string $ssccCode,
        public array $articles,
        public string $packageReference = '1E',
    ) {
    }
}
