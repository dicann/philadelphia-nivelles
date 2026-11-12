<?php
/**
 * Lecture des contenus éditables : content/site.json, content/videos.json,
 * content/actualites/* et content/pages/*.md
 */
final class Content
{
    private static ?array $site = null;

    /** Pages du site : clé => [slug FR, slug RO]. */
    public const ROUTES = [
        'accueil'         => ['', ''],
        'histoire'        => ['histoire', 'istorie'],
        'communaute'      => ['communaute', 'comunitate'],
        'projet'          => ['le-projet', 'proiectul'],
        'don'             => ['faire-un-don', 'doneaza'],
        'don_paiement'    => ['faire-un-don/paiement', 'doneaza/plata'],
        'don_merci'       => ['faire-un-don/merci', 'doneaza/multumim'],
        'don_annule'      => ['faire-un-don/annule', 'doneaza/anulat'],
        'galerie'         => ['galerie', 'galerie'],
        'actualites'      => ['actualites', 'noutati'],
        'contact'         => ['contact', 'contact'],
        'mentions'        => ['mentions-legales', 'mentiuni-legale'],
        'confidentialite' => ['politique-de-confidentialite', 'politica-de-confidentialitate'],
    ];

    public const LANGS = ['fr', 'ro'];

    public static function site(): array
    {
        if (self::$site === null) {
            self::$site = self::readJson(ROOT . '/content/site.json');
        }
        return self::$site;
    }

    /** Valeur de site.json par chemin « a.b.c ». */
    public static function get(string $path, $default = null)
    {
        $v = self::site();
        foreach (explode('.', $path) as $p) {
            if (!is_array($v) || !array_key_exists($p, $v)) {
                return $default;
            }
            $v = $v[$p];
        }
        return $v;
    }

