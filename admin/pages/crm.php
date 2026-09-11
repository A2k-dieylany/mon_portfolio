<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_auth();
?>
<div class="cms-module">
  <div class="cms-header">
    <div>
      <h2><span class="h2-icon" aria-hidden="true">🤝</span> Prospects &amp; Clients</h2>
      <p class="cms-subtitle">Toutes vos demandes, de la première prise de contact à la signature</p>
    </div>
    <button class="btn btn-primary" onclick="openDeal()">+ Nouvelle opportunité</button>
  </div>

  <div class="kpi-grid" id="crm-kpis"></div>

  <div class="data-card" style="margin-top:20px">
    <div class="data-card-header">
      <input type="search" id="crm-search" class="search-input"
             placeholder="Rechercher un nom, une entreprise, un numéro…"
             oninput="renderPipeline()" aria-label="Rechercher dans les opportunités">
    </div>
    <div id="crm-pipeline" class="crm-pipeline">
      <div class="empty-state"><p>Chargement…</p></div>
    </div>
  </div>
</div>

<!-- Fiche d'une opportunité -->
<div class="modal-overlay" id="crm-modal">
  <div class="modal-panel" style="max-width:860px">
    <div class="modal-header">
      <h3 id="crm-modal-title">Opportunité</h3>
      <button class="modal-close" onclick="closeDeal()" aria-label="Fermer">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="crm-id">

      <div class="form-row">
        <div class="form-group" style="flex:2">
          <label for="crm-title">Objet de la demande</label>
          <input type="text" id="crm-title" class="form-input" required
                 placeholder="ex : Site vitrine pour une pharmacie">
        </div>
        <div class="form-group" style="flex:1">
          <label for="crm-stage">Étape</label>
          <select id="crm-stage" class="form-input">
            <option value="nouveau">Nouveau</option>
            <option value="contacte">Contacté</option>
            <option value="devis">Devis envoyé</option>
            <option value="gagne">Gagné</option>
            <option value="perdu">Perdu</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex:2">
          <label for="crm-contact-name">Nom du contact</label>
          <input type="text" id="crm-contact-name" class="form-input">
        </div>
        <div class="form-group" style="flex:2">
          <label for="crm-contact-company">Entreprise</label>
          <input type="text" id="crm-contact-company" class="form-input">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex:1">
          <label for="crm-contact-phone">Téléphone / WhatsApp</label>
          <input type="tel" id="crm-contact-phone" class="form-input" placeholder="+221 7…">
        </div>
        <div class="form-group" style="flex:1">
          <label for="crm-contact-email">E-mail</label>
          <input type="email" id="crm-contact-email" class="form-input">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex:1">
          <label for="crm-source">Origine</label>
          <select id="crm-source" class="form-input">
            <option value="chatbot">Chatbot MAX</option>
            <option value="formulaire">Formulaire du site</option>
            <option value="whatsapp">WhatsApp</option>
            <option value="recommandation">Recommandation</option>
            <option value="direct">Contact direct</option>
            <option value="autre">Autre</option>
          </select>
        </div>
        <div class="form-group" style="flex:1">
          <label for="crm-amount">Montant estimé (FCFA)</label>
          <input type="number" id="crm-amount" class="form-input" min="0" step="1000">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex:2">
          <label for="crm-next-action">Prochaine action</label>
          <input type="text" id="crm-next-action" class="form-input"
                 placeholder="ex : rappeler pour valider le périmètre">
        </div>
        <div class="form-group" style="flex:1">
          <label for="crm-next-action-at">Pour le</label>
          <input type="date" id="crm-next-action-at" class="form-input">
        </div>
      </div>

      <div class="form-group" id="crm-lost-wrap" style="display:none">
        <label for="crm-lost-reason">Raison de la perte</label>
        <input type="text" id="crm-lost-reason" class="form-input"
               placeholder="ex : budget trop élevé, projet reporté…">
      </div>

      <div class="form-group">
        <label for="crm-summary">La demande</label>
        <textarea id="crm-summary" class="form-input" rows="5"
                  placeholder="Ce que le prospect a demandé, tel qu'il l'a formulé"></textarea>
      </div>

      <div id="crm-history-wrap" style="display:none">
        <h4 style="margin:24px 0 12px;font-size:.9rem;color:var(--text-muted)">Historique</h4>
        <div class="form-row">
          <div class="form-group" style="flex:1">
            <select id="crm-activity-kind" class="form-input">
              <option value="note">Note</option>
              <option value="appel">Appel</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="email">E-mail</option>
              <option value="rdv">Rendez-vous</option>
            </select>
          </div>
          <div class="form-group" style="flex:3">
            <input type="text" id="crm-activity-body" class="form-input"
                   placeholder="Ce qui s'est dit…" onkeydown="if(event.key==='Enter')addActivity()">
          </div>
          <div class="form-group" style="flex:0 0 auto;display:flex;align-items:flex-end">
            <button class="btn btn-ghost" onclick="addActivity()">Ajouter</button>
          </div>
        </div>
        <div id="crm-activities"></div>
      </div>
    </div>
    <div class="modal-footer" style="display:flex;justify-content:space-between;gap:12px">
      <button class="btn btn-danger" id="crm-delete" onclick="deleteDeal()" style="display:none">Supprimer</button>
      <div style="display:flex;gap:12px;margin-left:auto">
        <button class="btn btn-ghost" onclick="closeDeal()">Annuler</button>
        <button class="btn btn-primary" onclick="saveDeal()">Enregistrer</button>
      </div>
    </div>
  </div>
