(() => {
  const body = document.body;
  const shell = document.querySelector('.page-shell') || document.querySelector('.container');
  if (!body || !shell) return;
  const prefetched = new Set();

  const overlay = document.createElement('div');
  overlay.className = 'page-transition-overlay';
  body.appendChild(overlay);

  body.classList.add('page-enter');
  requestAnimationFrame(() => {
    body.classList.add('page-enter-active');
    setTimeout(() => {
      body.classList.remove('page-enter');
      body.classList.remove('page-enter-active');
    }, 240);
  });

  const navLinks = document.querySelectorAll('.page-nav a, .d-flex.gap-2 a.btn');

  const prefetch = (href) => {
    if (!href || href.startsWith('#')) return;
    if (/^https?:\/\//i.test(href) && !href.includes(window.location.host)) return;
    if (prefetched.has(href)) return;
    prefetched.add(href);

    const link = document.createElement('link');
    link.rel = 'prefetch';
    link.href = href;
    link.as = 'document';
    document.head.appendChild(link);
  };

  navLinks.forEach((link) => {
    const href = link.getAttribute('href') || '';
    link.addEventListener('mouseenter', () => prefetch(href), { passive: true });
    link.addEventListener('touchstart', () => prefetch(href), { passive: true });

    link.addEventListener('click', (event) => {
      const isNewTab = link.target === '_blank';
      const hasModifier = event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;

      if (!href || href.startsWith('#') || isNewTab || hasModifier) {
        return;
      }

      event.preventDefault();
      body.classList.add('page-leave');
      prefetch(href);
      setTimeout(() => {
        window.location.href = href;
      }, 140);
    });
  });
})();
