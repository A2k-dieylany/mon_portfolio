<?php
/**
 * Page de détail d'un service — URL propre : /services/<slug>
 * (le routage est assuré par _app.php sur Vercel, et par le paramètre ?slug= en local)
 */
require_once __DIR__ . '/admin/includes/db.php';
$pdo = getDB();

/**
 * Meta description destinée au SERP : une accroche, pas un résumé tronqué.
 *
 * On part de l'accroche client (headline), on complète avec la description
 * tant qu'il reste de la place, puis on termine par le prix, le délai et
 * l'appel à l'action — c'est ce trio qui déclenche le clic dans Google.
 * La coupe se fait toujours sur un espace : plus de « livré en l ».
 */
function sds_meta_description(array $service, int $max = 155): string {
    $norm = static fn($t) => trim(preg_replace('/\s+/u', ' ', strip_tags((string) $t)));

    $head = $norm($service['headline_fr'] ?? '') ?: $norm($service['title_fr']) . ' à Dakar';
    $head = rtrim($head, ' .') . '.';

    $ucfirstU = static fn($t) => mb_strtoupper(mb_substr($t, 0, 1), 'UTF-8') . mb_substr($t, 1);
    // Le délai suit un tiret, pas une virgule : « …la séance, Séances de 2 h »
    // se lisait mal. On le passe en minuscule sauf s'il commence par un sigle.
    $lcfirstU = static function ($t) {
        $first = mb_substr($t, 0, 1);
        $second = mb_substr($t, 1, 1);
        if ($second !== '' && $second === mb_strtoupper($second, 'UTF-8')) {
            return $t; // « IA », « RDV »… : on ne touche pas
        }
        return mb_strtolower($first, 'UTF-8') . mb_substr($t, 1);
    };

    $tail = '';
    if (!empty($service['price_from'])) {
        $tail .= ' ' . $ucfirstU(rtrim($norm($service['price_from']), ' .'));
        if (!empty($service['delay_text'])) {
            $tail .= ' — ' . $lcfirstU(rtrim($norm($service['delay_text']), ' .'));
        }
        $tail .= '.';
    }
    $tail .= ' Diagnostic gratuit.';

    $room = $max - mb_strlen($head) - mb_strlen($tail) - 1;
    $middle = '';
    if ($room > 30) {
        $desc = $norm($service['desc_fr']);
        if (mb_strlen($desc) > $room) {
            // On coupe sur un séparateur de clause, pas sur un simple espace :
            // « prêtes à… » se lit mal, « templates premium » se lit bien.
            $cut  = mb_substr($desc, 0, $room);
            $stop = 0;
            foreach ([',', ' :', ';', '.', ' —'] as $sep) {
                $at = mb_strrpos($cut, $sep);
                if ($at !== false && $at > $stop) {
                    $stop = $at;
                }
            }
            $desc = $stop > 20 ? rtrim(mb_substr($cut, 0, $stop), " ,;:—-") : '';
        }
        if ($desc !== '') {
            $middle = ' ' . $desc . (str_ends_with($desc, '.') ? '' : '.');
        }
    }

    return $head . $middle . $tail;
}

$slug = $_GET['slug'] ?? '';
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

$stmt = $pdo->prepare("SELECT * FROM services WHERE slug = :slug AND is_visible = 1 AND detail_fr IS NOT NULL LIMIT 1");
$stmt->execute([':slug' => $slug]);
$service = $stmt->fetch();

// Colonnes optionnelles (ajoutées au fil des évolutions) : on garantit leur
// présence pour éviter tout avertissement si la migration n'a pas encore tourné.
if ($service) {
    foreach (['headline_fr', 'headline_en', 'headline_ar', 'price_from', 'price_from_en', 'price_from_ar',
              'delay_text', 'delay_text_en', 'delay_text_ar', 'detail_en', 'detail_ar'] as $optional) {
        $service[$optional] = $service[$optional] ?? '';
    }
}

if (!$service) {
    http_response_code(404);
    $notFound = true;
} else {
    $notFound = false;
}

