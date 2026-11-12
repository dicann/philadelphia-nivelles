<?php
/**
 * Envoi d'e-mail par SMTP authentifié (sans bibliothèque externe).
 * Utilisé par le formulaire de contact quand 'smtp' est rempli dans config.php :
 * c'est la méthode la plus fiable chez les hébergeurs qui bloquent mail().
 */
final class Smtp
{
    public static string $error = '';

    /** @var resource|null */
    private static $sock = null;

    public static function configured(): bool
    {
        $c = Config::get('smtp', []);
        return is_array($c) && !empty($c['host']) && !empty($c['user']) && isset($c['pass'])
            && !Config::isPlaceholder((string) $c['pass']);
    }

    /**
     * Envoie le message. Essaie d'abord les réglages de config.php, puis les variantes
     * habituelles chez cPanel (587/STARTTLS, 465/SSL, serveur local port 25).
     */
    public static function send(string $from, string $fromName, string $to, string $replyTo, string $replyName, string $subject, string $body): bool
    {
        $c = Config::get('smtp');
        $host = (string) $c['host'];
        $verify = ($c['verify_cert'] ?? true) !== false;
        $tries = [[$host, (int) ($c['port'] ?? 465), strtolower((string) ($c['secure'] ?? 'ssl'))]];
        foreach ([[$host, 587, 'tls'], [$host, 465, 'ssl'], ['localhost', 587, 'tls'], ['localhost', 25, '']] as $t) {
            if (!in_array($t, $tries, true)) {
                $tries[] = $t;
            }
        }
        $errors = [];
        foreach ($tries as [$h, $port, $secure]) {
            // Sur le serveur local, le certificat porte le nom du serveur, pas « localhost ».
            $err = self::attempt($h, $port, $secure, $h === 'localhost' ? false : $verify, $c, $from, $fromName, $to, $replyTo, $replyName, $subject, $body);
            if ($err === null) {
                self::$error = '';
                return true;
            }
            $errors[] = "$h:$port" . ($secure ? "/$secure" : '') . " → $err";
            if (str_contains($err, 'mot de passe')) {
                break; // inutile d'insister si les identifiants sont refusés
            }
        }
        self::$error = 'SMTP : ' . implode(' | ', $errors);
        return false;
    }

    /** Une tentative d'envoi. Renvoie null si OK, sinon le message d'erreur. */
    private static function attempt(string $host, int $port, string $secure, bool $verify, array $c, string $from, string $fromName, string $to, string $replyTo, string $replyName, string $subject, string $body): ?string
    {
        try {
            $ctx = stream_context_create(['ssl' => [
                'verify_peer' => $verify, 'verify_peer_name' => $verify, 'allow_self_signed' => !$verify, 'SNI_enabled' => true, 'peer_name' => $host === 'localhost' ? (string) $c['host'] : $host,
            ]]);
            $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
            $sock = @stream_socket_client($remote, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $ctx);
            if (!$sock) {
                return 'connexion impossible (' . ($errstr ?: 'refusée') . ')';
            }
            self::$sock = $sock;
            stream_set_timeout($sock, 30);
            self::expect(220, 'pas de message d\'accueil du serveur');
            $me = preg_replace('/[^A-Za-z0-9.\-]/', '', $_SERVER['SERVER_NAME'] ?? 'localhost') ?: 'localhost';
            $ehlo = self::cmd("EHLO $me", 250);
            if ($secure === 'tls') {
                self::cmd('STARTTLS', 220);
                if (!@stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new RuntimeException('échec du chiffrement TLS');
                }
                $ehlo = self::cmd("EHLO $me", 250);
            }
            if (stripos($ehlo, 'AUTH') !== false) {
                self::cmd('AUTH LOGIN', 334);
                self::cmd(base64_encode((string) $c['user']), 334);
                self::cmd(base64_encode((string) $c['pass']), 235, 'identifiant ou mot de passe SMTP refusé');
            } elseif ($host !== 'localhost') {
                throw new RuntimeException('le serveur ne propose pas d\'authentification');
            }
            self::cmd("MAIL FROM:<$from>", 250);
            self::cmd("RCPT TO:<$to>", [250, 251]);
            self::cmd('DATA', 354);
            $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
            $headers = [
                'Date: ' . date('r'),
                'From: ' . self::addr($fromName, $from),
                'To: <' . $to . '>',
                'Reply-To: ' . self::addr($replyName, $replyTo),
                'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8'),
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
            ];
            $data = implode("\r\n", $headers) . "\r\n\r\n" . rtrim(chunk_split(base64_encode($body), 76, "\r\n")) . "\r\n.";
            self::cmd($data, 250);
            @fwrite($sock, "QUIT\r\n");
            fclose($sock);
            self::$sock = null;
            return null;
        } catch (\Throwable $e) {
            if (self::$sock) {
                @fclose(self::$sock);
                self::$sock = null;
            }
            return $e->getMessage();
        }
    }

    private static function addr(string $name, string $email): string
    {
        $name = trim(preg_replace('/[\r\n"<>]+/', ' ', $name));
        return ($name !== '' ? mb_encode_mimeheader($name, 'UTF-8') . ' ' : '') . '<' . $email . '>';
    }

    /** Envoie une commande et vérifie le code de réponse. */
    private static function cmd(string $line, $codes, string $hint = ''): string
    {
        fwrite(self::$sock, $line . "\r\n");
        return self::expect($codes, $hint, str_starts_with($line, 'AUTH') || strlen($line) > 200 ? strtok($line, ' ') : $line);
    }

    private static function expect($codes, string $hint = '', string $after = ''): string
    {
        $codes = (array) $codes;
        $resp = '';
        while (($l = fgets(self::$sock, 1024)) !== false) {
            $resp .= $l;
            if (strlen($l) < 4 || $l[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($resp, 0, 3);
        if (!in_array($code, $codes, true)) {
            $shown = preg_match('/^[A-Za-z0-9+\/=]+$/', $after) ? '(identifiants)' : $after;
            throw new RuntimeException(($hint ? $hint . ' — ' : '') . 'réponse inattendue' . ($shown ? " après « $shown »" : '') . ' : ' . trim($resp ?: 'aucune réponse'));
        }
        return $resp;
    }
}
