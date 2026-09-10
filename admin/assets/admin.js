/**
 * SDS Admin — Routeur SPA léger + utilitaires
 */

const Admin = {
    currentPage: null,
    // Déduit du chemin de la page courante : « /admin/dashboard.php » donne
    // « /admin » en production et « /mes_dossiers/sds/admin » sous XAMPP.
    // Codé en dur, il pointait sur l'arborescence locale et le tableau de
    // bord ne chargeait aucune page en ligne.
    basePath: window.location.pathname.replace(/\/[^/]*$/, '') || '/admin',

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
            crm: '🤝 Prospects & Clients',
            projects: '🚀 Projets',
            services: '⚙️ Services',
            skills: '🧠 Compétences',
            blog: '✍️ Blog',
            testimonials: '⭐ Témoignages',
            analytics: '👁️ Analytics',
            chatbot: '🤖 Chatbot',
            timeline: '📅 Timeline',
            appearance: '🎨 Apparence',
            settings: '⚙️ Paramètres',
            account: '🔑 Mon compte'
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
            
            // Ré-exécuter les scripts injectés (innerHTML ne le fait pas nativement)
            Array.from(container.querySelectorAll('script')).forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            container.style.animation = 'none';
            container.offsetHeight; // force reflow
            container.style.animation = '';

        } catch (err) {
            console.error(`Chargement de la page « ${page} » :`, err);
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <p>Impossible de charger le module « ${page} ».</p>
                    <p style="font-size:.85rem;opacity:.7">${err.message}</p>
                    <p style="font-size:.85rem;opacity:.7">
                       Si le problème persiste, rechargez la page avec Ctrl + Maj + R.</p>
                </div>`;
        }
    },

    /** Attacher la navigation */
    bindNav() {
        document.querySelectorAll('.nav-item[data-page]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                // Fermé dès l'appui : attendre la fin du chargement laissait le
                // menu ouvert sur un réseau lent, et appuyer sur le module déjà
                // affiché ne le fermait jamais.
                this.setMobileMenu(false);
                this.loadPage(el.dataset.page);
            });
        });
    },

    /**
     * Menu latéral sur mobile. Seul gestionnaire du bouton ☰ : dashboard.php
     * en portait un second, et chaque appui ouvrait puis refermait le menu.
     * L'état est porté par une classe sur <body>, que la feuille de style
     * utilise aussi pour afficher le voile.
     */
    setMobileMenu(open) {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.getElementById('admin-menu-toggle');
        sidebar?.classList.toggle('open', open);
        document.body.classList.toggle('sidebar-open', open);
        if (toggle) {
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
        }
    },

    bindMobileMenu() {
        const toggle = document.getElementById('admin-menu-toggle');
        const overlay = document.getElementById('sidebar-overlay');
        toggle?.addEventListener('click', () => {
            this.setMobileMenu(!document.body.classList.contains('sidebar-open'));
        });
        overlay?.addEventListener('click', () => this.setMobileMenu(false));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.setMobileMenu(false);
        });
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
