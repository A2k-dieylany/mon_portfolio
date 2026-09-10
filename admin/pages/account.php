<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_auth();
?>
<div class="cms-module">
  <div class="cms-header">
    <div>
      <h2><span class="h2-icon" aria-hidden="true">🔑</span> Mon compte</h2>
      <p class="cms-subtitle">Changez le mot de passe de votre espace d'administration</p>
    </div>
  </div>

  <form id="form-password" onsubmit="return submitPassword(event)" style="max-width:34rem">
    <div class="form-group">
      <label for="pw-current">Mot de passe actuel</label>
      <input type="password" id="pw-current" class="form-input" name="current_password"
             autocomplete="current-password" required>
    </div>
    <div class="form-group">
      <label for="pw-new">Nouveau mot de passe</label>
      <input type="password" id="pw-new" class="form-input" name="new_password"
             autocomplete="new-password" minlength="10" required>
      <small style="color:var(--text-muted)">10 caractères minimum. Choisissez-en un que vous
        retiendrez : il n'existe aucun envoi de e-mail de récupération.</small>
    </div>
    <div class="form-group">
      <label for="pw-confirm">Confirmer le nouveau mot de passe</label>
      <input type="password" id="pw-confirm" class="form-input" name="confirm_password"
             autocomplete="new-password" minlength="10" required>
    </div>
    <p id="pw-error" role="alert" style="display:none;color:var(--red);font-size:.85rem;margin:0 0 1rem"></p>
    <button type="submit" class="btn btn-primary" id="pw-submit">Modifier le mot de passe</button>
  </form>
</div>

<script>
async function submitPassword(e) {
    e.preventDefault();
    const err     = document.getElementById('pw-error');
    const btn     = document.getElementById('pw-submit');
    const current = document.getElementById('pw-current').value;
    const next    = document.getElementById('pw-new').value;
    const confirm = document.getElementById('pw-confirm').value;

    const fail = (msg) => { err.textContent = msg; err.style.display = 'block'; };
    err.style.display = 'none';

    if (next !== confirm) { fail('Les deux nouveaux mots de passe ne correspondent pas.'); return false; }
    if (next.length < 10) { fail('Le nouveau mot de passe doit faire au moins 10 caractères.'); return false; }

    btn.disabled = true;
    const res = await Admin.api('account.php', {
        method: 'POST',
        body: { action: 'change_password', current_password: current, new_password: next }
    });
    btn.disabled = false;

    if (res.error) { fail(res.error); return false; }

    document.getElementById('form-password').reset();
    Admin.toast('Mot de passe modifié.');
    return false;
}
</script>
