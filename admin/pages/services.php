<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_auth();
?>
<div class="cms-module">
  <div class="cms-header">
    <div>
      <h2><span class="h2-icon" aria-hidden="true">🛠️</span> Services de l'Agence</h2>
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
    <div class="modal-panel" style="width: 860px">
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

            <!-- Page détaillée /services/<slug> : ces champs existaient en base
                 mais n'étaient pas modifiables depuis l'admin. -->
            <h4 class="srv-section">Page détaillée</h4>

            <div style="margin-bottom:16px">
                <label class="meta-label" for="srv-slug">Adresse de la page</label>
                <div class="srv-slug-row">
                    <span class="srv-slug-prefix">dieylany.dev/services/</span>
                    <input type="text" id="srv-slug" class="form-input" style="min-height:40px;height:40px"
                           placeholder="ex : developpement-web" oninput="updateSlugPreview()">
                </div>
                <small class="srv-help" id="srv-slug-help">Vide : pas de page détaillée pour ce service.</small>
            </div>

            <div style="margin-bottom:16px">
                <label class="meta-label" for="srv-headline-fr">Accroche — titre de la page (FR)</label>
                <input type="text" id="srv-headline-fr" class="form-input" style="min-height:40px;height:40px" maxlength="200"
                       placeholder="ex : Un site que vos clients trouvent vraiment">
            </div>

            <div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap">
                <div style="flex:1;min-width:200px">
                    <label class="meta-label" for="srv-price-from">Tarif affiché (FR)</label>
                    <input type="text" id="srv-price-from" class="form-input" style="min-height:40px;height:40px" maxlength="60"
                           placeholder="ex : À partir de 150 000 FCFA">
                </div>
                <div style="flex:1;min-width:200px">
                    <label class="meta-label" for="srv-delay">Délai (FR)</label>
                    <input type="text" id="srv-delay" class="form-input" style="min-height:40px;height:40px" maxlength="120"
                           placeholder="ex : 1 à 4 semaines selon le projet">
                </div>
            </div>

            <div style="margin-bottom:12px">
                <label class="meta-label" for="srv-detail-fr">Contenu de la page (FR)</label>
                <textarea id="srv-detail-fr" class="form-input srv-html" rows="14"
                          placeholder="<h2>Ce que vous obtenez</h2>&#10;<ul>&#10;  <li>…</li>&#10;</ul>"></textarea>
                <small class="srv-help">HTML accepté : &lt;h2&gt;, &lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;/&lt;ol&gt;/&lt;li&gt;,
                    &lt;strong&gt;, &lt;em&gt;, &lt;a&gt;, &lt;div class="…"&gt;. Toute autre balise, les scripts
                    et les attributs d'événement sont retirés à l'enregistrement.</small>
            </div>

            <details class="srv-translations">
                <summary>Traductions de la page — anglais et arabe</summary>
                <div style="display:flex;gap:12px;margin:12px 0;flex-wrap:wrap">
                    <div style="flex:1;min-width:200px">
                        <label class="meta-label" for="srv-headline-en">Accroche (EN)</label>
                        <input type="text" id="srv-headline-en" class="form-input" style="min-height:40px;height:40px" maxlength="200">
                    </div>
                    <div style="flex:1;min-width:200px">
                        <label class="meta-label" for="srv-headline-ar">Accroche (AR)</label>
                        <input type="text" id="srv-headline-ar" class="form-input" style="min-height:40px;height:40px" maxlength="200" dir="rtl">
                    </div>
                </div>
                <div style="display:flex;gap:12px;margin-bottom:12px;flex-wrap:wrap">
                    <div style="flex:1;min-width:200px">
                        <label class="meta-label" for="srv-price-from-en">Tarif (EN)</label>
                        <input type="text" id="srv-price-from-en" class="form-input" style="min-height:40px;height:40px" maxlength="60">
                    </div>
                    <div style="flex:1;min-width:200px">
                        <label class="meta-label" for="srv-price-from-ar">Tarif (AR)</label>
                        <input type="text" id="srv-price-from-ar" class="form-input" style="min-height:40px;height:40px" maxlength="60" dir="rtl">
                    </div>
                </div>
                <div style="display:flex;gap:12px;margin-bottom:12px;flex-wrap:wrap">
                    <div style="flex:1;min-width:200px">
                        <label class="meta-label" for="srv-delay-en">Délai (EN)</label>
                        <input type="text" id="srv-delay-en" class="form-input" style="min-height:40px;height:40px" maxlength="120">
                    </div>
                    <div style="flex:1;min-width:200px">
                        <label class="meta-label" for="srv-delay-ar">Délai (AR)</label>
                        <input type="text" id="srv-delay-ar" class="form-input" style="min-height:40px;height:40px" maxlength="120" dir="rtl">
                    </div>
                </div>
                <div style="margin-bottom:12px">
                    <label class="meta-label" for="srv-detail-en">Contenu de la page (EN)</label>
                    <textarea id="srv-detail-en" class="form-input srv-html" rows="10"></textarea>
                </div>
                <div style="margin-bottom:12px">
                    <label class="meta-label" for="srv-detail-ar">Contenu de la page (AR)</label>
                    <textarea id="srv-detail-ar" class="form-input srv-html" rows="10" dir="rtl"></textarea>
                </div>
                <small class="srv-help">Une traduction laissée vide affiche la version française.</small>
            </details>

            <div style="margin:16px 0">
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


