<?php
/**
 * Script de migration des données statiques (i18n.js) vers la Base de Données
 * À exécuter une seule fois !
 * ⚠️ PROTÉGÉ — Nécessite une session admin active
 */
require_once __DIR__ . '/includes/auth_check.php';
require_auth();

// Vérifier que c'est un superadmin
$admin = get_admin();
if ($admin['role'] !== 'superadmin') {
    die('Accès réservé au superadmin.');
}

require_once __DIR__ . '/includes/db.php';
$pdo = getDB();

try {
    $pdo->beginTransaction();

    // 1. SERVICES
    $services = [
        ['icon' => '📱', 'title_fr' => 'Automatisation WhatsApp Business', 'desc_fr' => 'Agents IA sur WhatsApp pour répondre aux clients 24/7, prendre les commandes et faire du support client sans intervention humaine.'],
        ['icon' => '💻', 'title_fr' => 'Développement Web', 'desc_fr' => 'Sites vitrines, e-commerce, tableaux de bord admin et applications web full-stack. Code propre, rapide, déployé sur Netlify.'],
        ['icon' => '🤖', 'title_fr' => 'Intégration IA & Agents', 'desc_fr' => 'Intégration d\'API OpenAI, agents autonomes, automatisation de workflows complexes avec Make et n8n.'],
        ['icon' => '🎨', 'title_fr' => 'Design Graphique & Branding', 'desc_fr' => 'Affiches événementielles, identité visuelle, supports marketing digitaux. Un design moderne qui parle au public africain.'],
        ['icon' => '🔒', 'title_fr' => 'Cybersécurité', 'desc_fr' => 'Sensibilisation, audits de base et mise en place des bonnes pratiques pour protéger vos données et vos systèmes.'],
        ['icon' => '📈', 'title_fr' => 'Stratégie Digitale', 'desc_fr' => 'Accompagnement dans la transformation digitale : choix d\'outils, automatisation des process, formation des équipes.']
    ];
    $stmtSrv = $pdo->prepare("INSERT INTO services (icon, title_fr, title_en, title_ar, desc_fr, desc_en, desc_ar, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($services as $i => $s) {
        $stmtSrv->execute([$s['icon'], $s['title_fr'], $s['title_fr'], $s['title_fr'], $s['desc_fr'], $s['desc_fr'], $s['desc_fr'], $i]);
    }

    // 2. TIMELINE
    $timeline = [
        ['year' => '2022', 'badge' => '🏆 Excellence académique', 'title' => 'Concours Général Sénégalais', 'desc' => '2ème Prix National en Langue Arabe. Une distinction qui a forgé la rigueur intellectuelle.'],
        ['year' => '2023', 'badge' => '📚 Formation technique', 'title' => 'BTS Génie Logiciel — ISI Suptech', 'desc' => 'Formation intensive en génie logiciel à Dakar. Maîtrise des algorithmes, bases de données, web.'],
        ['year' => 'Jan. 2024', 'badge' => '🚀 Lancement entrepreneurial', 'title' => 'Création de SEN DIGITAL SOLUTION', 'desc' => 'Création de l\'agence digitale SDS — vision : devenir le leader de la transformation digitale en Afrique.'],
        ['year' => 'Juin 2024', 'badge' => '💼 Premier client', 'title' => 'Premier client — Dibiterie Ameth Boll', 'desc' => 'Site complet avec commandes WhatsApp et panel admin. Première preuve de valeur.'],
        ['year' => 'Sept. 2024', 'badge' => '🤖 IA en production', 'title' => 'Bot IA WhatsApp — SDS_Shop', 'desc' => 'Agent IA via n8n et OpenAI : commandes automatiques, log Google Sheets, service 24/7.'],
        ['year' => '2025', 'badge' => '⏳ En cours', 'title' => 'Expert IA Reconnu + Mémoire BTS', 'desc' => 'Positionnement expert IA Afrique de l\'Ouest, personal branding LinkedIn, mémoire de fin d\'études.']
    ];
    $stmtTm = $pdo->prepare("INSERT INTO timeline_items (year_fr, year_en, year_ar, badge_fr, badge_en, badge_ar, title_fr, title_en, title_ar, desc_fr, desc_en, desc_ar, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($timeline as $i => $t) {
        $stmtTm->execute([$t['year'], $t['year'], $t['year'], $t['badge'], $t['badge'], $t['badge'], $t['title'], $t['title'], $t['title'], $t['desc'], $t['desc'], $t['desc'], $i]);
    }

    // 3. SKILLS
    $skills = [
        ['group' => '💻 Développement Web', 'icon' => '💻', 'name' => 'HTML5 / CSS3 / JavaScript', 'perc' => 95],
        ['group' => '💻 Développement Web', 'icon' => '💻', 'name' => 'PHP & MySQL (PDO)', 'perc' => 90],
        ['group' => '💻 Développement Web', 'icon' => '💻', 'name' => 'Architecture MVC & SPA', 'perc' => 85],
        ['group' => '⚙️ Automatisation & IA', 'icon' => '⚙️', 'name' => 'n8n & Make (Integromat)', 'perc' => 95],
        ['group' => '⚙️ Automatisation & IA', 'icon' => '⚙️', 'name' => 'Prompt Engineering', 'perc' => 90],
        ['group' => '⚙️ Automatisation & IA', 'icon' => '⚙️', 'name' => 'Création d\'Agents Autonomes', 'perc' => 85],
        ['group' => '🔌 APIs & Intégrations', 'icon' => '🔌', 'name' => 'OpenAI API', 'perc' => 90],
        ['group' => '🔌 APIs & Intégrations', 'icon' => '🔌', 'name' => 'WhatsApp Business API', 'perc' => 85],
        ['group' => '🔌 APIs & Intégrations', 'icon' => '🔌', 'name' => 'Google Workspace (Sheets, Drive)', 'perc' => 85],
        ['group' => '🛠️ Outils & DevOps', 'icon' => '🛠️', 'name' => 'Git & GitHub', 'perc' => 80],
        ['group' => '🛠️ Outils & DevOps', 'icon' => '🛠️', 'name' => 'Netlify & Hébergement', 'perc' => 85],
        ['group' => '🛠️ Outils & DevOps', 'icon' => '🛠️', 'name' => 'Canva & UI Design', 'perc' => 90]
    ];
    $stmtSk = $pdo->prepare("INSERT INTO skills (group_name_fr, group_name_en, group_name_ar, group_icon, skill_name, percentage, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($skills as $i => $s) {
        $stmtSk->execute([$s['group'], $s['group'], $s['group'], $s['icon'], $s['name'], $s['perc'], $i]);
    }

    // 4. PROJETS
    $projects = [
        ['title' => 'Dibiterie Ameth Boll', 'cat' => 'Site E-commerce & Admin', 'desc' => 'Site complet avec commande WhatsApp, panel d\'administration, génération de tickets et architecture config.js.', 'img' => 'images/p1.webp'],
        ['title' => 'LuxeGold Jewelry', 'cat' => 'Plateforme E-commerce', 'desc' => 'Plateforme e-commerce haut de gamme pour une bijouterie. Interface élégante avec catalogue de produits.', 'img' => 'images/p2.webp'],
        ['title' => 'SDS_Shop Bot', 'cat' => 'Agent IA WhatsApp', 'desc' => 'Agent IA WhatsApp : réponses OpenAI, enregistrement Google Sheets, gestion des commandes 24/7.', 'img' => 'images/p3.webp'],
        ['title' => 'AfriEvent Design', 'cat' => 'Design & Event', 'desc' => 'Plateforme événementielle et affiches premium de mariage. Design HTML/CSS de luxe.', 'img' => 'images/p4.webp'],
        ['title' => 'School Management System', 'cat' => 'Application Web', 'desc' => 'Application de gestion des inscriptions étudiantes et paiements de scolarité. Projet de mémoire BTS Génie Logiciel.', 'img' => 'images/p5.webp']
    ];
    $stmtPr = $pdo->prepare("INSERT INTO projects (title_fr, title_en, title_ar, category_fr, category_en, category_ar, desc_fr, desc_en, desc_ar, main_image, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($projects as $i => $p) {
        $stmtPr->execute([$p['title'], $p['title'], $p['title'], $p['cat'], $p['cat'], $p['cat'], $p['desc'], $p['desc'], $p['desc'], $p['img'], $i]);
    }

    $pdo->commit();
    echo "Migration réussie ! Vous pouvez maintenant voir vos données dans le Dashboard.";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Erreur : " . $e->getMessage();
}
