<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Document;

/**
 * Kvalifikátor obchodního partnera (EDIFACT DE 3035, viz list Code_list dokumentace ORION).
 */
enum PartyQualifier: string
{
    case Buyer = 'BY';
    case OrderedBy = 'OB';
    case StoreNumber = 'SN';
    case UltimateCustomer = 'UC';
    case DeliveryParty = 'DP';
    case Invoicee = 'IV';
    case Supplier = 'SU';
    case Seller = 'SE';
    case ShipFrom = 'SF';
}