    /**
     * Lit un fichier JSON. En cas d'erreur de syntaxe, affiche un message clair
     * (en français) plutôt qu'une page blanche.
     */
    public static function readJson(string $file, bool $required = true): array
    {
        if (!is_file($file)) {
            if ($required) {
                self::fatal("Fichier introuvable : " . basename($file));
            }
            return [];
        }
        $raw = file_get_contents($file);
        // Tolérance : supprime un éventuel BOM ajouté par certains éditeurs.
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string) $raw);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $msg = json_last_error_msg();
            $hint = self::jsonErrorLine($raw);
            self::fatal("Erreur dans le fichier " . str_replace(ROOT . '/', '', $file) . " : $msg. $hint"
                . " Vérifiez les guillemets et les virgules (pas de virgule après le dernier élément d'une liste).");
        }
        return $data;
    }

    /** Essaie de situer la ligne d'une erreur JSON (approximatif). */
    private static function jsonErrorLine(string $raw): string
    {
        $lines = explode("\n", $raw);
        $acc = '';
        foreach ($lines as $i => $line) {
            $acc .= $line . "\n";
            // On cherche la première ligne à partir de laquelle le début du fichier
            // ne peut plus être complété en JSON valide : heuristique simple sur les virgules.
            if (preg_match('/,\s*$/', $line) && isset($lines[$i + 1]) && preg_match('/^\s*[}\]]/', $lines[$i + 1])) {
                return 'Virgule en trop probable à la ligne ' . ($i + 1) . '.';
            }
            if (preg_match('/"\s*$/', $line) && isset($lines[$i + 1]) && preg_match('/^\s*"/', $lines[$i + 1])) {
                return 'Virgule manquante probable à la ligne ' . ($i + 1) . '.';
            }
        }
        return '';
    }

    public static function fatal(string $message): void
    {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>Erreur de contenu</title>'
            . '<div style="font-family:sans-serif;max-width:640px;margin:10vh auto;padding:24px;border:2px solid #b45309;border-radius:8px;background:#fff7ed">'
            . '<h1 style="margin-top:0">Le site ne peut pas s\'afficher</h1><p>' . htmlspecialchars($message) . '</p>'
            . '<p style="color:#555">Corrigez le fichier via le Gestionnaire de fichiers de cPanel, puis rechargez la page.</p></div>';
        exit;
    }

    // ------------------------------------------------------------------ vidéos

    /**
     * Vidéos : liens YouTube/Vimeo de content/videos.json + fichiers MP4 de uploads/video/.
     * @return array<int, array{type:string,id?:string,src?:string,poster?:?string,titre:mixed}>
     */
    public static function videos(): array
    {
        $out = [];
        $data = self::readJson(ROOT . '/content/videos.json', false);
        $listed = [];
        foreach ($data['videos'] ?? [] as $v) {
            $file = trim((string) ($v['fichier'] ?? ''));
            if ($file !== '') {
                $file = ltrim(str_replace('\\', '/', $file), '/');
                if (!str_contains($file, '..') && str_starts_with($file, 'uploads/video/') && is_file(ROOT . '/' . $file)) {
                    $base = preg_replace('/\.[^.]+$/', '', $file);
                    $poster = null;
                    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                        if (is_file(ROOT . "/$base.$ext")) { $poster = "$base.$ext"; break; }
                    }
                    $out[] = ['type' => 'fichier', 'src' => $file, 'poster' => $poster, 'titre' => $v['titre'] ?? '', 'vertical' => !empty($v['vertical'])];
                    $listed[] = $file;
                }
                continue;
            }
            $url = trim((string) ($v['lien'] ?? ''));
            if ($url === '') {
                continue;
            }
            $titre = $v['titre'] ?? '';
            if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
                $out[] = ['type' => 'youtube', 'id' => $m[1], 'titre' => $titre];
            } elseif (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
                $out[] = ['type' => 'vimeo', 'id' => $m[1], 'titre' => $titre];
            }
        }
        $dir = ROOT . '/uploads/video';
        if (is_dir($dir)) {
            $files = glob($dir . '/*.{mp4,MP4,webm,WEBM}', GLOB_BRACE) ?: [];
            natcasesort($files);
            foreach ($files as $f) {
                if (in_array('uploads/video/' . basename($f), $listed, true)) {
                    continue;
                }
                $base = pathinfo($f, PATHINFO_FILENAME);
                $poster = null;
                foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                    if (is_file("$dir/$base.$ext")) {
                        $poster = "uploads/video/$base.$ext";
                        break;
                    }
                }
                $out[] = [
                    'type' => 'fichier',
                    'src' => 'uploads/video/' . basename($f),
                    'poster' => $poster,
                    'titre' => Media::humanize($base),
                ];
            }
        }
        return $out;
    }

    // ------------------------------------------------------------- actualités

    /**
     * Chaque actualité est un dossier dans content/actualites/ nommé
     * AAAA-MM-JJ-titre-court, contenant fr.md, ro.md et une image facultative.
     */
    public static function news(): array
    {
        $items = [];
        foreach (glob(ROOT . '/content/actualites/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $folder = basename($dir);
            if (!preg_match('/^(\d{4}-\d{2}-\d{2})-([a-z0-9-]+)$/', $folder, $m)) {
                continue;
            }
            $item = ['slug' => $m[2], 'dossier' => $folder, 'date' => $m[1], 'image' => null, 'fr' => null, 'ro' => null];
            foreach (self::LANGS as $l) {
                if (is_file("$dir/$l.md")) {
                    $item[$l] = Markdown::parseFile("$dir/$l.md");
                }
            }
            if (!$item['fr'] && !$item['ro']) {
                continue;
            }
            $meta = ($item['fr'] ?? $item['ro'])['meta'];
            if (isset($meta['publie']) && in_array(mb_strtolower($meta['publie']), ['non', 'nu', 'false', '0'], true)) {
                continue;
            }
            foreach (glob("$dir/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}", GLOB_BRACE) ?: [] as $img) {
                $item['image'] = 'content/actualites/' . $folder . '/' . basename($img);
                break;
            }
            $items[] = $item;
        }
        usort($items, fn($a, $b) => strcmp($b['dossier'], $a['dossier']));
        return $items;
    }

    public static function newsItem(string $slug): ?array
    {
        foreach (self::news() as $n) {
            if ($n['slug'] === $slug) {
                return $n;
            }
        }
        return null;
    }

    /** Page de texte libre (mentions légales, confidentialité) en Markdown. */
    public static function page(string $name, string $lang): ?array
    {
        foreach ([$lang, 'fr'] as $l) {
            $f = ROOT . "/content/pages/$name.$l.md";
            if (is_file($f)) {
                return Markdown::parseFile($f);
            }
        }
        return null;
    }
}
