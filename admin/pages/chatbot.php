<div class="cms-module">
  <div class="cms-header">
    <div>
      <h2>🤖 Chatbot AI</h2>
      <p class="cms-subtitle">Historique des conversations avec MAX</p>
    </div>
    <div style="display:flex;gap:1rem;">
      <button class="btn-refresh" onclick="loadChatbotData()" style="background:rgba(255,255,255,0.08);border:none;padding:.6rem 1.2rem;border-radius:8px;color:#fff;cursor:pointer;">🔄 Actualiser</button>
      <button class="btn-delete" onclick="clearAllLogs()" style="background:rgba(255,71,87,0.15);border:1px solid rgba(255,71,87,0.2);padding:.6rem 1.2rem;border-radius:8px;color:#ff4757;cursor:pointer;">🗑️ Tout effacer</button>
    </div>
  </div>

  <div class="chatbot-kpis">
    <div class="kpi-card">
      <div class="kpi-title">Conversations totales</div>
      <div class="kpi-val" id="cb-total-conv">0</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-title">Aujourd'hui</div>
      <div class="kpi-val" id="cb-today-conv">0</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-title">Langues</div>
      <div class="kpi-val" id="cb-langs" style="font-size:1rem;font-weight:normal;color:#ccc;line-height:1.5;">-</div>
    </div>
  </div>

  <div class="chatbot-layout">
    <div class="conv-list-pane">
      <h3 style="padding:1rem;margin:0;border-bottom:1px solid rgba(255,255,255,0.05);font-size:1rem;">Conversations</h3>
      <div id="conv-list" class="conv-list">
        <div class="loading-spinner" style="margin:2rem auto;">Chargement...</div>
      </div>
    </div>

    <div class="conv-detail-pane">
      <div id="conv-detail-empty" style="display:flex;align-items:center;justify-content:center;height:100%;color:#666;">
        Sélectionnez une conversation
      </div>
      <div id="conv-detail-content" style="display:none;height:100%;flex-direction:column;">
        <div class="conv-detail-header">
          <div>
            <span id="detail-session-id" style="font-family:monospace;color:#6C63FF;background:rgba(108,99,255,0.1);padding:2px 6px;border-radius:4px;"></span>
            <span id="detail-date" style="margin-left:10px;font-size:0.8rem;color:#888;"></span>
          </div>
          <button onclick="deleteCurrentConv()" style="background:none;border:none;color:#ff4757;cursor:pointer;font-size:1.2rem;">🗑️</button>
        </div>
        <div class="conv-messages" id="conv-messages">
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.chatbot-kpis {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  margin-bottom: 2rem;
}
.kpi-card {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 12px;
  padding: 1.5rem;
}
.kpi-title { font-size: .85rem; color: #888; margin-bottom: .5rem; }
.kpi-val { font-size: 2rem; font-weight: 700; color: #fff; }

.chatbot-layout {
  display: grid;
  grid-template-columns: 350px 1fr;
  gap: 1.5rem;
  height: 600px;
}
.conv-list-pane {
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.conv-list {
  flex: 1;
  overflow-y: auto;
}
.conv-item {
  padding: 1rem;
  border-bottom: 1px solid rgba(255,255,255,0.03);
  cursor: pointer;
  transition: background .2s;
}
.conv-item:hover { background: rgba(255,255,255,0.04); }
.conv-item.active { background: rgba(108,99,255,0.1); border-left: 3px solid #6C63FF; }
.conv-item-top { display: flex; justify-content: space-between; margin-bottom: .5rem; align-items:center; }
.conv-item-id { font-family: monospace; font-size: .8rem; color: #aaa; }
.conv-item-time { font-size: .75rem; color: #666; }
.conv-item-bottom { display: flex; justify-content: space-between; font-size: .8rem; color: #888; }
.conv-lang { text-transform: uppercase; background: rgba(255,255,255,0.05); padding: 1px 6px; border-radius: 4px; font-size:0.7rem; }

.conv-detail-pane {
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 12px;
  overflow: hidden;
}
.conv-detail-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.conv-messages {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.msg-bubble { max-width: 80%; padding: 1rem; border-radius: 12px; line-height: 1.5; font-size: .9rem; }
.msg-user {
  align-self: flex-end;
  background: rgba(108,99,255,0.15);
  border: 1px solid rgba(108,99,255,0.2);
  color: #fff;
  border-bottom-right-radius: 2px;
}
.msg-bot {
  align-self: flex-start;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.05);
  color: #ddd;
  border-bottom-left-radius: 2px;
}
.msg-time { font-size: .7rem; color: #666; margin-top: .5rem; text-align: right; }

@media (max-width: 900px) {
  .chatbot-layout { grid-template-columns: 1fr; height: auto; }
  .conv-list-pane { height: 300px; }
  .conv-detail-pane { height: 500px; }
}
</style>

<script>
const API_CB = 'api/chatbot.php';
let currentSession = null;

async function loadChatbotData() {
  try {
    const res = await fetch(API_CB);
    const data = await res.json();
    
    // Stats
    document.getElementById('cb-total-conv').textContent = data.stats.total_conversations;
    document.getElementById('cb-today-conv').textContent = data.stats.today_conversations;
    
    let langs = data.stats.languages.map(l => `${l.language.toUpperCase()}: ${l.cnt}`).join(' | ');
    document.getElementById('cb-langs').textContent = langs || '-';
    
    // List
    const listEl = document.getElementById('conv-list');
    if (data.conversations.length === 0) {
      listEl.innerHTML = '<div style="padding:1rem;color:#666;text-align:center;">Aucune conversation</div>';
    } else {
      listEl.innerHTML = data.conversations.map(c => `
        <div class="conv-item" onclick="loadConversation('${c.session_id}')" id="conv-item-${c.session_id}">
          <div class="conv-item-top">
            <span class="conv-item-id">#${c.session_id.substring(0,8)}</span>
            <span class="conv-item-time">${formatDate(c.last_activity)}</span>
          </div>
          <div class="conv-item-bottom">
            <span>${c.msg_count} messages</span>
            <span class="conv-lang">${c.lang || 'FR'}</span>
          </div>
        </div>
      `).join('');
    }
    
    // Clear detail
    currentSession = null;
    document.getElementById('conv-detail-empty').style.display = 'flex';
    document.getElementById('conv-detail-content').style.display = 'none';
    
  } catch(e) { console.error(e); }
}

async function loadConversation(sessionId) {
  currentSession = sessionId;
  
  // Update active state in list
  document.querySelectorAll('.conv-item').forEach(el => el.classList.remove('active'));
  const activeItem = document.getElementById(`conv-item-${sessionId}`);
  if (activeItem) activeItem.classList.add('active');
  
  document.getElementById('conv-detail-empty').style.display = 'none';
  document.getElementById('conv-detail-content').style.display = 'flex';
  
  document.getElementById('detail-session-id').textContent = '#' + sessionId.substring(0,8);
  
  const msgEl = document.getElementById('conv-messages');
  msgEl.innerHTML = '<div class="loading-spinner" style="margin:2rem auto;"></div>';
  
  try {
    const res = await fetch(`${API_CB}?session_id=${sessionId}`);
    const data = await res.json();
    
    if (data.messages.length > 0) {
      document.getElementById('detail-date').textContent = formatDate(data.messages[0].created_at);
      
      let html = '';
      data.messages.forEach(m => {
        html += `
          <div class="msg-bubble msg-user">
            <div>${escapeHtml(m.user_message)}</div>
            <div class="msg-time">${formatTime(m.created_at)}</div>
          </div>
          <div class="msg-bubble msg-bot">
            <div style="white-space:pre-wrap">${escapeHtml(m.bot_response)}</div>
            <div class="msg-time">${formatTime(m.created_at)}</div>
          </div>
        `;
      });
      msgEl.innerHTML = html;
      
      // Scroll to bottom
      setTimeout(() => {
        msgEl.scrollTop = msgEl.scrollHeight;
      }, 50);
    }
  } catch(e) {
    msgEl.innerHTML = '<div style="color:#ff4757;">Erreur de chargement</div>';
  }
}

async function deleteCurrentConv() {
  if (!currentSession) return;
  if (!confirm('Supprimer cette conversation ?')) return;
  
  try {
    await fetch(API_CB, {
      method: 'DELETE',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({session_id: currentSession})
    });
    loadChatbotData();
  } catch(e) { alert('Erreur'); }
}

async function clearAllLogs() {
  if (!confirm('ATTENTION: Vous allez supprimer TOUT l\'historique des conversations. Continuer ?')) return;
  try {
    await fetch(API_CB, {
      method: 'DELETE',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({clear_all: true})
    });
    loadChatbotData();
  } catch(e) { alert('Erreur'); }
}

function formatDate(ds) {
  const d = new Date(ds);
  return d.toLocaleDateString('fr-FR', {day:'2-digit',month:'short'}) + ' ' + d.toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit'});
}
function formatTime(ds) {
  return new Date(ds).toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit'});
}
function escapeHtml(unsafe) {
  return (unsafe||'').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

loadChatbotData();
</script>
