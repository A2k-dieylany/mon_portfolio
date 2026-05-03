<!-- Projects Page Fragment -->
<div class="data-card">
    <div class="data-card-header">
        <h3>Projets Portfolio</h3>
        <button class="btn btn-primary" onclick="openProjModal()">+ Ajouter un projet</button>
    </div>
    
    <div id="projects-table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px"></th>
                    <th style="width: 80px">Image</th>
                    <th>Titre (FR)</th>
                    <th>Catégorie</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="projects-tbody">
                <tr><td colspan="6"><div class="page-loader"><div class="spinner"></div></div></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Project -->
<div class="modal-overlay" id="proj-modal">
    <div class="modal-panel" style="width: 800px">
        <div class="modal-header">
            <h3 id="proj-modal-title">Nouveau Projet</h3>
            <button class="modal-close" onclick="closeProjModal()">✕</button>
        </div>
        <div class="modal-body" style="display:flex;gap:24px">
            <input type="hidden" id="proj-id">
            
            <!-- Colonne Gauche : Infos Textuelles -->
            <div style="flex:1">
                <div style="margin-bottom:16px">
                    <label class="meta-label">Titre (FR) *</label>
                    <input type="text" id="proj-title-fr" class="notes-area" style="min-height:40px;height:40px" required>
                </div>
                <div style="display:flex;gap:12px;margin-bottom:16px">
                    <div style="flex:1">
                        <label class="meta-label">Titre (EN)</label>
                        <input type="text" id="proj-title-en" class="notes-area" style="min-height:40px;height:40px">
                    </div>
                    <div style="flex:1">
                        <label class="meta-label">Titre (AR)</label>
                        <input type="text" id="proj-title-ar" class="notes-area" style="min-height:40px;height:40px" dir="rtl">
                    </div>
                </div>

                <div style="margin-bottom:16px">
                    <label class="meta-label">Catégorie (FR) *</label>
                    <input type="text" id="proj-category-fr" class="notes-area" style="min-height:40px;height:40px" placeholder="ex: Application Web" required>
                </div>
                <div style="display:flex;gap:12px;margin-bottom:16px">
                    <div style="flex:1">
                        <label class="meta-label">Catégorie (EN)</label>
                        <input type="text" id="proj-category-en" class="notes-area" style="min-height:40px;height:40px">
                    </div>
                    <div style="flex:1">
                        <label class="meta-label">Catégorie (AR)</label>
                        <input type="text" id="proj-category-ar" class="notes-area" style="min-height:40px;height:40px" dir="rtl">
                    </div>
                </div>

                <div style="margin-bottom:16px">
                    <label class="meta-label">Description (FR)</label>
                    <textarea id="proj-desc-fr" class="notes-area" style="min-height:80px"></textarea>
                </div>

                <div style="display:flex;gap:12px;margin-bottom:16px">
                    <div style="flex:1">
                        <label class="meta-label">Client</label>
                        <input type="text" id="proj-client" class="notes-area" style="min-height:40px;height:40px">
                    </div>
                    <div style="flex:1">
                        <label class="meta-label">Date</label>
                        <input type="date" id="proj-date" class="notes-area" style="min-height:40px;height:40px">
                    </div>
                </div>

                <div style="display:flex;gap:12px;margin-bottom:16px">
                    <div style="flex:1">
                        <label class="meta-label">Lien Site (Live)</label>
                        <input type="url" id="proj-live" class="notes-area" style="min-height:40px;height:40px" placeholder="https://...">
                    </div>
                    <div style="flex:1">
                        <label class="meta-label">Lien GitHub</label>
                        <input type="url" id="proj-github" class="notes-area" style="min-height:40px;height:40px" placeholder="https://...">
                    </div>
                </div>

                <div style="margin-bottom:16px">
                    <label class="meta-label">Tags (séparés par des virgules)</label>
                    <input type="text" id="proj-tags" class="notes-area" style="min-height:40px;height:40px" placeholder="HTML, CSS, React">
                </div>

                <div style="margin-bottom:16px">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" id="proj-visible" checked>
                        <span style="font-size:0.9rem">Visible sur le site public</span>
                    </label>
                </div>
            </div>

            <!-- Colonne Droite : Images -->
            <div style="width: 250px; flex-shrink:0; border-left:1px solid var(--border); padding-left:24px">
                <div style="margin-bottom:24px">
                    <label class="meta-label">Image Principale</label>
                    <div id="main-img-preview" style="width:100%;height:150px;background:var(--surface);border:1px dashed var(--border);border-radius:8px;margin-bottom:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;background-size:cover;background-position:center">
                        <span style="color:var(--text-muted);font-size:2rem">🖼️</span>
                    </div>
                    <input type="hidden" id="proj-main-img">
                    <input type="file" id="file-main" accept="image/*" style="display:none" onchange="uploadImage(this, 'main')">
                    <button class="btn btn-ghost" style="width:100%" onclick="document.getElementById('file-main').click()">Upload Image</button>
                </div>

                <div>
                    <label class="meta-label">Galerie Supplémentaire</label>
                    <div id="gallery-container" style="display:flex;flex-direction:column;gap:8px;margin-bottom:8px">
                        <!-- Preview gallery images will go here -->
                    </div>
                    <input type="file" id="file-gallery" accept="image/*" multiple style="display:none" onchange="uploadGallery(this)">
                    <button class="btn btn-ghost" style="width:100%" onclick="document.getElementById('file-gallery').click()">+ Ajouter des images</button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeProjModal()">Annuler</button>
            <button class="btn btn-primary" id="btn-save-proj" onclick="saveProj()">Enregistrer</button>
        </div>
    </div>
