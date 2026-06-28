/* ================================================================
   Architect Documentation Site — _assets/docs.js
   Theme toggle, TOC highlighting, copy buttons.
   No build step — vanilla JS only.
================================================================ */

(function () {
  'use strict';

  /* ── Theme toggle ─────────────────────────────────────────── */
  function initTheme() {
    var stored = localStorage.getItem('arch-docs-theme');
    if (stored === 'light') {
      document.documentElement.setAttribute('data-theme', 'light');
    }
  }

  function buildThemeToggle() {
    var btn = document.getElementById('theme-toggle');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var isLight = document.documentElement.getAttribute('data-theme') === 'light';
      if (isLight) {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('arch-docs-theme', 'dark');
        btn.textContent = '☀ Light mode';
      } else {
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('arch-docs-theme', 'light');
        btn.textContent = '🌙 Dark mode';
      }
    });
    var isDark = document.documentElement.getAttribute('data-theme') !== 'light';
    btn.textContent = isDark ? '☀ Light mode' : '🌙 Dark mode';
  }

  /* ── Active sidebar link ──────────────────────────────────── */
  function highlightActiveSidebarLink() {
    var currentPage = window.location.pathname.split('/').pop();
    if (!currentPage) currentPage = 'index.html';
    var links = document.querySelectorAll('#sidebar a');
    links.forEach(function (link) {
      var href = link.getAttribute('href');
      if (href === currentPage) {
        link.classList.add('active');
      }
    });
  }

  /* ── Build TOC ────────────────────────────────────────────── */
  function buildToc() {
    var tocEl = document.getElementById('toc');
    if (!tocEl) return;
    var headings = document.querySelectorAll('main h2, main h3');
    if (headings.length === 0) { tocEl.style.display = 'none'; return; }

    var list = document.createElement('div');
    headings.forEach(function (h) {
      if (!h.id) {
        h.id = h.textContent.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
      }
      var a = document.createElement('a');
      a.href = '#' + h.id;
      a.textContent = h.textContent;
      if (h.tagName === 'H3') a.classList.add('toc-h3');
      list.appendChild(a);
    });

    var title = document.createElement('h3');
    title.textContent = 'On this page';
    tocEl.prepend(title);
    tocEl.appendChild(list);
  }

  /* ── TOC scroll spy ───────────────────────────────────────── */
  function initScrollSpy() {
    var tocLinks = document.querySelectorAll('#toc a');
    if (tocLinks.length === 0) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          tocLinks.forEach(function (link) {
            link.classList.toggle('toc-active', link.getAttribute('href') === '#' + entry.target.id);
          });
        }
      });
    }, { rootMargin: '-10% 0px -80% 0px', threshold: 0 });

    document.querySelectorAll('main h2, main h3').forEach(function (h) {
      if (h.id) observer.observe(h);
    });
  }

  /* ── Syntax highlighting (Prism.js) ──────────────────────────
     Code blocks in this site are hand-authored without language-*
     classes, so we sniff the content and tag each block before
     calling Prism — avoids hand-annotating every <pre><code> in
     every page. */
  function detectLanguage(text) {
    var trimmed = text.trim();
    if (/^<\?php/.test(trimmed) || /Architect::|->[a-zA-Z_]+\(/.test(trimmed)) return 'php';
    if (/^(composer|php artisan|npm|npx|git|cd|ls)\b/.test(trimmed)) return 'bash';
    if (/^[{\[]/.test(trimmed)) return 'json';
    if (/^</.test(trimmed) && /<\/[a-zA-Z]/.test(trimmed)) return 'markup';
    return 'php';
  }

  function highlightCodeBlocks() {
    if (typeof window.Prism === 'undefined') return;
    document.querySelectorAll('pre > code').forEach(function (code) {
      if (code.className.indexOf('language-') !== -1) return;
      var lang = detectLanguage(code.textContent);
      code.classList.add('language-' + lang);
      window.Prism.highlightElement(code);
    });
  }

  /* ── Copy buttons ─────────────────────────────────────────── */
  function addCopyButtons() {
    document.querySelectorAll('pre').forEach(function (pre) {
      if (pre.querySelector('.copy-btn')) return;
      var btn = document.createElement('button');
      btn.className = 'copy-btn';
      btn.textContent = 'Copy';
      btn.addEventListener('click', function () {
        var code = pre.querySelector('code');
        var text = code ? code.innerText : pre.innerText;
        navigator.clipboard.writeText(text).then(function () {
          btn.textContent = 'Copied!';
          btn.classList.add('copied');
          setTimeout(function () {
            btn.textContent = 'Copy';
            btn.classList.remove('copied');
          }, 2000);
        });
      });
      pre.appendChild(btn);
    });
  }

  /* ── Full-text search (basic inline; full-text search via Fuse.js future) ── */
  function initSearch() {
    var searchInput = document.getElementById('doc-search');
    if (!searchInput) return;
    var pages = window.ARCH_DOC_PAGES || [];
    var results = document.getElementById('search-results');
    if (!results) return;

    searchInput.addEventListener('input', function () {
      var q = searchInput.value.trim().toLowerCase();
      results.innerHTML = '';
      if (q.length < 2) { results.style.display = 'none'; return; }
      results.style.display = 'block';
      var hits = pages.filter(function (p) {
        return (p.title + ' ' + (p.tags || '')).toLowerCase().includes(q);
      });
      if (hits.length === 0) {
        results.innerHTML = '<div class="sr-empty">No pages found</div>';
        return;
      }
      hits.forEach(function (p) {
        var a = document.createElement('a');
        a.className = 'sr-item';
        a.href = p.href;
        a.textContent = p.title;
        results.appendChild(a);
      });
    });

    document.addEventListener('click', function (e) {
      if (!results.contains(e.target) && e.target !== searchInput) {
        results.style.display = 'none';
      }
    });
  }

  /* ── Boot ─────────────────────────────────────────────────── */
  initTheme();
  document.addEventListener('DOMContentLoaded', function () {
    buildThemeToggle();
    highlightActiveSidebarLink();
    buildToc();
    initScrollSpy();
    highlightCodeBlocks();
    addCopyButtons();
    initSearch();
  });

})();
