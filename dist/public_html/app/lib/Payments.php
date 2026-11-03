<?php
/**
 * Dons en ligne : Stripe Checkout (par défaut) ou Mollie.
 * Le choix se fait dans config.php : 'payment_provider' => 'stripe' | 'mollie'.
 *
 * Les montants confirmés sont enregistrés UNIQUEMENT par les webhooks (notification
 * serveur à serveur du prestataire), jamais par la page « merci » qui peut être falsifiée.
 */
final class Payments
{
    public static function provider(): string
    {
        return Config::get('payment_provider') === 'mollie' ? 'mollie' : 'stripe';
    }

    /** Les dons en ligne sont-ils configurés (clés présentes) ? */
    public static function enabled(): bool
    {
        if (!Config::found()) {
            return false;
        }
        $mode = Config::isLive() ? 'live' : 'test';
        if (self::provider() === 'stripe') {
            return !Config::isPlaceholder(Config::get("stripe.$mode.secret_key"));
        }
        return !Config::isPlaceholder(Config::get("mollie.{$mode}_key"));
    }

    /**
     * Valide le formulaire de don.
     * @return array{0: array, 1: array<string,string>} [données propres, erreurs]
     */
    public static function validate(array $in, string $lang): array
    {
        $err = [];
        $min = (float) Config::get('donation_min', 2);
        $max = (float) Config::get('donation_max', 10000);

        $choice = (string) ($in['montant'] ?? '');
        $raw = $choice === 'autre' || $choice === '' ? (string) ($in['montant_libre'] ?? '') : $choice;
        $raw = str_replace([' ', "\u{00A0}", '€'], '', $raw);
        $raw = str_replace(',', '.', $raw);
        $amount = is_numeric($raw) ? round((float) $raw, 2) : 0.0;
        if ($amount < $min || $amount > $max) {
            $err['montant'] = sprintf(__('err_montant', $lang), money($min, $lang), money($max, $lang));
        }

        $freq = ($in['frequence'] ?? 'unique') === 'mensuel' ? 'mensuel' : 'unique';
        $anon = !empty($in['anonyme']);
        $name = trim(mb_substr(strip_tags((string) ($in['nom'] ?? '')), 0, 100));
        $email = trim((string) ($in['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err['email'] = __('err_email', $lang);
        }
        $stage = (string) ($in['etape'] ?? '');
        $stageIds = array_column(Content::get('projet.etapes', []), 'id');
        if ($stage !== '' && !in_array($stage, $stageIds, true)) {
            $stage = '';
        }
        if (!empty($in['site_web'])) { // pot de miel anti-robot
            $err['robot'] = 'robot';
        }
        return [[
            'montant' => $amount,
            'frequence' => $freq,
            'anonyme' => $anon,
            'nom' => $anon ? '' : $name,
            'email' => $email,
            'etape' => $stage,
        ], $err];
    }

    /** Crée la session de paiement et renvoie l'URL vers laquelle rediriger le donateur. */
    public static function checkoutUrl(array $don, string $lang): string
    {
        return self::provider() === 'mollie'
            ? MolliePay::create($don, $lang)
            : StripePay::create($don, $lang);
    }

    public static function description(array $don, string $lang): string
    {
        $d = __('don_libelle', $lang);
        if ($don['etape']) {
            foreach (Content::get('projet.etapes', []) as $e) {
                if ($e['id'] === $don['etape'] && !is_todo(t($e['titre'], $lang))) {
                    $d .= ' — ' . t($e['titre'], $lang);
                }
            }
        }
        return mb_substr($d, 0, 200);
    }

    public static function metadata(array $don): array
    {
        return [
            'source' => 'site-philadelphia',
            'etape' => $don['etape'],
            'anonyme' => $don['anonyme'] ? 'oui' : 'non',
            'nom' => $don['anonyme'] ? '' : $don['nom'],
            'frequence' => $don['frequence'],
        ];
    }
}

// =====================================================================  STRIPE

final class StripePay
{
    private static function client(): \Stripe\StripeClient
    {
        $mode = Config::isLive() ? 'live' : 'test';
        $opts = ['api_key' => Config::get("stripe.$mode.secret_key")];
        if ($base = Config::get('stripe.api_base')) {
            $opts['api_base'] = $base; // tests locaux uniquement
        }
        return new \Stripe\StripeClient($opts);
    }

    public static function create(array $don, string $lang): string
    {
        $monthly = $don['frequence'] === 'mensuel';
        $meta = Payments::metadata($don);
        $label = Payments::description($don, $lang) . ($monthly ? ' (' . mb_strtolower(__('mensuel', $lang)) . ')' : '');
        $price = [
            'currency' => 'eur',
            'unit_amount' => (int) round($don['montant'] * 100),
            'product_data' => ['name' => $label],
        ];
        if ($monthly) {
            $price['recurring'] = ['interval' => 'month'];
        }
        $params = [
            'mode' => $monthly ? 'subscription' : 'payment',
            'line_items' => [['quantity' => 1, 'price_data' => $price]],
            'success_url' => abs_url('don_merci', $lang) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => abs_url('don_annule', $lang),
            'locale' => $lang === 'ro' ? 'ro' : 'fr',
            'metadata' => $meta,
        ];
        $methods = Config::get($monthly ? 'stripe.methods_monthly' : 'stripe.methods_once');
        if (is_array($methods) && $methods) {
            $params['payment_method_types'] = array_values($methods);
        }
        if ($don['email'] !== '') {
            $params['customer_email'] = $don['email'];
        }
        if ($monthly) {
            $params['subscription_data'] = ['metadata' => $meta, 'description' => $label];
        } else {
            $params['submit_type'] = 'donate';
            $params['payment_intent_data'] = ['metadata' => $meta, 'description' => $label];
        }
        $session = self::client()->checkout->sessions->create($params);
        return $session->url;
    }

    /** Pour la page « merci » : état de la session (affichage seulement). */
    public static function sessionInfo(string $id): ?array
    {
        if (!preg_match('/^cs_(test|live)_[A-Za-z0-9]+$/', $id)) {
            return null;
        }
        try {
            $s = self::client()->checkout->sessions->retrieve($id);
            return [
                'montant' => ($s->amount_total ?? 0) / 100,
                'paye' => $s->payment_status === 'paid',
                'mensuel' => $s->mode === 'subscription',
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Traite une notification Stripe (appelé par /webhook-stripe.php). */
    public static function handleWebhook(string $payload, string $signature): string
    {
        $mode = Config::isLive() ? 'live' : 'test';
        $event = \Stripe\Webhook::constructEvent($payload, $signature, (string) Config::get("stripe.$mode.webhook_secret"));
        $o = $event->data->object;

        switch ($event->type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
            case 'checkout.session.async_payment_failed':
                if (($o->metadata->source ?? '') !== 'site-philadelphia' || $o->mode !== 'payment') {
                    return 'ignoré';
                }
                $statut = match (true) {
                    $event->type === 'checkout.session.async_payment_failed' => 'echoue',
                    $o->payment_status === 'paid' => 'confirme',
                    default => 'en_attente', // domiciliation SEPA : confirmation quelques jours plus tard
                };
                Donations::record(self::row($o->id, $o->amount_total, 'unique', $statut, $o->metadata, $o->customer_details->email ?? null));
                return "don unique $statut";

            case 'invoice.paid':
                // Chaque mensualité (y compris la première) d'un don mensuel.
                $meta = $o->parent->subscription_details->metadata
                    ?? $o->subscription_details->metadata
                    ?? ($o->lines->data[0]->metadata ?? null);
                if (($meta->source ?? '') !== 'site-philadelphia' || (int) $o->amount_paid <= 0) {
                    return 'ignoré';
                }
                Donations::record(self::row($o->id, $o->amount_paid, 'mensuel', 'confirme', $meta, $o->customer_email ?? null));
                return 'mensualité enregistrée';
        }
        return 'événement non utilisé : ' . $event->type;
    }

    private static function row(string $id, $cents, string $freq, string $statut, $meta, ?string $email): array
    {
        $anon = ($meta->anonyme ?? 'non') === 'oui';
        return [
            'id' => $id,
            'montant' => ((int) $cents) / 100,
            'mode' => $freq,
            'prestataire' => 'stripe',
            'statut' => $statut,
            'etape' => ($meta->etape ?? '') ?: null,
            'anonyme' => $anon,
            'nom' => $anon ? null : (($meta->nom ?? '') ?: null),
            'email' => $anon ? null : $email,
        ];
    }
}

// =====================================================================  MOLLIE

final class MolliePay
{
    private static function api(string $method, string $path, ?array $body = null): array
    {
        $mode = Config::isLive() ? 'live' : 'test';
        $key = Config::get("mollie.{$mode}_key");
        $ch = curl_init(rtrim((string) Config::get('mollie.api_base'), '/') . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Content-Type: application/json', 'Accept: application/json'],
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $res = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $data = json_decode((string) $res, true);
        if ($code >= 300 || !is_array($data)) {
            throw new RuntimeException('Mollie ' . $code . ' : ' . ($data['detail'] ?? $err ?: 'réponse invalide'));
        }
        return $data;
    }

    private static function money(float $v): array
    {
        return ['currency' => 'EUR', 'value' => number_format($v, 2, '.', '')];
    }

    public static function create(array $don, string $lang): string
    {
        $monthly = $don['frequence'] === 'mensuel';
        $ref = bin2hex(random_bytes(8));
        $meta = Payments::metadata($don) + ['ref' => $ref];
        $body = [
            'amount' => self::money($don['montant']),
            'description' => Payments::description($don, $lang),
            'redirectUrl' => abs_url('don_merci', $lang) . '?ref=' . $ref,
            'cancelUrl' => abs_url('don_annule', $lang),
            'webhookUrl' => site_url() . '/webhook-mollie.php',
            'locale' => $lang === 'ro' ? 'en_GB' : 'fr_BE',
            'metadata' => $meta,
        ];
        $methods = Config::get($monthly ? 'mollie.methods_monthly' : 'mollie.methods_once');
        if (is_array($methods) && $methods) {
            $body['method'] = array_values($methods);
        }
        if ($monthly) {
            // Don mensuel : client + premier paiement qui crée le mandat ; l'abonnement
            // est créé par le webhook une fois ce premier paiement réussi.
            $customer = self::api('POST', '/customers', array_filter([
                'name' => $don['nom'] ?: null,
                'email' => $don['email'] ?: null,
                'metadata' => ['source' => 'site-philadelphia'],
            ]));
            $body['customerId'] = $customer['id'];
            $body['sequenceType'] = 'first';
        }
        $p = self::api('POST', '/payments', $body);
        self::saveRef($ref, $p['id']);
        return $p['_links']['checkout']['href'];
    }

    private static function refFile(): ?string
    {
        $d = Config::dataDir();
        return $d ? $d . '/mollie-references.json' : null;
    }

    private static function saveRef(string $ref, string $paymentId): void
    {
        if (!$f = self::refFile()) {
            return;
        }
        $all = is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
        $all[$ref] = ['id' => $paymentId, 't' => time()];
        $all = array_filter($all, fn($r) => $r['t'] > time() - 86400 * 7); // on ne garde qu'une semaine
        file_put_contents($f, json_encode($all), LOCK_EX);
    }

    public static function infoByRef(string $ref): ?array
    {
        $f = self::refFile();
        if (!$f || !is_file($f) || !preg_match('/^[a-f0-9]{16}$/', $ref)) {
            return null;
        }
        $all = json_decode((string) file_get_contents($f), true) ?: [];
        if (!isset($all[$ref])) {
            return null;
        }
        try {
            $p = self::api('GET', '/payments/' . $all[$ref]['id']);
        } catch (\Throwable $e) {
            return null;
        }
        return [
            'montant' => (float) $p['amount']['value'],
            'paye' => $p['status'] === 'paid',
            'echec' => in_array($p['status'], ['canceled', 'expired', 'failed'], true),
            'mensuel' => ($p['sequenceType'] ?? '') === 'first',
        ];
    }

    /** Mollie envoie seulement l'identifiant : on relit le paiement chez Mollie (donc pas falsifiable). */
    public static function handleWebhook(string $id): string
    {
        if (!preg_match('/^tr_[A-Za-z0-9]+$/', $id)) {
            return 'ignoré';
        }
        $p = self::api('GET', '/payments/' . $id);
        $meta = $p['metadata'] ?? [];
        $fromSubscription = !empty($p['subscriptionId']);
        if (!$fromSubscription && ($meta['source'] ?? '') !== 'site-philadelphia') {
            return 'ignoré';
        }
        if ($fromSubscription && empty($meta)) {
            $sub = self::api('GET', "/customers/{$p['customerId']}/subscriptions/{$p['subscriptionId']}");
            $meta = $sub['metadata'] ?? [];
        }
        $statut = match ($p['status']) {
            'paid' => 'confirme',
            'canceled', 'expired', 'failed' => 'echoue',
            default => 'en_attente',
        };
        $anon = ($meta['anonyme'] ?? 'non') === 'oui';
        Donations::record([
            'id' => $p['id'],
            'montant' => (float) $p['amount']['value'],
            'mode' => ($fromSubscription || ($p['sequenceType'] ?? '') === 'first') ? 'mensuel' : 'unique',
            'prestataire' => 'mollie',
            'statut' => $statut,
            'etape' => ($meta['etape'] ?? '') ?: null,
            'anonyme' => $anon,
            'nom' => $anon ? null : (($meta['nom'] ?? '') ?: null),
            'email' => null,
        ]);

        // Premier paiement d'un don mensuel réussi : on crée l'abonnement (une seule fois).
        if ($statut === 'confirme' && ($p['sequenceType'] ?? '') === 'first' && !empty($p['customerId'])) {
            $existing = self::api('GET', "/customers/{$p['customerId']}/subscriptions");
            if (empty($existing['_embedded']['subscriptions'])) {
                self::api('POST', "/customers/{$p['customerId']}/subscriptions", [
                    'amount' => $p['amount'],
                    'interval' => '1 month',
                    'startDate' => date('Y-m-d', strtotime('+1 month')),
                    'description' => $p['description'] . ' (' . substr($p['id'], 3, 8) . ')',
                    'webhookUrl' => site_url() . '/webhook-mollie.php',
                    'metadata' => array_intersect_key($meta, array_flip(['source', 'etape', 'anonyme', 'nom', 'frequence'])),
                ]);
                return 'premier paiement + abonnement créé';
            }
        }
        return "paiement $statut";
    }
}
