<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Document\Invoic;

/**
 * Druh dokladu (kód) dle dokumentace ORION INVOIC.
 */
enum InvoicDocType: string
{
    case Invoice = '380';
    case CreditNote = '381';
    case Debit = '383';
}
