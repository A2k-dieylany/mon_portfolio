let lang = 'fr', twIdx = 0, twChar = 0, twDel = false, twTimer = null;
const twEl = document.getElementById('tw');
const cur = document.getElementById('cursor'), ring = document.getElementById('cursor-ring');
let mx = 0, my = 0, rx = 0, ry = 0;
const cv = document.getElementById('particles'), cx = cv.getContext('2d');
let W, H, pts = [];
let counted = false;
let particlesPaused = false;

function setLang(l) {
  lang = l;
  const html = document.documentElement;
  html.lang = l;
  html.dir = l === 'ar' ? 'rtl' : 'ltr';
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const k = el.dataset.i18n, v = T[l][k];
    if (v !== undefined) el.innerHTML = v;
  });
  document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
    const k = el.dataset.i18nPlaceholder, v = T[l][k];
    if (v !== undefined) el.placeholder = v;
  });
  document.querySelectorAll('.dynamic-i18n').forEach(el => {
    const v = el.dataset[l];
    if (v !== undefined && v.trim() !== "") el.innerHTML = v;
  });
  document.querySelectorAll('.lang-btn:not(#theme-toggle)').forEach(b => b.classList.toggle('active', b.textContent === l.toUpperCase()));
  twIdx = 0; twChar = 0; twDel = false;
  clearTimeout(twTimer);
  twTimer = null;
  typeWord();
}

function typeWord() {
  if (!twEl) return;
  const ws = words[lang], w = ws[twIdx % ws.length];
  twEl.textContent = twDel ? w.slice(0, twChar--) : w.slice(0, twChar++);
  if (!twDel && twChar > w.length) { twDel = true; twTimer = setTimeout(typeWord, 1400); return; }
  if (twDel && twChar < 0) { twDel = false; twIdx++; twTimer = setTimeout(typeWord, 400); return; }
  twTimer = setTimeout(typeWord, twDel ? 40 : 80);
}

function updateCursor(e) { mx = e.clientX; my = e.clientY; cur.style.left = mx + 'px'; cur.style.top = my + 'px'; }
function animateRing() { rx += (mx - rx) * .12; ry += (my - ry) * .12; ring.style.left = rx + 'px'; ring.style.top = ry + 'px'; requestAnimationFrame(animateRing); }

function setupInteractions() {
  document.addEventListener('mousemove', updateCursor);
  animateRing();

  // Hover effects for cursor
  document.querySelectorAll('a, button, .service-card, .project-card, .award-card, .blog-card, .contact-item').forEach(el => {
    el.addEventListener('mouseenter', () => { cur.style.width = '20px'; cur.style.height = '20px'; ring.style.width = '60px'; ring.style.height = '60px'; });
    el.addEventListener('mouseleave', () => { cur.style.width = '12px'; cur.style.height = '12px'; ring.style.width = '36px'; ring.style.height = '36px'; });
  });

  // Magnetic Buttons
  document.querySelectorAll('.btn-primary, .btn-outline').forEach(btn => {
    btn.addEventListener('mousemove', e => {
      const rect = btn.getBoundingClientRect();
      const x = e.clientX - rect.left - rect.width / 2;
      const y = e.clientY - rect.top - rect.height / 2;
      btn.style.transform = `translate(${x * 0.3}px, ${y * 0.3}px)`;
      // Magnetic cursor snap
      mx = rect.left + rect.width / 2 + x * 0.1;
      my = rect.top + rect.height / 2 + y * 0.1;
    });
    btn.addEventListener('mouseleave', () => {
      btn.style.transform = '';
    });
  });
}

