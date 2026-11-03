<?php
/**
 * Formulaire de contact : validation, anti-spam (pot de miel + délai minimum + limite par IP),
 * envoi avec la fonction mail() de PHP, copie facultative dans philadelphia-private/data/.
 */
final class Contact
{
    public const SUBJECTS = ['general', 'don', 'benevolat', 'materiaux', 'entreprise', 'visite'];

    /** Jeton horodaté signé : bloque les robots qui envoient le formulaire instantanément. */
    public static function token(): string
    {
        $t = (string) time();
        return $t . '.' . substr(hash_hmac('sha256', $t, self::secret()), 0, 16);
    }

    private static function secret(): string
    {
        // Clé dérivée de la configuration (aucun réglage nécessaire).
        return hash('sha256', __FILE__ . (string) Config::get('contact_to') . (string) Config::get('mail_from'));
    }

    private static function tokenAge(string $token): ?int
    {
        if (!preg_match('/^(\d+)\.([a-f0-9]{16})$/', $token, $m)) {
            return null;
        }
        if (!hash_equals(substr(hash_hmac('sha256', $m[1], self::secret()), 0, 16), $m[2])) {
            return null;
        }
        return time() - (int) $m[1];
    }

    /** @return array{0: array, 1: array<string,string>, 2: bool} [données, erreurs, robot ?] */
    public static function validate(array $in, string $lang): array
    {
        $d = [
            'nom' => trim(mb_substr((string) ($in['nom'] ?? ''), 0, 120)),
            'email' => trim(mb_substr((string) ($in['email'] ?? ''), 0, 200)),
            'gsm' => trim(mb_substr((string) ($in['gsm'] ?? ''), 0, 40)),
            'sujet' => in_array($in['sujet'] ?? '', self::SUBJECTS, true) ? $in['sujet'] : 'general',
            'message' => trim(mb_substr((string) ($in['message'] ?? ''), 0, 5000)),
            'consentement' => !empty($in['consentement']),
        ];
        $err = [];
        if ($d['nom'] === '') {
            $err['nom'] = __('err_requis', $lang);
        }
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $err['email'] = __('err_email', $lang);
        }
        if (mb_strlen($d['message']) < 5) {
            $err['message'] = __('err_message', $lang);
        }
        if (!$d['consentement']) {
            $err['consentement'] = __('err_consentement', $lang);
        }
        // Anti-spam silencieux : le robot croit que c'est envoyé.
        $age = self::tokenAge((string) ($in['jeton'] ?? ''));
        $robot = !empty($in['site_web']) || $age === null || $age < 3 || $age > 86400
            || preg_match_all('~https?://~i', $d['message']) > 3;
        return [$d, $err, (bool) $robot];
    }

    /** Limite : 5 messages par heure et par adresse IP. */
    public static function rateLimited(): bool
    {
        $dir = Config::dataDir();
        if (!$dir) {
            return false;
        }
        $f = $dir . '/limite-contact.json';
        $ip = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m-d')); // IP non stockée en clair
        $all = is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
        $now = time();
        foreach ($all as $k => $times) {
            $all[$k] = array_values(array_filter($times, fn($t) => $t > $now - 3600));
            if (!$all[$k]) {
                unset($all[$k]);
            }
        }
        $limited = count($all[$ip] ?? []) >= 5;
        if (!$limited) {
            $all[$ip][] = $now;
        }
        file_put_contents($f, json_encode($all), LOCK_EX);
        return $limited;
    }

    public static function send(array $d, string $lang): bool
    {
        $to = (string) Config::get('contact_to');
        $from = (string) Config::get('mail_from');
        $subjects = __('sujets', 'fr');
        $subject = '[Site Philadelphia] ' . ($subjects[$d['sujet']] ?? 'Message') . ' — ' . $d['nom'];
        $body = "Nouveau message envoyé depuis le formulaire de contact du site.\n\n"
            . "Nom : {$d['nom']}\nE-mail : {$d['email']}\nGSM : " . ($d['gsm'] ?: '-') . "\n"
            . 'Sujet : ' . ($subjects[$d['sujet']] ?? '') . "\nLangue : " . strtoupper($lang) . "\n"
            . 'Date : ' . date('d/m/Y H:i') . "\n\n" . str_repeat('-', 40) . "\n\n" . $d['message'] . "\n";

        if (Config::get('contact_keep_copy') && ($dir = Config::dataDir())) {
            @file_put_contents($dir . '/messages-contact.log', "==== " . date('c') . "\n" . $body . "\n", FILE_APPEND | LOCK_EX);
        }
        if (Config::isPlaceholder($to) || Config::isPlaceholder($from)) {
            return false;
        }
        // Protection contre l'injection d'en-têtes : aucune donnée du visiteur ne contient de retour à la ligne.
        $replyName = preg_replace('/[\r\n"<>]+/', ' ', $d['nom']);
        $headers = [
            'From' => 'Site Philadelphia <' . $from . '>',
            'Reply-To' => mb_encode_mimeheader($replyName, 'UTF-8') . ' <' . $d['email'] . '>',
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
            'X-Mailer' => 'PHP',
        ];
        return mail($to, mb_encode_mimeheader($subject, 'UTF-8'), $body, $headers, '-f' . $from);
    }
}