// Autres services proposant une page détail (pour la navigation en bas de page)
$others = $pdo->prepare(
    "SELECT icon, title_fr, title_en, title_ar, slug FROM services
     WHERE is_visible = 1 AND slug IS NOT NULL AND detail_fr IS NOT NULL AND slug <> :slug
     ORDER BY sort_order ASC"
);
$others->execute([':slug' => $slug]);
$otherServices = $others->fetchAll();

$settingsData = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
$settings = [];
foreach ($settingsData as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
$siteName = $settings['site_name'] ?? 'SEN DIGITAL SOLUTION';
$logoText = $settings['logo_text'] ?? 'A2K';
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <?php if ($notFound): ?>
  <title>Service introuvable — <?= htmlspecialchars($siteName) ?></title>
  <meta name="robots" content="noindex" />
  <?php else: ?>
  <title><?= htmlspecialchars($service['title_fr']) ?> à Dakar — <?= htmlspecialchars($siteName) ?></title>
  <?php
    $metaDesc = sds_meta_description($service);
    $svcUrl   = 'https://dieylany.dev/services/' . $service['slug'];
    // Carte partagée : une image par service serait mieux qu'un visuel générique.
    $svcImage = 'https://dieylany.dev/img/og/' . $service['slug'] . '.jpg';
    if (!is_file(__DIR__ . '/img/og/' . $service['slug'] . '.jpg')) {
        $svcImage = 'https://dieylany.dev/img/og/default.jpg';
        if (!is_file(__DIR__ . '/img/og/default.jpg')) {
            $svcImage = 'https://dieylany.dev/img/projects/luxe1.jpg';
        }
    }
  ?>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>" />
  <meta property="og:title" content="<?= htmlspecialchars($service['title_fr']) ?> — <?= htmlspecialchars($siteName) ?>" />
  <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>" />
  <meta property="og:type" content="website" />
  <meta property="og:locale" content="fr_SN" />
  <meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>" />
  <meta property="og:url" content="<?= htmlspecialchars($svcUrl) ?>" />
  <meta property="og:image" content="<?= htmlspecialchars($svcImage) ?>" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?= htmlspecialchars($service['title_fr']) ?> — <?= htmlspecialchars($siteName) ?>" />
  <meta name="twitter:description" content="<?= htmlspecialchars($metaDesc) ?>" />
  <meta name="twitter:image" content="<?= htmlspecialchars($svcImage) ?>" />
  <link rel="canonical" href="https://dieylany.dev/services/<?= htmlspecialchars($service['slug']) ?>" />
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Service",
    "name": <?= json_encode($service['title_fr'], JSON_UNESCAPED_UNICODE) ?>,
    "description": <?= json_encode(strip_tags($service['desc_fr']), JSON_UNESCAPED_UNICODE) ?>,
    "areaServed": { "@type": "City", "name": "Dakar" },
    "provider": {
      "@type": "ProfessionalService",
      "name": <?= json_encode($siteName, JSON_UNESCAPED_UNICODE) ?>,
      "url": "https://dieylany.dev/"
    }
  }
  </script>
  <?php endif; ?>
  <meta name="theme-color" content="#0A0A0F" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= sds_asset('style.css') ?>">
  <style>
    :root { --accent: <?= htmlspecialchars($settings['accent_color'] ?? '#6C63FF') ?>; --gold: <?= htmlspecialchars($settings['gold_color'] ?? '#D4AF37') ?>; }
  </style>
</head>

