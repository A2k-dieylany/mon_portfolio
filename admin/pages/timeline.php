<div class="cms-module">
  <div class="cms-header">
    <div>
      <h2>🎓 Parcours & Expériences</h2>
      <p class="cms-subtitle">Gérez vos formations et votre historique professionnel</p>
    </div>
    <button class="btn btn-primary" onclick="openTmModal()">+ Ajouter une étape</button>
  </div>

  <div class="data-card">
    
    <div id="timeline-table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px"></th>
                    <th>Année</th>
                    <th>Titre (FR)</th>
                    <th>Badge</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="timeline-tbody">
                <tr><td colspan="6"><div class="page-loader"><div class="spinner"></div></div></td></tr>
            </tbody>
        </table>
    </div>
  </div>
</div>

<!-- Modal Timeline -->
<div class="modal-overlay" id="tm-modal">
    <div class="modal-panel" style="width: 650px">
        <div class="modal-header">
            <h3 id="tm-modal-title">Nouvelle étape</h3>
            <button class="modal-close" onclick="closeTmModal()">✕</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="tm-id">
            
            <div style="display:flex;gap:12px;margin-bottom:16px">
                <div style="flex:1">
                    <label class="meta-label">Année (FR) *</label>
                    <input type="text" id="tm-year-fr" class="form-input" style="min-height:40px;height:40px" placeholder="ex: 2022 - Présent" required>
                </div>
                <div style="flex:1">
                    <label class="meta-label">Badge (FR) *</label>
                    <input type="text" id="tm-badge-fr" class="form-input" style="min-height:40px;height:40px" placeholder="ex: Diplôme, Emploi..." required>
                </div>
            </div>

            <!-- Titres -->
            <div style="display:flex;gap:12px;margin-bottom:16px">
                <div style="flex:1">
                    <label class="meta-label">Titre (FR) *</label>
                    <input type="text" id="tm-title-fr" class="form-input" style="min-height:40px;height:40px" required>
                </div>
            </div>

            <!-- Descriptions -->
            <div style="margin-bottom:16px">
                <label class="meta-label">Description (FR) *</label>
                <textarea id="tm-desc-fr" class="form-input" required></textarea>
            </div>

            <hr style="border:0;border-top:1px solid var(--border);margin:20px 0">
            <div style="margin-bottom:10px;color:var(--gold);font-size:0.85rem;font-weight:600">Traductions Optionnelles</div>

            <div style="display:flex;gap:12px;margin-bottom:12px">
                <div style="flex:1">
                    <input type="text" id="tm-year-en" class="form-input" style="min-height:35px;height:35px" placeholder="Année (EN)">
                </div>
                <div style="flex:1">
                    <input type="text" id="tm-badge-en" class="form-input" style="min-height:35px;height:35px" placeholder="Badge (EN)">
                </div>
                <div style="flex:1">
                    <input type="text" id="tm-title-en" class="form-input" style="min-height:35px;height:35px" placeholder="Titre (EN)">
                </div>
            </div>
            <textarea id="tm-desc-en" class="form-input" style="min-height:50px;margin-bottom:16px" placeholder="Description (EN)"></textarea>

            <div style="display:flex;gap:12px;margin-bottom:12px">
                <div style="flex:1">
                    <input type="text" id="tm-year-ar" class="form-input" style="min-height:35px;height:35px" placeholder="Année (AR)" dir="rtl">
                </div>
                <div style="flex:1">
                    <input type="text" id="tm-badge-ar" class="form-input" style="min-height:35px;height:35px" placeholder="Badge (AR)" dir="rtl">
                </div>
                <div style="flex:1">
                    <input type="text" id="tm-title-ar" class="form-input" style="min-height:35px;height:35px" placeholder="Titre (AR)" dir="rtl">
                </div>
            </div>
            <textarea id="tm-desc-ar" class="form-input" style="min-height:50px;margin-bottom:16px" placeholder="Description (AR)" dir="rtl"></textarea>

            <div style="margin-bottom:16px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" id="tm-visible" checked>
                    <span style="font-size:0.9rem">Visible sur le site public</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeTmModal()">Annuler</button>
            <button class="btn btn-primary" onclick="saveTm()">Enregistrer</button>
        </div>
    </div>
</div>

<script>
let allTimeline = [];

