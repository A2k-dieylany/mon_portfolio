<?php
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_auth();

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET' && $action === 'list') {
        $stmt = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC");
        json_response(['testimonials' => $stmt->fetchAll()]);
    } 
    elseif ($method === 'POST' && $action === 'save') {
        $id = $_POST['id'] ?? '';
        $client_name = $_POST['client_name'] ?? '';
        
        // Auto-generate initials if empty
        $client_initials = $_POST['client_initials'] ?? '';
        if(empty($client_initials)) {
            $words = explode(' ', $client_name);
            $client_initials = mb_strtoupper(mb_substr($words[0], 0, 1));
            if(isset($words[1])) {
                $client_initials .= mb_strtoupper(mb_substr($words[1], 0, 1));
            }
        }

        $role_fr = $_POST['role_fr'] ?? '';
        $role_en = $_POST['role_en'] ?? '';
        $role_ar = $_POST['role_ar'] ?? '';
        
        $text_fr = $_POST['text_fr'] ?? '';
        $text_en = $_POST['text_en'] ?? '';
        $text_ar = $_POST['text_ar'] ?? '';
        
        $stars = (int)($_POST['stars'] ?? 5);
        $is_visible = isset($_POST['is_visible']) ? 1 : 0;
        
        // As admin, approved is always 1 when saved from dashboard
        $is_approved = 1;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE testimonials SET client_name=?, client_initials=?, role_fr=?, role_en=?, role_ar=?, text_fr=?, text_en=?, text_ar=?, stars=?, is_approved=?, is_visible=? WHERE id=?");
            $stmt->execute([$client_name, $client_initials, $role_fr, $role_en, $role_ar, $text_fr, $text_en, $text_ar, $stars, $is_approved, $is_visible, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO testimonials (client_name, client_initials, role_fr, role_en, role_ar, text_fr, text_en, text_ar, stars, is_approved, is_visible) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$client_name, $client_initials, $role_fr, $role_en, $role_ar, $text_fr, $text_en, $text_ar, $stars, $is_approved, $is_visible]);
        }
        json_response(['success' => true]);
    } 
    elseif ($method === 'POST' && $action === 'delete') {
        $id = $_POST['id'] ?? '';
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id=?");
            $stmt->execute([$id]);
            json_response(['success' => true]);
        }
    }
} catch (Exception $e) {
    json_response(['error' => $e->getMessage()], 500);
}
