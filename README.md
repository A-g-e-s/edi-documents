# ages/edi-documents

PHP knihovna pro generování EDI dokladů ve formátu **ORION XML** (GRiT) — dodací list **DESADV** a faktura **INVOIC** — včetně generátoru **SSCC kódů** (GS1) a **SSCC etikety** (PDF 100×150 mm).

Model odpovídá provozu: **jedna obalová jednotka = jedna faktura = jeden DESADV + jeden INVOIC + jedno SSCC**. Posílají-li se 3 palety, vzniknou 3 sady dokladů.

---

## Dokumentace formátu

Specifikace GRiT ORION je v lokální složce `doc/` (není součástí repozitáře — dokumentace je určena pouze pro potřeby zákazníka GRiT):

| Zpráva | Dokument |
|---|---|
| DESADV | `DESADV ORION XML CZE ENG 3.1.8.xlsx` |
| INVOIC | `Dokumentace INVOIC CZE ENG 3.1.2.xlsx` |
| Ukázkové výstupy | `desadv_*.xml`, `invoice_*.xml` |

---

## Requirements

- PHP 8.4+
- ext-dom, ext-mbstring
- mPDF `^8.3`

## Installation

Path repository pro lokální vývoj:

```json
{
    "repositories": [
        { "type": "path", "url": "../edi-documents" }
    ],
    "require": {
        "ages/edi-documents": "*"
    }
}
```

---

## Configuration (Nette / neon)

Konfigurace je záměrně minimální — stačí GLN dodavatele a SSCC prefix. Plná data dodavatele (adresa, IČO, DIČ) a bankovní spojení se předávají per doklad: SU strana v `parties`, banka přes `InvoicDocument::$bankAccount` (typicky z dat faktury). Config hodnoty slouží jen jako fallback, když v dokladu chybí.

```neon
services:
    ediSupplier: Ages\EdiDocuments\Document\Party(
        qualifier: Ages\EdiDocuments\Document\PartyQualifier::Supplier
        gln: '8594000000009'
    )

    ediConfig: Ages\EdiDocuments\Config\EdiConfig(
        supplier: @ediSupplier
        ssccCompanyPrefix: '859400000'
        # testFlag: true                 # testovací zprávy
        # logoPath: %wwwDir%/img/logo.png  # logo na SSCC etiketě
    )

    - Ages\EdiDocuments\Generator\DesadvXmlGenerator
    - Ages\EdiDocuments\Generator\InvoicXmlGenerator
    - Ages\EdiDocuments\Sscc\SsccGenerator
    - Ages\EdiDocuments\Sscc\SsccLabelGenerator
```

---

## Usage

### SSCC kód

Generátor je bezstavový — pořadové číslo dodává aplikace (sekvence v DB, uloží se k zásilce). Kód se skládá z extension digit (`0`) + company prefixu + doplněného pořadového čísla + GS1 kontrolní číslice, celkem 18 číslic.

```php
$sscc = $ssccGenerator->generate(759); // '085940000000007593'
SsccGenerator::isValid($sscc);         // true
```

Pro 9místný prefix zbývá 7 číslic série, tedy kapacita 0–9 999 999.

### DESADV + INVOIC

Generátory vrací `EdiDocumentFile` (`filename` + `content`, UTF-8). Názvy souborů odpovídají konvenci protistrany: `desadv_{docNumber}.xml`, `invoice_{docNumber}.xml`.

```php
$file = $desadvGenerator->generate(new DesadvDocument(
    docNumber: '826400154',
    issueDate: new DateTimeImmutable(),
    ssccCode: $sscc,
    parties: [$buyer, $deliveryParty, $invoicee],   // SU se doplní z konfigurace
    articles: [
        new DesadvArticle(
            gtin: '6424002005780',
            name: 'Fiskars FF Silikonová mašlovačka',
            quantity: 1,
            supplierArticleId: '1023614',
            orderNumber: 'OB26127268',
            supplierOrderNumber: '825602536',
        ),
    ],
    orderNumber: 'OB26127268',
));

file_put_contents($dir . $file->filename, $file->content);
```

- Adresát zprávy (`receiver`) se bere z `receiverGln`, jinak z GLN strany `BY`.
- Strana `SU` (dodavatel): předej ji v `parties` s plnými daty (typicky z faktury); pokud chybí, doplní se z `EdiConfig`. U INVOIC se k SU připojí bankovní spojení (`InvoicDocument::$bankAccount`, fallback `EdiConfig::$bankAccount`) a variabilní symbol (default = číslo dokladu).
- INVOIC summary (součty, DPH skupiny 0/12/21 %) se počítá z položek.
- SSCC etiketa: odesílatele lze předat přes `SsccLabelData::$sender` (fallback `EdiConfig::$supplier`).

### SSCC etiketa

PDF 100×150 mm, GS1-128 čárový kód s AI (00). Jedna strana na obalovou jednotku.

```php
$pdf = $ssccLabelGenerator->generateLabel(new SsccLabelData(
    sscc: $sscc,
    recipientName: 'Odběratel a.s.',
    recipientStreet: 'Skladová 1',
    recipientCity: 'Bratislava',
    recipientZip: '90027',
    recipientCountry: 'SK',
    reference: '8261500157',      // číslo dokladu
    orderNumber: 'OB26127268',
    weightKg: 245.5,
));
```

---

## Integrace do projektu gen (plán)

1. SSCC se vygeneruje **při zakládání zásilky** (`ConsignmentService`) a uloží do tabulky `consignment` — atomicky se zásilkou, aby ApiPrint nikdy nestáhl neúplná data bez SSCC etikety.
2. Ze SSCC se vytvoří etiketa a EDI doklady (DESADV + INVOIC) z dat faktury.
3. `ApiPrint` (`ConsignmentModel::findLabels`) vrátí navíc URL SSCC etikety, aby vše vyjelo z tiskáren najednou.

## Vývoj

```bash
composer install
composer phpstan   # level max
```