function resizeCanvas() { W = cv.width = innerWidth; H = cv.height = innerHeight; }
function initPts() {
  const count = window.innerWidth <= 720 ? 0 : 70; // Pas de particules sur mobile
  pts = [];
  for (let i = 0; i < count; i++) pts.push({ x: Math.random() * W, y: Math.random() * H, vx: (Math.random() - .5) * .3, vy: (Math.random() - .5) * .3, r: Math.random() * 1.4 + .4, a: Math.random() });
}
function draw() {
  if (particlesPaused || pts.length === 0) return;
  cx.clearRect(0, 0, W, H);
  pts.forEach(p => { p.x += p.vx; p.y += p.vy; if (p.x < 0) p.x = W; if (p.x > W) p.x = 0; if (p.y < 0) p.y = H; if (p.y > H) p.y = 0; cx.beginPath(); cx.arc(p.x, p.y, p.r, 0, Math.PI * 2); cx.fillStyle = `rgba(245,166,35,${p.a * .35})`; cx.fill(); });
  pts.forEach((a, i) => pts.slice(i + 1).forEach(b => { const d = Math.hypot(a.x - b.x, a.y - b.y); if (d < 100) { cx.beginPath(); cx.moveTo(a.x, a.y); cx.lineTo(b.x, b.y); cx.strokeStyle = `rgba(245,166,35,${.05 * (1 - d / 100)})`; cx.lineWidth = .5; cx.stroke(); } }));
  requestAnimationFrame(draw);
}

function animCount(el) {
  const target = +el.dataset.target;
  const duration = 1800;
  const startTime = performance.now();
  const easeOutExpo = t => t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
  const animate = (now) => {
    const elapsed = now - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const current = Math.round(easeOutExpo(progress) * target);
    el.textContent = current + '+';
    if (progress < 1) requestAnimationFrame(animate);
  };
  requestAnimationFrame(animate);
}

function setupObservers() {
  const heroStats = document.querySelector('.hero-stats');
  if (heroStats) {
    const sObs = new IntersectionObserver(e => { if (e[0].isIntersecting && !counted) { counted = true; document.querySelectorAll('.stat-num[data-target]').forEach(animCount); } }, { threshold: .5 });
    sObs.observe(heroStats);
  }
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        e.target.querySelectorAll('.skill-fill').forEach(b => { b.style.width = b.dataset.w + '%'; });
        e.target.querySelectorAll('.divider').forEach(d => d.classList.add('animated'));
      }
    });
  }, { threshold: .1 });
  document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
  const dObs = new IntersectionObserver(entries => { entries.forEach(x => { if (x.isIntersecting) x.target.classList.add('animated'); }); }, { threshold: .3 });
  document.querySelectorAll('.divider').forEach(d => dObs.observe(d));
}

function setupForm() {
  const form = document.getElementById('contact-form');
  if (!form) return;
  const btn = form.querySelector('button[type="submit"]');

  // CSRF token : on le récupère au chargement de la page
  let csrfToken = '';
  const fetchCsrfToken = async () => {
    try {
      const res = await fetch('csrf.php');
      const data = await res.json();
      csrfToken = data.token || '';
    } catch (e) {
      console.warn('CSRF token fetch failed:', e);
    }
  };
  fetchCsrfToken();

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const name = form.querySelector('#name');
    const email = form.querySelector('#email');
    const subject = form.querySelector('#subject');
    const message = form.querySelector('#message');
    if (!name.value.trim() || !email.value.trim() || !subject.value.trim() || !message.value.trim()) {
      toast('Merci de remplir tous les champs du formulaire.', 'error');
      return;
    }
    const originalText = btn.innerHTML;
    btn.innerHTML = 'Envoi en cours...';
    btn.disabled = true;
    try {
      const formData = new FormData(form);
      formData.append('csrf_token', csrfToken);
      const res = await fetch('contact.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.status === 'success') {
        toast('Message envoyé avec succès !', 'success');
        form.reset();
        // Rafraîchir le token CSRF pour le prochain envoi
        fetchCsrfToken();
      } else {
        toast('Erreur : ' + (data.message || 'Erreur inconnue.'), 'error');
      }
    } catch (err) {
      toast('Erreur de connexion.', 'error');
    }
    btn.innerHTML = originalText;
    btn.disabled = false;
  });
}

function handleVisibility() {
  document.addEventListener('visibilitychange', () => { particlesPaused = document.hidden; if (!particlesPaused) draw(); });
}

function setupFilters() {
  const btns = document.querySelectorAll('.filter-btn');
  const cards = document.querySelectorAll('.project-card');
  if (!btns.length) return;
  btns.forEach(btn => {
    btn.addEventListener('click', () => {
      btns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const filter = btn.dataset.filter;
      cards.forEach(card => {
        if (filter === 'all' || card.dataset.category === filter) {
          card.classList.remove('hide-project');
        } else {
          card.classList.add('hide-project');
        }
      });
    });
  });
}

