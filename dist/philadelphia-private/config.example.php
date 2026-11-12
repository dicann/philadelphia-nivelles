<?php
/**
 * CONFIGURATION PRIVÉE — Église Philadelphia Nivelles
 * ---------------------------------------------------------------
 * 1. Copiez ce fichier sous le nom  config.php  (dans ce même dossier).
 * 2. Ce dossier (philadelphia-private) doit se trouver À CÔTÉ de public_html,
 *    jamais à l'intérieur :
 *        /home/VOTRE_COMPTE/public_html/
 *        /home/VOTRE_COMPTE/philadelphia-private/config.php   <-- ici
 * 3. Ne partagez jamais ce fichier : il contient les clés secrètes de paiement.
 *
 * Les textes du site (titres, objectif, IBAN, contact…) ne sont PAS ici :
 * ils sont dans  public_html/content/site.json
 */

return [

    // Adresse publique du site, sans « / » final.
    // Laisser vide = détection automatique (utile pour un test sur un sous-domaine temporaire).
    'site_url' => 'https://philadelphia-nivelles.be',

    // Afficher les erreurs PHP à l'écran ? true uniquement pour un test, jamais en production.
    'debug' => false,

    // ---------------------------------------------------------------
    // PAIEMENTS
    // ---------------------------------------------------------------

    // Prestataire de paiement : 'stripe' ou 'mollie'
    'payment_provider' => 'stripe',

    // Mode : 'test' (aucun argent réel) ou 'live' (vrais paiements).
    'payment_mode' => 'test',

    // Montant minimum et maximum accepté pour un don en ligne (en euros)
    'donation_min' => 2,
    'donation_max' => 10000,

    'stripe' => [
        // Stripe > Développeurs > Clés API (mode test : clés qui commencent par sk_test_ / pk_test_)
        'test' => [
            'secret_key'     => 'sk_test_REMPLACER',
            // Stripe > Développeurs > Webhooks > votre endpoint > « Secret de signature » (whsec_...)
            'webhook_secret' => 'whsec_REMPLACER',
        ],
        'live' => [
            'secret_key'     => 'sk_live_REMPLACER',
            'webhook_secret' => 'whsec_REMPLACER',
        ],
        // Moyens de paiement proposés. Mettez null pour laisser Stripe choisir
        // selon ce qui est activé dans votre tableau de bord (Paramètres > Moyens de paiement).
        'methods_once'    => ['bancontact', 'card', 'sepa_debit'],
        // Pour les dons mensuels, Bancontact n'est pas utilisable comme prélèvement récurrent :
        // carte et domiciliation SEPA uniquement.
        'methods_monthly' => ['card', 'sepa_debit'],
    ],

    'mollie' => [
        // Mollie > Développeurs > Clés API (test_... / live_...)
        'test_key' => 'test_REMPLACER',
        'live_key' => 'live_REMPLACER',
        // Méthodes pour un don unique (codes Mollie). null = toutes celles activées dans Mollie.
        'methods_once'    => ['bancontact', 'creditcard', 'banktransfer'],
        // Premier paiement d'un don mensuel (crée le mandat de prélèvement).
        'methods_monthly' => ['bancontact', 'creditcard'],
    ],

    // ---------------------------------------------------------------
    // FORMULAIRE DE CONTACT
    // ---------------------------------------------------------------

    // Adresse qui REÇOIT les messages du formulaire.
    'contact_to' => 'info@philadelphia-nivelles.be',
    // Adresse d'EXPÉDITION : doit être une adresse de VOTRE domaine (ex. site@philadelphia-nivelles.be),
    // sinon les messages risquent d'arriver en spam. Créez-la dans cPanel > Comptes de messagerie.
    'mail_from'  => 'site@philadelphia-nivelles.be', // à créer dans cPanel > Comptes de messagerie
    // Garder aussi une copie des messages dans philadelphia-private/data/ (utile si l'e-mail se perd).
    'contact_keep_copy' => true,

    // ---------------------------------------------------------------
    // DIVERS
    // ---------------------------------------------------------------

    // Dossier des données (dons enregistrés, copies des messages). Par défaut : ./data à côté de ce fichier.
    'data_dir' => __DIR__ . '/data',
];
