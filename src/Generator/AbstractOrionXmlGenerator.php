<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Generator;

use Ages\EdiDocuments\Config\EdiConfig;
use Ages\EdiDocuments\Document\Party;
use Ages\EdiDocuments\Document\PartyQualifier;
use Ages\EdiDocuments\EdiException;

abstract class AbstractOrionXmlGenerator
{
    public function __construct(protected readonly EdiConfig $config)
    {
    }

    /**
     * @return array{\DOMDocument, \DOMElement} dokument a element <body>
     */
    protected function createMessage(string $messageType, string $receiverGln, ?\DateTimeImmutable $createdAt = null): array
    {
        $createdAt ??= new \DateTimeImmutable();

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('orion_message');
        $dom->appendChild($root);

        $header = $dom->createElement('header');
        $root->appendChild($header);
        $this->appendElement($header, 'message_type', $messageType);
        $this->appendElement($header, 'version', $this->config->messageVersion);
        $this->appendElement($header, 'message_id', '');
        $this->appendElement($header, 'creation_date', $createdAt->format('Y-m-d'));
        $this->appendElement($header, 'creation_time', $createdAt->format('H:i'));
        $this->appendElement($header, 'receiver', $receiverGln);
        $this->appendElement($header, 'sender', $this->config->supplier->gln);
        $this->appendElement($header, 'test_flag', $this->config->testFlag ? '1' : '');

        $body = $dom->createElement('body');
        $root->appendChild($body);

        return [$dom, $body];
    }

    /**
     * Adresát zprávy: explicitně zadaný, jinak GLN kupujícího (BY).
     *
     * @param Party[] $parties
     */
    protected function resolveReceiver(?string $receiverGln, array $parties): string
    {
        if ($receiverGln !== null && $receiverGln !== '') {
            return $receiverGln;
        }

        foreach ($parties as $party) {
            if ($party->qualifier === PartyQualifier::Buyer) {
                return $party->gln;
            }
        }

        throw new EdiException('Nelze určit adresáta zprávy: chybí receiverGln i strana s kvalifikátorem BY.');
    }

    /**
     * Strany dokladu doplněné o dodavatele (SU) z konfigurace, pokud v dokladu chybí.
     *
     * @param Party[] $parties
     * @return Party[]
     */
    protected function partiesWithSupplier(array $parties): array
    {
        foreach ($parties as $party) {
            if ($party->qualifier === PartyQualifier::Supplier) {
                return $parties;
            }
        }

        $parties[] = $this->config->supplier;
        return $parties;
    }

    /**
     * @param string $glnTag tag pro GLN strany — DESADV používá party_ean, INVOIC party_id
     */
    protected function appendParty(\DOMElement $parent, Party $party, string $glnTag): \DOMElement
    {
        $el = $this->appendChild($parent, 'party');
        $this->appendElement($el, 'party_qualifier', $party->qualifier->value);
        $this->appendElement($el, $glnTag, $party->gln);
        $this->appendElement($el, 'party_name', $party->name);
        $this->appendElement($el, 'street', $party->street);
        $this->appendElement($el, 'place', $party->place);
        $this->appendElement($el, 'postcode', $party->postcode);
        $this->appendElement($el, 'country_code', $party->countryCode);
        $this->appendElement($el, 'country_name', $party->countryName);
        $this->appendElement($el, 'company_id', $party->companyId);
        $this->appendElement($el, 'tax_id', $party->taxId);
        $this->appendElement($el, 'vat_id', $party->vatId);
        $this->appendElement($el, 'contact_name', $party->contactName);
        $this->appendElement($el, 'contact_tel', $party->contactTel);
        $this->appendElement($el, 'contact_email', $party->contactEmail);

        return $el;
    }

    /**
     * Přidá kontejnerový element bez hodnoty.
     */
    protected function appendChild(\DOMElement $parent, string $name): \DOMElement
    {
        $doc = $parent->ownerDocument;
        assert($doc !== null);

        $el = $doc->createElement($name);
        $parent->appendChild($el);

        return $el;
    }

    /**
     * Přidá hodnotový element; null hodnota se vynechá, prázdný string vytvoří prázdný element.
     */
    protected function appendElement(\DOMElement $parent, string $name, ?string $value): void
    {
        if ($value === null) {
            return;
        }

        $doc = $parent->ownerDocument;
        assert($doc !== null);

        $el = $doc->createElement($name);
        if ($value !== '') {
            $el->appendChild($doc->createTextNode($value));
        }
        $parent->appendChild($el);
    }

    /**
     * Číslo bez koncových nul (138.00 → "138", 0.5 → "0.5").
     */
    protected function formatNumber(float $value, int $maxDecimals = 3): string
    {
        $formatted = number_format($value, $maxDecimals, '.', '');
        $formatted = rtrim($formatted, '0');

        return rtrim($formatted, '.');
    }

    protected function output(\DOMDocument $dom): string
    {
        // LIBXML_NOEMPTYTAG: prázdné elementy jako <message_id></message_id> místo <message_id/>,
        // stejně jako ukázkové soubory protistrany.
        $xml = $dom->saveXML(null, LIBXML_NOEMPTYTAG);
        if ($xml === false) {
            throw new EdiException('Serializace XML dokumentu selhala.');
        }

        return $xml;
    }
}
