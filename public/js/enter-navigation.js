(function () {
  'use strict';

  function focusables(container) {
    var selector = 'input, select, textarea, button, a[href], [tabindex]:not([tabindex="-1"])';
    var nodes = Array.prototype.slice.call(container.querySelectorAll(selector));
    return nodes.filter(function (el) {
      return !el.disabled && !el.readOnly && el.offsetParent !== null;
    });
  }

  function handleKeydown(e) {
    if (e.key !== 'Enter' || e.shiftKey) return;
    if (e.defaultPrevented) return;

    var container = e.target.closest('[data-enter-next]');
    if (!container) return;

    var isTextarea = e.target.tagName === 'TEXTAREA';
    var isSubmit = e.target.tagName === 'BUTTON' && e.target.type === 'submit';
    if (isTextarea || isSubmit) return;

    var list = focusables(container);
    var index = list.indexOf(e.target);
    if (index === -1) return;

    var next = list[index + 1];
    if (!next) return;

    next.focus();
    if (next.select) next.select();
    e.preventDefault();
  }

  document.addEventListener('keydown', handleKeydown, true);
})();
