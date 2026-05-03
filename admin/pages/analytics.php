<div class="cms-module">
  <div class="cms-header">
    <div>
      <h2>📊 Analytics & Visiteurs</h2>
      <p class="cms-subtitle">Suivez les performances de votre portfolio en temps réel</p>
    </div>
    <button class="btn-add" onclick="loadAnalytics()" style="background:rgba(255,255,255,0.08);">🔄 Actualiser</button>
  </div>

  <!-- KPI Cards -->
  <div class="analytics-kpis" id="kpi-container">
    <div class="kpi-card kpi-accent">
      <div class="kpi-icon">👁️</div>
      <div class="kpi-data">
        <div class="kpi-value" id="kpi-today">—</div>
        <div class="kpi-label">Aujourd'hui</div>
      </div>
    </div>
    <div class="kpi-card kpi-gold">
      <div class="kpi-icon">📅</div>
      <div class="kpi-data">
        <div class="kpi-value" id="kpi-week">—</div>
        <div class="kpi-label">Cette semaine</div>
      </div>
    </div>
    <div class="kpi-card kpi-green">
      <div class="kpi-icon">📆</div>
      <div class="kpi-data">
        <div class="kpi-value" id="kpi-month">—</div>
        <div class="kpi-label">Ce mois</div>
      </div>
    </div>
    <div class="kpi-card kpi-purple">
      <div class="kpi-icon">🌍</div>
      <div class="kpi-data">
        <div class="kpi-value" id="kpi-total">—</div>
        <div class="kpi-label">Total visiteurs</div>
      </div>
    </div>
  </div>

  <!-- Charts Row -->
  <div class="analytics-charts">
    <div class="chart-card chart-large">
      <div class="chart-header">
        <h3>📈 Visiteurs — 30 derniers jours</h3>
      </div>
      <div class="chart-body">
        <canvas id="chart-30days" height="280"></canvas>
      </div>
    </div>
    <div class="chart-card chart-small">
      <div class="chart-header">
        <h3>⏰ Trafic aujourd'hui (par heure)</h3>
      </div>
      <div class="chart-body">
        <canvas id="chart-hours" height="280"></canvas>
      </div>
    </div>
  </div>

  <!-- Bottom row -->
  <div class="analytics-bottom">
    <div class="chart-card">
      <div class="chart-header">
        <h3>📱 Appareils</h3>
      </div>
      <div class="chart-body chart-body-center">
        <canvas id="chart-devices" height="220" width="220"></canvas>
      </div>
    </div>
    <div class="chart-card" style="flex:2;">
      <div class="chart-header">
        <h3>🕐 Dernières visites</h3>
      </div>
      <div class="chart-body">
        <div id="recent-visits" class="recent-visits-list">
          <div class="loading-spinner">Chargement...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.analytics-kpis {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.kpi-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.2rem 1.5rem;
  border-radius: 16px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.06);
  transition: transform .2s, box-shadow .2s;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,0.3); }
