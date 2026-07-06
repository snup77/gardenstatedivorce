/* Garden State Divorce — mobile menu toggle.
   One nav, restyled responsively. Toggling body.menu-open turns the inline
   header menu into a full-screen overlay (see the 640px block in style.css). */
(function () {
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-menu-toggle]')) {
      document.body.classList.toggle('menu-open');
    } else if (e.target.closest('.site-menu a')) {
      // tapping a link navigates away; clear the state so it's closed on return
      document.body.classList.remove('menu-open');
    }
  });
})();