<body class="service-page">

  <nav>
    <a href="/" class="nav-logo" style="text-decoration:none"><?= htmlspecialchars($logoText) ?><span>.</span></a>
    <ul class="nav-links">
      <li><a href="/#services" class="svc-i18n" data-fr="Services" data-en="Services" data-ar="الخدمات">Services</a></li>
      <li><a href="/#projects" class="svc-i18n" data-fr="Projets" data-en="Projects" data-ar="المشاريع">Projets</a></li>
      <li><a href="/#contact" class="svc-i18n" data-fr="Contact" data-en="Contact" data-ar="تواصل">Contact</a></li>
    </ul>
    <div class="lang-switcher">
      <button class="lang-btn" id="theme-toggle" title="Thème clair/sombre" aria-label="Basculer entre le thème clair et sombre"><span aria-hidden="true">🌓</span></button>
      <button class="lang-btn active" onclick="setSvcLang('fr')" lang="fr" aria-label="Afficher la page en français">FR</button>
      <button class="lang-btn" onclick="setSvcLang('en')" lang="en" aria-label="Display this page in English">EN</button>
      <button class="lang-btn" onclick="setSvcLang('ar')" lang="ar" aria-label="عرض الصفحة بالعربية">AR</button>
    </div>
  </nav>

  <main class="svc-main">
    <div class="container">

      <?php if ($notFound): ?>
        <h1 class="svc-title">Service introuvable</h1>
        <p class="svc-lead">Cette page n'existe pas ou n'est plus disponible.</p>
        <p><a href="/#services" class="btn-primary">Voir tous les services</a></p>

      <?php else: ?>
        <a href="/#services" class="svc-back svc-i18n"
           data-fr="← Tous les services" data-en="← All services" data-ar="→ كل الخدمات">← Tous les services</a>

        <header class="svc-header">
          <span class="svc-icon"><?= htmlspecialchars($service['icon']) ?></span>
          <p class="svc-eyebrow svc-i18n"
             data-fr="<?= htmlspecialchars($service['title_fr']) ?>"
             data-en="<?= htmlspecialchars($service['title_en'] ?: $service['title_fr']) ?>"
             data-ar="<?= htmlspecialchars($service['title_ar'] ?: $service['title_fr']) ?>"><?= htmlspecialchars($service['title_fr']) ?></p>
          <h1 class="svc-title svc-i18n"
              data-fr="<?= htmlspecialchars($service['headline_fr'] ?: $service['title_fr']) ?>"
              data-en="<?= htmlspecialchars($service['headline_en'] ?: ($service['headline_fr'] ?: $service['title_fr'])) ?>"
              data-ar="<?= htmlspecialchars($service['headline_ar'] ?: ($service['headline_fr'] ?: $service['title_fr'])) ?>"><?= htmlspecialchars($service['headline_fr'] ?: $service['title_fr']) ?></h1>
          <?php if (!empty($service['price_from']) || !empty($service['delay_text'])): ?>
          <div class="svc-badges">
            <?php if (!empty($service['price_from'])): ?>
            <span class="svc-badge">💰 <span class="svc-i18n"
                  data-fr="<?= htmlspecialchars($service['price_from']) ?>"
                  data-en="<?= htmlspecialchars($service['price_from_en'] ?: $service['price_from']) ?>"
                  data-ar="<?= htmlspecialchars($service['price_from_ar'] ?: $service['price_from']) ?>"><?= htmlspecialchars($service['price_from']) ?></span></span>
            <?php endif; ?>
            <?php if (!empty($service['delay_text'])): ?>
            <span class="svc-badge">⏱️ <span class="svc-i18n"
                  data-fr="<?= htmlspecialchars($service['delay_text']) ?>"
                  data-en="<?= htmlspecialchars($service['delay_text_en'] ?: $service['delay_text']) ?>"
                  data-ar="<?= htmlspecialchars($service['delay_text_ar'] ?: $service['delay_text']) ?>"><?= htmlspecialchars($service['delay_text']) ?></span></span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </header>

        <article class="svc-body" id="svc-body-fr"><?= $service['detail_fr'] ?></article>
        <article class="svc-body" id="svc-body-en" hidden><?= $service['detail_en'] ?: $service['detail_fr'] ?></article>
        <article class="svc-body" id="svc-body-ar" hidden dir="rtl"><?= $service['detail_ar'] ?: $service['detail_fr'] ?></article>

        <section class="svc-cta">
          <h2 class="svc-i18n"
              data-fr="Discutons de votre projet"
              data-en="Let's talk about your project"
              data-ar="لنتحدّث عن مشروعك">Discutons de votre projet</h2>
          <p class="svc-i18n"
             data-fr="Diagnostic gratuit de 30 minutes, sans engagement."
             data-en="Free 30-minute assessment, no commitment."
             data-ar="تشخيص مجاني لمدة 30 دقيقة، دون التزام.">Diagnostic gratuit de 30 minutes, sans engagement.</p>
          <div class="svc-cta-btns">
            <a href="https://wa.me/221780152522" class="btn-primary svc-i18n" target="_blank" rel="noopener"
               data-fr="Réserver mon diagnostic gratuit" data-en="Book my free assessment" data-ar="احجز تشخيصك المجاني">Réserver mon diagnostic gratuit</a>
            <a href="/#contact" class="btn-outline svc-i18n"
               data-fr="Écrire un message" data-en="Send a message" data-ar="أرسل رسالة">Écrire un message</a>
          </div>
        </section>

        <?php if ($otherServices): ?>
        <section class="svc-others">
          <h2 class="svc-i18n" data-fr="Autres services" data-en="Other services" data-ar="خدمات أخرى">Autres services</h2>
          <div class="svc-others-grid">
            <?php foreach ($otherServices as $o): ?>
            <a href="/services/<?= htmlspecialchars($o['slug']) ?>" class="svc-other-card">
              <span class="svc-other-icon"><?= htmlspecialchars($o['icon']) ?></span>
              <span class="svc-i18n"
                    data-fr="<?= htmlspecialchars($o['title_fr']) ?>"
                    data-en="<?= htmlspecialchars($o['title_en'] ?: $o['title_fr']) ?>"
                    data-ar="<?= htmlspecialchars($o['title_ar'] ?: $o['title_fr']) ?>"><?= htmlspecialchars($o['title_fr']) ?></span>
            </a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>
      <?php endif; ?>

    </div>
  </main>

  <footer>
    <div class="footer-bottom">
      <p dir="ltr">© 2025–<?= date('Y') ?> <span>Dieylany</span> · <?= htmlspecialchars($siteName) ?></p>
    </div>
  </footer>

  <script>
    // Bascule de langue : corps de page + tous les textes d'interface (.svc-i18n)
    function setSvcLang(lang) {
      ['fr', 'en', 'ar'].forEach(function (l) {
        var el = document.getElementById('svc-body-' + l);
        if (el) el.hidden = (l !== lang);
      });

      document.querySelectorAll('.svc-i18n').forEach(function (el) {
        var v = el.dataset[lang];
        if (v !== undefined && v.trim() !== '') el.textContent = v;
      });

      document.documentElement.lang = lang;
      document.documentElement.dir = (lang === 'ar') ? 'rtl' : 'ltr';

      document.querySelectorAll('.lang-switcher .lang-btn').forEach(function (b) {
        if (['FR', 'EN', 'AR'].includes(b.textContent.trim())) {
          b.classList.toggle('active', b.textContent.trim().toLowerCase() === lang);
        }
      });
      try { localStorage.setItem('sds_lang', lang); } catch (e) {}
    }

    // Thème clair/sombre — même mécanisme que la page d'accueil
    (function () {
      var saved = 'dark';
      try { saved = localStorage.getItem('theme') || 'dark'; } catch (e) {}
      if (saved === 'light') document.documentElement.setAttribute('data-theme', 'light');

      var btn = document.getElementById('theme-toggle');
      if (btn) {
        btn.addEventListener('click', function () {
          var isLight = document.documentElement.getAttribute('data-theme') === 'light';
          if (isLight) {
            document.documentElement.removeAttribute('data-theme');
          } else {
            document.documentElement.setAttribute('data-theme', 'light');
          }
          try { localStorage.setItem('theme', isLight ? 'dark' : 'light'); } catch (e) {}
        });
      }

      var lang = null;
      try { lang = localStorage.getItem('sds_lang'); } catch (e) {}
      if (lang && lang !== 'fr') setSvcLang(lang);
    })();
  </script>
</body>

</html>
