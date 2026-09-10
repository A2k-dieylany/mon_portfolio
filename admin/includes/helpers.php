<?php
/**
 * SDS Admin — Fonctions utilitaires
 */

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

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
 * Nettoie un texte avant enregistrement — sans l'encoder.
 *
 * Le texte est stocké tel quel et échappé uniquement à l'affichage. L'ancien
 * sanitize() appliquait htmlspecialchars à l'enregistrement : le site
 * l'encodant une seconde fois, les visiteurs lisaient « Logistique &amp;amp;
 * RH », et chaque passage dans l'admin ajoutait une couche.
 */
function clean_text($input): string {
    $text = trim((string) $input);
    // Caractères de contrôle invisibles, hors tabulation et retours à la ligne.
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
}

/**
 * Nettoie une adresse avant enregistrement.
 *
 * N'accepte qu'une URL http(s) ou un chemin relatif (img/projects/x.jpg).
 * Stockée brute, une adresse « javascript:… » deviendrait un lien piégé sur
 * le site public : elle est refusée et remplacée par une chaîne vide.
 */
function clean_url($input): string {
    $url = clean_text($input);
    if ($url === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $url)) {
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }
    // Chemin relatif : aucun schéma (« xxx: ») avant le premier « / ».
    return preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url) ? '' : $url;
}

/**
 * Filtre le HTML des pages services avant enregistrement.
 *
 * Le contenu détaillé est affiché tel quel sur le site public : sans filtre,
 * une balise <script> enregistrée depuis l'admin s'exécuterait chez chaque
 * visiteur. On ne garde qu'une liste blanche de balises et d'attributs —
 * celles qu'utilisent réellement les contenus, plus quelques voisines utiles.
 * Les balises inconnues sont remplacées par leur texte ; script, style,
 * iframe et formulaires sont supprimés avec leur contenu.
 */
function clean_html($input): string {
    $html = trim((string) $input);
    if ($html === '') {
        return '';
    }

    $allowed = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h2', 'h3', 'h4', 'ul', 'ol', 'li',
                'div', 'span', 'blockquote', 'hr', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];
    $dropWithContent = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
                        'textarea', 'select', 'svg', 'math', 'template', 'link', 'meta', 'base'];

    if (!class_exists('DOMDocument')) {
        // Repli sans l'extension DOM : moins fin, mais sans attribut d'événement ni lien piégé.
        $html = preg_replace('#<(' . implode('|', $dropWithContent) . ')\b.*?</\1\s*>#is', '', $html);
        $html = strip_tags($html, '<' . implode('><', $allowed) . '>');
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        return preg_replace('/(href\s*=\s*["\']?)\s*(javascript|data|vbscript):/i', '$1#', $html);
    }

    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><div id="sds-root">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $root = $dom->getElementById('sds-root');
    if (!$root) {
        return htmlspecialchars(strip_tags($html), ENT_QUOTES, 'UTF-8');
    }

    $walk = function (DOMNode $node) use (&$walk, $allowed, $dropWithContent, $dom) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->nodeName);
            if (in_array($tag, $dropWithContent, true)) {
                $node->removeChild($child);
                continue;
            }
            $walk($child);
            if (!in_array($tag, $allowed, true)) {
                // Balise inconnue : on garde son contenu, pas la balise.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                $keep = $name === 'class'
                    || ($tag === 'a' && in_array($name, ['href', 'target', 'title'], true));
                if (!$keep) {
                    $child->removeAttribute($attr->nodeName);
                }
            }
            if ($tag === 'a') {
                $href = trim($child->getAttribute('href'));
                $safe = preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $href)
                    || ($href !== '' && !preg_match('#^[a-z][a-z0-9+.\-]*:#i', $href));
                if (!$safe) {
                    $child->removeAttribute('href');
                }
                if ($child->getAttribute('target') === '_blank') {
                    $child->setAttribute('rel', 'noopener noreferrer');
                }
            }
        }
    };
    $walk($root);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $dom->saveHTML($child);
    }
    return trim($out);
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
    // Table explicite plutôt que intl (absente de certains environnements) ou
    // iconv (qui translittère « é » en « 'e » selon le système, d'où
    // « cr-eation ») : la même saisie donne la même adresse partout.
    static $accents = [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
        'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ì' => 'i',
        'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ò' => 'o', 'õ' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'ÿ' => 'y', 'ñ' => 'n',
        'œ' => 'oe', 'æ' => 'ae', 'ß' => 'ss',
    ];
    $text = strtr(mb_strtolower($text, 'UTF-8'), $accents);
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
