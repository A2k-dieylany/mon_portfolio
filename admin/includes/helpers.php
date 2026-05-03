<?php
/**
 * SDS Admin — Fonctions utilitaires
 */

/**
 * Envoyer une réponse JSON propre et terminer le script
 */
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Nettoyer une chaîne contre XSS
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Vérifier que la requête est bien POST
 */
function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['error' => 'Méthode non autorisée.'], 405);
    }
}

/**
 * Vérifier que la requête est bien GET
 */
function require_get(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(['error' => 'Méthode non autorisée.'], 405);
    }
}

/**
 * Lire le body JSON de la requête
 */
function get_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_response(['error' => 'Corps de requête invalide.'], 400);
    }
    return $data;
}

/**
 * Vérifier qu'un champ requis existe et n'est pas vide
 */
function require_fields(array $data, array $fields): void {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            json_response(['error' => "Le champ '$field' est requis."], 400);
        }
    }
}

/**
 * Générer un slug URL-friendly à partir d'un texte
 */
function slugify(string $text): string {
    $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Envoyer les headers de sécurité
 */
function security_headers(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
