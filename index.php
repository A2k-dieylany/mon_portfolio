<?php
require_once __DIR__ . '/admin/includes/db.php';
$pdo = getDB();

// Récupérer toutes les données publiques
$projects = $pdo->query("SELECT * FROM projects WHERE is_visible = 1 ORDER BY sort_order ASC, id DESC")->fetchAll();
// Fetch galleries for all visible projects in a single query to avoid N+1 problem
$projectIds = array_column($projects, 'id');
$galleriesByProject = [];

if (!empty($projectIds)) {
    $in = str_repeat('?,', count($projectIds) - 1) . '?';
    $imgStmt = $pdo->prepare("SELECT * FROM project_images WHERE project_id IN ($in) ORDER BY sort_order ASC");
    $imgStmt->execute($projectIds);
    $allImages = $imgStmt->fetchAll();
    
    foreach ($allImages as $img) {
        $galleriesByProject[$img['project_id']][] = $img;
    }
}

foreach ($projects as &$p) {
    $p['gallery'] = $galleriesByProject[$p['id']] ?? [];
}
unset($p);

// On évite SELECT * : les colonnes detail_fr/en/ar pèsent ~90 Ko et ne servent
// que sur les pages /services/<slug>. Ici on n'a besoin que d'un booléen.
$services = $pdo->query(
    "SELECT id, icon, title_fr, title_en, title_ar, desc_fr, desc_en, desc_ar, tags, slug,
            (detail_fr IS NOT NULL AND detail_fr <> '') AS has_detail
     FROM services WHERE is_visible = 1 ORDER BY sort_order ASC, id ASC"
)->fetchAll();
$skills = $pdo->query("SELECT * FROM skills WHERE is_visible = 1 ORDER BY group_name_fr ASC, sort_order ASC, id ASC")->fetchAll();
$timeline = $pdo->query("SELECT * FROM timeline_items WHERE is_visible = 1 ORDER BY sort_order ASC, id DESC")->fetchAll();
$blog_posts = $pdo->query("SELECT * FROM blog_posts WHERE is_visible = 1 ORDER BY sort_order ASC, publish_date DESC")->fetchAll();
$testimonials = $pdo->query("SELECT * FROM testimonials WHERE is_visible = 1 AND is_approved = 1 ORDER BY created_at DESC")->fetchAll();

