<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Generator;

readonly class EdiDocumentFile
{
    public function __construct(
        public string $filename,
        public string $content,
    ) {
    }
}
