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
  cursor: pointer;
  user-select: none;
  transition: all 0.3s ease;
}
.settings-group-header:hover { background: rgba(255,255,255,0.05); }
.settings-group-header .group-icon { font-size: 1.4rem; filter: drop-shadow(0 2px 8px rgba(108,99,255,0.4)); }
.settings-group-header .group-name { font-weight: 700; font-size: 1.1rem; color: var(--text-primary,#fff); letter-spacing: 0.5px; }
.settings-group-header .group-count { margin-left: auto; font-size: 0.8rem; color: var(--accent-light); background: var(--accent-glow); padding: 4px 12px; border-radius: 20px; font-weight: 600; border: 1px solid rgba(108,99,255,0.2); }
.settings-group-header .chevron { margin-left: 1rem; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); color: var(--text-muted); font-size: 0.9rem; }
.settings-group-header.collapsed .chevron { transform: rotate(-90deg); }

.settings-group-body { padding: 0; transition: max-height 0.4s ease, opacity 0.4s ease; opacity: 1; }
.settings-group-body.collapsed { display: none; opacity: 0; }

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

.btn-delete-setting {
  background: rgba(255,77,106,0.1);
  color: var(--red);
  border: 1px solid rgba(255,77,106,0.2);
  padding: 0.6rem 0.8rem;
  border-radius: 10px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}
.btn-delete-setting:hover { background: var(--red); color: #fff; box-shadow: 0 4px 15px rgba(255,77,106,0.4); transform: translateY(-2px); }

@media (max-width: 768px) {
  .setting-row { grid-template-columns: 1fr; gap: 1rem; }
  .setting-actions { justify-content: flex-end; }
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
