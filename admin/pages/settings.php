<div class="cms-module">
  <div class="cms-header">
    <div>
      <h2>⚙️ Configuration du site</h2>
      <p class="cms-subtitle">Gérez les paramètres globaux de votre portfolio</p>
    </div>
    <button class="btn-add" onclick="addSetting()">+ Nouveau paramètre</button>
  </div>

  <div id="settings-container" class="settings-container">
    <div class="loading-spinner">Chargement...</div>
  </div>
</div>

<!-- Modale Ajout -->
<div class="modal-overlay" id="modal-add-overlay" onclick="closeModal('add')"></div>
<div class="modal" id="modal-add">
  <div class="modal-header">
    <h3>➕ Nouveau paramètre</h3>
    <button class="modal-close" onclick="closeModal('add')">✕</button>
  </div>
  <form id="form-add" onsubmit="return submitAdd(event)">
    <div class="form-row">
      <div class="form-group">
        <label>Clé technique</label>
        <input type="text" name="setting_key" placeholder="ex: whatsapp_number" required>
      </div>
      <div class="form-group">
        <label>Catégorie</label>
        <select name="category">
          <option value="general">Général</option>
          <option value="apparence">Apparence</option>
          <option value="hero">Hero</option>
          <option value="api">API</option>
          <option value="social">Réseaux sociaux</option>
          <option value="contact">Contact</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Label</label>
        <input type="text" name="label" placeholder="Nom affiché" required>
      </div>
      <div class="form-group">
        <label>Type</label>
        <select name="setting_type">
          <option value="text">Texte</option>
          <option value="color">Couleur</option>
          <option value="number">Nombre</option>
          <option value="boolean">Oui/Non</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Valeur</label>
      <input type="text" name="setting_value" placeholder="Valeur du paramètre" required>
    </div>
    <button type="submit" class="btn-submit">💾 Ajouter</button>
  </form>
</div>

<style>
.settings-container { display: flex; flex-direction: column; gap: 1.5rem; }

.settings-group {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 16px;
  overflow: hidden;
}

