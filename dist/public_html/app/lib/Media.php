<?php
/**
 * Images : redimensionnement automatique (miniatures mises en cache dans /cache/thumbs)
 * et image de remplacement quand une photo n'a pas encore été déposée.
 */
final class Media
{
    /** Largeurs générées (en pixels). */
    public const WIDTHS = [480, 960, 1600];

    /** Dossiers d'où les images peuvent être lues. */
    private const ROOTS = ['uploads', 'content/actualites'];

    private const EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /** Chemin relatif sûr (sous uploads/ ou content/actualites/) vers une image existante, sinon null. */
    public static function resolve(?string $rel): ?string
    {
        if (!$rel) {
            return null;
        }
        $rel = ltrim(str_replace('\\', '/', $rel), '/');
        if (str_contains($rel, '..')) {
            return null;
        }
        $ok = false;
        foreach (self::ROOTS as $r) {
            if (str_starts_with($rel, $r . '/')) {
                $ok = true;
            }
        }
        if (!$ok || !in_array(strtolower(pathinfo($rel, PATHINFO_EXTENSION)), self::EXT, true)) {
            return null;
        }
        $abs = ROOT . '/' . $rel;
        return is_file($abs) ? $rel : null;
    }

    /**
     * Si une image est indiquée dans site.json sans extension exacte (ex. .jpg alors que
     * vous avez déposé un .png ou .webp), on essaie les autres extensions.
     */
    public static function find(?string $rel): ?string
    {
        if (!$rel) {
            return null;
        }
        if ($r = self::resolve($rel)) {
            return $r;
        }
        $base = preg_replace('/\.[a-z0-9]+$/i', '', $rel);
        foreach (['jpg', 'jpeg', 'png', 'webp', 'JPG', 'JPEG', 'PNG', 'WEBP'] as $ext) {
            if ($r = self::resolve("$base.$ext")) {
                return $r;
            }
        }
        return null;
    }

    private static function cacheName(string $rel, int $w): string
    {
        $abs = ROOT . '/' . $rel;
        $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
        $out = in_array($ext, ['png', 'gif'], true) ? 'png' : 'jpg';
        return substr(sha1($rel . '|' . filemtime($abs) . '|' . filesize($abs)), 0, 20) . "-$w.$out";
    }

    /** URL d'une version redimensionnée (fichier en cache si déjà généré, sinon générateur). */
    public static function url(string $rel, int $w): string
    {
        $w = self::nearestWidth($w);
        $cached = 'cache/thumbs/' . self::cacheName($rel, $w);
        if (is_file(ROOT . '/' . $cached)) {
            return base_path() . $cached;
        }
        return base_path() . 'media.php?src=' . rawurlencode($rel) . '&w=' . $w;
    }

    public static function nearestWidth(int $w): int
    {
        foreach (self::WIDTHS as $c) {
            if ($w <= $c) {
                return $c;
            }
        }
        return end(self::WIDTHS);
    }

    public static function srcset(string $rel, int $max = 1600): string
    {
        $parts = [];
        foreach (self::WIDTHS as $w) {
            if ($w <= $max) {
                $parts[] = self::url($rel, $w) . " {$w}w";
            }
        }
        return implode(', ', $parts);
    }

    /**
     * Génère (si besoin) puis renvoie le chemin absolu de la miniature.
     * Renvoie le fichier original si GD est absent ou si l'image est trop grande pour la mémoire.
     */
    public static function thumbnail(string $rel, int $w): string
    {
        $w = self::nearestWidth($w);
        $src = ROOT . '/' . $rel;
        $dest = ROOT . '/cache/thumbs/' . self::cacheName($rel, $w);
        if (is_file($dest)) {
            return $dest;
        }
        if (!function_exists('imagecreatetruecolor')) {
            return $src;
        }
        $info = @getimagesize($src);
        if (!$info) {
            return $src;
        }
        [$sw, $sh, $type] = $info;
        // Mémoire nécessaire approximative ; on tente d'augmenter la limite pour les grosses photos.
        @ini_set('memory_limit', '512M');
        @set_time_limit(60);
        switch ($type) {
            case IMAGETYPE_JPEG: $im = @imagecreatefromjpeg($src); break;
            case IMAGETYPE_PNG:  $im = @imagecreatefrompng($src); break;
            case IMAGETYPE_GIF:  $im = @imagecreatefromgif($src); break;
            case IMAGETYPE_WEBP: $im = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false; break;
            default: $im = false;
        }
        if (!$im) {
            return $src;
        }
        // Orientation des photos de smartphone (EXIF).
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($src);
            $o = (int) ($exif['Orientation'] ?? 1);
            if ($o === 3) { $im = imagerotate($im, 180, 0); }
            if ($o === 6) { $im = imagerotate($im, -90, 0); [$sw, $sh] = [$sh, $sw]; }
            if ($o === 8) { $im = imagerotate($im, 90, 0); [$sw, $sh] = [$sh, $sw]; }
        }
        $tw = min($w, $sw);
        $th = (int) round($sh * $tw / $sw);
        $out = imagecreatetruecolor($tw, $th);
        $png = str_ends_with($dest, '.png');
        if ($png) {
            imagealphablending($out, false);
            imagesavealpha($out, true);
        }
        imagecopyresampled($out, $im, 0, 0, 0, 0, $tw, $th, $sw, $sh);
        if (!is_dir(dirname($dest))) {
            @mkdir(dirname($dest), 0755, true);
        }
        $tmp = $dest . '.' . getmypid() . '.tmp';
        if (!$png) {
            imageinterlace($out, true); // JPEG progressif
        }
        $ok = $png ? imagepng($out, $tmp, 7) : imagejpeg($out, $tmp, 80);
        imagedestroy($im);
        imagedestroy($out);
        if ($ok && @rename($tmp, $dest)) {
            return $dest;
        }
        @unlink($tmp);
        return $src;
    }

    /** « 2025-03_travaux-toiture » => « Travaux toiture » */
    public static function humanize(string $name): string
    {
        $name = preg_replace('/^\d{4}([-_.]\d{2}){0,2}[-_ ]*/', '', $name) ?: $name;
        $name = trim(str_replace(['-', '_'], ' ', $name));
        return mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
    }
}
