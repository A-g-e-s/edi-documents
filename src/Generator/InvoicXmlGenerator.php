<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Generator;

use Ages\EdiDocuments\Document\Invoic\InvoicDocument;
use Ages\EdiDocuments\Document\PartyQualifier;
use Ages\EdiDocuments\EdiException;

/**
 * Generátor zprávy ORION INVOIC XML (dokumentace v. 3.1.2).
 */
class InvoicXmlGenerator extends AbstractOrionXmlGenerator
{
    /** Mapování sazby DPH na daňovou skupinu (CZ: 0 %, 12 % snížená, 21 % základní). */
    private const array VAT_GROUPS = [
        '0' => 0.0,
        '1' => 12.0,
        '2' => 21.0,
    ];

    public function generate(InvoicDocument $document, ?\DateTimeImmutable $createdAt = null): EdiDocumentFile
    {
        if ($document->items === []) {
            throw new EdiException('Faktura musí obsahovat alespoň jednu položku.');
        }

        $parties = $this->partiesWithSupplier($document->parties);
        $receiver = $this->resolveReceiver($document->receiverGln, $parties);

        [$dom, $body] = $this->createMessage('INVOIC', $receiver, $createdAt);

        $header = $this->appendChild($body, 'message_header');
        $this->appendElement($header, 'doc_number', $document->docNumber);
        $this->appendElement($header, 'doc_type', $document->docType->value);
        $this->appendElement($header, 'doc_function', '9');
        $this->appendElement($header, 'issue_date', $document->issueDate->format('Y-m-d'));
        $this->appendElement($header, 'tax_date', $document->taxDate->format('Y-m-d'));
        $this->appendElement($header, 'delivery_date', $document->deliveryDate?->format('Y-m-d'));
        $this->appendElement($header, 'due_date', $document->dueDate->format('Y-m-d'));
        $this->appendElement($header, 'payment_means', $document->paymentMeans);
        $this->appendElement($header, 'currency_code', $document->currencyCode);
        $this->appendElement($header, 'registration', $document->registration);
        $this->appendElement($header, 'regulatory_information', $document->regulatoryInformation);
        $this->appendElement($header, 'order_number', $document->orderNumber);
        $this->appendElement($header, 'despatch_advice_number', $document->despatchAdviceNumber);

        $bankAccount = $document->bankAccount ?? $this->config->bankAccount;

        foreach ($parties as $party) {
            $el = $this->appendParty($header, $party, 'party_id');

            if ($party->qualifier === PartyQualifier::Supplier && $bankAccount !== null) {
                $bank = $bankAccount;
                $this->appendElement($el, 'account_number', $bank->accountNumber);
                $this->appendElement($el, 'bank_code', $bank->bankCode);
                $this->appendElement($el, 'bank_name', $bank->bankName);
                $this->appendElement($el, 'iban', $bank->iban);
                $this->appendElement($el, 'swift', $bank->swift);
                $this->appendElement($el, 'constant_symbol', $document->constantSymbol);
                $this->appendElement($el, 'variable_symbol', $document->variableSymbol ?? $document->docNumber);
            }
        }

        $lineItems = $this->appendChild($body, 'line_items');
        foreach ($document->items as $index => $item) {
            $el = $this->appendChild($lineItems, 'item');
            $this->appendElement($el, 'item_number', (string) ($index + 1));
            $this->appendElement($el, 'article_gtin', $item->gtin);
            $this->appendElement($el, 'article_id_supplier', $item->supplierArticleId);
            $this->appendElement($el, 'article_id_buyer', $item->buyerArticleId ?? '');
            $this->appendElement($el, 'article_name', $item->name);
            $this->appendElement($el, 'item_type', $item->itemType);
            $this->appendElement($el, 'quantity', $this->formatNumber($item->quantity));
            $this->appendElement($el, 'gross_price', $this->formatNumber($item->grossPrice));
            $this->appendElement($el, 'allowance_rate', $this->formatNumber($item->allowanceRate));
            $this->appendElement($el, 'allowance_total', $this->formatNumber($item->allowanceTotal));
            $this->appendElement($el, 'net_price', $this->formatNumber($item->netPrice));
            $this->appendElement($el, 'total_price', $this->formatNumber($item->totalPrice));
            $this->appendElement($el, 'vat_rate', $this->formatNumber($item->vatRate));
            $this->appendElement($el, 'vat_total', $this->formatNumber($item->vatTotal));
            $this->appendElement($el, 'total_price_vat', $this->formatNumber($item->totalPriceVat));
            $this->appendElement($el, 'unit', $item->unit);
            $this->appendElement($el, 'number_of_units', $this->formatNumber($item->numberOfUnits));
            $this->appendElement($el, 'delivery_date', ($item->deliveryDate ?? $document->deliveryDate)?->format('Y-m-d'));
            $this->appendElement($el, 'order_number', $item->orderNumber ?? $document->orderNumber);
            $this->appendElement($el, 'supplier_order_number', $item->supplierOrderNumber);
            $this->appendElement($el, 'despatch_advice_number', $item->despatchAdviceNumber ?? $document->despatchAdviceNumber);
        }

        $this->appendSummary($body, $document);

        return new EdiDocumentFile(
            filename: sprintf('invoice_%s.xml', $document->docNumber),
            content: $this->output($dom),
        );
    }

