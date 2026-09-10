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
        this.keepSessionAlive();
    },

    /**
     * Garde la session active tant que l'onglet est ouvert, et prévient si
     * elle a expiré malgré tout (ordinateur en veille, connexion coupée) —
     * avant que l'utilisateur ne perde une saisie en tentant d'enregistrer.
     */
    keepSessionAlive() {
        const ping = async () => {
            if (document.hidden) return;
            try {
                const res = await fetch(`${this.basePath}/api/auth.php`, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!data.authenticated) this.warnSessionExpired();
            } catch (e) { /* réseau momentanément absent : on réessaiera */ }
        };
        setInterval(ping, 10 * 60 * 1000);
        // Au retour sur l'onglet (après une veille, typiquement), on vérifie tout de suite.
        document.addEventListener('visibilitychange', () => { if (!document.hidden) ping(); });
    },

    /** Avertissement persistant : la saisie en cours peut encore être copiée. */
    warnSessionExpired() {
        if (document.getElementById('session-expired')) return;
        const bar = document.createElement('div');
        bar.id = 'session-expired';
        bar.setAttribute('role', 'alert');
        bar.style.cssText = 'position:fixed;left:50%;bottom:16px;transform:translateX(-50%);z-index:400;'
            + 'max-width:92vw;padding:12px 16px;border-radius:12px;background:#3b1219;color:#fecdd3;'
            + 'border:1px solid rgba(251,113,133,.4);font-size:.88rem;box-shadow:0 10px 30px rgba(0,0,0,.4)';
        bar.innerHTML = 'Votre session a expiré. Copiez la saisie en cours, puis '
            + `<a href="${this.basePath}/index.php" target="_blank" rel="noopener" style="color:#fff;font-weight:700">`
            + 'reconnectez-vous dans un nouvel onglet</a> avant d\'enregistrer.';
        document.body.appendChild(bar);
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
            
            // Ré-exécuter les scripts injectés (innerHTML ne le fait pas nativement).
            //
            // RÈGLE POUR LES PAGES : au premier niveau d'un script de module,
            // déclarer avec « var », jamais « let » ni « const ».
            // Chaque visite réexécute le script dans la portée globale ; un
            // « let »/« const » de premier niveau y lève « Identifier … has
            // already been declared » dès la deuxième visite, et le module
            // restait figé sur « Chargement… » (11 pages sur 14 étaient
            // touchées). Envelopper le script dans un bloc ne convient pas :
            // les fonctions « async » y deviendraient locales, et les
            // attributs onclick des fragments ne les trouveraient plus.
            const scriptErrors = [];
            const onScriptError = (e) => scriptErrors.push(e.message);
            window.addEventListener('error', onScriptError);
            try {
                Array.from(container.querySelectorAll('script')).forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            } finally {
                window.removeEventListener('error', onScriptError);
            }
            // Une erreur de syntaxe empêche tout le script du module de
            // s'exécuter et ne remonte pas au try/catch : sans ce relevé, le
            // module restait bloqué sur « Chargement… » sans aucun message.
            // Les autres erreurs sont seulement journalisées : le module peut
            // rester en partie utilisable.
            const fatal = scriptErrors.find(m => /SyntaxError/.test(m));
            if (fatal) {
                throw new Error(fatal);
            }
            scriptErrors.forEach(m => console.error(`Module « ${page} » :`, m));

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
        // Un FormData part tel quel : le convertir en JSON donnait « {} ».
        // Blog et Témoignages envoient des formulaires : chaque enregistrement
        // créait une fiche vide et masquée, et la saisie était perdue.
        // Pas de Content-Type dans ce cas : le navigateur pose lui-même
        // multipart/form-data avec sa frontière.
        const isForm = options.body instanceof FormData;
        const config = {
            ...options,
            headers: {
                // Sans Accept, une session expirée renvoyait la page de
                // connexion en HTML au lieu d'un 401 exploitable.
                'Accept': 'application/json',
                ...(isForm ? {} : { 'Content-Type': 'application/json' }),
                ...(options.headers || {}),
            },
        };
        if (!isForm && options.body && typeof options.body === 'object') {
            config.body = JSON.stringify(options.body);
        }
        try {
            const res = await fetch(`${this.basePath}/api/${endpoint}`, config);
            if (res.status === 401) {
                // Rediriger aussitôt vers la connexion effaçait la fiche en
                // cours de saisie. Si une fenêtre d'édition est ouverte, on
                // prévient sans quitter la page ; sinon il n'y a rien à perdre.
                if (document.querySelector('.modal-overlay.active')) {
                    this.warnSessionExpired();
                } else {
                    window.location.href = `${this.basePath}/index.php`;
                }
                return { error: 'Session expirée : reconnectez-vous dans un nouvel onglet, puis réessayez.' };
            }
            return await res.json();
        } catch (e) {
            console.error('API Error:', e);
            return { error: 'Erreur réseau ou serveur.' };
        }
    },

    /**
     * Échappe une valeur avant de l'insérer dans du HTML.
     * Obligatoire pour tout ce qui vient d'un visiteur (formulaire de contact,
     * chatbot, CRM) : ces textes sont libres et peuvent contenir du code.
     */
    esc(value) {
        return String(value ?? '').replace(/[&<>"']/g,
            c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
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