// Fetch settings
$settingsData = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
$settings = [];
foreach($settingsData as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Dieylany Khouma — Développeur Web &amp; Automatisation IA à Dakar | <?= htmlspecialchars($settings['site_name'] ?? 'SEN DIGITAL SOLUTION') ?></title>
  <meta name="description"
    content="Développeur full-stack à Dakar : création de sites web, e-commerce et automatisation WhatsApp avec l'IA pour les entreprises sénégalaises. Diagnostic gratuit." />
  <meta name="keywords" content="développeur web Dakar, création site internet Sénégal, automatisation WhatsApp, agent IA, chatbot WhatsApp Sénégal, e-commerce Dakar" />
  <meta property="og:title" content="Dieylany Khouma — Développeur Web &amp; Automatisation IA à Dakar" />
  <meta property="og:description"
    content="Création de sites web, e-commerce et automatisation WhatsApp avec l'IA pour les entreprises sénégalaises. Diagnostic gratuit." />
  <meta property="og:locale" content="fr_SN" />
  <meta property="og:site_name" content="<?= htmlspecialchars($settings['site_name'] ?? 'SEN DIGITAL SOLUTION') ?>" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="Dieylany Khouma — Développeur Web &amp; Automatisation IA à Dakar" />
  <meta name="twitter:description" content="Création de sites web, e-commerce et automatisation WhatsApp avec l'IA pour les entreprises sénégalaises." />
  <meta name="twitter:image" content="https://dieylany.dev/img/projects/luxe1.jpg" />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://dieylany.dev/" />
  <meta property="og:image" content="https://dieylany.dev/img/projects/luxe1.jpg" />
  <meta name="theme-color" content="#0A0A0F" />
  <meta name="google-site-verification" content="usAYIO1JnU6AhHftvc9i42hBIOAQEcBj7efoATT9VyU" />
  <link rel="icon" type="image/svg+xml" href="favicon.svg" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
    
  <!-- SEO JSON-LD : la personne, l'entreprise locale et les services proposés -->
  <script type="application/ld+json">
  <?php
  $siteNameLd = $settings['site_name'] ?? 'SEN DIGITAL SOLUTION';
  $serviceOffers = [];
  foreach ($services as $sv) {
      $offer = [
          '@type' => 'Offer',
          'itemOffered' => [
              '@type' => 'Service',
              'name' => $sv['title_fr'],
              'description' => mb_substr(strip_tags($sv['desc_fr']), 0, 200),
          ],
      ];
      if (!empty($sv['slug']) && !empty($sv['has_detail'])) {
          $offer['url'] = 'https://dieylany.dev/services/' . $sv['slug'];
      }
      $serviceOffers[] = $offer;
  }

  echo json_encode([
      '@context' => 'https://schema.org',
      '@graph' => [
          [
              '@type' => 'Person',
              '@id' => 'https://dieylany.dev/#person',
              'name' => 'Dieylany Khouma',
              'jobTitle' => 'Développeur Full-Stack & Spécialiste Automatisation IA',
              'url' => 'https://dieylany.dev/',
              'sameAs' => [
                  'https://github.com/A2k-dieylany',
                  'https://linkedin.com/in/dieylany-khouma',
              ],
              'address' => [
                  '@type' => 'PostalAddress',
                  'addressLocality' => 'Dakar',
                  'addressCountry' => 'SN',
              ],
              'knowsLanguage' => ['fr', 'en', 'ar', 'wo'],
              'worksFor' => ['@id' => 'https://dieylany.dev/#organization'],
          ],
          [
              '@type' => ['ProfessionalService', 'LocalBusiness'],
              '@id' => 'https://dieylany.dev/#organization',
              'name' => $siteNameLd,
              'url' => 'https://dieylany.dev/',
              'description' => "Développement web, e-commerce et automatisation IA (WhatsApp Business API, n8n, OpenAI) pour les entreprises sénégalaises.",
              'founder' => ['@id' => 'https://dieylany.dev/#person'],
              'telephone' => '+221780152522',
              'email' => 'sendigitalsolution@gmail.com',
              'address' => [
                  '@type' => 'PostalAddress',
                  'addressLocality' => 'Dakar',
                  'addressCountry' => 'SN',
              ],
              'areaServed' => [
                  ['@type' => 'City', 'name' => 'Dakar'],
                  ['@type' => 'Country', 'name' => 'Sénégal'],
              ],
              'availableLanguage' => ['Français', 'English', 'العربية', 'Wolof'],
              'sameAs' => [
                  'https://share.google/sP6xAYZoLLQ8kAKZa',
                  'https://github.com/A2k-dieylany',
                  'https://linkedin.com/in/dieylany-khouma',
              ],
              'hasOfferCatalog' => [
                  '@type' => 'OfferCatalog',
                  'name' => 'Services',
                  'itemListElement' => $serviceOffers,
              ],
          ],
      ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  ?>
  </script>

  <link rel="stylesheet" href="<?= sds_asset('style.css') ?>">
  <link rel="manifest" href="manifest.json">
  <link rel="canonical" href="https://dieylany.dev/" />
  <style>
    :root {
      --accent: <?= htmlspecialchars($settings['accent_color'] ?? '#6C63FF') ?>;
      --gold: <?= htmlspecialchars($settings['gold_color'] ?? '#D4AF37') ?>;
    }
  </style>
</head>

<body>
  <div id="preloader">
    <div class="preloader-content">
      <div class="preloader-logo"><?= htmlspecialchars($settings['logo_text'] ?? 'A2K') ?><span></span></div>
      <div class="preloader-bar">
        <div class="preloader-fill"></div>
      </div>
    </div>
  </div>
  <div id="progress-bar"></div>

  <div id="cursor"></div>
  <div id="cursor-ring"></div>
  <canvas id="particles"></canvas>
  <a href="https://wa.me/221780152522" class="wa-float" title="WhatsApp">
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="#fff" viewBox="0 0 16 16">
      <path
        d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z" />
    </svg>
  </a>

  <main>

  <!-- NAV -->
  <nav>
    <div class="nav-logo"><?= htmlspecialchars($settings['logo_text'] ?? 'A2K') ?><span>.</span></div>
    <ul class="nav-links" id="nav-links">
      <li><a href="#about" data-i18n="nav.about">À propos</a></li>
      <li><a href="#timeline" data-i18n="nav.timeline">Parcours</a></li>
      <li><a href="#services" data-i18n="nav.services">Services</a></li>
      <li><a href="#projects" data-i18n="nav.projects">Projets</a></li>
      <li><a href="#blog" data-i18n="nav.blog">Blog</a></li>
      <li><a href="#contact" data-i18n="nav.contact">Contact</a></li>
    </ul>
    <div class="lang-switcher">
      <button class="lang-btn" id="theme-toggle" title="Thème Clair/Sombre" aria-label="Basculer entre le thème clair et sombre"><span aria-hidden="true">🌓</span></button>
      <button class="lang-btn active" onclick="setLang('fr')" lang="fr" aria-label="Afficher le site en français">FR</button>
      <button class="lang-btn" onclick="setLang('en')" lang="en" aria-label="Display the site in English">EN</button>
      <button class="lang-btn" onclick="setLang('ar')" lang="ar" aria-label="عرض الموقع بالعربية">AR</button>
    </div>
    <button class="menu-toggle" id="menu-toggle" aria-label="Ouvrir le menu de navigation" aria-expanded="false" aria-controls="nav-links" title="Menu"><span aria-hidden="true">☰</span></button>
  </nav>

  <!-- HERO -->
  <section id="hero">
    <div class="hero-glow"></div>
    <div class="grid-bg"></div>
    <div class="shape s1"></div>
    <div class="shape s2"></div>
    <div class="hero-content">
      <div class="badge"><span class="dot-live"></span><span class="dynamic-i18n"
           data-fr="<?= htmlspecialchars($settings['hero_badge_fr'] ?? 'Disponible') ?>"
           data-en="<?= htmlspecialchars($settings['hero_badge_en'] ?? 'Available') ?>"
           data-ar="<?= htmlspecialchars($settings['hero_badge_ar'] ?? 'متاح') ?>"><?= htmlspecialchars($settings['hero_badge_fr'] ?? 'Disponible') ?></span></div>
      <h1><span data-i18n="hero.h1">Bonjour, je suis</span><br><span class="name">Dieylany</span></h1>
      <p class="hero-sub"><strong data-i18n="hero.agency"><?= htmlspecialchars($settings['site_name'] ?? 'SEN DIGITAL SOLUTION') ?></strong> &nbsp;·&nbsp; <span
          class="typewriter" id="tw"></span></p>
      <div class="hero-cta">
        <a href="#projects" class="btn-primary" data-i18n="hero.btn1">Voir mes projets</a>
        <a href="#contact" class="btn-outline" data-i18n="hero.btn2">Me contacter</a>
        <a href="cv_download.php" class="btn-cv" id="btn-cv" data-i18n="hero.cv">📄 Télécharger CV</a>
      </div>
      <div class="hero-stats">
        <div class="stat">
          <div class="stat-num" data-target="13">0</div>
          <div class="stat-label" data-i18n="stat.projects">Projets livrés</div>
        </div>
        <div class="stat">
          <div class="stat-num" data-target="2">0</div>
          <div class="stat-label" data-i18n="stat.years">Ans d'expérience</div>
        </div>
        <div class="stat">
          <div class="stat-num" data-target="1">0</div>
          <div class="stat-label" data-i18n="stat.agency">Agence fondée</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ABOUT -->
  <section id="about">
    <div class="container">
      <div class="reveal">
        <p class="section-tag" data-i18n="about.tag">Qui suis-je ?</p>
        <h2 class="section-title" data-i18n="about.title">Passionné de tech, ancré dans l'Afrique</h2>
        <div class="divider"></div>
      </div>
      <div class="about-grid">
        <div class="about-text reveal from-left">
          <p data-i18n="about.p1">Je suis étudiant en <strong>Génie Logiciel</strong> à l'Institut Supérieur
            d'Informatique (ISI Suptech) de Dakar et
            fondateur de <strong><?= htmlspecialchars($settings['site_name'] ?? 'SEN DIGITAL SOLUTION') ?> (SDS)</strong> — une agence digitale que je construis avec la
            vision de
            devenir le leader tech de l'Afrique de l'Ouest.</p>
          <p data-i18n="about.p2">Ma mission : intégrer l'<strong>intelligence artificielle</strong> et l'automatisation
            dans les workflows des entreprises africaines pour les rendre plus compétitives à l'échelle mondiale.</p>
          <p data-i18n="about.p3">Je combine développement web, automatisation no-code/low-code (<strong>n8n,
              Make</strong>), et intégration d'APIs IA pour livrer des solutions concrètes et mesurables.</p>
          <p data-i18n="about.p4">Au-delà du code, je construis ma <strong>marque personnelle</strong> — conférencier en
            devenir, laureát du Concours Général Sénégalais 2022.</p>
        </div>
        <div class="reveal from-right d1">
          <div class="award-card">
            <div class="award-icon">🏆</div>
            <div>
              <div class="award-title" data-i18n="award1.title">Lauréat – Concours Général Sénégalais 2022</div>
              <div class="award-desc" data-i18n="award1.desc">2ème Prix · Langue Arabe · Distinction nationale</div>
            </div>
          </div>
          <div class="award-card">
            <div class="award-icon">🤖</div>
            <div>
              <div class="award-title" data-i18n="award2.title">Expert IA & Automatisation</div>
              <div class="award-desc" data-i18n="award2.desc">n8n · Make · OpenAI API · WhatsApp Business API</div>
            </div>
          </div>
          <div class="award-card">
            <div class="award-icon">🌍</div>
            <div>
              <div class="award-title" data-i18n="award3.title">Vision Pan-Africaine</div>
              <div class="award-desc" data-i18n="award3.desc">Fondateur <?= htmlspecialchars($settings['site_name'] ?? 'SEN DIGITAL SOLUTION') ?> · Dakar, Sénégal</div>
            </div>
          </div>
          <div class="award-card">
            <div class="award-icon">🎙️</div>
            <div>
              <div class="award-title" data-i18n="award4.title">Speaker en construction</div>
              <div class="award-desc" data-i18n="award4.desc">Personal branding · LinkedIn · Prise de parole</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TIMELINE -->
  <section id="timeline">
    <div class="container">
      <div class="reveal">
        <p class="section-tag" data-i18n="tl.tag">Mon parcours</p>
        <h2 class="section-title" data-i18n="tl.title">De l'excellence académique à l'entrepreneuriat</h2>
        <div class="divider"></div>
      </div>
      <div class="tl-wrap">
        <div class="tl-line"></div>
        <?php foreach ($timeline as $idx => $t): ?>
        <div class="tl-item reveal d<?= ($idx % 2) + 1 ?>">
          <div class="tl-dot"></div>
          <div class="tl-card">
            <div class="tl-year dynamic-i18n" data-fr="<?= htmlspecialchars($t['year_fr']) ?>" data-en="<?= htmlspecialchars($t['year_en'] ?: $t['year_fr']) ?>" data-ar="<?= htmlspecialchars($t['year_ar'] ?: $t['year_fr']) ?>">
              <?= htmlspecialchars($t['year_fr']) ?>
            </div>
            <div class="tl-title dynamic-i18n" data-fr="<?= htmlspecialchars($t['title_fr']) ?>" data-en="<?= htmlspecialchars($t['title_en'] ?: $t['title_fr']) ?>" data-ar="<?= htmlspecialchars($t['title_ar'] ?: $t['title_fr']) ?>">
              <?= htmlspecialchars($t['title_fr']) ?>
            </div>
            <div class="tl-desc dynamic-i18n" data-fr="<?= htmlspecialchars($t['desc_fr']) ?>" data-en="<?= htmlspecialchars($t['desc_en'] ?: $t['desc_fr']) ?>" data-ar="<?= htmlspecialchars($t['desc_ar'] ?: $t['desc_fr']) ?>">
              <?= nl2br(htmlspecialchars($t['desc_fr'])) ?>
            </div>
            <span class="tl-badge dynamic-i18n" data-fr="<?= htmlspecialchars($t['badge_fr']) ?>" data-en="<?= htmlspecialchars($t['badge_en'] ?: $t['badge_fr']) ?>" data-ar="<?= htmlspecialchars($t['badge_ar'] ?: $t['badge_fr']) ?>">
              <?= htmlspecialchars($t['badge_fr']) ?>
            </span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- SERVICES -->
  <section id="services" style="background:var(--dark2);">
    <div class="container">
      <div class="reveal">
        <p class="section-tag" data-i18n="svc.tag">Ce que je fais</p>
        <h2 class="section-title">Services <?= htmlspecialchars($settings['site_name'] ?? 'SEN DIGITAL SOLUTION') ?></h2>
        <div class="divider"></div>
        <p class="section-sub" data-i18n="svc.sub">Des solutions digitales pensées pour les entreprises africaines qui
          veulent passer à la vitesse supérieure.</p>
      </div>
      <div class="services-grid">
        <?php foreach ($services as $idx => $s): ?>
        <div class="service-card reveal d<?= ($idx % 3) + 1 ?>">
          <div class="service-icon"><?= htmlspecialchars($s['icon']) ?></div>
          <div class="service-title dynamic-i18n" data-fr="<?= htmlspecialchars($s['title_fr']) ?>" data-en="<?= htmlspecialchars($s['title_en'] ?: $s['title_fr']) ?>" data-ar="<?= htmlspecialchars($s['title_ar'] ?: $s['title_fr']) ?>">
            <?= htmlspecialchars($s['title_fr']) ?>
          </div>
          <div class="service-desc dynamic-i18n" data-fr="<?= htmlspecialchars($s['desc_fr']) ?>" data-en="<?= htmlspecialchars($s['desc_en'] ?: $s['desc_fr']) ?>" data-ar="<?= htmlspecialchars($s['desc_ar'] ?: $s['desc_fr']) ?>">
            <?= htmlspecialchars($s['desc_fr']) ?>
          </div>
          <?php if (!empty($s['tags'])): ?>
          <span class="service-tag"><?= htmlspecialchars($s['tags']) ?></span>
          <?php endif; ?>
          <?php if (!empty($s['slug']) && !empty($s['has_detail'])): ?>
          <a href="/services/<?= htmlspecialchars($s['slug']) ?>" class="service-more">En savoir plus →</a>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- PROJECTS -->
  <section id="projects">
    <div class="container">
      <div class="reveal">
        <p class="section-tag" data-i18n="proj.tag">Réalisations</p>
        <h2 class="section-title" data-i18n="proj.title">Projets & Travaux</h2>
        <div class="divider"></div>
        <div class="project-filters" role="group" aria-label="Filtrer les projets par catégorie">
          <button class="filter-btn active" data-filter="all" aria-pressed="true">Tous</button>
          <button class="filter-btn" data-filter="web" aria-pressed="false">Web</button>
          <button class="filter-btn" data-filter="ai" aria-pressed="false">IA & Bot</button>
          <button class="filter-btn" data-filter="design" aria-pressed="false">Design</button>
        </div>
        <!-- Annonce le résultat du filtrage aux lecteurs d'écran -->
        <p id="filter-status" class="sr-only" role="status" aria-live="polite"></p>
      </div>
      <!-- CARD 1 -->
          <div class="projects-grid">
      <?php foreach ($projects as $idx => $p): 
          $cat = strtolower($p['category_fr']);
          $filterCat = 'all';
          if(strpos($cat, 'web') !== false || strpos($cat, 'site') !== false || strpos($cat, 'plateforme') !== false) $filterCat = 'web';
          elseif(strpos($cat, 'ia') !== false || strpos($cat, 'bot') !== false || strpos($cat, 'agent') !== false) $filterCat = 'ai';
          elseif(strpos($cat, 'design') !== false) $filterCat = 'design';
          
          $images = [];
          if(!empty($p['main_image'])) $images[] = htmlspecialchars($p['main_image']);
          if(!empty($p['gallery'])) {
              foreach($p['gallery'] as $g) {
                  $images[] = htmlspecialchars($g['image_url']);
              }
          }
          $imgString = implode(',', $images);
          $coverImg = !empty($images) ? $images[0] : 'data:image/svg+xml;utf8,<svg xmlns=\\\'http://www.w3.org/2000/svg\\\' width=\\\'400\\\' height=\\\'250\\\'><rect width=\\\'400\\\' height=\\\'250\\\' fill=\\\'%231a1a24\\\'/></svg>';
          
          $tags = !empty($p['tags']) ? explode(',', $p['tags']) : [];
      ?>
      <div class="project-card reveal d<?= ($idx % 3) + 1 ?>" data-category="<?= $filterCat ?>"
        data-live="<?= htmlspecialchars($p['live_url']) ?>"
        data-github="<?= htmlspecialchars($p['github_url']) ?>"
        data-images="<?= $imgString ?>"
        data-long-desc-fr="<?= htmlspecialchars(nl2br($p['desc_fr'])) ?>"
        data-long-desc-en="<?= htmlspecialchars(nl2br($p['desc_en'] ?: $p['desc_fr'])) ?>"
        data-long-desc-ar="<?= htmlspecialchars(nl2br($p['desc_ar'] ?: $p['desc_fr'])) ?>">
        <div class="project-shine"></div>
        <div class="project-cover">
          <img loading="lazy" src="<?= $coverImg ?>" alt="<?= htmlspecialchars($p['title_fr']) ?>" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\\\'http://www.w3.org/2000/svg\\\' width=\\\'400\\\' height=\\\'250\\\'><rect width=\\\'400\\\' height=\\\'250\\\' fill=\\\'%231a1a24\\\'/><text x=\\\'50%\\\' y=\\\'50%\\\' fill=\\\'%23555\\\' font-family=\\\'sans-serif\\\' font-size=\\\'14\\\' text-anchor=\\\'middle\\\' dominant-baseline=\\\'middle\\\'>Image non dispo</text></svg>'">
        </div>
        <div class="project-header">
          <div>
            <div class="project-type dynamic-i18n" data-fr="<?= htmlspecialchars($p['category_fr']) ?>" data-en="<?= htmlspecialchars($p['category_en'] ?: $p['category_fr']) ?>" data-ar="<?= htmlspecialchars($p['category_ar'] ?: $p['category_fr']) ?>"><?= htmlspecialchars($p['category_fr']) ?></div>
            <div class="project-name dynamic-i18n" data-fr="<?= htmlspecialchars($p['title_fr']) ?>" data-en="<?= htmlspecialchars($p['title_en'] ?: $p['title_fr']) ?>" data-ar="<?= htmlspecialchars($p['title_ar'] ?: $p['title_fr']) ?>"><?= htmlspecialchars($p['title_fr']) ?></div>
          </div>
          <?php if(!empty($p['live_url'])): ?>
          <span class="project-status status-live dynamic-i18n" data-fr="Live" data-en="Live" data-ar="مباشر">Live</span>
          <?php elseif(!empty($p['github_url'])): ?>
          <span class="project-status status-code dynamic-i18n" data-fr="Code dispo" data-en="Source available" data-ar="الكود متاح">Code dispo</span>
          <?php else: ?>
          <span class="project-status status-build dynamic-i18n" data-fr="Personnel" data-en="Personal" data-ar="شخصي">Personnel</span>
          <?php endif; ?>
        </div>
        <div class="project-body">
          <div class="project-desc dynamic-i18n" data-fr="<?= htmlspecialchars($p['desc_fr']) ?>" data-en="<?= htmlspecialchars($p['desc_en'] ?: $p['desc_fr']) ?>" data-ar="<?= htmlspecialchars($p['desc_ar'] ?: $p['desc_fr']) ?>"><?= htmlspecialchars($p['desc_fr']) ?></div>
          <div class="project-stack">
            <?php foreach($tags as $tag): ?>
            <?php if(trim($tag) !== ''): ?>
            <span class="stack-tag"><?= htmlspecialchars(trim($tag)) ?></span>
            <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <?php if (!empty($p['live_url']) || !empty($p['github_url'])): ?>
          <!-- Vrais liens côté serveur : visibles par le visiteur ET crawlables par Google -->
          <div class="project-links">
            <?php if (!empty($p['live_url'])): ?>
            <a href="<?= htmlspecialchars($p['live_url']) ?>" class="project-link" target="_blank" rel="noopener"
               onclick="event.stopPropagation()"
               aria-label="Voir la démo en ligne de <?= htmlspecialchars($p['title_fr']) ?>">
              <span aria-hidden="true">🔗</span>
              <span class="dynamic-i18n" data-fr="Voir le site" data-en="View site" data-ar="زيارة الموقع">Voir le site</span>
            </a>
            <?php endif; ?>
            <?php if (!empty($p['github_url'])): ?>
            <a href="<?= htmlspecialchars($p['github_url']) ?>" class="project-link" target="_blank" rel="noopener"
               onclick="event.stopPropagation()"
               aria-label="Voir le code de <?= htmlspecialchars($p['title_fr']) ?> sur GitHub">
              <svg class="project-link-icon" viewBox="0 0 16 16" width="14" height="14" fill="currentColor" aria-hidden="true">
                <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8z"/>
              </svg>
              <span>GitHub</span>
            </a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      </div> <!-- /.projects-grid -->
    </div> <!-- /.container -->
  </section>

  <!-- SKILLS -->
  <section id="skills" style="background:var(--dark2);">
    <div class="container">
      <div class="reveal">
        <p class="section-tag" data-i18n="sk.tag">Compétences</p>
        <h2 class="section-title" data-i18n="sk.title">Stack Technique</h2>
        <div class="divider"></div>
      </div>
      <?php
      $groupedSkills = [];
      foreach($skills as $sk) {
          $groupedSkills[$sk['group_name_fr']][] = $sk;
      }
      $skIdx = 0;
      ?>
      <div class="skills-grid">
        <?php foreach($groupedSkills as $gfr => $catSkills): ?>
        <?php
        $gen  = $catSkills[0]['group_name_en'] ?: $gfr;
        $gar  = $catSkills[0]['group_name_ar'] ?: $gfr;
        $icon = $catSkills[0]['group_icon'] ?? '';
        ?>
        <div class="skill-group reveal d<?= ($skIdx++ % 4) + 1 ?>">
          <div class="skill-group-title dynamic-i18n"
               data-fr="<?= htmlspecialchars($icon.' '.$gfr) ?>"
               data-en="<?= htmlspecialchars($icon.' '.$gen) ?>"
               data-ar="<?= htmlspecialchars($icon.' '.$gar) ?>">
            <?= htmlspecialchars($icon.' '.$gfr) ?>
          </div>
          <?php foreach($catSkills as $sk): ?>
          <div class="skill-row">
            <div class="skill-name">
              <span><?= htmlspecialchars($sk['skill_name']) ?></span>
              <span class="skill-pct"><?= intval($sk['percentage']) ?>%</span>
            </div>
            <div class="skill-bar">
              <div class="skill-fill" data-w="<?= intval($sk['percentage']) ?>"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- BLOG -->
  <section id="blog">
    <div class="container">
      <div class="reveal">
        <p class="section-tag" data-i18n="blog.tag">Réflexions & Articles</p>
        <h2 class="section-title" data-i18n="blog.title">Blog & Publications LinkedIn</h2>
        <div class="divider"></div>
        <p class="section-sub" data-i18n="blog.sub">Je partage mes réflexions sur l'IA, la tech africaine et l'entrepreneuriat digital.</p>
      </div>
      <div class="blog-grid">
        <?php $bIdx = 0; foreach($blog_posts as $b): ?>
        <div class="blog-card reveal d<?= ($bIdx++ % 3) + 1 ?>">
          <div class="blog-top">
            <span class="blog-emoji"><?= htmlspecialchars($b['emoji']) ?></span>
            <div class="blog-cat dynamic-i18n"
                 data-fr="<?= htmlspecialchars($b['category_fr']) ?>"
                 data-en="<?= htmlspecialchars($b['category_en'] ?: $b['category_fr']) ?>"
                 data-ar="<?= htmlspecialchars($b['category_ar'] ?: $b['category_fr']) ?>">
              <?= htmlspecialchars($b['category_fr']) ?>
            </div>
            <div class="blog-title dynamic-i18n"
                 data-fr="<?= htmlspecialchars($b['title_fr']) ?>"
                 data-en="<?= htmlspecialchars($b['title_en'] ?: $b['title_fr']) ?>"
                 data-ar="<?= htmlspecialchars($b['title_ar'] ?: $b['title_fr']) ?>">
              <?= htmlspecialchars($b['title_fr']) ?>
            </div>
            <div class="blog-excerpt dynamic-i18n"
                 data-fr="<?= htmlspecialchars($b['excerpt_fr']) ?>"
                 data-en="<?= htmlspecialchars($b['excerpt_en'] ?: $b['excerpt_fr']) ?>"
                 data-ar="<?= htmlspecialchars($b['excerpt_ar'] ?: $b['excerpt_fr']) ?>">
              <?= nl2br(htmlspecialchars($b['excerpt_fr'])) ?>
            </div>
          </div>
          <div class="blog-footer">
            <span class="blog-date"><?= date('M Y', strtotime($b['publish_date'])) ?> · <?= htmlspecialchars($b['read_time']) ?></span>
            <?php if(!empty($b['external_url'])): ?>
            <a href="<?= htmlspecialchars($b['external_url']) ?>" target="_blank" rel="noopener" class="blog-link" data-i18n="blink">Voir sur LinkedIn →</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- TESTIMONIALS -->
  <section id="testimonials" style="background:var(--dark2);">
    <div class="container">
      <div class="reveal">
        <p class="section-tag" data-i18n="testi.tag">Ils me font confiance</p>
        <h2 class="section-title" data-i18n="testi.title">Témoignages Clients</h2>
        <div class="divider"></div>
        <p class="section-sub" data-i18n="testi.sub">Ce que disent mes clients et partenaires sur la qualité de mon
          travail.</p>
      </div>
      <div class="testimonials-slider">
        <div class="testi-track" id="testi-track">
          <?php $tIdx = 0; foreach($testimonials as $t): ?>
          <div class="testi-card reveal d<?= ($tIdx++ % 3) + 1 ?>">
            <div class="testi-stars"><?= str_repeat('★', intval($t['stars'])) ?></div>
            <p class="testi-text dynamic-i18n"
               data-fr="<?= htmlspecialchars($t['text_fr']) ?>"
               data-en="<?= htmlspecialchars($t['text_en'] ?: $t['text_fr']) ?>"
               data-ar="<?= htmlspecialchars($t['text_ar'] ?: $t['text_fr']) ?>">
              "<?= nl2br(htmlspecialchars($t['text_fr'])) ?>"
            </p>
            <div class="testi-author">
              <div class="testi-avatar"><?= htmlspecialchars($t['client_initials']) ?></div>
              <div>
                <div class="testi-name"><?= htmlspecialchars($t['client_name']) ?></div>
                <div class="testi-role dynamic-i18n"
                     data-fr="<?= htmlspecialchars($t['role_fr']) ?>"
                     data-en="<?= htmlspecialchars($t['role_en'] ?: $t['role_fr']) ?>"
                     data-ar="<?= htmlspecialchars($t['role_ar'] ?: $t['role_fr']) ?>">
                  <?= htmlspecialchars($t['role_fr']) ?>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="testi-nav">
          <button class="testi-prev" id="testi-prev" aria-label="Précédent">‹</button>
          <div class="testi-dots" id="testi-dots"></div>
          <button class="testi-next" id="testi-next" aria-label="Suivant">›</button>
        </div>
      </div>
    </div>
  </section>

  <!-- CONTACT -->
  <section id="contact">
    <div class="container">
      <div class="reveal">
        <p class="section-tag" data-i18n="ct.tag">Contact</p>
        <h2 class="section-title" data-i18n="ct.title">Travaillons ensemble</h2>
        <div class="divider"></div>
        <p class="section-sub" data-i18n="ct.sub">Un projet digital, une automatisation, un site web ? Écrivez-moi — je
          réponds toujours.</p>
      </div>
      <div class="contact-grid">
        <div class="reveal from-left d1">
          <div class="contact-item">
            <div class="contact-icon">📍</div>
            <div>
              <div class="contact-label" data-i18n="ci1l">Localisation</div>
              <div class="contact-value">Dakar, Sénégal</div>
            </div>
          </div>
          <div class="contact-item">
            <div class="contact-icon">💼</div>
            <div>
              <div class="contact-label" data-i18n="ci2l">Agence</div>
              <div class="contact-value"><?= htmlspecialchars($settings['site_name'] ?? 'SEN DIGITAL SOLUTION') ?></div>
            </div>
          </div>
          <div class="contact-item">
            <div class="contact-icon">🌐</div>
            <div>
              <div class="contact-label" data-i18n="ci3l">Disponibilité</div>
              <div class="contact-value" data-i18n="ci3v">Projets locaux & internationaux</div>
            </div>
          </div>
          <div class="contact-item">
            <div class="contact-icon">💬</div>
            <div>
              <div class="contact-label">LinkedIn</div>
              <div class="contact-value">@Dieylany</div>
            </div>
          </div>
        </div>
        <div class="reveal from-right d2">
          <form id="contact-form">
            <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off" />
            <input class="form-input" type="text" id="name" name="name" data-i18n-placeholder="form.name"
              placeholder="Votre nom" required />
            <input class="form-input" type="email" id="email" name="email" placeholder="Email" required />
            <input class="form-input" type="text" id="subject" name="subject" data-i18n-placeholder="form.subject"
              placeholder="Sujet du projet" required />
            <textarea class="form-input" id="message" name="message" data-i18n-placeholder="form.msg"
              placeholder="Décrivez votre projet…" required></textarea>
            <button type="submit" class="btn-primary" style="width:100%;padding:.95rem;" data-i18n="form.send">Envoyer
              le message →</button>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- CHATBOT IA -->
  <div id="chatbot-container">
    <div id="chatbot-notification" class="chatbot-notif hidden">
      <span>👋 Besoin d'aide ? Discutons !</span>
      <button class="notif-close" aria-label="Fermer">✕</button>
    </div>
    <button id="chatbot-toggle" title="Discuter avec mon assistant IA" aria-label="Ouvrir le chat avec MAX, l'assistant IA">
      <span class="chatbot-pulse"></span>
      <img loading="lazy" src="img/max.jpg" class="chatbot-toggle-img" alt="MAX AI" onerror="this.outerHTML='<span style=\'font-size:1.6rem;font-weight:bold;color:var(--gold);z-index:1;position:relative;\'>M</span>'">
      <div class="chatbot-online-badge"></div>
      <span id="chat-unread" class="chat-unread-badge hidden">1</span>
    </button>
    <div id="chatbot-window" class="hidden">
      <div class="chatbot-header">
        <div class="chatbot-header-info">
          <div class="chatbot-header-avatar">
            <img loading="lazy" src="img/max.jpg" alt="MAX" onerror="this.outerHTML='<span>M</span>'">
            <span class="header-status-dot"></span>
          </div>
          <div>
            <strong>MAX</strong>
            <div class="chatbot-status"><span class="status-dot"></span> <span data-i18n="chat.online">En ligne</span></div>
          </div>
        </div>
        <div class="chatbot-header-actions">
          <button id="chatbot-clear" title="Nouvelle conversation" aria-label="Effacer la conversation">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14"/></svg>
          </button>
          <button id="chatbot-minimize" title="Réduire" aria-label="Réduire">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14"/></svg>
          </button>
          <button id="chatbot-close" title="Fermer" aria-label="Fermer">✕</button>
        </div>
      </div>
      <div id="chatbot-messages">
        <div class="chat-date-separator">
          <span>Aujourd'hui</span>
        </div>
      </div>
      <div class="chatbot-quick-replies" id="quick-replies"></div>
      <div class="chatbot-input">
        <button id="chat-emoji-btn" title="Emoji" aria-label="Ajouter un emoji">😊</button>
        <input type="text" id="chat-input" placeholder="Écrivez un message..." autocomplete="off" maxlength="1000">
        <span id="chat-char-count" class="chat-char-count"></span>
        <button id="chat-send" aria-label="Envoyer">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL PROJET -->
  <div id="project-modal" class="hidden">
    <div class="modal-overlay"></div>
    <div class="modal-content">
      <button class="modal-close" aria-label="Fermer la fenêtre du projet"><span aria-hidden="true">✕</span></button>
      <div class="modal-body" id="modal-content-body">
        <!-- Content injected by JS -->
      </div>
    </div>
  </div>

  </main> <!-- /main -->

  <footer>
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-logo">SDS<span>.</span></div>
        <p class="footer-desc" data-i18n="footer.desc">Solutions digitales innovantes pour les entreprises africaines.
          IA, automatisation et développement web.</p>
        <div class="footer-socials">
          <a href="https://linkedin.com/in/dieylany-khouma" class="social-link" title="LinkedIn" target="_blank"
            rel="noopener">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
            </svg>
          </a>
          <a href="https://github.com/a2k-dieylany" class="social-link" title="GitHub" target="_blank" rel="noopener">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
            </svg>
          </a>
          <a href="https://wa.me/221780152522" class="social-link" title="WhatsApp" target="_blank" rel="noopener">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
              <path
                d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326z" />
            </svg>
          </a>
        </div>
      </div>
      <div class="footer-links">
        <h4 data-i18n="footer.nav">Navigation</h4>
        <a href="#about" data-i18n="nav.about">À propos</a>
        <a href="#services" data-i18n="nav.services">Services</a>
        <a href="#projects" data-i18n="nav.projects">Projets</a>
        <a href="#contact" data-i18n="nav.contact">Contact</a>
      </div>
      <div class="footer-links">
        <h4 data-i18n="footer.services">Services</h4>
        <a href="#services" data-i18n="s1.t">Automatisation WhatsApp</a>
        <a href="#services" data-i18n="s2.t">Développement Web</a>
        <a href="#services" data-i18n="s3.t">Intégration IA</a>
        <a href="#services" data-i18n="s6.t">Conseil Digital</a>
      </div>
      <div class="footer-links">
        <h4 data-i18n="footer.contact">Contact</h4>
        <a href="mailto:sendigitalsolution@gmail.com">sendigitalsolution@gmail.com</a>
        <a href="https://wa.me/221780152522">+221 78 015 25 22</a>
        <span class="footer-location">Dakar, Sénégal</span>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025–<?= date('Y') ?> <span>Dieylany</span> · <?= htmlspecialchars($settings['site_name'] ?? 'SEN DIGITAL SOLUTION') ?></p>
    </div>
  </footer>

  <button id="scroll-top" title="Retour en haut" aria-label="Revenir en haut de la page"><span aria-hidden="true">↑</span></button>
  <script src="<?= sds_asset('i18n.js') ?>"></script>
  <script src="<?= sds_asset('script.js') ?>"></script>
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js').catch(err => {
          console.log('SW registration failed: ', err);
        });
      });
    }
  </script>
</body>

</html>
