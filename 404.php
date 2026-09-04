<?php
/**
 * Page 404 — volontairement autonome : aucune requête base de données, pour
 * qu'elle réponde même si Aiven est injoignable. Servie par _app.php.
 */
http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <meta name="robots" content="noindex,follow" />
  <title>Page introuvable — SEN DIGITAL SOLUTION</title>
  <style>
    :root{--gold:#F5A623;--dark:#0A0A0F;--card:#14141E;--border:#1E1E2E;--text:#E2E2F0;--muted:#7A7A9D;}
    @media (prefers-color-scheme: light){
      :root{--gold:#D97706;--dark:#F8F9FA;--card:#FFFFFF;--border:#E5E7EB;--text:#1F2937;--muted:#6B7280;}
    }
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
      padding:2rem;background:var(--dark);color:var(--text);
      font-family:'Segoe UI',system-ui,-apple-system,sans-serif;text-align:center;line-height:1.6}
    .box{max-width:32rem}
    .code{font-size:clamp(4rem,18vw,8rem);font-weight:800;line-height:1;margin:0;
      background:linear-gradient(135deg,var(--gold),#FFD166);-webkit-background-clip:text;
      background-clip:text;color:transparent}
    h1{font-size:clamp(1.3rem,4vw,1.8rem);margin:.5rem 0 1rem}
    p{color:var(--muted);margin:0 0 2rem}
    .links{display:flex;gap:.75rem;flex-wrap:wrap;justify-content:center}
    a{display:inline-block;padding:.75rem 1.5rem;border-radius:.6rem;text-decoration:none;
      font-weight:600;border:1px solid var(--border);color:var(--text);
      background:var(--card);transition:transform .2s,border-color .2s}
    a.primary{background:linear-gradient(135deg,var(--gold),#FFD166);color:#0A0A0F;border-color:transparent}
    a:hover{transform:translateY(-2px);border-color:var(--gold)}
  </style>
</head>
<body>
  <main class="box">
    <p class="code">404</p>
    <h1>Cette page n'existe pas</h1>
    <p>Le lien est peut-être périmé, ou l'adresse comporte une faute de frappe.</p>
    <div class="links">
      <a class="primary" href="/">Retour à l'accueil</a>
      <a href="/#services">Voir les services</a>
      <a href="/#contact">Me contacter</a>
    </div>
  </main>
</body>
</html>
