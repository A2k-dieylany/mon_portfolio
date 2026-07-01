<?php
/**
 * webhook.php
 * Ce fichier agit comme le webhook local (CRM) de SDS.
 * Il reçoit le JSON envoyé par contact.php (ou autre source)
 * et déclenche une notification WhatsApp via l'API Meta Cloud.
 */

// Permettre les requêtes cross-origin si nécessaire
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Charger la configuration globale
require_once __DIR__ . '/../config.php';

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée. Utilisez POST.']);
    exit;
}

// Récupérer le corps de la requête (Payload JSON)
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

// Vérifier que le JSON est valide et contient les données minimales
if (!$data || !isset($data['name']) || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données JSON invalides ou incomplètes.']);
    exit;
}

$name = $data['name'] ?? 'Inconnu';
$email = $data['email'] ?? 'Non fourni';
$subject = $data['subject'] ?? 'Aucun sujet';
$message = $data['message'] ?? '';
$source = $data['source'] ?? 'Site Web SDS';
$timestamp = $data['timestamp'] ?? date('d/m/Y H:i');

// === INTEGRATION META WHATSAPP CLOUD API ===

$metaToken = defined('META_API_TOKEN') ? META_API_TOKEN : '';
$phoneId = defined('META_PHONE_ID') ? META_PHONE_ID : '';
$targetPhone = defined('META_TARGET_PHONE') ? META_TARGET_PHONE : '';

if (empty($metaToken) || empty($phoneId) || empty($targetPhone)) {
    // Les clés ne sont pas configurées, on log simplement en succès pour dire que le webhook a bien reçu la donnée
    error_log("SDS Webhook : Lead reçu de $name ($email) mais API Meta non configurée.");
    echo json_encode(['status' => 'success', 'message' => 'Lead reçu localement, API Meta inactive.']);
    exit;
}

// Construction du message WhatsApp (Formatage Markdown WhatsApp)
$whatsappText = "🚨 *NOUVEAU LEAD SDS* 🚨\n\n";
$whatsappText .= "*Source:* $source\n";
$whatsappText .= "*Client:* $name\n";
$whatsappText .= "*Email:* $email\n";
$whatsappText .= "*Sujet:* $subject\n\n";
$whatsappText .= "📝 *Message :*\n$message\n\n";
$whatsappText .= "_Reçu le : " . $timestamp . "_";

// Format du payload Meta
$metaPayload = json_encode([
    'messaging_product' => 'whatsapp',
    'to' => $targetPhone,
    'type' => 'text',
    'text' => [
        'body' => $whatsappText
    ]
]);

// Appel cURL vers l'API Meta
$metaUrl = "https://graph.facebook.com/v19.0/$phoneId/messages";

$ch = curl_init($metaUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $metaPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $metaToken,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['status' => 'success', 'message' => 'Lead reçu et WhatsApp envoyé !']);
} else {
    error_log("SDS Webhook Meta API Error : HTTP $httpCode - Response: $response");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Échec de la notification WhatsApp.', 'meta_response' => json_decode($response)]);
}
