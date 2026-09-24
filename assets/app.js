/* Cyclone Store — instant client-side search on the storefront grid */
(function () {
  'use strict';

  var input = document.querySelector('[data-search]');
  if (!input) return;

  var cards = Array.prototype.slice.call(document.querySelectorAll('[data-name]'));
  if (!cards.length) return;

  var empty = document.querySelector('[data-empty]');
  var heading = document.querySelector('[data-count]');
  var total = cards.length;

  function applyFilter() {
    var q = input.value.trim().toLowerCase();
    var shown = 0;
    cards.forEach(function (card) {
      var match = !q || (card.getAttribute('data-name') || '').indexOf(q) !== -1;
      card.style.display = match ? '' : 'none';
      if (match) shown++;
    });
    if (empty) empty.style.display = shown ? 'none' : '';
    if (heading) {
      heading.textContent = q
        ? shown + ' result' + (shown === 1 ? '' : 's') + ' for “' + q + '”'
        : 'Latest Apps';
    }
  }

  input.addEventListener('input', applyFilter);

  // Keep server-rendered ?q= results in sync with the instant filter.
  var form = input.closest('form');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      applyFilter();
    });
  }

  if (input.value) applyFilter();
})();
