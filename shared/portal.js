/* RC Campus Connect — shared behaviour (jQuery). DO NOT EDIT. Your own code goes in js/app.js. */
$(function () {
  // Dismissable alerts
  $(document).on('click', '.alert-close', function () { $(this).closest('.alert').fadeOut(150); });

  // <button data-confirm="Delete this record?"> or <a data-confirm="…">
  $(document).on('click', '[data-confirm]', function (e) {
    if (!window.confirm($(this).data('confirm'))) { e.preventDefault(); e.stopImmediatePropagation(); }
  });

  // Modals: <button data-modal="#my-modal">, <div class="modal-back" id="my-modal"><div class="modal">…
  $(document).on('click', '[data-modal]', function (e) { e.preventDefault(); $($(this).data('modal')).addClass('open'); });
  $(document).on('click', '.modal-back', function (e) { if (e.target === this) { $(this).removeClass('open'); } });
  $(document).on('click', '[data-close]', function () { $(this).closest('.modal-back').removeClass('open'); });

  // Tooltip text for links that are not available yet
  $('[data-tip]').on('click', function (e) { e.preventDefault(); window.alert($(this).data('tip')); });

  // Every jQuery AJAX POST carries the CSRF token, so csrf_verify() works in api/*.php
  $.ajaxSetup({ headers: { 'X-CSRF-Token': window.RC ? window.RC.csrf : '' } });
});
