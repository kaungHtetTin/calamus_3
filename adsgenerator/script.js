(() => {
  const slides = Array.from(document.querySelectorAll('.slide'));
  const previousButton = document.querySelector('#previous-slide');
  const nextButton = document.querySelector('#next-slide');
  const overviewButton = document.querySelector('#overview-toggle');
  const status = document.querySelector('#slide-status');
  const params = new URLSearchParams(window.location.search);
  const capture = params.get('capture') === '1';
  let currentSlide = Math.min(Math.max(Number.parseInt(params.get('slide') || '1', 10), 1), slides.length);
  let overview = false;

  if (capture) document.body.classList.add('capture');

  const render = ({ syncUrl = true } = {}) => {
    slides.forEach((slide, index) => {
      const active = overview || index === currentSlide - 1;
      slide.classList.toggle('is-active', active);
      slide.setAttribute('aria-hidden', active ? 'false' : 'true');
    });

    status.textContent = overview ? `${slides.length} slides` : `${currentSlide} / ${slides.length}`;
    previousButton.disabled = overview || currentSlide === 1;
    nextButton.disabled = overview || currentSlide === slides.length;
    overviewButton.setAttribute('aria-pressed', String(overview));
    overviewButton.textContent = overview ? 'Single slide' : 'Overview';
    document.body.classList.toggle('overview', overview);

    if (syncUrl && !capture) {
      const nextParams = new URLSearchParams(window.location.search);
      nextParams.set('slide', String(currentSlide));
      window.history.replaceState({}, '', `${window.location.pathname}?${nextParams.toString()}`);
    }
  };

  const goTo = (number) => {
    if (overview) return;
    currentSlide = Math.min(Math.max(number, 1), slides.length);
    render();
  };

  previousButton.addEventListener('click', () => goTo(currentSlide - 1));
  nextButton.addEventListener('click', () => goTo(currentSlide + 1));
  overviewButton.addEventListener('click', () => {
    overview = !overview;
    render();
  });

  window.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') goTo(currentSlide - 1);
    if (event.key === 'ArrowRight') goTo(currentSlide + 1);
    if (event.key.toLowerCase() === 'o' && !capture) {
      overview = !overview;
      render();
    }
  });

  render({ syncUrl: false });
})();
