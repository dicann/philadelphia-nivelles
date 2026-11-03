<?php
/**
 * Liste toutes les mentions [À COMPLÉTER ...] / [À VÉRIFIER ...] encore présentes dans le contenu.
 * Utilisation : php tools/lister-placeholders.php
 */
$root = dirname(__DIR__) . '/dist/public_html';
$files = array_merge(
    [$root . '/content/site.json', $root . '/content/videos.json'],
    glob($root . '/content/pages/*.md'),
    glob($root . '/content/actualites/*/*.md')
);
$total = 0;
foreach ($files as $f) {
    $lines = file($f);
    $hits = [];
    foreach ($lines as $n => $line) {
        if (preg_match_all('/\[À (?:COMPLÉTER|VÉRIFIER)[^\]]*\]/u', $line, $m)) {
            foreach ($m[0] as $t) {
                $hits[] = sprintf('  ligne %3d : %s', $n + 1, $t);
            }
        }
    }
    if ($hits) {
        echo str_replace(dirname(__DIR__) . '/dist/', '', $f), ' (', count($hits), ")\n", implode("\n", $hits), "\n\n";
        $total += count($hits);
    }
}
echo "Total : $total mentions à compléter.\n";
