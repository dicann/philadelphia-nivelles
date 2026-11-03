<?php
/**
 * Registre des dons confirmés (rempli par les webhooks Stripe / Mollie).
 * Stockage : philadelphia-private/data/dons.json (lisible, exportable).
 * Chaque don a un identifiant unique : un même événement reçu deux fois n'est compté qu'une fois.
 */
final class Donations
{
    private static function file(): ?string
    {
        $d = Config::dataDir();
        return $d ? $d . '/dons.json' : null;
    }

    /** Lit le registre. */
    public static function all(): array
    {
        $f = self::file();
        if (!$f || !is_file($f)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($f), true);
        return is_array($data) ? $data : [];
    }

    /**
     * Ajoute un don (ou met à jour son statut). Verrou de fichier pour éviter
     * que deux webhooks simultanés n'écrasent les données.
     *
     * @param array{id:string, montant:float, devise?:string, mode:string, prestataire:string,
     *              statut?:string, etape?:?string, nom?:?string, email?:?string, anonyme?:bool} $don
     * @return bool true si le don est nouveau
     */
    public static function record(array $don): bool
    {
        $f = self::file();
        if (!$f) {
            throw new RuntimeException('Dossier de données introuvable ou non inscriptible (philadelphia-private/data).');
        }
        $fp = fopen($f, 'c+');
        if (!$fp || !flock($fp, LOCK_EX)) {
            throw new RuntimeException('Impossible de verrouiller ' . $f);
        }
        $raw = stream_get_contents($fp);
        $list = json_decode($raw ?: '[]', true) ?: [];
        $isNew = true;
        foreach ($list as $i => $existing) {
            if ($existing['id'] === $don['id']) {
                $isNew = false;
                $list[$i] = array_merge($existing, array_filter($don, fn($v) => $v !== null));
                break;
            }
        }
        if ($isNew) {
            $don += ['devise' => 'EUR', 'statut' => 'confirme', 'date' => date('c')];
            $list[] = $don;
        }
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return $isNew;
    }

    /** Totaux pour la barre de progression. */
    public static function totals(): array
    {
        $online = 0.0;
        $count = 0;
        $byStage = [];
        foreach (self::all() as $d) {
            if (($d['statut'] ?? '') !== 'confirme' || ($d['devise'] ?? 'EUR') !== 'EUR') {
                continue;
            }
            $online += (float) $d['montant'];
            $count++;
            if (!empty($d['etape'])) {
                $byStage[$d['etape']] = ($byStage[$d['etape']] ?? 0) + (float) $d['montant'];
            }
        }
        $c = Content::get('campagne', []);
        $offline = is_numeric($c['montant_hors_ligne'] ?? null) ? (float) $c['montant_hors_ligne'] : 0.0;
        $forced = is_numeric($c['montant_force'] ?? null) ? (float) $c['montant_force'] : null;
        $goal = is_numeric($c['objectif'] ?? null) && $c['objectif'] > 0 ? (float) $c['objectif'] : null;
        $raised = $forced ?? ($online + $offline);
        return [
            'collecte' => $raised,
            'objectif' => $goal,
            'pourcentage' => $goal ? min(100, round($raised / $goal * 100, 1)) : null,
            'nombre' => $count,
            'en_ligne' => $online,
            'par_etape' => $byStage,
        ];
    }
}