<style>
  .srv-section { margin: 26px 0 14px; padding-top: 18px; border-top: 1px solid var(--border);
                 font-size: .95rem; color: var(--text); }
  .srv-slug-row { display: flex; align-items: center; gap: 8px; }
  .srv-slug-prefix { color: var(--text-muted); font-size: .85rem; white-space: nowrap; }
  .srv-help { display: block; margin-top: 6px; color: var(--text-muted); font-size: .78rem; line-height: 1.5; }
  .srv-help a, .srv-meta a { color: var(--accent-light); }
  .srv-html { font-family: ui-monospace, Consolas, monospace; font-size: .82rem; line-height: 1.55; min-height: 160px; }
  .srv-translations { margin: 8px 0 4px; }
  .srv-translations summary { cursor: pointer; color: var(--accent-light); font-size: .88rem; padding: 6px 0; }
  .srv-meta { font-weight: 400; font-size: .75rem; color: var(--text-muted); margin-top: 4px; }
  @media (max-width: 640px) {
    .srv-slug-row { flex-direction: column; align-items: stretch; gap: 4px; }
  }
</style>
<script>
var allServices = [];

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
                <td style="font-size:1.5rem">${Admin.esc(s.icon)}</td>
                <td style="font-weight:600">${esc(s.title_fr)}${s.slug ? `<div class="srv-meta">${s.price_from ? esc(s.price_from) + ' · ' : ''}<a href="/services/${encodeURIComponent(s.slug)}" target="_blank" rel="noopener">/services/${esc(s.slug)} ↗</a></div>` : '<div class="srv-meta">Pas de page détaillée</div>'}</td>
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

// Champ en base → identifiant de l'élément du formulaire.
var SRV_FIELDS = {
    icon: 'srv-icon', title_fr: 'srv-title-fr', title_en: 'srv-title-en', title_ar: 'srv-title-ar',
    desc_fr: 'srv-desc-fr', desc_en: 'srv-desc-en', desc_ar: 'srv-desc-ar', tags: 'srv-tags',
    slug: 'srv-slug',
    headline_fr: 'srv-headline-fr', headline_en: 'srv-headline-en', headline_ar: 'srv-headline-ar',
    price_from: 'srv-price-from', price_from_en: 'srv-price-from-en', price_from_ar: 'srv-price-from-ar',
    delay_text: 'srv-delay', delay_text_en: 'srv-delay-en', delay_text_ar: 'srv-delay-ar',
    detail_fr: 'srv-detail-fr', detail_en: 'srv-detail-en', detail_ar: 'srv-detail-ar'
};

function updateSlugPreview() {
    const slug = document.getElementById('srv-slug').value.trim();
    const help = document.getElementById('srv-slug-help');
    help.innerHTML = slug
        ? `Page publique : <a href="/services/${encodeURIComponent(slug)}" target="_blank" rel="noopener">/services/${Admin.esc(slug)} ↗</a>`
        : 'Vide : pas de page détaillée pour ce service.';
}

function openSrvModal(id = null) {
    document.getElementById('srv-modal-title').textContent = id ? 'Modifier le service' : 'Nouveau service';
    const s = id ? allServices.find(x => x.id == id) : null;

    document.getElementById('srv-id').value = s ? s.id : '';
    for (const [field, elId] of Object.entries(SRV_FIELDS)) {
        document.getElementById(elId).value = s ? (s[field] ?? '') : '';
    }
    document.getElementById('srv-visible').checked = s ? s.is_visible == 1 : true;

    // Les traductions de la page restent repliées sauf si elles existent déjà.
    const tr = document.querySelector('#srv-modal .srv-translations');
    if (tr) tr.open = !!(s && (s.detail_en || s.detail_ar || s.headline_en || s.headline_ar));
    updateSlugPreview();

    document.getElementById('srv-modal').classList.add('active');
}

function closeSrvModal() {
    document.getElementById('srv-modal').classList.remove('active');
}

async function saveSrv() {
    const id = document.getElementById('srv-id').value;
    const body = { is_visible: document.getElementById('srv-visible').checked ? 1 : 0 };
    for (const [field, elId] of Object.entries(SRV_FIELDS)) {
        body[field] = document.getElementById(elId).value;
    }

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
    if (!data.success) Admin.fail(data);
}

async function deleteSrv(id) {
    if (!Admin.confirm('Supprimer ce service ?')) return;
    const data = await Admin.api(`services.php?id=${id}`, { method: 'DELETE' });
    if (data.success) {
        Admin.toast('Supprimé.');
        loadServices();
    }
    if (!data.success) Admin.fail(data);
}

loadServices();
</script>
