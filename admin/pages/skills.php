<div class="cms-module">
  <div class="cms-header">
    <div>
      <h2>⚡ Compétences Techniques</h2>
      <p class="cms-subtitle">Gérez vos technologies et niveaux de maîtrise</p>
    </div>
    <button class="btn btn-primary" onclick="openSkillModal()">+ Ajouter une compétence</button>
  </div>

  <div class="data-card">
    
    <div id="skills-table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px"></th>
                    <th>Groupe</th>
                    <th>Compétence</th>
                    <th>Niveau (%)</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="skills-tbody">
                <tr><td colspan="6"><div class="page-loader"><div class="spinner"></div></div></td></tr>
            </tbody>
        </table>
    </div>
  </div>
</div>

<!-- Modal Skill -->
<div class="modal-overlay" id="skill-modal">
    <div class="modal-panel" style="width: 500px">
        <div class="modal-header">
            <h3 id="skill-modal-title">Nouvelle compétence</h3>
            <button class="modal-close" onclick="closeSkillModal()">✕</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="skill-id">
            
            <div style="display:flex;gap:12px;margin-bottom:16px">
                <div style="flex:1">
                    <label class="meta-label">Groupe (FR)</label>
                    <input type="text" id="skill-group-fr" class="form-input" style="min-height:40px;height:40px" placeholder="ex: Frontend, Backend..." required>
                </div>
                <div style="width:60px">
                    <label class="meta-label">Icône</label>
                    <input type="text" id="skill-icon" class="form-input" style="min-height:40px;height:40px;text-align:center" placeholder="💻">
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-bottom:16px">
                <div style="flex:1">
                    <label class="meta-label">Groupe (EN)</label>
                    <input type="text" id="skill-group-en" class="form-input" style="min-height:40px;height:40px" placeholder="(Optionnel)">
                </div>
                <div style="flex:1">
                    <label class="meta-label">Groupe (AR)</label>
                    <input type="text" id="skill-group-ar" class="form-input" style="min-height:40px;height:40px" placeholder="(Optionnel)">
                </div>
            </div>

            <div style="margin-bottom:16px">
                <label class="meta-label">Nom de la compétence</label>
                <input type="text" id="skill-name" class="form-input" style="min-height:40px;height:40px" placeholder="ex: React, PHP, MySQL..." required>
            </div>

            <div style="margin-bottom:16px">
                <label class="meta-label">Niveau d'expertise : <span id="skill-perc-val" style="color:var(--accent);font-weight:bold">85</span>%</label>
                <input type="range" id="skill-percentage" min="0" max="100" value="85" style="width:100%;accent-color:var(--accent);margin-top:8px" oninput="document.getElementById('skill-perc-val').textContent=this.value">
            </div>

            <div style="margin-bottom:16px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" id="skill-visible" checked>
                    <span style="font-size:0.9rem">Visible sur le site public</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeSkillModal()">Annuler</button>
            <button class="btn btn-primary" onclick="saveSkill()">Enregistrer</button>
        </div>
    </div>
</div>

<script>
let allSkills = [];

async function loadSkills() {
    const tbody = document.getElementById('skills-tbody');
    const data = await Admin.api('skills.php');
    if (data.error) return Admin.toast(data.error, 'error');

    allSkills = data.skills || [];
    
    if (allSkills.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><p>Aucune compétence.</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = allSkills.map((s, index) => {
        return `
            <tr>
                <td style="color:var(--text-muted);cursor:ns-resize" title="Ordre actuel: ${s.sort_order}">↕️</td>
                <td><span style="margin-right:6px">${s.group_icon||''}</span> ${esc(s.group_name_fr)}</td>
                <td style="font-weight:600">${esc(s.skill_name)}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="flex:1;height:6px;background:var(--surface);border-radius:3px;overflow:hidden">
                            <div style="width:${s.percentage}%;height:100%;background:var(--accent)"></div>
                        </div>
                        <span style="font-size:0.8rem;width:30px">${s.percentage}%</span>
                    </div>
                </td>
                <td>
                    <span class="status ${s.is_visible ? 'status-read' : 'status-archived'}" style="cursor:pointer" onclick="toggleSkillVis(${s.id}, ${s.is_visible})">
                        ${s.is_visible ? 'Visible' : 'Masqué'}
                    </span>
                </td>
                <td>
                    <div class="action-btns">
                        <button class="action-btn" onclick="openSkillModal(${s.id})">✏️ Editer</button>
                        <button class="action-btn danger" onclick="deleteSkill(${s.id})">🗑️</button>
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

function openSkillModal(id = null) {
    document.getElementById('skill-modal-title').textContent = id ? 'Modifier la compétence' : 'Nouvelle compétence';
    
    if (id) {
        const s = allSkills.find(x => x.id == id);
        document.getElementById('skill-id').value = s.id;
        document.getElementById('skill-group-fr').value = s.group_name_fr;
        document.getElementById('skill-group-en').value = s.group_name_en;
        document.getElementById('skill-group-ar').value = s.group_name_ar;
        document.getElementById('skill-icon').value = s.group_icon;
        document.getElementById('skill-name').value = s.skill_name;
        document.getElementById('skill-percentage').value = s.percentage;
        document.getElementById('skill-perc-val').textContent = s.percentage;
        document.getElementById('skill-visible').checked = s.is_visible == 1;
    } else {
        document.getElementById('skill-id').value = '';
        document.getElementById('skill-group-fr').value = '';
        document.getElementById('skill-group-en').value = '';
        document.getElementById('skill-group-ar').value = '';
        document.getElementById('skill-icon').value = '';
        document.getElementById('skill-name').value = '';
        document.getElementById('skill-percentage').value = 85;
        document.getElementById('skill-perc-val').textContent = 85;
        document.getElementById('skill-visible').checked = true;
    }
    
    document.getElementById('skill-modal').classList.add('active');
}

function closeSkillModal() {
    document.getElementById('skill-modal').classList.remove('active');
}

async function saveSkill() {
    const id = document.getElementById('skill-id').value;
    const body = {
        group_name_fr: document.getElementById('skill-group-fr').value,
        group_name_en: document.getElementById('skill-group-en').value,
        group_name_ar: document.getElementById('skill-group-ar').value,
        group_icon: document.getElementById('skill-icon').value,
        skill_name: document.getElementById('skill-name').value,
        percentage: document.getElementById('skill-percentage').value,
        is_visible: document.getElementById('skill-visible').checked ? 1 : 0
    };

    if (!body.group_name_fr || !body.skill_name) return Admin.toast('Remplissez les champs requis', 'error');

    const method = id ? 'PUT' : 'POST';
    if (id) body.id = id;
    
    const data = await Admin.api('skills.php', { method, body });
    if (data.success) {
        Admin.toast(data.message);
        closeSkillModal();
        loadSkills();
    } else {
        Admin.toast(data.error, 'error');
    }
}

async function toggleSkillVis(id, currentVis) {
    const data = await Admin.api('skills.php', { method: 'PUT', body: { id, is_visible: currentVis ? 0 : 1 } });
    if (data.success) loadSkills();
}

async function deleteSkill(id) {
    if (!Admin.confirm('Supprimer cette compétence ?')) return;
    const data = await Admin.api(`skills.php?id=${id}`, { method: 'DELETE' });
    if (data.success) {
        Admin.toast('Supprimé.');
        loadSkills();
    }
}

loadSkills();
</script>