</div>

<style>
  .crm-pipeline { display:flex; gap:14px; overflow-x:auto; padding:16px; align-items:flex-start; }
  .crm-col { flex:0 0 265px; background:rgba(255,255,255,.02);
             border:1px solid var(--border); border-radius:14px; padding:12px; }
  .crm-col-head { display:flex; justify-content:space-between; align-items:center;
                  font-size:.78rem; font-weight:700; letter-spacing:.03em;
                  text-transform:uppercase; color:var(--text-muted); margin-bottom:10px; }
  .crm-col-count { background:rgba(255,255,255,.06); border-radius:999px; padding:1px 8px; }
  .crm-card { background:var(--card); border:1px solid var(--border); border-radius:11px;
              padding:12px; margin-bottom:9px; cursor:pointer; transition:var(--transition); }
  .crm-card:hover { border-color:var(--accent); transform:translateY(-1px); }
  .crm-card-title { font-weight:600; font-size:.86rem; margin-bottom:5px; line-height:1.35; }
  .crm-card-meta { font-size:.75rem; color:var(--text-muted); display:flex;
                   flex-wrap:wrap; gap:4px 10px; }
  .crm-amount { color:var(--green); font-weight:600; }
  /* Une relance en retard doit sauter aux yeux : c'est de l'argent qui dort. */
  .crm-late { color:var(--red); font-weight:600; }
  .crm-empty { font-size:.78rem; color:var(--text-dim); padding:10px 2px; }
  .crm-activity { border-left:2px solid var(--border); padding:8px 0 8px 12px; margin-bottom:6px; }
  .crm-activity-meta { font-size:.72rem; color:var(--text-dim); margin-bottom:3px; }
  .crm-activity-body { font-size:.85rem; white-space:pre-wrap; }
  @media (max-width:820px) { .crm-col { flex:0 0 84vw; } }
</style>

<script>
var CRM_STAGES = {
    nouveau:  'Nouveau',
    contacte: 'Contacté',
    devis:    'Devis envoyé',
    gagne:    'Gagné',
    perdu:    'Perdu'
};
var CRM_SOURCE_LABELS = {
    chatbot: '🤖 Chatbot', formulaire: '📩 Formulaire', whatsapp: '💬 WhatsApp',
    recommandation: '🗣️ Recommandation', direct: '📞 Direct', autre: '• Autre'
};

var crmDeals = [];

