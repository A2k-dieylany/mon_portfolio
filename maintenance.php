<?php
/**
 * Page servie quand la base est injoignable.
 *
 * Volontairement autonome et sans dépendance : aucune requête, aucun asset
 * externe. Renvoie 503 avec Retry-After pour que Google comprenne qu'il
 * s'agit d'une indisponibilité temporaire et ne désindexe pas la page.
 */
http_response_code(503);
header('Retry-After: 300');
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <meta name="robots" content="noindex,follow" />
  <title>Service temporairement indisponible — SEN DIGITAL SOLUTION</title>
  <style>
    :root{--gold:#F5A623;--dark:#0A0A0F;--card:#14141E;--border:#1E1E2E;--text:#E2E2F0;--muted:#7A7A9D;}
    @media (prefers-color-scheme: light){
      :root{--gold:#D97706;--dark:#F8F9FA;--card:#FFFFFF;--border:#E5E7EB;--text:#1F2937;--muted:#6B7280;}
    }
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
      padding:2rem;background:var(--dark);color:var(--text);
      font-family:'Segoe UI',system-ui,-apple-system,sans-serif;text-align:center;line-height:1.6}
    .box{max-width:34rem}
    .icon{font-size:3.5rem;line-height:1;margin-bottom:1rem}
    h1{font-size:clamp(1.3rem,4vw,1.9rem);margin:0 0 .75rem}
    p{color:var(--muted);margin:0 0 1.75rem}
    .links{display:flex;gap:.75rem;flex-wrap:wrap;justify-content:center}
    a{display:inline-block;padding:.75rem 1.5rem;border-radius:.6rem;text-decoration:none;
      font-weight:600;border:1px solid var(--border);color:var(--text);background:var(--card);
      transition:transform .2s,border-color .2s}
    a.primary{background:linear-gradient(135deg,var(--gold),#FFD166);color:#0A0A0F;border-color:transparent}
    a:hover{transform:translateY(-2px);border-color:var(--gold)}
  </style>
</head>
<body>
  <main class="box">
    <div class="icon" aria-hidden="true">🔧</div>
    <h1>Le site revient dans un instant</h1>
    <p>Une maintenance technique est en cours. En attendant, vous pouvez me joindre
       directement — je réponds vite.</p>
    <div class="links">
      <a class="primary" href="https://wa.me/221780152522">Écrire sur WhatsApp</a>
      <a href="mailto:sendigitalsolution@gmail.com">Envoyer un e-mail</a>
    </div>
  </main>
</body>
</html>
