<?php
/**
 * Webhook Mollie : appelé automatiquement par Mollie (l'adresse est transmise à chaque paiement,
 * rien à configurer dans le tableau de bord Mollie).
 */
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    exit('Webhook Mollie actif.');
}
try {
    echo MolliePay::handleWebhook((string) ($_POST['id'] ?? ''));
} catch (\Throwable $e) {
    error_log('[webhook-mollie] ' . $e->getMessage());
    http_response_code(500); // Mollie réessaiera
    echo 'Erreur';
}
