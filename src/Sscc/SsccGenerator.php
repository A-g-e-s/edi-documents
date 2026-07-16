<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Sscc;

use Ages\EdiDocuments\Config\EdiConfig;
use Ages\EdiDocuments\EdiException;

/**
 * Generátor SSCC kódů (GS1 Serial Shipping Container Code, 18 číslic):
 * extension digit (1) + company prefix + sériové číslo + kontrolní číslice (1).
 *
 * Sériové číslo dodává volající (např. sekvence v DB aplikace) — generátor je bezstavový.
 */
readonly class SsccGenerator
{
    public function __construct(private EdiConfig $config)
    {
    }

    public function generate(int $sequenceNumber): string
    {
        $prefix = $this->config->ssccCompanyPrefix;
        $serialLength = 16 - strlen($prefix);
        $maxSequence = (10 ** $serialLength) - 1;

        if ($sequenceNumber < 0 || $sequenceNumber > $maxSequence) {
            throw new EdiException(sprintf('Sériové číslo SSCC %d je mimo rozsah 0–%d pro prefix %s.', $sequenceNumber, $maxSequence, $prefix));
        }

        $data = $this->config->ssccExtensionDigit
            . $prefix
            . str_pad((string) $sequenceNumber, $serialLength, '0', STR_PAD_LEFT);

        return $data . self::checkDigit($data);
    }

    public static function isValid(string $sscc): bool
    {
        if (strlen($sscc) !== 18 || !ctype_digit($sscc)) {
            return false;
        }

        return $sscc[17] === self::checkDigit(substr($sscc, 0, 17));
    }

    /**
     * Kontrolní číslice GS1 (mod 10, váhy 3-1-3… zprava).
     */
    public static function checkDigit(string $digits): string
    {
        if ($digits === '' || !ctype_digit($digits)) {
            throw new EdiException('Kontrolní číslici lze spočítat jen z neprázdného číselného řetězce.');
        }

        $sum = 0;
        $len = strlen($digits);
        for ($i = 0; $i < $len; $i++) {
            $digit = (int) $digits[$len - 1 - $i];
            $sum += ($i % 2 === 0) ? $digit * 3 : $digit;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }
}
