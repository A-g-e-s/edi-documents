<?php

declare(strict_types=1);

namespace Ages\EdiDocuments\Sscc;

use Ages\EdiDocuments\Config\EdiConfig;
use Ages\EdiDocuments\EdiException;
use Mpdf\Mpdf;

/**
 * SSCC etiketa 100×150 mm, jedna strana na obalovou jednotku.
 * Layout vychází z etikety Gebrüder Weiss v ages/shipping-gateway.
 */
class SsccLabelGenerator
{
    public function __construct(private readonly EdiConfig $config)
    {
    }

    /**
     * @param SsccLabelData[] $labels
     * @return string PDF obsah
     */
    public function generateLabels(array $labels): string
    {
        if ($labels === []) {
            throw new EdiException('Není co tisknout: seznam etiket je prázdný.');
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [100, 150],
            'margin_left' => 3,
            'margin_right' => 3,
            'margin_top' => 3,
            'margin_bottom' => 3,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);
        $mpdf->SetAutoPageBreak(false);

        foreach (array_values($labels) as $index => $label) {
            if ($index > 0) {
                $mpdf->AddPage();
            }
            $mpdf->WriteHTML($this->buildHtml($label));
        }

        $pdf = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
        if (!is_string($pdf)) {
            throw new EdiException('Generování PDF etikety selhalo.');
        }

        return $pdf;
    }

    public function generateLabel(SsccLabelData $label): string
    {
        return $this->generateLabels([$label]);
    }

    private function buildHtml(SsccLabelData $label): string
    {
        $supplier = $label->sender ?? $this->config->supplier;

        $recipientZipCountry = trim($label->recipientCountry . ' ' . $label->recipientZip);
        $senderName = $supplier->name ?? '';
        $senderStreet = $supplier->street ?? '';
        $senderZipCity = trim(($supplier->countryCode ?? '') . ' ' . ($supplier->postcode ?? '') . ' ' . ($supplier->place ?? ''));

        $date = (new \DateTimeImmutable())->format('d.m.Y');
        $weight = $label->weightKg !== null
            ? number_format($label->weightKg, 2, ',', '.') . ' KG'
            : '';

        $logoHtml = '';
        if ($this->config->logoPath !== null && file_exists($this->config->logoPath)) {
            $path = $this->esc($this->config->logoPath);
            $logoHtml = "<img src=\"{$path}\" style=\"display:block; margin:0 auto; max-width:26mm; max-height:19mm;\" />";
        }

        $name2Html = $label->recipientName2 !== null
            ? '<div style="font-size:11pt; font-weight:bold; line-height:1.1;">' . $this->esc($label->recipientName2) . '</div>'
            : '';

        $orderNumber = $label->orderNumber ?? $label->reference;

        // Výšky řádků: 22+38+17+11+11+45 = 144 mm
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Arial, sans-serif; font-size: 7pt; }
table { width: 100%; border-collapse: collapse; }
.lbl { font-size: 6pt; color: #444; }
.bold { font-weight: bold; font-size: 7pt; }
</style>
</head>
<body>

<table style="height:144mm; table-layout:fixed;">

  <!-- Řádek 1: Hlavička — 22mm -->
  <tr style="height:22mm;">
    <td style="border-bottom:0.5pt solid #000; padding:0; overflow:hidden;">
      <table style="height:22mm;">
        <tr>
          <td style="width:30mm; border-right:0.5pt solid #000; text-align:center; vertical-align:middle; height:22mm; padding:1mm;">
            {$logoHtml}
          </td>
          <td style="font-size:6.5pt; vertical-align:top; padding:1.5mm 1.5mm 0 2mm;">
            <strong>{$this->esc($senderName)}</strong><br>
            {$this->esc($senderStreet)}<br>
            {$this->esc($senderZipCity)}
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- Řádek 2: PRIJEMCE — 38mm -->
  <tr style="height:38mm;">
    <td style="border-bottom:0.5pt solid #000; padding:1mm 1.5mm; vertical-align:top; overflow:hidden;">
      <div class="lbl">PRIJEMCE:</div>
      <div style="font-size:11pt; font-weight:bold; line-height:1.1;">{$this->esc($label->recipientName)}</div>
      {$name2Html}
      <div style="font-size:8pt; font-weight:bold; line-height:1.2;">{$this->esc($label->recipientStreet)}</div>
      <div style="font-size:12pt; font-weight:bold; line-height:1.15;">{$this->esc($recipientZipCountry)}</div>
      <div style="font-size:11pt; font-weight:bold; line-height:1.1;">{$this->esc($label->recipientCity)}</div>
    </td>
  </tr>

  <!-- Řádek 3: ODESILATEL — 17mm -->
  <tr style="height:17mm;">
    <td style="border-bottom:0.5pt solid #000; padding:1mm 1.5mm; vertical-align:top; overflow:hidden;">
      <div class="lbl">ODESILATEL:</div>
      <div style="font-size:7.5pt; font-weight:bold;">{$this->esc($senderName)}</div>
      <div style="font-size:7pt;">{$this->esc($senderStreet)}</div>
      <div style="font-size:7pt;">{$this->esc($senderZipCity)}</div>
    </td>
  </tr>

  <!-- Řádek 4: Objednávka + datum — 11mm -->
  <tr style="height:11mm;">
    <td style="border-bottom:0.5pt solid #000; padding:0; overflow:hidden;">
      <table style="height:11mm;">
        <tr>
          <td style="width:50%; border-right:0.5pt solid #000; padding:1mm 1.5mm; vertical-align:top;">
            <div class="lbl">Cislo objednavky:</div>
            <div class="bold">{$this->esc($orderNumber)}</div>
          </td>
          <td style="padding:1mm 1.5mm; vertical-align:top;">
            <div class="lbl">Datum zasilky:</div>
            <div class="bold">{$date}</div>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- Řádek 5: Colli + hmotnost + doklad — 11mm -->
  <tr style="height:11mm;">
    <td style="border-bottom:0.5pt solid #000; padding:0; overflow:hidden;">
      <table style="height:11mm;">
        <tr>
          <td style="width:33%; border-right:0.5pt solid #000; padding:1mm; vertical-align:top;">
            <div class="lbl">Pocet colli:</div>
            <div class="bold">{$label->parcelNumber}/{$label->totalParcels}</div>
          </td>
          <td style="width:34%; border-right:0.5pt solid #000; padding:1mm; vertical-align:top;">
            <div class="lbl">Hmotnost:</div>
            <div class="bold">{$weight}</div>
          </td>
          <td style="padding:1mm; vertical-align:top;">
            <div class="lbl">Doklad:</div>
            <div class="bold">{$this->esc($label->reference)}</div>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- Řádek 6: Čárový kód — 45mm -->
  <tr style="height:45mm;">
    <td style="text-align:center; vertical-align:middle; padding:2mm 4mm;">
      <barcode code="00{$label->sscc}" type="C128C" height="2" size="1" text="0" />
      <br>
      <span style="font-size:6.5pt;">SSCC/NVE (00){$this->esc($label->sscc)}</span>
    </td>
  </tr>

</table>

</body>
</html>
HTML;
    }

    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
