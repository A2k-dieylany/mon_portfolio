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
            container.innerHTML = data.recent_messages.map(m => {
                const statusCls = 'status-' + (m.status || 'unread');
                const date = new Date(m.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
                const initials = m.name.split(' ').map(w => w[0]).join('').substring(0,2).toUpperCase();
                return `
                    <div style="padding:14px 0;border-bottom:1px solid rgba(255,255,255,0.03);cursor:pointer;transition:all 0.2s;display:flex;gap:12px;align-items:center"
                         onmouseover="this.style.background='rgba(124,106,255,0.03)';this.style.paddingLeft='8px'"
                         onmouseout="this.style.background='';this.style.paddingLeft='0'"
                         onclick="Admin.loadPage('messages')">
                        <div style="width:36px;height:36px;border-radius:10px;background:var(--accent-glow);color:var(--accent-light);display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:700;flex-shrink:0">${initials}</div>
                        <div style="flex:1;min-width:0">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
                                <strong style="font-size:0.84rem;font-weight:600">${m.name}</strong>
                                <span class="status ${statusCls}" style="font-size:0.65rem">${m.status || 'unread'}</span>
                            </div>
                            <div style="font-size:0.8rem;color:var(--text-dim);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${m.subject}</div>
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