function setupTheme() {
  const btn = document.getElementById('theme-toggle');
  if (!btn) return;
  const current = localStorage.getItem('theme') || 'dark';
  if (current === 'light') document.documentElement.setAttribute('data-theme', 'light');
  btn.addEventListener('click', () => {
    if (document.documentElement.getAttribute('data-theme') === 'light') {
      document.documentElement.removeAttribute('data-theme');
      localStorage.setItem('theme', 'dark');
    } else {
      document.documentElement.setAttribute('data-theme', 'light');
      localStorage.setItem('theme', 'light');
    }
  });
}

function setupChatbot() {
  const toggle = document.getElementById('chatbot-toggle');
  const win = document.getElementById('chatbot-window');
  const closeBtn = document.getElementById('chatbot-close');
  const input = document.getElementById('chat-input');
  const sendBtn = document.getElementById('chat-send');
  const messages = document.getElementById('chatbot-messages');
  const quickReplies = document.getElementById('quick-replies');

  if (!toggle) return;

  // ✅ Historique de la conversation (réinitialisé à chaque ouverture de page)
  let chatHistory = [];

  toggle.addEventListener('click', () => win.classList.remove('hidden'));
  closeBtn.addEventListener('click', () => win.classList.add('hidden'));

  const parseMarkdown = (text) => {
    return text
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.*?)\*/g, '<em>$1</em>')
      .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank" rel="noopener" style="color:var(--dark);font-weight:bold;text-decoration:underline;">$1</a>')
      .replace(/\n/g, '<br>')
      .replace(/- (.*?)<br>/g, '• $1<br>');
  };

  const rebuildHistory = () => {
    chatHistory = [];
    const msgElements = Array.from(messages.querySelectorAll('.chat-msg'));
    for (let i = 1; i < msgElements.length; i++) {
      const el = msgElements[i];
      const raw = el.dataset.raw;
      if (raw) {
        if (el.classList.contains('user-msg')) {
          chatHistory.push({ role: 'user', content: raw });
        } else {
          chatHistory.push({ role: 'assistant', content: raw });
        }
      }
    }
  };

  const appendMsg = (text, sender, animate = false) => {
    const d = document.createElement('div');
    d.className = `chat-msg ${sender}-msg`;
    d.dataset.raw = text;

    if (sender === 'bot') {
      d.innerHTML = `
        <div class="chat-avatar-mini"><img src="img/max.jpg" alt="MAX" onerror="this.style.display='none'; this.parentNode.innerHTML='M'"></div>
        <div class="chat-bubble"></div>
        <button class="chat-copy-btn" title="Copier la réponse">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
        </button>
      `;
      const bubble = d.querySelector('.chat-bubble');
      bubble.innerHTML = parseMarkdown(text);
      
      const copyBtn = d.querySelector('.chat-copy-btn');
      copyBtn.addEventListener('click', () => {
        navigator.clipboard.writeText(text).then(() => {
          copyBtn.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
          copyBtn.style.color = 'var(--green)';
          setTimeout(() => {
            copyBtn.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>`;
            copyBtn.style.color = '';
          }, 2000);
        });
      });
      
      if (animate) {
        bubble.style.opacity = '0';
        bubble.style.transform = 'translateY(10px)';
        bubble.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        setTimeout(() => {
          bubble.style.opacity = '1';
          bubble.style.transform = 'translateY(0)';
        }, 50);
      }
    } else {
      d.innerHTML = `<button class="chat-edit-btn" title="Modifier ce message">✎</button><div class="chat-bubble"></div>`;
      d.querySelector('.chat-bubble').textContent = text;
      
      d.querySelector('.chat-edit-btn').addEventListener('click', () => {
        const typing = messages.querySelector('.typing-indicator');
        if (typing) typing.remove();
        input.value = text;
        input.focus();
        let next = d.nextElementSibling;
        while(next) {
          const toRemove = next;
          next = next.nextElementSibling;
          toRemove.remove();
        }
        d.remove();
        rebuildHistory();
      });
    }
    messages.appendChild(d);
    messages.scrollTop = messages.scrollHeight;
  };

  const handleSend = async (text) => {
    if (window.location.protocol === 'file:') {
      appendMsg("Attention : Vous avez ouvert le fichier directement. Pour que le chatbot fonctionne, passez par http://localhost/...", 'bot');
      return;
    }

    const msg = (text || input.value).trim();
    if (!msg) return;

    appendMsg(msg, 'user');
    input.value = '';

    // Cacher les suggestions rapides après le premier message
    if (quickReplies) quickReplies.style.display = 'none';

    // Ajouter le message user à l'historique
    chatHistory.push({ role: 'user', content: msg });

    // Indicateur de saisie
    const typing = document.createElement('div');
    typing.className = 'typing-indicator';
    typing.innerHTML = '<span></span><span></span><span></span>';
    messages.appendChild(typing);
    messages.scrollTop = messages.scrollHeight;

    try {
      const res = await fetch('chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          message: msg,
          history: chatHistory.slice(0, -1) // historique sans le message actuel (déjà dans 'message')
        })
      });

      const data = await res.json();
      messages.removeChild(typing);

      const reply = data.reply || "Désolé, une erreur s'est produite.";
      appendMsg(reply, 'bot', true);

      // Ajouter la réponse du bot à l'historique
      chatHistory.push({ role: 'assistant', content: reply });

      // Limiter l'historique à 20 échanges pour éviter les requêtes trop lourdes
      if (chatHistory.length > 20) {
        chatHistory = chatHistory.slice(-20);
      }

    } catch (err) {
      console.error('Chatbot Error:', err);
      if (messages.contains(typing)) messages.removeChild(typing);
      appendMsg("Erreur de connexion au serveur.", 'bot', true);
    }
  };

  // Suggestions rapides
  if (quickReplies) {
    quickReplies.querySelectorAll('.quick-reply-btn').forEach(btn => {
      btn.addEventListener('click', () => handleSend(btn.dataset.msg));
    });
  }

  sendBtn.addEventListener('click', () => handleSend());
  input.addEventListener('keypress', e => { if (e.key === 'Enter') handleSend(); });
}


/* ========== TOAST SYSTEM ========== */
function toast(message, type = 'info', duration = 3500) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.textContent = message;
  container.appendChild(t);
  setTimeout(() => {
    t.classList.add('toast-out');
    setTimeout(() => t.remove(), 400);
  }, duration);
}

/* ========== NAV ACTIVE ON SCROLL ========== */
function setupActiveNav() {
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav-links a');
  if (!sections.length || !navLinks.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        navLinks.forEach(link => link.classList.remove('active-section'));
        const active = document.querySelector(`.nav-links a[href="#${entry.target.id}"]`);
        if (active) active.classList.add('active-section');
      }
    });
  }, { threshold: 0.35 });

  sections.forEach(s => observer.observe(s));
}

/* ========== COPY TO CLIPBOARD ========== */
function setupContactCopy() {
  const items = document.querySelectorAll('.contact-item');
  items.forEach(item => {
    const valueEl = item.querySelector('.contact-value');
    if (!valueEl) return;
    const originalText = valueEl.textContent;
    item.addEventListener('click', () => {
      navigator.clipboard.writeText(originalText).then(() => {
        item.classList.add('copied');
        valueEl.textContent = '✓ Copié !';
        toast(`"${originalText}" copié dans le presse-papier`, 'success', 2500);
        setTimeout(() => {
          valueEl.textContent = originalText;
          item.classList.remove('copied');
        }, 1500);
      }).catch(() => {
        toast('Impossible de copier', 'error');
      });
    });
  });
}

function setupMenu() {
  const toggle = document.getElementById('menu-toggle');
  const navLinks = document.querySelector('.nav-links');
  if (!toggle || !navLinks) return;
  toggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
    toggle.textContent = navLinks.classList.contains('active') ? '✕' : '☰';
  });
  navLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      navLinks.classList.remove('active');
      toggle.textContent = '☰';
    });
  });
}

function setupScrollFeatures() {
  const progressBar = document.getElementById('progress-bar');
  const scrollTopBtn = document.getElementById('scroll-top');
  const heroGlow = document.querySelector('.hero-glow');
  const gridBg = document.querySelector('.grid-bg');
  const shapes = document.querySelectorAll('.shape');
  const nav = document.querySelector('nav');
  let scrollTicking = false;

  if (scrollTopBtn) {
    scrollTopBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  window.addEventListener('scroll', () => {
    if (scrollTicking) return;
    scrollTicking = true;
    requestAnimationFrame(() => {
      const scrollY = window.scrollY;

      // Nav scrolled state
      if (nav) {
        if (scrollY > 80) nav.classList.add('scrolled');
        else nav.classList.remove('scrolled');
      }

      // Parallax
      if (scrollY < window.innerHeight) {
        if (heroGlow) heroGlow.style.transform = `translate(-50%, calc(-50% + ${scrollY * 0.3}px))`;
        if (gridBg) gridBg.style.transform = `translateY(${scrollY * 0.15}px)`;
        shapes.forEach((s, i) => {
          s.style.transform = `translateY(${scrollY * (0.2 + i * 0.15)}px)`;
        });
      }

      // Progress Bar
      if (progressBar) {
        const scrollPx = document.documentElement.scrollTop;
        const winHeightPx = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = `${(scrollPx / winHeightPx) * 100}%`;
        progressBar.style.width = scrolled;
      }
      // Scroll To Top Button
      if (scrollTopBtn) {
        if (scrollY > 500) {
          scrollTopBtn.classList.add('visible');
        } else {
          scrollTopBtn.classList.remove('visible');
        }
      }

      scrollTicking = false;
    });
  }, { passive: true });
}

function setupTilt() {
  const cards = document.querySelectorAll('.service-card, .project-card, .award-card, .blog-card');
  cards.forEach(card => {
    card.addEventListener('mousemove', e => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const cx = rect.width / 2;
      const cy = rect.height / 2;
      const rx = (cy - y) / 15; // rotateX
      const ry = (x - cx) / 15; // rotateY

      card.style.transform = `perspective(1000px) rotateX(${rx}deg) rotateY(${ry}deg) scale3d(1.02, 1.02, 1.02)`;
      card.style.transition = 'none';
      card.style.zIndex = "10";
    });
    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
      card.style.transition = 'all 0.4s ease';
      card.style.zIndex = "1";
    });
  });
}

function setupProjectModals() {
  const modal = document.getElementById('project-modal');
  const modalBody = document.getElementById('modal-content-body');
  const closeBtn = document.querySelector('.modal-close');
  const overlay = document.querySelector('.modal-overlay');

  if (!modal) return;

  const closeModal = () => {
    modal.classList.add('hidden');
    document.body.style.overflow = '';
  };

  closeBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', closeModal);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  document.querySelectorAll('.project-card').forEach(card => {
    card.style.cursor = 'pointer';

    card.addEventListener('click', () => {
      const type = card.querySelector('.project-type').textContent;
      const name = card.querySelector('.project-name').textContent;
      const shortDesc = card.querySelector('.project-desc').textContent;
      const stackHtml = card.querySelector('.project-stack').innerHTML;
      const statusEl = card.querySelector('.project-status');
      const status = statusEl ? statusEl.textContent : '';
      const statusClass = statusEl ? statusEl.className.replace('project-status', '').trim() : '';

      // data attributes
      const l = lang.charAt(0).toUpperCase() + lang.slice(1);
      const longDesc = card.dataset['longDesc'+l] || card.dataset.longDescFr || card.dataset.longDesc || shortDesc;
      const liveUrl = card.dataset.live || '';
      const githubUrl = card.dataset.github || '';
      const images = card.dataset.images
        ? card.dataset.images.split(',').map(s => s.trim()).filter(Boolean)
        : [];

      // --- Galerie ---
      let galleryHtml = '';
      if (images.length > 0) {
        const thumbs = images.map((src, i) =>
          `<img src="${src}" class="modal-thumb${i === 0 ? ' active' : ''}" data-index="${i}" alt="Aperçu ${i + 1}" loading="lazy" onerror="this.style.display='none'">`
        ).join('');

        galleryHtml = `
          <div class="modal-gallery">
            <div class="modal-main-img-wrap">
              <img src="${images[0]}" id="modal-main-img" class="modal-main-img" alt="${name}" loading="lazy" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\\\'http://www.w3.org/2000/svg\\\' width=\\\'800\\\' height=\\\'450\\\'><rect width=\\\'800\\\' height=\\\'450\\\' fill=\\\'%231a1a24\\\'/><text x=\\\'50%\\\' y=\\\'50%\\\' fill=\\\'%23555\\\' font-family=\\\'sans-serif\\\' font-size=\\\'18\\\' text-anchor=\\\'middle\\\' dominant-baseline=\\\'middle\\\'>Image non disponible</text></svg>'">
              ${images.length > 1 ? `
                <button class="gallery-arrow gallery-prev" id="gallery-prev">&#8249;</button>
                <button class="gallery-arrow gallery-next" id="gallery-next">&#8250;</button>
              ` : ''}
            </div>
            ${images.length > 1 ? `<div class="modal-thumbs">${thumbs}</div>` : ''}
          </div>
        `;
      }

      // --- Liens ---
      const liveBtn = liveUrl
        ? `<a href="${liveUrl}" class="modal-btn modal-btn-live" target="_blank" rel="noopener">
             <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
             Voir le site
           </a>`
        : `<span class="modal-btn modal-btn-disabled">🔒 Site privé</span>`;

      const githubBtn = githubUrl
        ? `<a href="${githubUrl}" class="modal-btn modal-btn-github" target="_blank" rel="noopener">
             <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577v-2.165c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.63-5.37-12-12-12z"/></svg>
             GitHub
           </a>`
        : `<span class="modal-btn modal-btn-disabled">Repo privé</span>`;

      // --- Injection ---
      modalBody.innerHTML = `
        ${galleryHtml}
        <div class="modal-meta">
          <span class="modal-project-type">${type}</span>
          <span class="project-status ${statusClass}">${status}</span>
        </div>
        <h3 class="modal-project-name">${name}</h3>
        <p class="modal-project-desc">${longDesc}</p>
        <div class="modal-stack-label">Stack technique</div>
        <div class="project-stack modal-stack">${stackHtml}</div>
        <div class="modal-links">${liveBtn}${githubBtn}</div>
      `;

      // --- Ouverture d'image ---
      const mainImg = modalBody.querySelector('#modal-main-img');
      if (mainImg) {
        mainImg.addEventListener('click', (e) => {
          e.stopPropagation();
          window.open(mainImg.src, '_blank');
        });
      }

      // --- Galerie interactive ---
      if (images.length > 1) {
        let current = 0;
        const thumbs2 = modalBody.querySelectorAll('.modal-thumb');
        const prevBtn2 = modalBody.querySelector('#gallery-prev');
        const nextBtn2 = modalBody.querySelector('#gallery-next');

        const goTo = (idx) => {
          current = (idx + images.length) % images.length;
          mainImg.style.opacity = '0';
          setTimeout(() => {
            mainImg.src = images[current];
            mainImg.style.opacity = '1';
          }, 180);
          thumbs2.forEach((t, i) => t.classList.toggle('active', i === current));
        };

        prevBtn2.addEventListener('click', e => { e.stopPropagation(); goTo(current - 1); });
        nextBtn2.addEventListener('click', e => { e.stopPropagation(); goTo(current + 1); });
        thumbs2.forEach((t, i) => t.addEventListener('click', e => { e.stopPropagation(); goTo(i); }));

        // Swipe sur mobile
        let touchX = 0;
        mainImg.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, { passive: true });
        mainImg.addEventListener('touchend', e => {
          const diff = touchX - e.changedTouches[0].clientX;
          if (Math.abs(diff) > 40) goTo(diff > 0 ? current + 1 : current - 1);
        }, { passive: true });
      }

      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    });
  });
}

/* ========== TESTIMONIALS SLIDER ========== */
function setupTestimonials() {
  const track = document.getElementById('testi-track');
  const prevBtn = document.getElementById('testi-prev');
  const nextBtn = document.getElementById('testi-next');
  const dotsContainer = document.getElementById('testi-dots');

  if (!track || !prevBtn || !nextBtn) return;

  const cards = track.querySelectorAll('.testi-card');
  const total = cards.length;
  let current = 0;
  let autoPlayTimer = null;

  // Determine how many cards to show based on screen width
  const getVisibleCount = () => {
    if (window.innerWidth <= 720) return 1;
    if (window.innerWidth <= 960) return 2;
    return 3;
  };

  // Create dots
  const createDots = () => {
    dotsContainer.innerHTML = '';
    const visibleCount = getVisibleCount();
    const maxSlide = Math.max(0, total - visibleCount);
    for (let i = 0; i <= maxSlide; i++) {
      const dot = document.createElement('button');
      dot.className = `testi-dot${i === 0 ? ' active' : ''}`;
      dot.addEventListener('click', () => goTo(i));
      dotsContainer.appendChild(dot);
    }
  };

  const updateDots = () => {
    dotsContainer.querySelectorAll('.testi-dot').forEach((dot, i) => {
      dot.classList.toggle('active', i === current);
    });
  };

  const goTo = (index) => {
    const visibleCount = getVisibleCount();
    const maxSlide = Math.max(0, total - visibleCount);
    current = Math.max(0, Math.min(index, maxSlide));
    const cardWidth = cards[0].offsetWidth + 24; // 24 = gap (1.5rem)
    track.style.transform = `translateX(-${current * cardWidth}px)`;
    updateDots();
  };

  prevBtn.addEventListener('click', () => { goTo(current - 1); resetAutoPlay(); });
  nextBtn.addEventListener('click', () => { goTo(current + 1); resetAutoPlay(); });

  // Auto-play
  const startAutoPlay = () => {
    autoPlayTimer = setInterval(() => {
      const visibleCount = getVisibleCount();
      const maxSlide = Math.max(0, total - visibleCount);
      goTo(current >= maxSlide ? 0 : current + 1);
    }, 5000);
  };

  const resetAutoPlay = () => {
    clearInterval(autoPlayTimer);
    startAutoPlay();
  };

  // Touch/swipe support
  let touchStartX = 0;
  track.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
  track.addEventListener('touchend', e => {
    const diff = touchStartX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 50) {
      if (diff > 0) goTo(current + 1);
      else goTo(current - 1);
      resetAutoPlay();
    }
  }, { passive: true });

  // Handle resize
  window.addEventListener('resize', () => { createDots(); goTo(Math.min(current, Math.max(0, total - getVisibleCount()))); });

  createDots();
  startAutoPlay();
}

function initPage() {
  setLang(lang);
  // Désactiver le curseur custom et les interactions hover sur les écrans tactiles
  const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
  if (!isTouchDevice) {
    setupInteractions();
  } else {
    // Masquer le curseur custom sur mobile
    if (cur) cur.style.display = 'none';
    if (ring) ring.style.display = 'none';
    document.body.style.cursor = 'auto';
  }
  resizeCanvas();
  window.addEventListener('resize', resizeCanvas);
  initPts();
  draw();
  setupObservers();
  setupForm();
  setupFilters();
  setupTheme();
  setupChatbot();
  setupMenu();
  setupScrollFeatures();
  setupTilt();
  setupProjectModals();
  setupActiveNav();
  setupContactCopy();
  setupTestimonials();
  handleVisibility();

  // Preloader logic
  const preloader = document.getElementById('preloader');
  setTimeout(() => {
    if (preloader) {
      preloader.style.opacity = '0';
      preloader.style.visibility = 'hidden';
      setTimeout(() => preloader.remove(), 500);
    }
    document.body.classList.add('loaded');
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      setTimeout(typeWord, 500);
    } else {
      twEl.textContent = words[lang][0];
    }
  }, 1500);

  // Compteur de visiteurs
  const setupVisitors = async () => {
    try {
      const res = await fetch('visitors.php');
      const data = await res.json();
      if (data.status === 'ok') {
        const vc = document.getElementById('visitor-counter');
        const vt = document.getElementById('v-today');
        const vtot = document.getElementById('v-total');
        if (vc && vt && vtot) {
          vt.textContent = data.today;
          vtot.textContent = data.total;
          vc.style.display = 'flex';
        }
      }
    } catch (e) {
      console.log('Erreur compteur visiteurs:', e);
    }
  };
  setupVisitors();

  // CV button handler
  const cvBtn = document.getElementById('btn-cv');
  if (cvBtn) {
    cvBtn.addEventListener('click', () => {
      toast('Téléchargement du CV en cours…', 'success');
    });
  }
}

window.addEventListener('DOMContentLoaded', initPage);
