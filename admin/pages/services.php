<div class="cms-module">
  <div class="cms-header">
    <div>
      <h2>🛠️ Services de l'Agence</h2>
      <p class="cms-subtitle">Gérez les solutions et expertises que vous proposez</p>
    </div>
    <button class="btn btn-primary" onclick="openSrvModal()">+ Ajouter un service</button>
  </div>

  <div class="data-card">
    
    <div id="services-table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px"></th>
                    <th style="width: 60px">Icône</th>
                    <th>Titre (FR)</th>
                    <th>Description</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="services-tbody">
                <tr><td colspan="6"><div class="page-loader"><div class="spinner"></div></div></td></tr>
            </tbody>
        </table>
    </div>
  </div>
</div>

<!-- Modal Service -->
<div class="modal-overlay" id="srv-modal">
    <div class="modal-panel" style="width: 650px">
        <div class="modal-header">
            <h3 id="srv-modal-title">Nouveau service</h3>
            <button class="modal-close" onclick="closeSrvModal()">✕</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="srv-id">
            
            <div style="margin-bottom:16px">
                <label class="meta-label">Icône (SVG ou Emoji)</label>
                <input type="text" id="srv-icon" class="form-input" style="min-height:40px;height:40px" placeholder="ex: 📱 ou code SVG">
            </div>

            <!-- Titres -->
            <div style="display:flex;gap:12px;margin-bottom:16px">
                <div style="flex:1">
                    <label class="meta-label">Titre (FR) *</label>
                    <input type="text" id="srv-title-fr" class="form-input" style="min-height:40px;height:40px" required>
                </div>
                <div style="flex:1">
                    <label class="meta-label">Titre (EN)</label>
                    <input type="text" id="srv-title-en" class="form-input" style="min-height:40px;height:40px">
                </div>
                <div style="flex:1">
                    <label class="meta-label">Titre (AR)</label>
                    <input type="text" id="srv-title-ar" class="form-input" style="min-height:40px;height:40px" dir="rtl">
                </div>
            </div>

            <!-- Descriptions -->
            <div style="margin-bottom:16px">
                <label class="meta-label">Description (FR) *</label>
                <textarea id="srv-desc-fr" class="form-input" required></textarea>
            </div>
            <div style="display:flex;gap:12px;margin-bottom:16px">
                <div style="flex:1">
                    <label class="meta-label">Description (EN)</label>
                    <textarea id="srv-desc-en" class="form-input" style="min-height:60px"></textarea>
                </div>
                <div style="flex:1">
                    <label class="meta-label">Description (AR)</label>
                    <textarea id="srv-desc-ar" class="form-input" style="min-height:60px" dir="rtl"></textarea>
                </div>
            </div>

            <div style="margin-bottom:16px">
                <label class="meta-label">Tags (séparés par des virgules)</label>
                <input type="text" id="srv-tags" class="form-input" style="min-height:40px;height:40px" placeholder="ex: Web, Mobile, SEO">
            </div>

            <div style="margin-bottom:16px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" id="srv-visible" checked>
                    <span style="font-size:0.9rem">Visible sur le site public</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeSrvModal()">Annuler</button>
            <button class="btn btn-primary" onclick="saveSrv()">Enregistrer</button>
        </div>
    </div>
</div>

<script>
let allServices = [];

async function loadServices() {
    const tbody = document.getElementById('services-tbody');
    const data = await Admin.api('services.php');
    if (data.error) return Admin.toast(data.error, 'error');

    allServices = data.services || [];
    
    if (allServices.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><p>Aucun service.</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = allServices.map(s => {
        return `
            <tr>
                <td style="color:var(--text-muted);cursor:ns-resize">↕️</td>
                <td style="font-size:1.5rem">${s.icon}</td>
                <td style="font-weight:600">${esc(s.title_fr)}</td>
                <td class="truncate" style="max-width:250px;color:var(--text-dim)">${esc(s.desc_fr)}</td>
                <td>
                    <span class="status ${s.is_visible ? 'status-read' : 'status-archived'}" style="cursor:pointer" onclick="toggleSrvVis(${s.id}, ${s.is_visible})">
                        ${s.is_visible ? 'Visible' : 'Masqué'}
                    </span>
                </td>
                <td>
                    <div class="action-btns">
                        <button class="action-btn" onclick="openSrvModal(${s.id})">✏️ Editer</button>
                        <button class="action-btn danger" onclick="deleteSrv(${s.id})">🗑️</button>
                    </div>
                </td>
            </tr>`;
    }).join('');
}

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

function openSrvModal(id = null) {
    document.getElementById('srv-modal-title').textContent = id ? 'Modifier le service' : 'Nouveau service';
    
    if (id) {
        const s = allServices.find(x => x.id == id);
        document.getElementById('srv-id').value = s.id;
        document.getElementById('srv-icon').value = s.icon;
        document.getElementById('srv-title-fr').value = s.title_fr;
        document.getElementById('srv-title-en').value = s.title_en;
        document.getElementById('srv-title-ar').value = s.title_ar;
        document.getElementById('srv-desc-fr').value = s.desc_fr;
        document.getElementById('srv-desc-en').value = s.desc_en;
        document.getElementById('srv-desc-ar').value = s.desc_ar;
        document.getElementById('srv-tags').value = s.tags;
        document.getElementById('srv-visible').checked = s.is_visible == 1;
    } else {
        document.getElementById('srv-id').value = '';
        document.getElementById('srv-icon').value = '';
        document.getElementById('srv-title-fr').value = '';
        document.getElementById('srv-title-en').value = '';
        document.getElementById('srv-title-ar').value = '';
        document.getElementById('srv-desc-fr').value = '';
        document.getElementById('srv-desc-en').value = '';
        document.getElementById('srv-desc-ar').value = '';
        document.getElementById('srv-tags').value = '';
        document.getElementById('srv-visible').checked = true;
    }
    
    document.getElementById('srv-modal').classList.add('active');
}

function closeSrvModal() {
    document.getElementById('srv-modal').classList.remove('active');
}

async function saveSrv() {
    const id = document.getElementById('srv-id').value;
    const body = {
        icon: document.getElementById('srv-icon').value,
        title_fr: document.getElementById('srv-title-fr').value,
        title_en: document.getElementById('srv-title-en').value,
        title_ar: document.getElementById('srv-title-ar').value,
        desc_fr: document.getElementById('srv-desc-fr').value,
        desc_en: document.getElementById('srv-desc-en').value,
        desc_ar: document.getElementById('srv-desc-ar').value,
        tags: document.getElementById('srv-tags').value,
        is_visible: document.getElementById('srv-visible').checked ? 1 : 0
    };

    if (!body.title_fr || !body.desc_fr || !body.icon) return Admin.toast('Remplissez Titre, Description et Icône', 'error');

    const method = id ? 'PUT' : 'POST';
    if (id) body.id = id;
    
    const data = await Admin.api('services.php', { method, body });
    if (data.success) {
        Admin.toast(data.message);
        closeSrvModal();
        loadServices();
    } else {
        Admin.toast(data.error, 'error');
    }
}

async function toggleSrvVis(id, currentVis) {
    const data = await Admin.api('services.php', { method: 'PUT', body: { id, is_visible: currentVis ? 0 : 1 } });
    if (data.success) loadServices();
}

async function deleteSrv(id) {
    if (!Admin.confirm('Supprimer ce service ?')) return;
    const data = await Admin.api(`services.php?id=${id}`, { method: 'DELETE' });
    if (data.success) {
        Admin.toast('Supprimé.');
        loadServices();
    }
}

loadServices();
</script>
