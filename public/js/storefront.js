'use strict';
(() => {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  const hero = document.querySelector('[data-hero]');
  if (hero) {
    const slides = [...hero.querySelectorAll('[data-hero-slide]')];
    let index = 0;
    let timer;
    const restart = () => {
      clearInterval(timer);
      if (slides.length < 2 || document.hidden || reduced.matches || hero.matches(':hover') || hero.contains(document.activeElement)) return;
      timer = setInterval(() => show(index + 1), 4000);
    };
    const show = next => {
      index = (next + slides.length) % slides.length;
      slides.forEach((slide, i) => {
        slide.hidden = i !== index;
        slide.classList.toggle('hero-enter', i === index && !reduced.matches);
      });
      hero.querySelector('[data-hero-position]').textContent = `${String(index + 1).padStart(2, '0')} / ${String(slides.length).padStart(2, '0')}`;
    };
    hero.querySelector('[data-hero-prev]')?.addEventListener('click', () => { show(index - 1); restart(); });
    hero.querySelector('[data-hero-next]')?.addEventListener('click', () => { show(index + 1); restart(); });
    hero.addEventListener('mouseenter', () => clearInterval(timer));
    hero.addEventListener('mouseleave', restart);
    hero.addEventListener('focusin', () => clearInterval(timer));
    hero.addEventListener('focusout', () => setTimeout(restart, 0));
    document.addEventListener('visibilitychange', restart);
    reduced.addEventListener('change', restart);
    window.addEventListener('pagehide', () => clearInterval(timer));
    window.addEventListener('pageshow', restart);
    restart();
  }
  const filters = document.querySelector('.nest-filters details');
  if (filters && window.matchMedia('(max-width:760px)').matches) filters.open = false;
  const gallery = document.querySelector('[data-product-gallery]');
  if (gallery) {
    const main = gallery.querySelector('[data-gallery-main]');
    const thumbs = [...gallery.querySelectorAll('[data-gallery-thumb]')];
    const dialog = document.querySelector('[data-gallery-dialog]');
    let transition;
    thumbs.forEach(button => button.addEventListener('click', () => {
      if (button.getAttribute('aria-pressed') === 'true') return;
      thumbs.forEach(thumb => {const selected = thumb === button; thumb.classList.toggle('selected', selected); thumb.setAttribute('aria-pressed', String(selected));});
      main.src = button.dataset.src;
      if (!reduced.matches && main.animate) {
        transition?.cancel();
        transition = main.animate([{opacity:.45},{opacity:1}], {duration:180,easing:'ease-out'});
      }
    }));
    gallery.querySelector('[data-gallery-zoom]')?.addEventListener('click', () => {
      if (!dialog?.showModal) return;
      dialog.querySelector('img').src = main.src;
      dialog.showModal();
    });
    dialog?.querySelector('[data-gallery-close]')?.addEventListener('click', () => dialog.close());
    dialog?.addEventListener('click', event => {if (event.target === dialog) dialog.close();});
  }
})();
