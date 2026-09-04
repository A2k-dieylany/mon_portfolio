<?php
/**
 * Endpoint de santé — sert aussi de "keep-alive" pour la base.
 *
 * Le plan gratuit Aiven éteint automatiquement un service inactif. Un ping
 * régulier (via un service de monitoring externe type UptimeRobot/cron-job.org)
 * génère assez d'activité pour l'éviter.
 *
 * Volontairement minimal : une seule requête triviale, aucune écriture, donc
 * aucune pollution des statistiques de visite.
 */

header('Content-Type: application/json');
header('Cache-Control: no-store');

require_once __DIR__ . '/admin/includes/db.php';

try {
    $pdo = getDB();
    $pdo->query('SELECT 1');
    echo json_encode([
        'status'    => 'ok',
        'database'  => 'up',
        'timestamp' => date('c'),
    ]);
} catch (Throwable $e) {
    error_log('SDS Health check failed: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'status'   => 'error',
        'database' => 'down',
    ]);
}
