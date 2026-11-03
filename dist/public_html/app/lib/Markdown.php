<?php
/**
 * Convertisseur Markdown minimal et sûr (le HTML saisi est échappé).
 * Gère : en-tête « --- clé: valeur --- », titres #, paragraphes, listes - et 1.,
 * citations >, gras **, italique *, liens [texte](url), séparateur ---.
 */
final class Markdown
{
    /** @return array{meta: array<string,string>, html: string, text: string} */
    public static function parseFile(string $file): array
    {
        $raw = (string) file_get_contents($file);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $meta = [];
        if (preg_match('/^---\n(.*?)\n---\n?/s', $raw, $m)) {
            foreach (explode("\n", $m[1]) as $line) {
                if (preg_match('/^\s*([\p{L}_-]+)\s*:\s*(.*)$/u', $line, $kv)) {
                    $meta[mb_strtolower($kv[1])] = trim($kv[2], " \t\"'");
                }
            }
            $raw = substr($raw, strlen($m[0]));
        }
        return ['meta' => $meta, 'html' => self::toHtml($raw), 'text' => trim(strip_tags(self::toHtml($raw)))];
    }

    public static function toHtml(string $md): string
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $md));
        $html = [];
        $para = [];
        $list = null; // 'ul' | 'ol'
        $quote = [];

        $flushPara = function () use (&$para, &$html) {
            if ($para) {
                // Un retour à la ligne dans le texte = un retour à la ligne sur le site.
                $html[] = '<p>' . implode("<br>\n", array_map([self::class, 'inline'], $para)) . '</p>';
                $para = [];
            }
        };
        $flushList = function () use (&$list, &$html) {
            if ($list) {
                $html[] = "</$list>";
                $list = null;
            }
        };
        $flushQuote = function () use (&$quote, &$html) {
            if ($quote) {
                $html[] = '<blockquote><p>' . self::inline(implode(' ', $quote)) . '</p></blockquote>';
                $quote = [];
            }
        };

        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') {
                $flushPara(); $flushList(); $flushQuote();
                continue;
            }
            if (preg_match('/^(#{1,4})\s+(.*)$/', $t, $m)) {
                $flushPara(); $flushList(); $flushQuote();
                $lvl = min(4, strlen($m[1]) + 1); // # => h2 (le h1 est le titre de la page)
                $html[] = "<h$lvl>" . self::inline($m[2]) . "</h$lvl>";
                continue;
            }
            if (preg_match('/^(-{3,}|\*{3,})$/', $t)) {
                $flushPara(); $flushList(); $flushQuote();
                $html[] = '<hr>';
                continue;
            }
            if (preg_match('/^>\s?(.*)$/', $t, $m)) {
                $flushPara(); $flushList();
                $quote[] = $m[1];
                continue;
            }
            if (preg_match('/^[-*]\s+(.*)$/', $t, $m) || preg_match('/^\d+[.)]\s+(.*)$/', $t, $m2)) {
                $flushPara(); $flushQuote();
                $type = isset($m[1]) && $m ? 'ul' : 'ol';
                $content = $m[1] ?? $m2[1];
                if ($list !== $type) {
                    $flushList();
                    $html[] = "<$type>";
                    $list = $type;
                }
                $html[] = '<li>' . self::inline($content) . '</li>';
                continue;
            }
            $flushList(); $flushQuote();
            $para[] = $t;
        }
        $flushPara(); $flushList(); $flushQuote();
        return implode("\n", $html);
    }

    private static function inline(string $s): string
    {
        $s = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        // Liens [texte](https://...) — seuls http(s), mailto, tel et chemins relatifs sont acceptés.
        $s = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', function ($m) {
            $href = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
            if (!preg_match('~^(https?://|mailto:|tel:|/|#)~i', $href)) {
                return $m[0];
            }
            $ext = preg_match('~^https?://~i', $href) ? ' rel="noopener" target="_blank"' : '';
            return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . $ext . '>' . $m[1] . '</a>';
        }, $s);
        $s = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s);
        $s = preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/', '<em>$1</em>', $s);
        return $s;
    }
}
