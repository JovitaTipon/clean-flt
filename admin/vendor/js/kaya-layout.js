(function () {
  var MOBILE_MAX = 991, body = document.body;

  // Backdrop for mobile drawer (insert once)
  if (!document.querySelector('.kaya-backdrop')) {
    var b = document.createElement('div');
    b.className = 'kaya-backdrop';
    b.addEventListener('click', function(){ body.classList.remove('kaya-drawer-open'); });
    document.body.appendChild(b);
  }

  function handleToggle(e){
    if (e) e.preventDefault();

    // Desktop collapse vs. mobile drawer
    if (window.innerWidth <= MOBILE_MAX) {
      body.classList.toggle('kaya-drawer-open');      // slide-in on mobile
    } else {
      body.classList.toggle('sidebar-toggled');        // SB-Admin convention
      var s = document.querySelector('#accordionSidebar');
      if (s) s.classList.toggle('toggled');            // SB-Admin convention (safe no-op if absent)
    }
  }

  // Wire both buttons if present
  ['#sidebarToggle', '#sidebarToggleTop'].forEach(function(sel){
    var btn = document.querySelector(sel);
    if (btn) { btn.removeEventListener('click', handleToggle); btn.addEventListener('click', handleToggle); }
  });

  // Close drawer if user resizes to desktop
  window.addEventListener('resize', function(){
    if (window.innerWidth > MOBILE_MAX) body.classList.remove('kaya-drawer-open');
  });
})();
