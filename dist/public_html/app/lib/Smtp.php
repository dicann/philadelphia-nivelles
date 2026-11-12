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

    public static function send(string $from, string $fromName, string $to, string $replyTo, string $replyName, string $subject, string $body): bool
    {
        $c = Config::get('smtp');
        $host = (string) $c['host'];
        $port = (int) ($c['port'] ?? 465);
        $secure = strtolower((string) ($c['secure'] ?? 'ssl')); // ssl (465) | tls (587) | '' (aucun)
        $verify = ($c['verify_cert'] ?? true) !== false;
        self::$error = '';
        try {
            $ctx = stream_context_create(['ssl' => [
                'verify_peer' => $verify, 'verify_peer_name' => $verify, 'allow_self_signed' => !$verify, 'SNI_enabled' => true, 'peer_name' => $host,
            ]]);
            $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
            $sock = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
            if (!$sock) {
                throw new RuntimeException("connexion impossible à $host:$port ($errstr)");
            }
            self::$sock = $sock;
            stream_set_timeout($sock, 20);
            self::expect(220);
            $me = preg_replace('/[^A-Za-z0-9.\-]/', '', $_SERVER['SERVER_NAME'] ?? 'localhost') ?: 'localhost';
            self::cmd("EHLO $me", 250);
            if ($secure === 'tls') {
                self::cmd('STARTTLS', 220);
                if (!@stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new RuntimeException('échec du chiffrement TLS');
                }
                self::cmd("EHLO $me", 250);
            }
            self::cmd('AUTH LOGIN', 334);
            self::cmd(base64_encode((string) $c['user']), 334);
            self::cmd(base64_encode((string) $c['pass']), 235, 'identifiant ou mot de passe SMTP refusé');
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
            return true;
        } catch (\Throwable $e) {
            self::$error = 'SMTP : ' . $e->getMessage();
            if (self::$sock) {
                @fclose(self::$sock);
                self::$sock = null;
            }
            return false;
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