    private function appendSummary(\DOMElement $body, InvoicDocument $document): void
    {
        $totalQuantity = 0.0;
        $itemsPrice = 0.0;
        $vatTotal = 0.0;
        foreach ($document->items as $item) {
            $totalQuantity += $item->quantity;
            $itemsPrice += $item->totalPrice;
            $vatTotal += $item->vatTotal;
        }

        $priceInvoiced = $itemsPrice + $vatTotal + $document->roundingDifference;

        $summary = $this->appendChild($body, 'summary');
        $this->appendElement($summary, 'number_of_items', (string) count($document->items));
        $this->appendElement($summary, 'total_quantity', $this->formatNumber($totalQuantity));
        $this->appendElement($summary, 'price_invoiced', $this->formatNumber($priceInvoiced, 2));
        $this->appendElement($summary, 'items_price', $this->formatNumber($itemsPrice, 2));
        $this->appendElement($summary, 'taxable_amount', $this->formatNumber($itemsPrice, 2));
        $this->appendElement($summary, 'remains_unsettled', $this->formatNumber($priceInvoiced - $document->pricePaid, 2));
        $this->appendElement($summary, 'rounding_difference', $this->formatNumber($document->roundingDifference, 2));

        $taxDetails = $this->appendChild($summary, 'tax_details');
        foreach (self::VAT_GROUPS as $group => $rate) {
            [$basis, $total] = $this->vatGroupTotals($document, $rate);
            $el = $this->appendChild($taxDetails, 'vat');
            $this->appendElement($el, 'vat_group', (string) $group);
            $this->appendElement($el, 'vat_basis', $this->formatNumber($basis, 2));
            $this->appendElement($el, 'vat_rate', $this->formatNumber($rate, 2));
            $this->appendElement($el, 'vat_total', $this->formatNumber($total, 2));
        }
        $this->appendElement($taxDetails, 'vat_total', $this->formatNumber($vatTotal, 2));
        $this->appendElement($taxDetails, 'excise_duty', '0');
    }

    /**
     * @return array{float, float} základ a částka DPH pro danou sazbu
     */
    private function vatGroupTotals(InvoicDocument $document, float $rate): array
    {
        $basis = 0.0;
        $total = 0.0;
        foreach ($document->items as $item) {
            if (!in_array($item->vatRate, self::VAT_GROUPS, true)) {
                throw new EdiException(sprintf('Neznámá sazba DPH %s %%, podporované sazby: %s.', $this->formatNumber($item->vatRate), implode(', ', array_map(fn(float $r): string => $this->formatNumber($r), self::VAT_GROUPS))));
            }
            if ($item->vatRate === $rate) {
                $basis += $item->totalPrice;
                $total += $item->vatTotal;
            }
        }

        return [$basis, $total];
    }
}
