<?php
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_auth();
?>
<div class="cms-module">
  <div class="cms-header">
      <div>
        <h2>⭐ Témoignages & Avis</h2>
        <p class="cms-subtitle">Gérez les retours clients et avis de votre portfolio</p>
      </div>
      <button class="btn btn-primary" onclick="openTestimonialModal()">+ Nouvel Avis</button>
  </div>

  <div class="data-card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Init.</th>
                    <th>Rôle / Entreprise (FR)</th>
                    <th>Étoiles</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="testi-tbody">
                <tr><td colspan="6" style="text-align:center;">Chargement...</td></tr>
            </tbody>
        </table>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="testi-modal">
    <div class="modal-panel" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="testi-modal-title">Nouvel Avis</h3>
            <button class="modal-close" onclick="closeTestimonialModal()">✕</button>
        </div>
        <form id="testi-form" onsubmit="saveTestimonial(event)">
            <div class="modal-body">
                <input type="hidden" id="testi-id" name="id">
            
            <div class="form-row">
                <div class="form-group" style="flex:2">
                    <label>Nom du client</label>
                    <input type="text" id="testi-name" name="client_name" class="form-input" required>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Initiales (optionnel)</label>
                    <input type="text" id="testi-initials" name="client_initials" class="form-input" placeholder="ex: JD">
                </div>
                <div class="form-group" style="flex:1">
                    <label>Étoiles (1 à 5)</label>
                    <input type="number" id="testi-stars" name="stars" class="form-input" value="5" min="1" max="5" required>
                </div>
            </div>

            <h4>🌍 Rôle / Entreprise</h4>
            <div class="form-row">
                <div class="form-group">
                    <label>Rôle (FR) 🇫🇷</label>
                    <input type="text" id="testi-role-fr" name="role_fr" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Rôle (EN) 🇬🇧</label>
                    <input type="text" id="testi-role-en" name="role_en" class="form-input">
                </div>
                <div class="form-group">
                    <label>Rôle (AR) 🇦🇪</label>
                    <input type="text" id="testi-role-ar" name="role_ar" class="form-input" dir="rtl">
                </div>
            </div>
            
            <h4>💬 Témoignage</h4>
            <div class="form-row">
                <div class="form-group">
                    <label>Texte (FR) 🇫🇷</label>
                    <textarea id="testi-text-fr" name="text_fr" class="form-input" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Texte (EN) 🇬🇧</label>
                    <textarea id="testi-text-en" name="text_en" class="form-input" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label>Texte (AR) 🇦🇪</label>
                    <textarea id="testi-text-ar" name="text_ar" class="form-input" rows="4" dir="rtl"></textarea>
                </div>
            </div>

            <div class="form-row" style="align-items: center;">
                <div class="form-group">
                    <label class="toggle-switch" style="display: inline-flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="testi-visible" name="is_visible" value="1" checked>
                        <span class="toggle-slider"></span>
                        Visible sur le site
                    </label>
                </div>
            </div>
          </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeTestimonialModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentTestis = [];

async function loadTestimonials() {
    try {
        const data = await Admin.api('api/testimonials.php?action=list');
        currentTestis = data.testimonials || [];
        const tbody = document.getElementById('testi-tbody');
        tbody.innerHTML = '';
        if(currentTestis.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Aucun témoignage trouvé.</td></tr>';
            return;
        }
        currentTestis.forEach(t => {
            const status = t.is_visible ? '<span class="status-badge status-read">Visible</span>' : '<span class="status-badge status-unread">Caché</span>';
            const stars = '⭐'.repeat(t.stars);
            tbody.innerHTML += `
                <tr>
                    <td><strong>${t.client_name}</strong></td>
                    <td><div class="sidebar-avatar" style="width:30px;height:30px;font-size:0.8rem;margin:0;">${t.client_initials}</div></td>
                    <td>${t.role_fr}</td>
                    <td>${stars}</td>
                    <td>${status}</td>
                    <td class="action-btns">
                        <button class="action-btn" onclick="editTestimonial(${t.id})" title="Modifier">✏️</button>
                        <button class="action-btn danger" onclick="deleteTestimonial(${t.id})" title="Supprimer">🗑️</button>
                    </td>
                </tr>
            `;
        });
    } catch(e) {
        Admin.toast('Erreur de chargement', 'error');
    }
}

function openTestimonialModal() {
    document.getElementById('testi-form').reset();
    document.getElementById('testi-id').value = '';
    document.getElementById('testi-stars').value = '5';
    document.getElementById('testi-modal-title').textContent = 'Nouvel Avis';
    document.getElementById('testi-modal').classList.add('active');
}

function closeTestimonialModal() {
    document.getElementById('testi-modal').classList.remove('active');
}

function editTestimonial(id) {
    const t = currentTestis.find(x => x.id == id);
    if(!t) return;
    
    document.getElementById('testi-id').value = t.id;
    document.getElementById('testi-name').value = t.client_name;
    document.getElementById('testi-initials').value = t.client_initials;
    document.getElementById('testi-stars').value = t.stars;
    
    document.getElementById('testi-role-fr').value = t.role_fr;
    document.getElementById('testi-role-en').value = t.role_en;
    document.getElementById('testi-role-ar').value = t.role_ar;
    
    document.getElementById('testi-text-fr').value = t.text_fr;
    document.getElementById('testi-text-en').value = t.text_en;
    document.getElementById('testi-text-ar').value = t.text_ar;
    
    document.getElementById('testi-visible').checked = t.is_visible == 1;
    
    document.getElementById('testi-modal-title').textContent = 'Modifier l\\'Avis';
    document.getElementById('testi-modal').classList.add('active');
}

async function saveTestimonial(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        await Admin.api('api/testimonials.php?action=save', {
            method: 'POST',
            body: formData
        });
        Admin.toast('Avis enregistré avec succès');
        closeTestimonialModal();
        loadTestimonials();
    } catch(err) {
        Admin.toast(err.message, 'error');
    }
}

async function deleteTestimonial(id) {
    if(confirm('Supprimer cet avis ?')) {
        const fd = new FormData();
        fd.append('id', id);
        try {
            await Admin.api('api/testimonials.php?action=delete', {
                method: 'POST',
                body: fd
            });
            Admin.toast('Avis supprimé');
            loadTestimonials();
        } catch(e) {
            Admin.toast(e.message, 'error');
        }
    }
}

// Initialisation
loadTestimonials();
</script>
