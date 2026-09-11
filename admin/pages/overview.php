<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_auth();
?>
<!-- Overview Page — Premium Design -->
<div class="kpi-grid" id="kpi-grid">
    <div class="kpi-card" style="border-top:2px solid rgba(96,165,250,0.4)">
        <div class="kpi-header">
            <div class="kpi-icon blue">👁️</div>
            <div class="kpi-trend up" id="kpi-today-trend" style="display:none">—</div>
        </div>
        <div class="kpi-value" id="kpi-today">—</div>
        <div class="kpi-label">Visiteurs aujourd'hui</div>
    </div>
    <div class="kpi-card" style="border-top:2px solid rgba(52,211,153,0.4)">
        <div class="kpi-header">
            <div class="kpi-icon green">📈</div>
        </div>
        <div class="kpi-value" id="kpi-week">—</div>
        <div class="kpi-label">Cette semaine</div>
    </div>
    <div class="kpi-card" style="border-top:2px solid rgba(251,191,36,0.4)">
        <div class="kpi-header">
            <div class="kpi-icon orange">💬</div>
        </div>
        <div class="kpi-value" id="kpi-unread">—</div>
        <div class="kpi-label">Messages non lus</div>
    </div>
    <div class="kpi-card" style="border-top:2px solid rgba(124,106,255,0.4)">
        <div class="kpi-header">
            <div class="kpi-icon accent">🚀</div>
        </div>
        <div class="kpi-value" id="kpi-projects">—</div>
        <div class="kpi-label">Projets publiés</div>
    </div>
    <div class="kpi-card" style="border-top:2px solid rgba(251,113,133,0.4)">
        <div class="kpi-header">
            <div class="kpi-icon red">🤖</div>
        </div>
        <div class="kpi-value" id="kpi-chatbot">—</div>
        <div class="kpi-label">Conversations IA (24h)</div>
    </div>
    <div class="kpi-card" style="border-top:2px solid rgba(52,211,153,0.4)">
        <div class="kpi-header">
            <div class="kpi-icon green">🌍</div>
        </div>
        <div class="kpi-value" id="kpi-total">—</div>
        <div class="kpi-label">Visiteurs total</div>
    </div>
</div>

<div class="chart-card" id="crm-overview" style="display:none;margin-bottom:28px">
    <div class="crm-ov-head">
        <h3 style="margin:0">🤝 Pipeline commercial</h3>
        <button type="button" class="btn btn-ghost" onclick="Admin.loadPage('crm')">Tout voir →</button>
    </div>
    <div class="crm-ov-stats" id="crm-ov-stats"></div>
    <div id="crm-ov-next"></div>
</div>

