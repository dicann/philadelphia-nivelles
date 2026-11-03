<?php
/**
 * Webhook Stripe : adresse à déclarer dans Stripe > Développeurs > Webhooks
 *   https://VOTRE-DOMAINE/webhook-stripe.php
 * Événements : checkout.session.completed, checkout.session.async_payment_succeeded,
 *              checkout.session.async_payment_failed, invoice.paid
 */
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    exit('Webhook Stripe actif. (Cette adresse reçoit uniquement des notifications de Stripe.)');
}
try {
    $result = StripePay::handleWebhook(
        (string) file_get_contents('php://input'),
        (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '')
    );
    echo $result;
} catch (\Stripe\Exception\SignatureVerificationException | \UnexpectedValueException $e) {
    http_response_code(400); // signature invalide : la requête ne vient pas de Stripe
    echo 'Signature invalide';
} catch (\Throwable $e) {
    error_log('[webhook-stripe] ' . $e->getMessage());
    http_response_code(500); // Stripe réessaiera automatiquement
    echo 'Erreur';
}
