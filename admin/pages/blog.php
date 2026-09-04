<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_auth();
?>
<div class="cms-module">
  <div class="cms-header">
      <div>
        <h2>✍️ Blog & Publications</h2>
        <p class="cms-subtitle">Gérez les articles de votre blog trilingue</p>
      </div>
      <button class="btn btn-primary" onclick="openBlogModal()">+ Nouvel Article</button>
  </div>

  <div class="data-card">
    <div id="blog-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Emoji</th>
                    <th>Titre (FR)</th>
                    <th>Catégorie</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th style="width:60px;text-align:center">Ordre</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody id="blog-tbody">
                <tr><td colspan="7" style="text-align:center;">Chargement...</td></tr>
            </tbody>
        </table>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="blog-modal">
    <div class="modal-panel" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="blog-modal-title">Nouvel Article</h3>
            <button class="modal-close" onclick="closeBlogModal()">✕</button>
        </div>
        <form id="blog-form" onsubmit="saveBlog(event)">
            <div class="modal-body">
                <input type="hidden" id="blog-id" name="id">
            
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>Emoji 🎯</label>
                    <input type="text" id="blog-emoji" name="emoji" class="form-input" placeholder="ex: 🌍" required>
                </div>
                <div class="form-group" style="flex:2">
                    <label>Date de publication</label>
                    <input type="date" id="blog-date" name="publish_date" class="form-input" required>
                </div>
                <div class="form-group" style="flex:2">
                    <label>Temps de lecture</label>
                    <input type="text" id="blog-read-time" name="read_time" class="form-input" placeholder="ex: 4 min" required>
                </div>
            </div>

            <div class="form-group">
                <label>Lien externe (LinkedIn, Medium, etc.)</label>
                <input type="url" id="blog-url" name="external_url" class="form-input" placeholder="https://..." required>
            </div>

            <h4>🌍 Contenu Trilingue</h4>
            <div class="form-row">
                <div class="form-group">
                    <label>Catégorie (FR) 🇫🇷</label>
                    <input type="text" id="blog-cat-fr" name="category_fr" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Catégorie (EN) 🇬🇧</label>
                    <input type="text" id="blog-cat-en" name="category_en" class="form-input">
                </div>
                <div class="form-group">
                    <label>Catégorie (AR) 🇦🇪</label>
                    <input type="text" id="blog-cat-ar" name="category_ar" class="form-input" dir="rtl">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Titre (FR) 🇫🇷</label>
                    <input type="text" id="blog-title-fr" name="title_fr" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Titre (EN) 🇬🇧</label>
                    <input type="text" id="blog-title-en" name="title_en" class="form-input">
                </div>
                <div class="form-group">
                    <label>Titre (AR) 🇦🇪</label>
                    <input type="text" id="blog-title-ar" name="title_ar" class="form-input" dir="rtl">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Extrait (FR) 🇫🇷</label>
                    <textarea id="blog-excerpt-fr" name="excerpt_fr" class="form-input" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Extrait (EN) 🇬🇧</label>
                    <textarea id="blog-excerpt-en" name="excerpt_en" class="form-input" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Extrait (AR) 🇦🇪</label>
                    <textarea id="blog-excerpt-ar" name="excerpt_ar" class="form-input" rows="3" dir="rtl"></textarea>
                </div>
            </div>

            <div class="form-row" style="align-items: center;">
                <div class="form-group" style="flex:1">
                    <label>Ordre d'affichage</label>
                    <input type="number" id="blog-sort" name="sort_order" class="form-input" value="0">
                </div>
                <div class="form-group" style="flex:1">
                    <label class="toggle-switch" style="margin-top: 1.5rem; display: inline-flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="blog-visible" name="is_visible" value="1" checked>
                        <span class="toggle-slider"></span>
                        Visible sur le site
                    </label>
                </div>
            </div>
          </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeBlogModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentBlogs = [];