<style>
  .crm-ov-head { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
  .crm-ov-stats { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:12px; margin-bottom:18px; }
  .crm-ov-stat { background:var(--glass, rgba(255,255,255,0.03)); border:1px solid var(--border); border-radius:12px; padding:12px 14px; min-width:0; }
  .crm-ov-stat.alert { border-color:var(--red); }
  .crm-ov-num { font-size:1.15rem; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .crm-ov-lbl { font-size:0.72rem; color:var(--text-dim); margin-top:3px; }
  .crm-ov-sub { font-size:0.78rem; color:var(--text-muted); margin:0 0 8px; text-transform:uppercase; letter-spacing:.04em; }
  .crm-ov-row { display:flex; gap:12px; align-items:center; padding:11px 8px; border-bottom:1px solid rgba(255,255,255,0.03); cursor:pointer; border-radius:8px; }
  .crm-ov-row:hover, .crm-ov-row:focus-visible { background:rgba(124,106,255,0.05); outline:none; }
  .crm-ov-main { flex:1; min-width:0; }
  .crm-ov-title { font-size:0.84rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .crm-ov-meta { font-size:0.74rem; color:var(--text-dim); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .crm-ov-date { font-size:0.74rem; color:var(--text-muted); white-space:nowrap; flex-shrink:0; }
  .crm-ov-date.late { color:var(--red); font-weight:600; }
  @media (max-width:640px) { .crm-ov-stats { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
</style>

<div class="charts-grid">
    <div class="chart-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h3 style="margin:0">📊 Visites — 30 derniers jours</h3>
            <span style="font-size:0.72rem;color:var(--text-muted);background:var(--glass);padding:4px 10px;border-radius:6px;border:1px solid var(--glass-border)">Live</span>
        </div>
        <div class="chart-wrapper">
            <canvas id="visits-chart"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h3>💬 Messages récents</h3>
        <div id="recent-messages" style="max-height:280px;overflow-y:auto">
            <div class="page-loader"><div class="spinner"></div></div>
        </div>
    </div>
</div>

<script>
(async () => {
    try {
        const data = await Admin.api('overview.php');
        if (data.error) return;

        const k = data.kpis;
        
        // Animate KPI values
        const animateValue = (el, target) => {
            let current = 0;
            const step = Math.max(1, Math.ceil(target / 30));
            const timer = setInterval(() => {
                current += step;
                if (current >= target) { current = target; clearInterval(timer); }
                el.textContent = current.toLocaleString('fr-FR');
            }, 30);
        };
        
        animateValue(document.getElementById('kpi-today'), k.visitors_today);
        animateValue(document.getElementById('kpi-week'), k.visitors_week);
        animateValue(document.getElementById('kpi-unread'), k.messages_unread);
        animateValue(document.getElementById('kpi-projects'), k.projects_visible);
        animateValue(document.getElementById('kpi-chatbot'), k.chatbot_24h);
        animateValue(document.getElementById('kpi-total'), k.visitors_total);

        // Pipeline commercial. Titres et noms viennent du chatbot et du
        // formulaire public : tout passe par Admin.esc avant innerHTML.
        if (data.crm) {
            const c = data.crm;
            const e = Admin.esc;
            const fcfa = (n) => Number(n || 0).toLocaleString('fr-FR') + ' F';
            const stages = { nouveau: 'Nouveau', contacte: 'Contacté', devis: 'Devis envoyé' };
            const today = new Date().toISOString().slice(0, 10);

            document.getElementById('crm-ov-stats').innerHTML = `
                <div class="crm-ov-stat"><div class="crm-ov-num">${c.open_count}</div>
                    <div class="crm-ov-lbl">En cours · dont ${c.new_count} nouveau(x)</div></div>
                <div class="crm-ov-stat"><div class="crm-ov-num">${fcfa(c.open_amount)}</div>
                    <div class="crm-ov-lbl">Montant en jeu</div></div>
                <div class="crm-ov-stat"><div class="crm-ov-num">${fcfa(c.won_amount)}</div>
                    <div class="crm-ov-lbl">${c.won_count} affaire(s) gagnée(s)</div></div>
                <div class="crm-ov-stat${c.overdue ? ' alert' : ''}"><div class="crm-ov-num">${c.overdue}</div>
                    <div class="crm-ov-lbl">Relance(s) en retard</div></div>`;

            const next = c.next || [];
            document.getElementById('crm-ov-next').innerHTML = next.length
                ? '<p class="crm-ov-sub">Prochaines actions</p>' + next.map(d => {
                    const late = d.next_action_at && d.next_action_at < today;
                    const who = d.contact_company || d.contact_name || 'Contact inconnu';
                    const what = d.next_action || stages[d.stage] || d.stage;
                    const when = d.next_action_at
                        ? new Date(d.next_action_at + 'T00:00:00').toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
                        : 'Sans date';
                    return `
                    <div class="crm-ov-row" role="button" tabindex="0" data-deal="${Number(d.id)}">
                        <div class="crm-ov-main">
                            <div class="crm-ov-title">${e(d.title)}</div>
                            <div class="crm-ov-meta">${e(who)} · ${e(what)}${d.amount ? ' · ' + fcfa(d.amount) : ''}</div>
                        </div>
                        <div class="crm-ov-date${late ? ' late' : ''}">${late ? '⏰ ' : ''}${e(when)}</div>
                    </div>`;
                }).join('')
                : '<div class="empty-state"><p>Aucune opportunité en cours.</p></div>';

            const openDealFromOverview = (row) => {
                window.CRM_OPEN_DEAL = Number(row.dataset.deal);
                Admin.loadPage('crm');
            };
            document.querySelectorAll('#crm-ov-next .crm-ov-row').forEach(row => {
                row.addEventListener('click', () => openDealFromOverview(row));
                row.addEventListener('keydown', (ev) => {
                    if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); openDealFromOverview(row); }
                });
            });
            document.getElementById('crm-overview').style.display = '';
        }

        // Chart
        const ctx = document.getElementById('visits-chart');
        if (ctx && data.visits_chart) {
            const labels = data.visits_chart.map(d => {
                const dt = new Date(d.date);
                return dt.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
            });
            const values = data.visits_chart.map(d => d.count);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Visiteurs',
                        data: values,
                        borderColor: '#7C6AFF',
                        backgroundColor: (context) => {
                            const g = context.chart.ctx.createLinearGradient(0, 0, 0, 260);
                            g.addColorStop(0, 'rgba(124,106,255,0.2)');
                            g.addColorStop(1, 'rgba(124,106,255,0)');
                            return g;
                        },
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#7C6AFF',
                        pointHoverBackgroundColor: '#fff',
                        pointBorderWidth: 0,
                        pointHoverBorderWidth: 2,
                        pointHoverBorderColor: '#7C6AFF',
                        borderWidth: 2.5,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15,15,22,0.95)',
                            borderColor: 'rgba(124,106,255,0.3)',
                            borderWidth: 1,
                            titleFont: { family: 'Inter', weight: '600' },
                            bodyFont: { family: 'Inter' },
                            padding: 12,
                            cornerRadius: 10,
                            displayColors: false,
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: 'rgba(255,255,255,0.2)', font: { size: 10, family: 'Inter' }, maxRotation: 0 },
                            grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { color: 'rgba(255,255,255,0.2)', font: { size: 10, family: 'Inter' }, stepSize: 1 },
                            grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false }
                        }
                    }
                }
            });
        }

        // Recent messages
        const container = document.getElementById('recent-messages');
        if (data.recent_messages && data.recent_messages.length > 0) {
            // Nom et objet viennent du formulaire de contact public : insérés
            // tels quels, un visiteur pouvait y placer un script exécuté dans
            // la session de l'administrateur dès l'ouverture de cette page.
            const e = Admin.esc;
            container.innerHTML = data.recent_messages.map(m => {
                const status = e(m.status || 'unread');
                const statusCls = 'status-' + status;
                const date = new Date(m.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
                const initials = e((m.name || '?').split(' ').map(w => w[0] || '').join('').substring(0,2).toUpperCase());
                const name = e(m.name);
                const subject = e(m.subject);
                return `
                    <div style="padding:14px 0;border-bottom:1px solid rgba(255,255,255,0.03);cursor:pointer;transition:all 0.2s;display:flex;gap:12px;align-items:center"
                         onmouseover="this.style.background='rgba(124,106,255,0.03)';this.style.paddingLeft='8px'"
                         onmouseout="this.style.background='';this.style.paddingLeft='0'"
                         onclick="Admin.loadPage('messages')">
                        <div style="width:36px;height:36px;border-radius:10px;background:var(--accent-glow);color:var(--accent-light);display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:700;flex-shrink:0">${initials}</div>
                        <div style="flex:1;min-width:0">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
                                <strong style="font-size:0.84rem;font-weight:600">${name}</strong>
                                <span class="status ${statusCls}" style="font-size:0.65rem">${status}</span>
                            </div>
                            <div style="font-size:0.8rem;color:var(--text-dim);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${subject}</div>
                            <div style="font-size:0.68rem;color:var(--text-muted);margin-top:3px">${date}</div>
                        </div>
                    </div>`;
            }).join('');
        } else {
            container.innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><p>Aucun message pour le moment.</p></div>';
        }

    } catch (err) {
        console.error('Overview load error:', err);
    }
})();
</script>