var fcfa = (n) => Number(n || 0).toLocaleString('fr-FR') + ' F';
var esc = (s) => String(s ?? '').replace(/[&<>"']/g,
    c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

async function loadCrm() {
    const data = await Admin.api('crm.php');
    if (data.setup_required) {
        document.getElementById('crm-pipeline').innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <p>Le CRM n'est pas encore installé.</p>
                <p style="font-size:.85rem;opacity:.7">
                   Lancez <code>php tools/migrate_crm.php --apply</code> pour créer les tables
                   et reprendre vos demandes existantes.</p>
            </div>`;
        return;
    }
    if (data.error) { Admin.toast(data.error, 'error'); return; }
    crmDeals = data.deals || [];
    renderKpis(data.stats || {});
    renderPipeline();
}

function renderKpis(s) {
    document.getElementById('crm-kpis').innerHTML = `
        <div class="kpi-card">
            <div class="kpi-header"><div class="kpi-icon blue">🎯</div></div>
            <div class="kpi-value">${s.open || 0}</div>
            <div class="kpi-label">Opportunités en cours</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header"><div class="kpi-icon">💰</div></div>
            <div class="kpi-value">${fcfa(s.open_amount)}</div>
            <div class="kpi-label">Montant en jeu</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header"><div class="kpi-icon">✅</div></div>
            <div class="kpi-value">${fcfa(s.won_amount)}</div>
            <div class="kpi-label">${s.won || 0} affaire(s) gagnée(s)</div>
        </div>
        <div class="kpi-card" ${s.overdue ? 'style="border-top:2px solid var(--red)"' : ''}>
            <div class="kpi-header"><div class="kpi-icon">⏰</div></div>
            <div class="kpi-value">${s.overdue || 0}</div>
            <div class="kpi-label">Relance(s) en retard</div>
        </div>`;
}

function renderPipeline() {
    const q = (document.getElementById('crm-search').value || '').toLowerCase().trim();
    const visible = !q ? crmDeals : crmDeals.filter(d =>
        [d.title, d.contact_name, d.contact_company, d.contact_phone, d.contact_email, d.summary]
            .some(v => (v || '').toLowerCase().includes(q)));

    const today = new Date().toISOString().slice(0, 10);

    document.getElementById('crm-pipeline').innerHTML =
        Object.entries(CRM_STAGES).map(([stage, label]) => {
            const items = visible.filter(d => d.stage === stage);
            const cards = items.length ? items.map(d => {
                const late = d.next_action_at && d.next_action_at < today
                          && stage !== 'gagne' && stage !== 'perdu';
                const who = d.contact_company || d.contact_name || 'Contact inconnu';
                return `
                <div class="crm-card" onclick="openDeal(${d.id})">
                    <div class="crm-card-title">${esc(d.title)}</div>
                    <div class="crm-card-meta">
                        <span>${esc(who)}</span>
                        ${d.amount ? `<span class="crm-amount">${fcfa(d.amount)}</span>` : ''}
                    </div>
                    <div class="crm-card-meta" style="margin-top:5px">
                        <span>${CRM_SOURCE_LABELS[d.source] || d.source}</span>
                        ${d.next_action_at
                            ? `<span class="${late ? 'crm-late' : ''}">${late ? '⏰ ' : '📅 '}${d.next_action_at}</span>`
                            : ''}
                    </div>
                </div>`;
            }).join('') : '<div class="crm-empty">Aucune</div>';

            return `<div class="crm-col">
                        <div class="crm-col-head">
                            <span>${label}</span><span class="crm-col-count">${items.length}</span>
                        </div>${cards}
                    </div>`;
        }).join('');
}

function setLostVisibility() {
    document.getElementById('crm-lost-wrap').style.display =
        document.getElementById('crm-stage').value === 'perdu' ? 'block' : 'none';
}

async function openDeal(id) {
    const set = (k, v) => { document.getElementById(k).value = v ?? ''; };
    ['crm-title','crm-contact-name','crm-contact-company','crm-contact-phone',
     'crm-contact-email','crm-amount','crm-next-action','crm-next-action-at',
     'crm-lost-reason','crm-summary'].forEach(k => set(k, ''));
    set('crm-id', id || '');
    set('crm-stage', 'nouveau');
    set('crm-source', 'direct');

    const isNew = !id;
    document.getElementById('crm-modal-title').textContent =
        isNew ? 'Nouvelle opportunité' : 'Opportunité';
    document.getElementById('crm-delete').style.display = isNew ? 'none' : 'inline-flex';
    document.getElementById('crm-history-wrap').style.display = isNew ? 'none' : 'block';

    if (!isNew) {
        const data = await Admin.api(`crm.php?id=${id}`);
        if (data.error) { Admin.toast(data.error, 'error'); return; }
        const d = data.deal;
        set('crm-title', d.title);            set('crm-stage', d.stage);
        set('crm-source', d.source);          set('crm-amount', d.amount);
        set('crm-summary', d.summary);        set('crm-next-action', d.next_action);
        set('crm-next-action-at', d.next_action_at);
        set('crm-lost-reason', d.lost_reason);
        set('crm-contact-name', d.contact_name);
        set('crm-contact-company', d.contact_company);
        set('crm-contact-phone', d.contact_phone);
        set('crm-contact-email', d.contact_email);
        renderActivities(data.activities || []);
    }

    setLostVisibility();
    document.getElementById('crm-modal').classList.add('active');
}

function renderActivities(list) {
    document.getElementById('crm-activities').innerHTML = list.length
        ? list.map(a => `
            <div class="crm-activity">
                <div class="crm-activity-meta">
                    ${esc(a.kind)} · ${esc(a.author || '—')} · ${esc(a.created_at)}
                </div>
                <div class="crm-activity-body">${esc(a.body)}</div>
            </div>`).join('')
        : '<div class="crm-empty">Aucun échange enregistré.</div>';
}

function closeDeal() {
    document.getElementById('crm-modal').classList.remove('active');
}

function dealPayload() {
    const val = (k) => document.getElementById(k).value.trim();
    return {
        title: val('crm-title'),
        stage: val('crm-stage'),
        source: val('crm-source'),
        amount: val('crm-amount'),
        summary: val('crm-summary'),
        next_action: val('crm-next-action'),
        next_action_at: val('crm-next-action-at'),
        lost_reason: val('crm-lost-reason'),
        contact_name: val('crm-contact-name'),
        contact_company: val('crm-contact-company'),
        contact_phone: val('crm-contact-phone'),
        contact_email: val('crm-contact-email')
    };
}

async function saveDeal() {
    const payload = dealPayload();
    if (!payload.title) { Admin.toast('L\'objet de la demande est obligatoire.', 'error'); return; }

    const id = document.getElementById('crm-id').value;
    const res = id
        ? await Admin.api('crm.php', { method: 'PUT', body: { id: Number(id), ...payload } })
        : await Admin.api('crm.php', { method: 'POST', body: { action: 'create_deal', ...payload } });

    if (res.error) { Admin.toast(res.error, 'error'); return; }
    Admin.toast('Enregistré.');
    closeDeal();
    loadCrm();
}

async function addActivity() {
    const input = document.getElementById('crm-activity-body');
    const body = input.value.trim();
    if (!body) { return; }
    const res = await Admin.api('crm.php', {
        method: 'POST',
        body: {
            action: 'add_activity',
            deal_id: Number(document.getElementById('crm-id').value),
            kind: document.getElementById('crm-activity-kind').value,
            body
        }
    });
    if (res.error) { Admin.toast(res.error, 'error'); return; }
    input.value = '';
    const data = await Admin.api(`crm.php?id=${document.getElementById('crm-id').value}`);
    renderActivities(data.activities || []);
}

async function deleteDeal() {
    const id = document.getElementById('crm-id').value;
    if (!id || !Admin.confirm('Supprimer cette opportunité et tout son historique ?')) { return; }
    const res = await Admin.api(`crm.php?id=${id}`, { method: 'DELETE' });
    if (res.error) { Admin.toast(res.error, 'error'); return; }
    Admin.toast('Supprimé.');
    closeDeal();
    loadCrm();
}

document.getElementById('crm-stage').addEventListener('change', setLostVisibility);
// La Vue d'ensemble peut demander l'ouverture directe d'une opportunité.
loadCrm().then(() => {
    const id = window.CRM_OPEN_DEAL;
    window.CRM_OPEN_DEAL = null;
    if (id) { openDeal(id); }
});
</script>
