// Небольшой фильтр карточек по направлению (только переключение видимости).
const chips = document.querySelectorAll('[data-filter]');
const cards = document.querySelectorAll('.card[data-category]');
const counter = document.getElementById('visible-count');
const emptyState = document.getElementById('empty-state');

chips.forEach((chip) => {
  chip.addEventListener('click', () => {
    const filter = chip.dataset.filter;
    let visible = 0;
    cards.forEach((card) => {
      const show = filter === 'all' || card.dataset.category === filter;
      card.hidden = !show;
      if (show) visible += 1;
    });
    chips.forEach((c) => c.classList.toggle('chip--active', c === chip));
    counter.textContent = visible;
    emptyState.hidden = visible > 0;
  });
});