.settings-group-header {
  display: flex;
  align-items: center;
  gap: .7rem;
  padding: 1rem 1.5rem;
  background: rgba(255,255,255,0.04);
  border-bottom: 1px solid rgba(255,255,255,0.06);
  cursor: pointer;
  user-select: none;
  transition: background .2s;
}
.settings-group-header:hover { background: rgba(255,255,255,0.07); }
.settings-group-header .group-icon { font-size: 1.2rem; }
.settings-group-header .group-name { font-weight: 600; font-size: 1rem; color: var(--text-primary,#fff); text-transform: capitalize; }
.settings-group-header .group-count { margin-left: auto; font-size: .8rem; color: var(--text-muted,#888); background: rgba(255,255,255,0.08); padding: 2px 10px; border-radius: 20px; }
.settings-group-header .chevron { margin-left: .5rem; transition: transform .3s; color: var(--text-muted,#888); }
.settings-group-header.collapsed .chevron { transform: rotate(-90deg); }

.settings-group-body { padding: 0; }
.settings-group-body.collapsed { display: none; }

.setting-row {
  display: grid;
  grid-template-columns: 1fr 1.5fr auto;
  gap: 1rem;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  transition: background .2s;
}
.setting-row:last-child { border-bottom: none; }
.setting-row:hover { background: rgba(255,255,255,0.03); }

.setting-label {
  display: flex;
  flex-direction: column;
  gap: .2rem;
}
.setting-label .label-text { font-weight: 500; color: var(--text-primary,#fff); font-size: .95rem; }
.setting-label .label-key { font-size: .75rem; color: var(--text-muted,#888); font-family: monospace; }

.setting-input {
  width: 100%;
  padding: .6rem .8rem;
  background: rgba(0,0,0,0.3);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  color: #fff;
  font-size: .9rem;
  transition: border-color .3s;
}
.setting-input:focus { outline: none; border-color: var(--accent,#6C63FF); }

.setting-input-color {
  width: 50px;
  height: 38px;
  border: 2px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  cursor: pointer;
  background: none;
  padding: 2px;
}

.setting-color-wrap {
  display: flex;
  align-items: center;
  gap: .8rem;
}
.setting-color-hex {
  padding: .5rem .7rem;
  background: rgba(0,0,0,0.3);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  color: #fff;
  font-family: monospace;
  font-size: .85rem;
  width: 100px;
}

.setting-actions { display: flex; gap: .5rem; }

.btn-save-setting {
  background: linear-gradient(135deg, #6C63FF, #4ECDC4);
  color: #fff;
  border: none;
  padding: .5rem .8rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: .8rem;
  font-weight: 600;
  transition: transform .2s, opacity .2s;
  opacity: 0.5;
  pointer-events: none;
}
.btn-save-setting.active { opacity: 1; pointer-events: auto; }
.btn-save-setting.active:hover { transform: scale(1.05); }
.btn-save-setting.saved { background: #27ae60 !important; }

.btn-delete-setting {
  background: rgba(255,71,87,0.15);
  color: #ff4757;
  border: 1px solid rgba(255,71,87,0.2);
  padding: .5rem .7rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: .8rem;
  transition: background .2s;
}
.btn-delete-setting:hover { background: rgba(255,71,87,0.3); }

@media (max-width: 768px) {
  .setting-row { grid-template-columns: 1fr; }
}
</style>

<script>
const API_SETTINGS = 'api/settings.php';
let settingsData = [];

const CATEGORY_META = {
  general:   { icon: '🏢', label: 'Général' },
  apparence: { icon: '🎨', label: 'Apparence' },
  hero:      { icon: '🚀', label: 'Section Hero' },
  api:       { icon: '🔑', label: 'Clés API' },
  social:    { icon: '🔗', label: 'Réseaux Sociaux' },
  contact:   { icon: '📬', label: 'Contact' }
};

async function loadSettings() {
  const container = document.getElementById('settings-container');
  try {
    const res = await fetch(API_SETTINGS);
    settingsData = await res.json();

    // Grouper par catégorie
    const groups = {};
    settingsData.forEach(s => {
      const cat = s.category || 'general';
      if (!groups[cat]) groups[cat] = [];
      groups[cat].push(s);
    });

    let html = '';
    for (const [cat, items] of Object.entries(groups)) {
      const meta = CATEGORY_META[cat] || { icon: '⚙️', label: cat };
      html += `
        <div class="settings-group">
          <div class="settings-group-header" onclick="toggleGroup(this)">
            <span class="group-icon">${meta.icon}</span>
            <span class="group-name">${meta.label}</span>
            <span class="group-count">${items.length}</span>
            <span class="chevron">▼</span>
          </div>
          <div class="settings-group-body">`;
      
      items.forEach(s => {
        html += renderSettingRow(s);
      });

      html += `</div></div>`;
    }

    container.innerHTML = html;
  } catch(e) {
    container.innerHTML = '<div class="empty-state">❌ Erreur de chargement des paramètres</div>';
    console.error(e);
  }
}

function renderSettingRow(s) {
  const key = s.setting_key;
  let inputHtml = '';

  if (s.setting_type === 'color') {
    inputHtml = `
      <div class="setting-color-wrap">
        <input type="color" class="setting-input-color" value="${s.setting_value}" data-key="${key}" onchange="onColorChange(this)">
        <input type="text" class="setting-color-hex setting-input" value="${s.setting_value}" data-key="${key}" oninput="onHexChange(this)">
      </div>`;
  } else if (s.setting_type === 'boolean') {
    inputHtml = `
      <select class="setting-input" data-key="${key}" data-orig="${s.setting_value}" onchange="markDirty(this)">
        <option value="true" ${s.setting_value==='true'?'selected':''}>Oui</option>
        <option value="false" ${s.setting_value==='false'?'selected':''}>Non</option>
      </select>`;
  } else if (s.setting_type === 'number') {
    inputHtml = `<input type="number" class="setting-input" value="${s.setting_value}" data-key="${key}" data-orig="${s.setting_value}" oninput="markDirty(this)">`;
  } else {
    // Password mask for API keys
    const isSecret = key.includes('api_key') || key.includes('secret') || key.includes('password');
    inputHtml = `<input type="${isSecret ? 'password' : 'text'}" class="setting-input" value="${s.setting_value}" data-key="${key}" data-orig="${s.setting_value}" oninput="markDirty(this)">`;
  }

  return `
    <div class="setting-row" id="row-${key}">
      <div class="setting-label">
        <span class="label-text">${s.label || key}</span>
        <span class="label-key">${key}</span>
      </div>
      <div>${inputHtml}</div>
      <div class="setting-actions">
        <button class="btn-save-setting" id="btn-${key}" onclick="saveSetting('${key}')">💾</button>
        <button class="btn-delete-setting" onclick="deleteSetting('${key}')" title="Supprimer">🗑️</button>
      </div>
    </div>`;
}

function markDirty(el) {
  const key = el.dataset.key;
  const btn = document.getElementById('btn-' + key);
  if (btn) {
    btn.classList.toggle('active', el.value !== el.dataset.orig);
    btn.classList.remove('saved');
  }
}

function onColorChange(colorInput) {
  const key = colorInput.dataset.key;
  const hexInput = colorInput.parentElement.querySelector('.setting-color-hex');
  hexInput.value = colorInput.value;
  const btn = document.getElementById('btn-' + key);
  if (btn) { btn.classList.add('active'); btn.classList.remove('saved'); }
}

function onHexChange(hexInput) {
  const key = hexInput.dataset.key;
  const colorInput = hexInput.parentElement.querySelector('input[type=color]');
  if (/^#[0-9a-fA-F]{6}$/.test(hexInput.value)) colorInput.value = hexInput.value;
  const btn = document.getElementById('btn-' + key);
  if (btn) { btn.classList.add('active'); btn.classList.remove('saved'); }
}

function toggleGroup(header) {
  header.classList.toggle('collapsed');
  const body = header.nextElementSibling;
  body.classList.toggle('collapsed');
}

async function saveSetting(key) {
  const row = document.getElementById('row-' + key);
  const input = row.querySelector('.setting-input, .setting-color-hex');
  const btn = document.getElementById('btn-' + key);

  try {
    const res = await fetch(API_SETTINGS, {
      method: 'PUT',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ setting_key: key, setting_value: input.value })
    });
    const data = await res.json();
    if (data.success) {
      btn.textContent = '✅';
      btn.classList.add('saved');
      btn.classList.remove('active');
      if (input.dataset) input.dataset.orig = input.value;
      setTimeout(() => { btn.textContent = '💾'; btn.classList.remove('saved'); }, 2000);
    }
  } catch(e) { alert('Erreur de sauvegarde'); }
}

async function deleteSetting(key) {
  if (!confirm(`Supprimer le paramètre "${key}" ?`)) return;
  try {
    const res = await fetch(API_SETTINGS, {
      method: 'DELETE',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ setting_key: key })
    });
    const data = await res.json();
    if (data.success) {
      const row = document.getElementById('row-' + key);
      if (row) row.remove();
    }
  } catch(e) { alert('Erreur de suppression'); }
}

function addSetting() {
  document.getElementById('modal-add').classList.add('active');
  document.getElementById('modal-add-overlay').classList.add('active');
}

function closeModal(type) {
  document.getElementById('modal-' + type).classList.remove('active');
  document.getElementById('modal-' + type + '-overlay').classList.remove('active');
}

async function submitAdd(e) {
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form));
  try {
    const res = await fetch(API_SETTINGS, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) {
      closeModal('add');
      form.reset();
      loadSettings();
    }
  } catch(e) { alert('Erreur'); }
}

// Init
loadSettings();
</script>
