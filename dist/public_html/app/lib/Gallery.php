<?php
/**
 * Galerie automatique : chaque sous-dossier de uploads/galerie/ est un album.
 *
 *   uploads/galerie/2025-03 Travaux toiture/photo1.jpg
 *   uploads/galerie/2025-03 Travaux toiture/album.txt   (facultatif, voir README)
 *
 * Les albums sont triés du plus récent au plus ancien (ordre inverse des noms de dossier),
 * les photos par nom de fichier.
 */
final class Gallery
{
    private const EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public static function albums(): array
    {
        $base = ROOT . '/uploads/galerie';
        $albums = [];
        // Photos posées directement dans uploads/galerie/ : album « Photos ».
        $loose = self::images($base, 'uploads/galerie');
        foreach (glob($base . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);
            if ($name[0] === '.' || $name[0] === '_') {
                continue; // dossiers masqués
            }
            $images = self::images($dir, 'uploads/galerie/' . $name);
            if (!$images) {
                continue;
            }
            $albums[] = [
                'id' => self::slug($name),
                'dossier' => $name,
                'titre' => self::titles($dir, $name),
                'images' => $images,
            ];
        }
        usort($albums, fn($a, $b) => strnatcasecmp($b['dossier'], $a['dossier']));
        if ($loose) {
            $albums[] = ['id' => 'photos', 'dossier' => '', 'titre' => ['fr' => 'Photos', 'ro' => 'Fotografii'], 'images' => $loose];
        }
        return $albums;
    }

    /** Légendes facultatives : legendes.txt (« fichier | légende FR | legendă RO »). */
    private static function captions(string $dir): array
    {
        $map = [];
        if (is_file("$dir/legendes.txt")) {
            foreach (file("$dir/legendes.txt") ?: [] as $line) {
                $line = trim(preg_replace('/^\xEF\xBB\xBF/', '', $line));
                if ($line === '' || $line[0] === '#') {
                    continue;
                }
                $p = array_map('trim', explode('|', $line));
                if (count($p) >= 2) {
                    $map[$p[0]] = ['fr' => $p[1], 'ro' => $p[2] ?? $p[1]];
                }
            }
        }
        return $map;
    }

    private static function images(string $dir, string $rel): array
    {
        $out = [];
        $caps = self::captions($dir);
        foreach (scandir($dir) ?: [] as $f) {
            if ($f[0] === '.' || !is_file("$dir/$f")) {
                continue;
            }
            if (!in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), self::EXT, true)) {
                continue;
            }
            $size = @getimagesize("$dir/$f");
            $out[] = [
                'src' => "$rel/$f",
                'w' => $size[0] ?? 4,
                'h' => $size[1] ?? 3,
                'legende' => isset($caps[$f]) ? t($caps[$f]) : Media::humanize(pathinfo($f, PATHINFO_FILENAME)),
            ];
        }
        usort($out, fn($a, $b) => strnatcasecmp($a['src'], $b['src']));
        return $out;
    }

    /**
     * Titre de l'album : fichier album.txt facultatif
     *   ligne 1 = titre en français, ligne 2 = titre en roumain.
     * Sinon : le nom du dossier.
     */
    private static function titles(string $dir, string $name): array
    {
        $h = Media::humanize($name);
        $t = ['fr' => $h, 'ro' => $h];
        if (is_file("$dir/album.txt")) {
            $lines = array_values(array_filter(array_map('trim', file("$dir/album.txt") ?: [])));
            if (isset($lines[0])) {
                $t['fr'] = $t['ro'] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]);
            }
            if (isset($lines[1])) {
                $t['ro'] = $lines[1];
            }
        }
        return $t;
    }

    private static function slug(string $s): string
    {
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-') ?: 'album';
    }
}
