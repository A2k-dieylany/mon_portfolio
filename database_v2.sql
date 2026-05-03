-- ================================================================
-- SDS ADMIN DASHBOARD — Script de migration V2
-- Base : portfolio_sds
-- Date : 03 Mai 2026
-- ================================================================

USE portfolio_sds;

-- ========================================
-- 🔐 AUTHENTIFICATION ADMIN
-- ========================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    role ENUM('superadmin', 'admin', 'editor') DEFAULT 'admin',
    last_login DATETIME NULL,
    login_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Compte admin par défaut
INSERT INTO admin_users (username, password_hash, display_name, email, role)
VALUES (
    'Dieylany',
    '$2y$10$KuEkPtBtH6AMek/dMEblM.Lhd5KkmTvl2RljCTFWp084O2lamKtAC',
    'Dieylany',
    'dieylanya2k@gmail.com',
    'superadmin'
);

-- ========================================
-- 🚀 PROJETS
-- ========================================
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    category ENUM('web', 'ai', 'design', 'mobile') NOT NULL,
    project_type VARCHAR(100) DEFAULT '',
    short_desc TEXT NOT NULL,
    long_desc TEXT NOT NULL,
    status ENUM('live', 'done', 'wip', 'private') DEFAULT 'done',
    live_url VARCHAR(500) DEFAULT '',
    github_url VARCHAR(500) DEFAULT '',
    sort_order INT DEFAULT 0,
    is_visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- 🖼️ IMAGES PROJETS (table dédiée)
-- ========================================
CREATE TABLE IF NOT EXISTS project_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) DEFAULT '',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- 🏷️ STACK TECHNIQUE PROJETS (table dédiée)
-- ========================================
CREATE TABLE IF NOT EXISTS project_stack (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    tag_name VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- ⚙️ SERVICES
-- ========================================
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    icon VARCHAR(10) NOT NULL,
    title_fr VARCHAR(200) NOT NULL,
    title_en VARCHAR(200) NOT NULL,
    title_ar VARCHAR(200) NOT NULL,
    desc_fr TEXT NOT NULL,
    desc_en TEXT NOT NULL,
    desc_ar TEXT NOT NULL,
    tags VARCHAR(200) DEFAULT '',
    sort_order INT DEFAULT 0,
    is_visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- 🧠 COMPÉTENCES
-- ========================================
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name_fr VARCHAR(100) NOT NULL,
    group_name_en VARCHAR(100) NOT NULL,
    group_name_ar VARCHAR(100) NOT NULL,
    group_icon VARCHAR(10) DEFAULT '',
    skill_name VARCHAR(100) NOT NULL,
    percentage INT NOT NULL,
    sort_order INT DEFAULT 0,
    is_visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- ✍️ BLOG / ARTICLES
-- ========================================
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emoji VARCHAR(10) DEFAULT '📝',
    category_fr VARCHAR(100) NOT NULL,
    category_en VARCHAR(100) NOT NULL,
    category_ar VARCHAR(100) NOT NULL,
    title_fr VARCHAR(300) NOT NULL,
    title_en VARCHAR(300) NOT NULL,
    title_ar VARCHAR(300) NOT NULL,
    excerpt_fr TEXT NOT NULL,
    excerpt_en TEXT NOT NULL,
    excerpt_ar TEXT NOT NULL,
    external_url VARCHAR(500) DEFAULT '',
    read_time VARCHAR(20) DEFAULT '3 min',
    publish_date DATE NOT NULL,
    sort_order INT DEFAULT 0,
    is_visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- ⭐ TÉMOIGNAGES
-- ========================================
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(100) NOT NULL,
    client_initials VARCHAR(5) NOT NULL,
    role_fr VARCHAR(200) NOT NULL,
    role_en VARCHAR(200) NOT NULL,
    role_ar VARCHAR(200) NOT NULL,
    text_fr TEXT NOT NULL,
    text_en TEXT NOT NULL,
    text_ar TEXT NOT NULL,
    stars INT DEFAULT 5,
    is_approved TINYINT(1) DEFAULT 0,
    is_visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- 📅 TIMELINE / PARCOURS
-- ========================================
CREATE TABLE IF NOT EXISTS timeline_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year_fr VARCHAR(50) NOT NULL,
    year_en VARCHAR(50) NOT NULL,
    year_ar VARCHAR(50) NOT NULL,
    title_fr VARCHAR(200) NOT NULL,
    title_en VARCHAR(200) NOT NULL,
    title_ar VARCHAR(200) NOT NULL,
    desc_fr TEXT NOT NULL,
    desc_en TEXT NOT NULL,
    desc_ar TEXT NOT NULL,
    badge_fr VARCHAR(100) NOT NULL,
    badge_en VARCHAR(100) NOT NULL,
    badge_ar VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    is_visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- 🎨 APPARENCE / CONFIGURATION SITE
-- ========================================
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type ENUM('text', 'color', 'number', 'json', 'boolean') DEFAULT 'text',
    category VARCHAR(50) DEFAULT 'general',
    label VARCHAR(200) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valeurs par défaut apparence
INSERT INTO site_settings (setting_key, setting_value, setting_type, category, label) VALUES
('accent_color', '#6C63FF', 'color', 'apparence', 'Couleur accent principale'),
('gold_color', '#D4AF37', 'color', 'apparence', 'Couleur dorée'),
('hero_badge_fr', 'Disponible pour des collaborations – Dakar, Sénégal', 'text', 'hero', 'Badge Hero (FR)'),
('hero_badge_en', 'Open for collaborations – Dakar, Senegal', 'text', 'hero', 'Badge Hero (EN)'),
('hero_badge_ar', 'متاح للتعاون – داكار، السنغال', 'text', 'hero', 'Badge Hero (AR)'),
('site_name', 'SEN DIGITAL SOLUTION', 'text', 'general', 'Nom du site'),
('logo_text', 'A2K', 'text', 'general', 'Texte du logo'),
('default_theme', 'dark', 'text', 'apparence', 'Thème par défaut'),
('notification_email', 'dieylanya2k@gmail.com', 'text', 'general', 'Email de notification'),
('groq_api_key', 'VOTRE_CLE_GROQ_ICI', 'text', 'api', 'Clé API Groq');

-- ========================================
-- 🤖 CHATBOT LOGS
-- ========================================
CREATE TABLE IF NOT EXISTS chatbot_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    user_message TEXT NOT NULL,
    bot_response TEXT NOT NULL,
    language VARCHAR(5) DEFAULT 'fr',
    ip_hash VARCHAR(64) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- 👁️ PAGE VIEWS (Analytics détaillé)
-- ========================================
CREATE TABLE IF NOT EXISTS page_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    page VARCHAR(100) DEFAULT 'home',
    referrer VARCHAR(500) DEFAULT '',
    device ENUM('mobile', 'desktop', 'tablet') DEFAULT 'desktop',
    country VARCHAR(50) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (created_at),
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- 💬 MISE À JOUR TABLE MESSAGES (ajout statut)
-- ========================================
ALTER TABLE messages
    ADD COLUMN IF NOT EXISTS status ENUM('unread', 'read', 'replied', 'archived') DEFAULT 'unread' AFTER message,
    ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT '' AFTER status,
    ADD COLUMN IF NOT EXISTS replied_at DATETIME NULL AFTER notes;