</div>

<script>
let allProjects = [];
let galleryUrls = [];

async function loadProjects() {
    const tbody = document.getElementById('projects-tbody');
    const data = await Admin.api('projects.php');
    if (data.error) return Admin.toast(data.error, 'error');

    allProjects = data.projects || [];
    
    if (allProjects.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><p>Aucun projet.</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = allProjects.map(p => {
        const imgUrl = p.main_image ? '../' + p.main_image : '';
        const imgHtml = imgUrl ? `<div style="width:50px;height:35px;border-radius:4px;background:url('${imgUrl}') center/cover"></div>` : '—';
        return `
            <tr>
                <td style="color:var(--text-muted);cursor:ns-resize">↕️</td>
                <td>${imgHtml}</td>
                <td style="font-weight:600">${esc(p.title_fr)}</td>
                <td style="color:var(--text-dim)">${esc(p.category_fr)}</td>
                <td>
                    <span class="status ${p.is_visible ? 'status-read' : 'status-archived'}" style="cursor:pointer" onclick="toggleProjVis(${p.id}, ${p.is_visible})">
                        ${p.is_visible ? 'Visible' : 'Masqué'}
                    </span>
                </td>
                <td>
                    <div class="action-btns">
                        <button class="action-btn" onclick="openProjModal(${p.id})">✏️ Editer</button>
                        <button class="action-btn danger" onclick="deleteProj(${p.id})">🗑️</button>
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

async function uploadImage(inputElem, type) {
    if (!inputElem.files || inputElem.files.length === 0) return;
    
    const file = inputElem.files[0];
    const formData = new FormData();
    formData.append('image', file);

    Admin.toast('Upload en cours...', 'info');

    try {
        const res = await fetch(Admin.basePath + '/api/upload.php', {
            method: 'POST',
            body: formData // pas de JSON ici
        });
        const data = await res.json();
        
        if (data.success) {
            Admin.toast('Image uploadée !');
            if (type === 'main') {
                document.getElementById('proj-main-img').value = data.url;
                document.getElementById('main-img-preview').style.backgroundImage = `url('../${data.url}')`;
                document.getElementById('main-img-preview').innerHTML = '';
            }
        } else {
            Admin.toast(data.error || 'Erreur upload', 'error');
        }
    } catch (e) {
        Admin.toast('Erreur réseau', 'error');
    }
}

async function uploadGallery(inputElem) {
    if (!inputElem.files || inputElem.files.length === 0) return;
    
    for (let file of inputElem.files) {
        const formData = new FormData();
        formData.append('image', file);
        
        try {
            const res = await fetch(Admin.basePath + '/api/upload.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                galleryUrls.push(data.url);
            }
        } catch (e) {
            console.error('Gallery upload error', e);
        }
    }
    renderGallery();
}

function renderGallery() {
    const container = document.getElementById('gallery-container');
    container.innerHTML = galleryUrls.map((url, i) => `
        <div style="display:flex;align-items:center;gap:8px;background:var(--surface);padding:4px;border-radius:4px;border:1px solid var(--border)">
            <div style="width:40px;height:30px;background:url('../${url}') center/cover;border-radius:2px"></div>
            <div style="flex:1;font-size:0.7rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${url.split('/').pop()}</div>
            <button class="action-btn danger" style="padding:2px 6px" onclick="removeGalleryImg(${i})">✕</button>
        </div>
    `).join('');
}

function removeGalleryImg(index) {
    galleryUrls.splice(index, 1);
    renderGallery();
}

async function openProjModal(id = null) {
    document.getElementById('proj-modal-title').textContent = id ? 'Modifier le projet' : 'Nouveau projet';
    galleryUrls = [];
    
    // Reset image UI
    document.getElementById('main-img-preview').style.backgroundImage = '';
    document.getElementById('main-img-preview').innerHTML = '<span style="color:var(--text-muted);font-size:2rem">🖼️</span>';
    document.getElementById('proj-main-img').value = '';
    renderGallery();

    if (id) {
        // Fetch full project data including gallery
        const data = await Admin.api(`projects.php?id=${id}`);
        if (data.error) return Admin.toast(data.error, 'error');

        document.getElementById('proj-id').value = data.id;
        document.getElementById('proj-title-fr').value = data.title_fr || '';
        document.getElementById('proj-title-en').value = data.title_en || '';
        document.getElementById('proj-title-ar').value = data.title_ar || '';
        document.getElementById('proj-category-fr').value = data.category_fr || '';
        document.getElementById('proj-category-en').value = data.category_en || '';
        document.getElementById('proj-category-ar').value = data.category_ar || '';
        document.getElementById('proj-desc-fr').value = data.desc_fr || '';
        document.getElementById('proj-client').value = data.client_name || '';
        document.getElementById('proj-date').value = data.project_date || '';
        document.getElementById('proj-live').value = data.live_url || '';
        document.getElementById('proj-github').value = data.github_url || '';
        document.getElementById('proj-tags').value = data.tags || '';
        document.getElementById('proj-visible').checked = data.is_visible == 1;

        if (data.main_image) {
            document.getElementById('proj-main-img').value = data.main_image;
            document.getElementById('main-img-preview').style.backgroundImage = `url('../${data.main_image}')`;
            document.getElementById('main-img-preview').innerHTML = '';
        }

        if (data.gallery && data.gallery.length > 0) {
            galleryUrls = data.gallery.map(g => g.image_url);
            renderGallery();
        }

    } else {
        // Reset inputs
        document.getElementById('proj-id').value = '';
        document.querySelectorAll('#proj-modal input[type="text"], #proj-modal input[type="url"], #proj-modal input[type="date"], #proj-modal textarea').forEach(el => el.value = '');
        document.getElementById('proj-visible').checked = true;
    }
    
    document.getElementById('proj-modal').classList.add('active');
}

function closeProjModal() {
    document.getElementById('proj-modal').classList.remove('active');
}

async function saveProj() {
    const btn = document.getElementById('btn-save-proj');
    btn.textContent = 'Enregistrement...';
    btn.disabled = true;

    const id = document.getElementById('proj-id').value;
    const body = {
        title_fr: document.getElementById('proj-title-fr').value,
        title_en: document.getElementById('proj-title-en').value,
        title_ar: document.getElementById('proj-title-ar').value,
        category_fr: document.getElementById('proj-category-fr').value,
        category_en: document.getElementById('proj-category-en').value,
        category_ar: document.getElementById('proj-category-ar').value,
        desc_fr: document.getElementById('proj-desc-fr').value,
        client_name: document.getElementById('proj-client').value,
        project_date: document.getElementById('proj-date').value,
        live_url: document.getElementById('proj-live').value,
        github_url: document.getElementById('proj-github').value,
        tags: document.getElementById('proj-tags').value,
        main_image: document.getElementById('proj-main-img').value,
        is_visible: document.getElementById('proj-visible').checked ? 1 : 0,
        gallery: galleryUrls
    };

    if (!body.title_fr || !body.category_fr) {
        btn.textContent = 'Enregistrer'; btn.disabled = false;
        return Admin.toast('Le Titre et la Catégorie (FR) sont requis.', 'error');
    }

    const method = id ? 'PUT' : 'POST';
    if (id) body.id = id;
    
    const data = await Admin.api('projects.php', { method, body });
    if (data.success) {
        Admin.toast(data.message);
        closeProjModal();
        loadProjects();
    } else {
        Admin.toast(data.error, 'error');
    }
    
    btn.textContent = 'Enregistrer'; btn.disabled = false;
}

async function toggleProjVis(id, currentVis) {
    const data = await Admin.api('projects.php', { method: 'PUT', body: { id, is_visible: currentVis ? 0 : 1 } });
    if (data.success) loadProjects();
}

async function deleteProj(id) {
    if (!Admin.confirm('Supprimer ce projet ? Cette action est irréversible.')) return;
    const data = await Admin.api(`projects.php?id=${id}`, { method: 'DELETE' });
    if (data.success) {
        Admin.toast('Projet supprimé.');
        loadProjects();
    }
}

loadProjects();
</script>