async function loadBlogs() {
    try {
        const data = await Admin.api('blog.php?action=list');
        currentBlogs = data.posts || [];
        const tbody = document.getElementById('blog-tbody');
        tbody.innerHTML = '';
        if(currentBlogs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Aucun article trouvé.</td></tr>';
            return;
        }
        currentBlogs.forEach(p => {
            const isVisible = p.is_visible == 1;
            const statusLabel = isVisible ? 'Visible' : 'Caché';
            const statusClass = isVisible ? 'status-read' : 'status-archived';
            const statusColor = isVisible ? 'var(--green)' : 'inherit';
            
            tbody.innerHTML += `
                <tr style="transition:all 0.2s">
                    <td style="font-size:1.5rem;text-align:center">${p.emoji}</td>
                    <td style="font-weight:600;color:var(--text)">${p.title_fr}</td>
                    <td style="color:rgba(255,255,255,0.5)">${p.category_fr}</td>
                    <td style="color:rgba(255,255,255,0.4);font-size:0.8rem">${p.publish_date}</td>
                    <td>
                        <span class="status ${statusClass}" style="opacity:0.9">
                            <span style="color:${statusColor}">●</span> ${statusLabel}
                        </span>
                    </td>
                    <td style="text-align:center;color:var(--text-dim)">${p.sort_order}</td>
                    <td style="text-align:right">
                        <div class="action-btns" style="justify-content:flex-end">
                            <button class="action-btn" onclick="editBlog(${p.id})" title="Modifier">✏️</button>
                            <button class="action-btn danger" onclick="deleteBlog(${p.id})" title="Supprimer">🗑️</button>
                        </div>
                    </td>
                </tr>
            `;
        });
    } catch(e) {
        Admin.toast('Erreur de chargement', 'error');
    }
}

function openBlogModal() {
    document.getElementById('blog-form').reset();
    document.getElementById('blog-id').value = '';
    document.getElementById('blog-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('blog-modal-title').textContent = 'Nouvel Article';
    document.getElementById('blog-modal').classList.add('active');
}

function closeBlogModal() {
    document.getElementById('blog-modal').classList.remove('active');
}

function editBlog(id) {
    const p = currentBlogs.find(x => x.id == id);
    if(!p) return;
    
    document.getElementById('blog-id').value = p.id;
    document.getElementById('blog-emoji').value = p.emoji;
    document.getElementById('blog-date').value = p.publish_date;
    document.getElementById('blog-read-time').value = p.read_time;
    document.getElementById('blog-url').value = p.external_url;
    
    document.getElementById('blog-cat-fr').value = p.category_fr;
    document.getElementById('blog-cat-en').value = p.category_en;
    document.getElementById('blog-cat-ar').value = p.category_ar;
    
    document.getElementById('blog-title-fr').value = p.title_fr;
    document.getElementById('blog-title-en').value = p.title_en;
    document.getElementById('blog-title-ar').value = p.title_ar;
    
    document.getElementById('blog-excerpt-fr').value = p.excerpt_fr;
    document.getElementById('blog-excerpt-en').value = p.excerpt_en;
    document.getElementById('blog-excerpt-ar').value = p.excerpt_ar;
    
    document.getElementById('blog-sort').value = p.sort_order;
    document.getElementById('blog-visible').checked = p.is_visible == 1;
    
    document.getElementById('blog-modal-title').textContent = "Modifier l'Article";
    document.getElementById('blog-modal').classList.add('active');
}

async function saveBlog(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        await Admin.api('blog.php?action=save', {
            method: 'POST',
            body: formData
        });
        Admin.toast('Article enregistré avec succès');
        closeBlogModal();
        loadBlogs();
    } catch(err) {
        Admin.toast(err.message, 'error');
    }
}

async function deleteBlog(id) {
    if(confirm('Supprimer cet article ?')) {
        const fd = new FormData();
        fd.append('id', id);
        try {
            await Admin.api('blog.php?action=delete', {
                method: 'POST',
                body: fd
            });
            Admin.toast('Article supprimé');
            loadBlogs();
        } catch(e) {
            Admin.toast(e.message, 'error');
        }
    }
}

// Initialisation
loadBlogs();
</script>
