<?php
/**
 * Lecture structurée d'une conversation du chatbot.
 *
 * MAX converse en langage naturel ; ce second passage relit l'échange et en
 * tire les champs qui alimentent le CRM (nom, numéro, service, budget, besoin).
 * On sépare volontairement les deux : demander au modèle de discuter ET de
 * produire du JSON dans le même appel dégrade les deux.
 *
 * L'appel n'a lieu que lorsqu'une intention d'achat a été détectée, pas à
 * chaque message : inutile de payer un aller-retour pour « bonjour ».
 *
 * Placé sous includes/ : le dispatcher refuse d'y servir un fichier en HTTP.
 */

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

/**
 * Analyse la transcription et renvoie les champs exploitables.
 *
 * @return array{name:?string,phone:?string,email:?string,service:?string,
 *               budget:?string,need:?string,ready:bool}|null
 */
function sds_chat_extract(string $transcript, string $apiKey, string $model): ?array
{
    if ($apiKey === '' || trim($transcript) === '') {
        return null;
    }

    $instruction = <<<'PROMPT'
Tu analyses la conversation entre un visiteur et l'assistante d'une agence web
sénégalaise. Renvoie UNIQUEMENT un objet JSON, sans texte autour, avec ces clés :

{
  "name":    nom du visiteur s'il l'a donné, sinon null,
  "phone":   son numéro de téléphone s'il l'a donné, sinon null,
  "email":   son adresse e-mail s'il l'a donnée, sinon null,
  "company": le nom de son entreprise ou activité, sinon null,
  "service": le service qui l'intéresse en trois mots maximum, sinon null,
  "budget":  le budget qu'il a évoqué, sinon null,
  "need":    son besoin résumé en une phrase factuelle, sinon null,
  "ready":   true si le visiteur souhaite être recontacté, sinon false
}

N'invente rien : si une information n'a pas été donnée, mets null.
Le résumé doit décrire le besoin du visiteur, pas la réponse de l'assistante.
PROMPT;

    $payload = [
        'model'    => $model,
        'messages' => [
            ['role' => 'system', 'content' => $instruction],
            ['role' => 'user',   'content' => mb_substr($transcript, -6000)],
        ],
        'max_tokens'      => 400,
        'temperature'     => 0,
        'response_format' => ['type' => 'json_object'],
    ];

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        // Le visiteur attend déjà sa réponse : on ne bloque pas indéfiniment.
        CURLOPT_TIMEOUT => 8,
    ]);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) {
        error_log("Extraction chat : HTTP $status");
        return null;
    }

    $content = json_decode($response, true)['choices'][0]['message']['content'] ?? '';
    // Certains modèles encadrent le JSON d'un bloc de code malgré la consigne.
    if (preg_match('/\{.*\}/s', $content, $m)) {
        $content = $m[0];
    }
    $parsed = json_decode($content, true);
    if (!is_array($parsed)) {
        error_log('Extraction chat : réponse non exploitable');
        return null;
    }

    $clean = static function ($v): ?string {
        if (!is_string($v)) {
            return null;
        }
        $v = trim($v);
        // Les modèles renvoient parfois « null » ou « non fourni » en texte.
        if ($v === '' || in_array(mb_strtolower($v), ['null', 'none', 'non fourni', 'n/a'], true)) {
            return null;
        }
        return mb_substr($v, 0, 255);
    };

    return [
        'name'    => $clean($parsed['name'] ?? null),
        'phone'   => $clean($parsed['phone'] ?? null),
        'email'   => $clean($parsed['email'] ?? null),
        'company' => $clean($parsed['company'] ?? null),
        'service' => $clean($parsed['service'] ?? null),
        'budget'  => $clean($parsed['budget'] ?? null),
        'need'    => $clean($parsed['need'] ?? null),
        'ready'   => !empty($parsed['ready']),
    ];
}
