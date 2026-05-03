<?php
session_start();
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
/* Réutilisation des styles de settings.php */
.settings-container { display: flex; flex-direction: column; gap: 1.5rem; }
.settings-group { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; overflow: hidden; }
.settings-group-header { display: flex; align-items: center; gap: .7rem; padding: 1rem 1.5rem; background: rgba(255,255,255,0.04); border-bottom: 1px solid rgba(255,255,255,0.06); cursor: pointer; user-select: none; }
.settings-group-header .group-icon { font-size: 1.2rem; }
.settings-group-header .group-name { font-weight: 600; font-size: 1rem; color: var(--text-primary,#fff); text-transform: capitalize; }
.setting-row { display: grid; grid-template-columns: 1fr 1.5fr auto; gap: 1rem; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); }
.setting-row:last-child { border-bottom: none; }
.setting-label { display: flex; flex-direction: column; gap: .2rem; }
.setting-label .label-text { font-weight: 500; color: var(--text-primary,#fff); font-size: .95rem; }
.setting-label .label-key { font-size: .75rem; color: var(--text-muted,#888); font-family: monospace; }
.setting-input { width: 100%; padding: .6rem .8rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-size: .9rem; }
.setting-input:focus { outline: none; border-color: var(--accent,#6C63FF); }
.setting-input-color { width: 50px; height: 38px; border: 2px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer; background: none; padding: 2px; }
.setting-color-wrap { display: flex; align-items: center; gap: .8rem; }
.setting-color-hex { padding: .5rem .7rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-family: monospace; width: 100px; }
.btn-save-setting { background: linear-gradient(135deg, #6C63FF, #4ECDC4); color: #fff; border: none; padding: .5rem .8rem; border-radius: 8px; cursor: pointer; font-size: .8rem; font-weight: 600; opacity: 0.5; pointer-events: none; }
.btn-save-setting.active { opacity: 1; pointer-events: auto; }
.btn-save-setting.saved { background: #27ae60 !important; opacity: 1; }
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
