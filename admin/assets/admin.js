/**
 * SDS Admin — Routeur SPA léger + utilitaires
 */

const Admin = {
    currentPage: null,
    basePath: '/mes_dossiers/sds/admin',

    /** Initialiser le dashboard */
    init() {
        this.bindNav();
        this.bindMobileMenu();
        this.loadPage('overview');
        this.loadUnreadCount();
    },

    /** Naviguer vers une page */
    async loadPage(page) {
        if (this.currentPage === page) return;
        this.currentPage = page;

        // Mettre à jour la nav active
        document.querySelectorAll('.nav-item').forEach(el => {
            el.classList.toggle('active', el.dataset.page === page);
        });

        // Mettre à jour le titre
        const titles = {
            overview: '📊 Vue d\'ensemble',
            messages: '💬 Messages',
            projects: '🚀 Projets',
            services: '⚙️ Services',
            skills: '🧠 Compétences',
            blog: '✍️ Blog',
            testimonials: '⭐ Témoignages',
            analytics: '👁️ Analytics',
            chatbot: '🤖 Chatbot',
            timeline: '📅 Timeline',
            appearance: '🎨 Apparence',
            settings: '⚙️ Paramètres'
        };
        const headerTitle = document.getElementById('header-title');
        if (headerTitle) headerTitle.textContent = titles[page] || page;

        // Charger le fragment
        const container = document.getElementById('page-content');
        container.innerHTML = '<div class="page-loader"><div class="spinner"></div></div>';

        try {
            const res = await fetch(`${this.basePath}/pages/${page}.php`);
            if (!res.ok) throw new Error(`Page ${page} introuvable`);
            container.innerHTML = await res.text();
            container.style.animation = 'none';
            container.offsetHeight; // force reflow
            container.style.animation = '';

            // Fermer le menu mobile
            document.querySelector('.sidebar')?.classList.remove('open');
        } catch (err) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">🚧</div>
                    <p>Module "${page}" en cours de développement.</p>
                </div>`;
        }
    },

    /** Attacher la navigation */
    bindNav() {
        document.querySelectorAll('.nav-item[data-page]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                this.loadPage(el.dataset.page);
            });
        });
    },

    /** Menu mobile */
    bindMobileMenu() {
        const toggle = document.getElementById('admin-menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        if (toggle && sidebar) {
            toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        }
    },

    /** Charger le compteur de messages non lus */
    async loadUnreadCount() {
        try {
            const res = await fetch(`${this.basePath}/api/messages.php?count_unread=1`);
            const data = await res.json();
            const badge = document.getElementById('msg-badge');
            if (badge && data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'inline-block';
            }
        } catch (e) { /* silencieux */ }
    },

    /** Appel API générique */
    async api(endpoint, options = {}) {
        const defaults = {
            headers: { 'Content-Type': 'application/json' },
        };
        const config = { ...defaults, ...options };
        if (options.body && typeof options.body === 'object') {
            config.body = JSON.stringify(options.body);
        }
        try {
            const res = await fetch(`${this.basePath}/api/${endpoint}`, config);
            if (res.status === 401) {
                window.location.href = `${this.basePath}/index.php`;
                return { error: 'Session expirée' };
            }
            return await res.json();
        } catch (e) {
            console.error('API Error:', e);
            return { error: 'Erreur réseau ou serveur.' };
        }
    },

    /** Toast notification */
    toast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const el = document.createElement('div');
        el.className = `toast toast-${type}`;
        el.textContent = message;
        container.appendChild(el);
        setTimeout(() => el.remove(), 3000);
    },

    /** Confirmer une action */
    confirm(message) {
        return window.confirm(message);
    }
};

// Lancer au chargement
document.addEventListener('DOMContentLoaded', () => Admin.init());
