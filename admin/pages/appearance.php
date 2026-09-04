<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_auth();
?>
<div class="cms-module">
  <div class="cms-header">
    <div>
      <h2>🎨 Apparence & Thème</h2>
      <p class="cms-subtitle">Personnalisez les couleurs et le style de votre portfolio</p>
    </div>
  </div>

  <div id="appearance-container" class="settings-container">
    <div class="loading-spinner">Chargement...</div>
  </div>
</div>

<style>
.settings-container { display: flex; flex-direction: column; gap: 1.5rem; }

.settings-group {
  background: var(--card);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(0,0,0,0.15);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.settings-group:hover {
  border-color: rgba(255,255,255,0.1);
  box-shadow: 0 12px 40px rgba(0,0,0,0.2);
}

.settings-group-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.2rem 1.5rem;
  background: rgba(255,255,255,0.02);
  border-bottom: 1px solid var(--border);
  user-select: none;
}
.settings-group-header .group-icon { font-size: 1.4rem; filter: drop-shadow(0 2px 8px rgba(108,99,255,0.4)); }
.settings-group-header .group-name { font-weight: 700; font-size: 1.1rem; color: var(--text-primary,#fff); letter-spacing: 0.5px; }

.setting-row {
  display: grid;
  grid-template-columns: 1fr 1.5fr auto;
  gap: 1.5rem;
  align-items: center;
  padding: 1.2rem 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.03);
  transition: background 0.3s ease;
}
.setting-row:last-child { border-bottom: none; }
.setting-row:hover { background: rgba(255,255,255,0.02); }

.setting-label {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}
.setting-label .label-text { font-weight: 600; color: var(--text-primary,#fff); font-size: 0.95rem; }
.setting-label .label-key { font-size: 0.75rem; color: var(--text-muted); font-family: 'Courier New', Courier, monospace; letter-spacing: 0.5px; }

.setting-input {
  width: 100%;
  padding: 0.8rem 1rem;
  background: rgba(0,0,0,0.2);
  border: 1px solid var(--border);
  border-radius: 10px;
  color: #fff;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}
.setting-input:focus { outline: none; border-color: var(--accent); background: rgba(0,0,0,0.4); box-shadow: 0 0 0 3px var(--accent-glow); }

.setting-input-color {
  width: 50px;
  height: 42px;
  border: 1px solid var(--border);
  border-radius: 10px;
  cursor: pointer;
  background: rgba(0,0,0,0.2);
  padding: 2px;
  transition: all 0.3s ease;
}
.setting-input-color:hover { border-color: var(--accent); }

.setting-color-wrap {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.setting-color-hex {
  width: 110px;
  text-align: center;
}

.setting-actions { display: flex; gap: 0.8rem; }

.btn-save-setting {
  background: linear-gradient(135deg, var(--accent), var(--accent-light));
  color: #fff;
  border: none;
  padding: 0.6rem 1.2rem;
  border-radius: 10px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 600;
  transition: all 0.3s ease;
  opacity: 0.5;
  pointer-events: none;
  box-shadow: 0 4px 15px rgba(108,99,255,0.3);
}
.btn-save-setting.active { opacity: 1; pointer-events: auto; }
.btn-save-setting.active:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(108,99,255,0.5); }
.btn-save-setting.active:active { transform: translateY(1px); }
.btn-save-setting.saved { background: linear-gradient(135deg, #00D68F, #00A66F) !important; box-shadow: 0 4px 15px rgba(0,214,143,0.3) !important; opacity: 1; }

@media (max-width: 768px) {
  .setting-row { grid-template-columns: 1fr; gap: 1rem; }
  .setting-actions { justify-content: flex-end; }
}
</style>

<script>
async function loadAppearanceSettings() {
  const container = document.getElementById('appearance-container');
  try {
    const res = await fetch('api/settings.php');
    let data = await res.json();
    
    // Filtrer uniquement la catégorie apparence
    data = data.filter(s => s.category === 'apparence' || s.category === 'hero' || s.setting_key === 'site_name' || s.setting_key === 'logo_text');

    let html = `
      <div class="settings-group">
        <div class="settings-group-header">
          <span class="group-icon">🎨</span>
          <span class="group-name">Couleurs & Textes de Marque</span>
        </div>
        <div class="settings-group-body">`;
    
    data.forEach(s => {
      html += renderAppRow(s);
    });

    html += `</div></div>`;
    container.innerHTML = html;
  } catch(e) {
    container.innerHTML = '<div class="empty-state">❌ Erreur de chargement</div>';
  }
}

function renderAppRow(s) {
  const key = s.setting_key;
  let inputHtml = '';

  if (s.setting_type === 'color') {
    inputHtml = `
      <div class="setting-color-wrap">
        <input type="color" class="setting-input-color" value="${s.setting_value}" data-key="${key}" onchange="appColorChange(this)">
        <input type="text" class="setting-color-hex setting-input" value="${s.setting_value}" data-key="${key}" oninput="appHexChange(this)">
      </div>`;
  } else {
    inputHtml = `<input type="text" class="setting-input" value="${s.setting_value}" data-key="${key}" data-orig="${s.setting_value}" oninput="appMarkDirty(this)">`;
  }

  return `
    <div class="setting-row" id="app-row-${key}">
      <div class="setting-label">
        <span class="label-text">${s.label || key}</span>
        <span class="label-key">${key}</span>
      </div>
      <div>${inputHtml}</div>
      <div class="setting-actions">
        <button class="btn-save-setting" id="app-btn-${key}" onclick="saveAppSetting('${key}')">💾 Sauvegarder</button>
      </div>
    </div>`;
}

function appMarkDirty(el) {
  const key = el.dataset.key;
  const btn = document.getElementById('app-btn-' + key);
  if (btn) {
    btn.classList.toggle('active', el.value !== el.dataset.orig);
    btn.classList.remove('saved');
    btn.textContent = '💾 Sauvegarder';
  }
}

function appColorChange(colorInput) {
  const key = colorInput.dataset.key;
  const hexInput = colorInput.parentElement.querySelector('.setting-color-hex');
  hexInput.value = colorInput.value;
  const btn = document.getElementById('app-btn-' + key);
  if (btn) { btn.classList.add('active'); btn.classList.remove('saved'); btn.textContent = '💾 Sauvegarder'; }
}

function appHexChange(hexInput) {
  const key = hexInput.dataset.key;
  const colorInput = hexInput.parentElement.querySelector('input[type=color]');
  if (/^#[0-9a-fA-F]{6}$/.test(hexInput.value)) colorInput.value = hexInput.value;
  const btn = document.getElementById('app-btn-' + key);
  if (btn) { btn.classList.add('active'); btn.classList.remove('saved'); btn.textContent = '💾 Sauvegarder'; }
}

async function saveAppSetting(key) {
  const row = document.getElementById('app-row-' + key);
  const input = row.querySelector('.setting-input, .setting-color-hex');
  const btn = document.getElementById('app-btn-' + key);

  try {
    const res = await fetch('api/settings.php', {
      method: 'PUT',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ setting_key: key, setting_value: input.value })
    });
    const data = await res.json();
    if (data.success) {
      btn.textContent = '✅ Enregistré';
      btn.classList.add('saved');
      btn.classList.remove('active');
      if (input.dataset) input.dataset.orig = input.value;
    }
  } catch(e) { Admin.toast('Erreur', 'error'); }
}

// Init
loadAppearanceSettings();
</script>
