<?php
/**
 * Code QR de virement SEPA (norme EPC069-12, « QR code de paiement »).
 * Lisible par les applications bancaires belges (scanner pour payer).
 */
use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;

final class Epc
{
    public static function normalizeIban(string $iban): string
    {
        return strtoupper(preg_replace('/\s+/', '', $iban));
    }

    /** Vérifie un IBAN (longueur + clé modulo 97). */
    public static function validIban(string $iban): bool
    {
        $iban = self::normalizeIban($iban);
        if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban)) {
            return false;
        }
        $moved = substr($iban, 4) . substr($iban, 0, 4);
        $num = '';
        foreach (str_split($moved) as $ch) {
            $num .= ctype_alpha($ch) ? (string) (ord($ch) - 55) : $ch;
        }
        $rem = 0;
        foreach (str_split($num, 7) as $chunk) {
            $rem = (int) (($rem . $chunk) % 97);
        }
        return $rem === 1;
    }

    /** Les données de virement de site.json sont-elles complètes ? */
    public static function ready(): bool
    {
        $v = Content::get('don.virement', []);
        return self::validIban((string) ($v['iban'] ?? ''))
            && !is_todo((string) ($v['beneficiaire'] ?? ''));
    }

    public static function payload(?float $amount, string $lang): string
    {
        $v = Content::get('don.virement', []);
        $bic = (string) ($v['bic'] ?? '');
        $bic = is_todo($bic) ? '' : strtoupper(preg_replace('/\s+/', '', $bic));
        $comm = t($v['communication'] ?? '', $lang);
        $comm = is_todo($comm) ? '' : mb_substr($comm, 0, 140);
        $lines = [
            'BCD', '002', '1', 'SCT',
            $bic,
            mb_substr((string) $v['beneficiaire'], 0, 70),
            self::normalizeIban((string) $v['iban']),
            $amount ? 'EUR' . number_format($amount, 2, '.', '') : '',
            '', // code « purpose »
            '', // référence structurée ISO 11649
            $comm, // communication libre
        ];
        return rtrim(implode("\n", $lines));
    }

    public static function svg(?float $amount, string $lang): string
    {
        $options = new QROptions([
            'eccLevel' => EccLevel::M,
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => false,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'svgUseFillAttributes' => true,
            'drawLightModules' => false,
            'moduleValues' => [],
        ]);
        return (new QRCode($options))->render(self::payload($amount, $lang));
    }
}