.kpi-icon { font-size: 2rem; }
.kpi-value { font-size: 1.8rem; font-weight: 800; }
.kpi-label { font-size: .8rem; color: var(--text-muted,#888); margin-top: 2px; }

.kpi-accent .kpi-value { color: #6C63FF; }
.kpi-gold   .kpi-value { color: #D4AF37; }
.kpi-green  .kpi-value { color: #4ECDC4; }
.kpi-purple .kpi-value { color: #A78BFA; }

.analytics-charts {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.analytics-bottom {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 1.5rem;
}

.chart-card {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 16px;
  overflow: hidden;
}
.chart-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.chart-header h3 { font-size: .95rem; font-weight: 600; color: var(--text-primary,#fff); margin: 0; }
.chart-body { padding: 1.2rem; }
.chart-body-center { display: flex; justify-content: center; align-items: center; }

.recent-visits-list {
  max-height: 300px;
  overflow-y: auto;
}

.visit-row {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: .8rem;
  align-items: center;
  padding: .6rem 0;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  font-size: .85rem;
}
.visit-row:last-child { border-bottom: none; }
.visit-hash { font-family: monospace; color: #6C63FF; background: rgba(108,99,255,0.1); padding: 2px 8px; border-radius: 6px; font-size: .8rem; }
.visit-country { color: var(--text-muted,#888); }
.visit-date { color: var(--text-muted,#888); font-size: .8rem; }

@media (max-width: 900px) {
  .analytics-charts { grid-template-columns: 1fr; }
  .analytics-bottom { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
  .analytics-kpis { grid-template-columns: 1fr 1fr; }
}
</style>

<script>
const API_ANALYTICS = 'api/analytics.php';
let chart30 = null, chartHours = null, chartDevices = null;

async function loadAnalytics() {
  await Promise.all([
    loadSummary(),
    loadChart30(),
    loadChartHours(),
    loadDevices(),
    loadRecent()
  ]);
}

async function loadSummary() {
  try {
    const res = await fetch(API_ANALYTICS + '?action=summary');
    const d = await res.json();
    animateCounter('kpi-today', d.visitors_today);
    animateCounter('kpi-week', d.visitors_week);
    animateCounter('kpi-month', d.visitors_month);
    animateCounter('kpi-total', d.visitors_total);
  } catch(e) { console.error(e); }
}

function animateCounter(id, target) {
  const el = document.getElementById(id);
  if (!el) return;
  let current = 0;
  const step = Math.max(1, Math.ceil(target / 30));
  const timer = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = current.toLocaleString();
    if (current >= target) clearInterval(timer);
  }, 30);
}

async function loadChart30() {
  try {
    const res = await fetch(API_ANALYTICS + '?action=chart_30days');
    const d = await res.json();
    const ctx = document.getElementById('chart-30days');
    if (chart30) chart30.destroy();
    chart30 = new Chart(ctx, {
      type: 'line',
      data: {
        labels: d.labels,
        datasets: [{
          label: 'Visiteurs',
          data: d.data,
          borderColor: '#6C63FF',
          backgroundColor: 'rgba(108,99,255,0.1)',
          fill: true,
          tension: 0.4,
          borderWidth: 2,
          pointRadius: 2,
          pointHoverRadius: 6,
          pointBackgroundColor: '#6C63FF'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: 'rgba(20,20,30,0.95)',
            titleColor: '#fff',
            bodyColor: '#ccc',
            borderColor: 'rgba(108,99,255,0.3)',
            borderWidth: 1,
            cornerRadius: 10
          }
        },
        scales: {
          x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#666', maxTicksLimit: 10 } },
          y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#666', stepSize: 1 }, beginAtZero: true }
        }
      }
    });
  } catch(e) { console.error(e); }
}

async function loadChartHours() {
  try {
    const res = await fetch(API_ANALYTICS + '?action=chart_hours');
    const d = await res.json();
    const ctx = document.getElementById('chart-hours');
    if (chartHours) chartHours.destroy();
    chartHours = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: d.labels,
        datasets: [{
          label: 'Visites',
          data: d.data,
          backgroundColor: 'rgba(78,205,196,0.6)',
          borderColor: '#4ECDC4',
          borderWidth: 1,
          borderRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { color: '#666', maxTicksLimit: 8 } },
          y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#666', stepSize: 1 }, beginAtZero: true }
        }
      }
    });
  } catch(e) { console.error(e); }
}

async function loadDevices() {
  try {
    const res = await fetch(API_ANALYTICS + '?action=devices');
    const d = await res.json();
    const ctx = document.getElementById('chart-devices');
    if (chartDevices) chartDevices.destroy();

    const labels = d.length > 0 ? d.map(r => r.device || 'Inconnu') : ['Desktop', 'Mobile'];
    const data   = d.length > 0 ? d.map(r => r.cnt) : [1, 0];
    const colors = ['#6C63FF', '#4ECDC4', '#D4AF37', '#A78BFA'];

    chartDevices = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: colors.slice(0, labels.length),
          borderWidth: 0,
          hoverOffset: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '65%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { color: '#999', padding: 15, font: { size: 12 } }
          }
        }
      }
    });
  } catch(e) { console.error(e); }
}

async function loadRecent() {
  const container = document.getElementById('recent-visits');
  try {
    const res = await fetch(API_ANALYTICS + '?action=recent');
    const visits = await res.json();
    if (visits.length === 0) {
      container.innerHTML = '<div class="empty-state" style="padding:2rem;text-align:center;color:#666;">Aucune visite enregistrée pour le moment.</div>';
      return;
    }
    container.innerHTML = visits.map(v => `
      <div class="visit-row">
        <span class="visit-hash">#${v.visitor_hash}</span>
        <span class="visit-country">${v.country || '🌐 Inconnu'}</span>
        <span class="visit-date">${new Date(v.created_at).toLocaleString('fr-FR', {day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'})}</span>
      </div>
    `).join('');
  } catch(e) {
    container.innerHTML = '<div class="empty-state">Erreur de chargement</div>';
  }
}

// Init
loadAnalytics();
</script>
