<!-- Messages Page Fragment — CRM léger -->
<div class="data-card">
    <div class="data-card-header">
        <h3>Boîte de réception</h3>
        <span class="count" id="messages-count">Chargement...</span>
    </div>
    <div class="data-filters">
        <button class="filter-btn active" data-status="all" onclick="msgFilter(this, '')">Tous</button>
        <button class="filter-btn" data-status="unread" onclick="msgFilter(this, 'unread')">📩 Non lus</button>
        <button class="filter-btn" data-status="read" onclick="msgFilter(this, 'read')">✅ Lus</button>
        <button class="filter-btn" data-status="replied" onclick="msgFilter(this, 'replied')">↩️ Répondus</button>
        <button class="filter-btn" data-status="archived" onclick="msgFilter(this, 'archived')">📦 Archivés</button>
        <input type="text" class="search-input" id="msg-search" placeholder="🔍 Rechercher..." oninput="msgSearch(this.value)">
    </div>
    <div id="messages-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Expéditeur</th>
                    <th>Email</th>
                    <th>Sujet</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="messages-tbody">
                <tr><td colspan="6"><div class="page-loader"><div class="spinner"></div></div></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal détail message -->
<div class="modal-overlay" id="msg-modal">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 id="modal-msg-subject">—</h3>
            <button class="modal-close" onclick="closeMsgModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="meta-row">
                <div class="meta-item">
                    <div class="meta-label">De</div>
                    <div class="meta-value" id="modal-msg-name">—</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Email</div>
                    <div class="meta-value" id="modal-msg-email">—</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Date</div>
                    <div class="meta-value" id="modal-msg-date">—</div>
                </div>
            </div>
            <div class="msg-content" id="modal-msg-body">—</div>
            <div class="meta-label" style="margin-bottom:6px">Notes internes</div>
            <textarea class="notes-area" id="modal-msg-notes" placeholder="Ajoutez vos notes ici..."></textarea>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" onclick="deleteMsg()">🗑️ Supprimer</button>
            <button class="btn btn-ghost" onclick="updateMsgStatus('archived')">📦 Archiver</button>
            <button class="btn btn-ghost" onclick="updateMsgStatus('replied')">↩️ Répondu</button>
            <button class="btn btn-primary" onclick="saveMsgNotes()">💾 Enregistrer</button>
        </div>
    </div>
</div>

<script>
let currentMsgId = null;
let currentFilter = '';
let searchTimeout = null;

async function loadMessages(status = '', search = '') {
    const tbody = document.getElementById('messages-tbody');
    tbody.innerHTML = '<tr><td colspan="6"><div class="page-loader"><div class="spinner"></div></div></td></tr>';

    let url = 'messages.php?';
    if (status) url += `status=${status}&`;
    if (search) url += `search=${encodeURIComponent(search)}&`;

    const data = await Admin.api(url.replace('messages.php', '').length > 1 ? url.slice(url.indexOf('messages.php')) : 'messages.php');

    const msgs = data.messages || [];
    document.getElementById('messages-count').textContent = `${msgs.length} message(s)`;

    if (msgs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="empty-icon">📭</div><p>Aucun message trouvé.</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = msgs.map(m => {
        const statusCls = 'status-' + (m.status || 'unread');
        const statusLabel = m.status || 'unread';
        const date = new Date(m.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
        return `
            <tr onclick="openMsg(${m.id})">
                <td><strong>${esc(m.name)}</strong></td>
                <td class="truncate" style="color:var(--text-dim)">${esc(m.email)}</td>
                <td class="truncate">${esc(m.subject)}</td>
                <td><span class="status ${statusCls}">${statusLabel}</span></td>
                <td style="color:var(--text-dim);font-size:0.8rem;white-space:nowrap">${date}</td>
                <td>
                    <div class="action-btns">
                        <button class="action-btn" onclick="event.stopPropagation();quickStatus(${m.id},'read')" title="Marquer lu">✅</button>
                        <button class="action-btn danger" onclick="event.stopPropagation();quickDelete(${m.id})" title="Supprimer">🗑️</button>
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

function msgFilter(btn, status) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentFilter = status;
    loadMessages(status, document.getElementById('msg-search').value);
}

function msgSearch(val) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadMessages(currentFilter, val), 300);
}

async function openMsg(id) {
    currentMsgId = id;
    const data = await Admin.api(`messages.php?id=${id}`);
    if (data.error) return Admin.toast(data.error, 'error');

    document.getElementById('modal-msg-subject').textContent = data.subject || '—';
    document.getElementById('modal-msg-name').textContent = data.name || '—';
    document.getElementById('modal-msg-email').textContent = data.email || '—';
    document.getElementById('modal-msg-date').textContent = new Date(data.created_at).toLocaleString('fr-FR');
    document.getElementById('modal-msg-body').textContent = data.message || '—';
    document.getElementById('modal-msg-notes').value = data.notes || '';

    document.getElementById('msg-modal').classList.add('active');

    // Marquer comme lu automatiquement
    if (!data.status || data.status === 'unread') {
        await Admin.api('messages.php', { method: 'PUT', body: { id, status: 'read' } });
        Admin.loadUnreadCount();
    }
}

function closeMsgModal() {
    document.getElementById('msg-modal').classList.remove('active');
    loadMessages(currentFilter, document.getElementById('msg-search').value);
}

async function updateMsgStatus(status) {
    if (!currentMsgId) return;
    const data = await Admin.api('messages.php', { method: 'PUT', body: { id: currentMsgId, status } });
    if (data.success) {
        Admin.toast('Statut mis à jour.');
        Admin.loadUnreadCount();
        closeMsgModal();
    }
}

async function saveMsgNotes() {
    if (!currentMsgId) return;
    const notes = document.getElementById('modal-msg-notes').value;
    const data = await Admin.api('messages.php', { method: 'PUT', body: { id: currentMsgId, notes } });
    if (data.success) Admin.toast('Notes enregistrées.');
}

async function deleteMsg() {
    if (!currentMsgId || !Admin.confirm('Supprimer ce message définitivement ?')) return;
    const data = await Admin.api(`messages.php?id=${currentMsgId}`, { method: 'DELETE' });
    if (data.success) {
        Admin.toast('Message supprimé.');
        Admin.loadUnreadCount();
        closeMsgModal();
    }
}

async function quickStatus(id, status) {
    await Admin.api('messages.php', { method: 'PUT', body: { id, status } });
    Admin.toast('Marqué comme lu.');
    Admin.loadUnreadCount();
    loadMessages(currentFilter, document.getElementById('msg-search').value);
}

async function quickDelete(id) {
    if (!Admin.confirm('Supprimer ce message ?')) return;
    await Admin.api(`messages.php?id=${id}`, { method: 'DELETE' });
    Admin.toast('Supprimé.');
    Admin.loadUnreadCount();
    loadMessages(currentFilter, document.getElementById('msg-search').value);
}

// Charger les messages au chargement de la page
loadMessages();
</script>