async function loadTimeline() {
    const tbody = document.getElementById('timeline-tbody');
    const data = await Admin.api('timeline.php');
    if (data.error) return Admin.toast(data.error, 'error');

    allTimeline = data.timeline || [];
    
    if (allTimeline.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><p>Aucune étape dans le parcours.</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = allTimeline.map(t => {
        return `
            <tr>
                <td style="color:var(--text-muted);cursor:ns-resize">↕️</td>
                <td style="color:var(--accent);font-weight:600">${esc(t.year_fr)}</td>
                <td style="font-weight:600">${esc(t.title_fr)}</td>
                <td><span class="status status-unread">${esc(t.badge_fr)}</span></td>
                <td>
                    <span class="status ${t.is_visible ? 'status-read' : 'status-archived'}" style="cursor:pointer" onclick="toggleTmVis(${t.id}, ${t.is_visible})">
                        ${t.is_visible ? 'Visible' : 'Masqué'}
                    </span>
                </td>
                <td>
                    <div class="action-btns">
                        <button class="action-btn" onclick="openTmModal(${t.id})">✏️ Editer</button>
                        <button class="action-btn danger" onclick="deleteTm(${t.id})">🗑️</button>
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

function openTmModal(id = null) {
    document.getElementById('tm-modal-title').textContent = id ? 'Modifier l\'étape' : 'Nouvelle étape';
    
    if (id) {
        const t = allTimeline.find(x => x.id == id);
        document.getElementById('tm-id').value = t.id;
        document.getElementById('tm-year-fr').value = t.year_fr;
        document.getElementById('tm-badge-fr').value = t.badge_fr;
        document.getElementById('tm-title-fr').value = t.title_fr;
        document.getElementById('tm-desc-fr').value = t.desc_fr;

        document.getElementById('tm-year-en').value = t.year_en;
        document.getElementById('tm-badge-en').value = t.badge_en;
        document.getElementById('tm-title-en').value = t.title_en;
        document.getElementById('tm-desc-en').value = t.desc_en;

        document.getElementById('tm-year-ar').value = t.year_ar;
        document.getElementById('tm-badge-ar').value = t.badge_ar;
        document.getElementById('tm-title-ar').value = t.title_ar;
        document.getElementById('tm-desc-ar').value = t.desc_ar;

        document.getElementById('tm-visible').checked = t.is_visible == 1;
    } else {
        document.getElementById('tm-id').value = '';
        document.getElementById('tm-year-fr').value = '';
        document.getElementById('tm-badge-fr').value = '';
        document.getElementById('tm-title-fr').value = '';
        document.getElementById('tm-desc-fr').value = '';
        
        document.getElementById('tm-year-en').value = '';
        document.getElementById('tm-badge-en').value = '';
        document.getElementById('tm-title-en').value = '';
        document.getElementById('tm-desc-en').value = '';

        document.getElementById('tm-year-ar').value = '';
        document.getElementById('tm-badge-ar').value = '';
        document.getElementById('tm-title-ar').value = '';
        document.getElementById('tm-desc-ar').value = '';

        document.getElementById('tm-visible').checked = true;
    }
    
    document.getElementById('tm-modal').classList.add('active');
}

function closeTmModal() {
    document.getElementById('tm-modal').classList.remove('active');
}

async function saveTm() {
    const id = document.getElementById('tm-id').value;
    const body = {
        year_fr: document.getElementById('tm-year-fr').value,
        badge_fr: document.getElementById('tm-badge-fr').value,
        title_fr: document.getElementById('tm-title-fr').value,
        desc_fr: document.getElementById('tm-desc-fr').value,
        
        year_en: document.getElementById('tm-year-en').value,
        badge_en: document.getElementById('tm-badge-en').value,
        title_en: document.getElementById('tm-title-en').value,
        desc_en: document.getElementById('tm-desc-en').value,

        year_ar: document.getElementById('tm-year-ar').value,
        badge_ar: document.getElementById('tm-badge-ar').value,
        title_ar: document.getElementById('tm-title-ar').value,
        desc_ar: document.getElementById('tm-desc-ar').value,

        is_visible: document.getElementById('tm-visible').checked ? 1 : 0
    };

    if (!body.year_fr || !body.title_fr || !body.desc_fr || !body.badge_fr) {
        return Admin.toast('Remplissez les champs FR requis', 'error');
    }

    const method = id ? 'PUT' : 'POST';
    if (id) body.id = id;
    
    const data = await Admin.api('timeline.php', { method, body });
    if (data.success) {
        Admin.toast(data.message);
        closeTmModal();
        loadTimeline();
    } else {
        Admin.toast(data.error, 'error');
    }
}

async function toggleTmVis(id, currentVis) {
    const data = await Admin.api('timeline.php', { method: 'PUT', body: { id, is_visible: currentVis ? 0 : 1 } });
    if (data.success) loadTimeline();
}

async function deleteTm(id) {
    if (!Admin.confirm('Supprimer cette étape ?')) return;
    const data = await Admin.api(`timeline.php?id=${id}`, { method: 'DELETE' });
    if (data.success) {
        Admin.toast('Supprimé.');
        loadTimeline();
    }
}

loadTimeline();
</script>
