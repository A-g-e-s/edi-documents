<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Generator;

use Ages\EdiDocuments\Document\Desadv\DesadvDocument;
use Ages\EdiDocuments\EdiException;

/**
 * Generátor zprávy ORION DESADV XML (dokumentace v. 3.1.8).
 *
 * Každá obalová jednotka (paleta) je jeden <item> v line_items s vlastním
 * package_number, SSCC kódem a seznamem artiklů, které jsou v ní fyzicky uložené.
 */
class DesadvXmlGenerator extends AbstractOrionXmlGenerator
{
    public function generate(DesadvDocument $document, ?\DateTimeImmutable $createdAt = null): EdiDocumentFile
    {
        $packages = $document->packages();
        foreach ($packages as $package) {
            if ($package->articles === []) {
                throw new EdiException(sprintf('Obalová jednotka %d (SSCC %s) neobsahuje žádnou položku.', $package->number, $package->ssccCode));
            }
        }

        $parties = $this->partiesWithSupplier($document->parties);
        $receiver = $this->resolveReceiver($document->receiverGln, $parties);

        [$dom, $body] = $this->createMessage('DESADV', $receiver, $createdAt);

        $header = $this->appendChild($body, 'message_header');
        $this->appendElement($header, 'doc_number', $document->docNumber);
        $this->appendElement($header, 'doc_type', '351');
        $this->appendElement($header, 'doc_function', '9');
        $this->appendElement($header, 'doc_date_of_issue', $document->issueDate->format('Y-m-d'));
        $this->appendElement($header, 'delivery_date', $document->deliveryDate?->format('Y-m-d'));
        $this->appendElement($header, 'order_number', $document->orderNumber);
        $this->appendElement($header, 'despatch_advice_number', $document->despatchAdviceNumber ?? $document->docNumber);
        $this->appendElement($header, 'invoice_number', $document->invoiceNumber);

        foreach ($parties as $party) {
            $this->appendParty($header, $party, 'party_ean');
        }

        $lineItems = $this->appendChild($body, 'line_items');
        // Číslo řádku (item_number) běží souvisle přes všechny jednotky, aby byl v dokladu jednoznačný.
        $lineNumber = 0;
        foreach ($packages as $package) {
            $packageEl = $this->appendChild($lineItems, 'item');
            $this->appendElement($packageEl, 'package_number', (string) $package->number);
            $this->appendElement($packageEl, 'package_reference', $package->packageReference);
            $this->appendElement($packageEl, 'sscc_code', $package->ssccCode);

            $articles = $this->appendChild($packageEl, 'articles');
            foreach ($package->articles as $article) {
                $el = $this->appendChild($articles, 'article');
                $this->appendElement($el, 'item_number', (string) ++$lineNumber);
                $this->appendElement($el, 'article_gtin', $article->gtin);
                $this->appendElement($el, 'article_id_supplier', $article->supplierArticleId);
                $this->appendElement($el, 'article_id_buyer', $article->buyerArticleId ?? '');
                $this->appendElement($el, 'item_type', $article->itemType);
                $this->appendElement($el, 'article_name', $article->name);
                $this->appendElement($el, 'quantity', $this->formatNumber($article->quantity));
                $this->appendElement($el, 'unit', $article->unit);
                $this->appendElement($el, 'number_of_units', $this->formatNumber($article->numberOfUnits));
                $this->appendElement($el, 'order_number', $article->orderNumber ?? $document->orderNumber);
                $this->appendElement($el, 'supplier_order_number', $article->supplierOrderNumber);
            }
        }

        $summary = $this->appendChild($body, 'summary');
        $this->appendElement($summary, 'number_of_items', (string) $lineNumber);

        return new EdiDocumentFile(
            filename: sprintf('desadv_%s.xml', $document->docNumber),
            content: $this->output($dom),
        );
    }
}
