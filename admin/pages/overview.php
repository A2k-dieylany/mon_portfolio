<!-- Overview Page Fragment — Chargé via AJAX dans dashboard.php -->
<div class="kpi-grid" id="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-header">
            <div class="kpi-icon blue">👁️</div>
        </div>
        <div class="kpi-value" id="kpi-today">—</div>
        <div class="kpi-label">Visiteurs aujourd'hui</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-header">
            <div class="kpi-icon green">📈</div>
        </div>
        <div class="kpi-value" id="kpi-week">—</div>
        <div class="kpi-label">Cette semaine</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-header">
            <div class="kpi-icon orange">💬</div>
        </div>
        <div class="kpi-value" id="kpi-unread">—</div>
        <div class="kpi-label">Messages non lus</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-header">
            <div class="kpi-icon accent">🚀</div>
        </div>
        <div class="kpi-value" id="kpi-projects">—</div>
        <div class="kpi-label">Projets publiés</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-header">
            <div class="kpi-icon red">🤖</div>
        </div>
        <div class="kpi-value" id="kpi-chatbot">—</div>
        <div class="kpi-label">Conversations IA (24h)</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-header">
            <div class="kpi-icon green">🌍</div>
        </div>
        <div class="kpi-value" id="kpi-total">—</div>
        <div class="kpi-label">Visiteurs total</div>
    </div>
</div>

<div class="charts-grid">
    <div class="chart-card">
        <h3>📊 Visites — 30 derniers jours</h3>
        <div class="chart-wrapper">
            <canvas id="visits-chart"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h3>💬 Messages récents</h3>
        <div id="recent-messages">
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
        document.getElementById('kpi-today').textContent = k.visitors_today;
        document.getElementById('kpi-week').textContent = k.visitors_week;
        document.getElementById('kpi-unread').textContent = k.messages_unread;
        document.getElementById('kpi-projects').textContent = k.projects_visible;
        document.getElementById('kpi-chatbot').textContent = k.chatbot_24h;
        document.getElementById('kpi-total').textContent = k.visitors_total;

        // Graphique visites
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
                        borderColor: '#6C63FF',
                        backgroundColor: 'rgba(108, 99, 255, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: '#6C63FF',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        x: {
                            ticks: { color: '#55556A', font: { size: 11 } },
                            grid: { color: 'rgba(42,42,58,0.5)' }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#55556A', font: { size: 11 }, stepSize: 1 },
                            grid: { color: 'rgba(42,42,58,0.5)' }
                        }
                    }
                }
            });
        }

        // Messages récents
        const container = document.getElementById('recent-messages');
        if (data.recent_messages && data.recent_messages.length > 0) {
            container.innerHTML = data.recent_messages.map(m => {
                const statusCls = 'status-' + (m.status || 'unread');
                const date = new Date(m.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
                return `
                    <div style="padding:12px 0;border-bottom:1px solid var(--border);cursor:pointer"
                         onclick="Admin.loadPage('messages')">
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                            <strong style="font-size:0.85rem">${m.name}</strong>
                            <span class="status ${statusCls}">${m.status || 'unread'}</span>
                        </div>
                        <div style="font-size:0.82rem;color:var(--text-dim)">${m.subject}</div>
                        <div style="font-size:0.72rem;color:var(--text-muted);margin-top:4px">${date}</div>
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
