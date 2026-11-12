<?php
/**
 * Lecture de la configuration privée (clés de paiement, e-mails).
 *
 * Le fichier config.php est cherché, dans l'ordre :
 *   1. le chemin indiqué par la variable d'environnement PHILADELPHIA_CONFIG ;
 *   2. ../philadelphia-private/config.php  (dossier À CÔTÉ de public_html — recommandé) ;
 *   3. ../../philadelphia-private/config.php (si le site est dans un sous-dossier).
 * S'il n'existe pas, le site fonctionne quand même (dons en ligne désactivés).
 */
final class Config
{
    private static array $cfg = [];
    private static ?string $file = null;

    private const DEFAULTS = [
        'site_url' => '',
        'debug' => false,
        'payment_provider' => 'stripe',
        'payment_mode' => 'test',
        'donation_min' => 2,
        'donation_max' => 10000,
        'stripe' => [
            'test' => ['secret_key' => '', 'webhook_secret' => ''],
            'live' => ['secret_key' => '', 'webhook_secret' => ''],
            'methods_once' => ['bancontact', 'card', 'sepa_debit'],
            'methods_monthly' => ['card', 'sepa_debit'],
            'api_base' => null, // uniquement pour les tests locaux
        ],
        'mollie' => [
            'test_key' => '', 'live_key' => '',
            'methods_once' => ['bancontact', 'creditcard', 'banktransfer'],
            'methods_monthly' => ['bancontact', 'creditcard'],
            'api_base' => 'https://api.mollie.com/v2',
        ],
        'smtp' => ['host' => '', 'port' => 465, 'secure' => 'ssl', 'user' => '', 'pass' => '', 'verify_cert' => true],
        'contact_to' => '',
        'mail_from' => '',
        'contact_keep_copy' => true,
        'data_dir' => null,
    ];

    public static function load(): void
    {
        $candidates = array_filter([
            getenv('PHILADELPHIA_CONFIG') ?: null,
            dirname(ROOT) . '/philadelphia-private/config.php',
            dirname(ROOT, 2) . '/philadelphia-private/config.php',
        ]);
        $user = [];
        foreach ($candidates as $c) {
            if (is_file($c)) {
                $loaded = require $c;
                if (is_array($loaded)) {
                    $user = $loaded;
                    self::$file = $c;
                }
                break;
            }
        }
        self::$cfg = array_replace_recursive(self::DEFAULTS, $user);
        // Les listes de moyens de paiement doivent être remplacées, pas fusionnées.
        foreach (['stripe', 'mollie'] as $p) {
            foreach (['methods_once', 'methods_monthly'] as $k) {
                if (array_key_exists($k, $user[$p] ?? [])) {
                    self::$cfg[$p][$k] = $user[$p][$k];
                }
            }
        }
        if (!self::$cfg['data_dir']) {
            self::$cfg['data_dir'] = self::$file ? dirname(self::$file) . '/data' : null;
        }
    }

    public static function get(string $key, $default = null)
    {
        $v = self::$cfg;
        foreach (explode('.', $key) as $part) {
            if (!is_array($v) || !array_key_exists($part, $v)) {
                return $default;
            }
            $v = $v[$part];
        }
        return $v;
    }

    public static function found(): bool
    {
        return self::$file !== null;
    }

    public static function isLive(): bool
    {
        return self::get('payment_mode') === 'live';
    }

    /** Dossier privé des données, créé si besoin. null si aucune config. */
    public static function dataDir(): ?string
    {
        $d = self::get('data_dir');
        if (!$d) {
            return null;
        }
        if (!is_dir($d)) {
            @mkdir($d, 0750, true);
            @file_put_contents($d . '/.htaccess', "Require all denied\n");
        }
        return is_dir($d) && is_writable($d) ? $d : null;
    }

    /** Une valeur est-elle encore un exemple à remplacer ? */
    public static function isPlaceholder($v): bool
    {
        return !is_string($v) || $v === '' || str_contains($v, 'REMPLACER');
    }
}
