(() => {
  'use strict';
  const shell=document.getElementById('admin-shell');
  const toggle=document.getElementById('sidebar-toggle');
  const overlay=document.getElementById('sidebar-overlay');
  const sidebar=document.getElementById('admin-sidebar');
  const mobile=window.matchMedia('(max-width: 767px)');
  const cookiePath=shell.dataset.cookiePath;
  function persist(collapsed) {
    const secure=location.protocol==='https:'?'; Secure':'';
    document.cookie=`wellnest_sidebar=${collapsed?'collapsed':'expanded'}; Path=${cookiePath}; Max-Age=${30*86400}; SameSite=Lax${secure}`;
  }
  function updateMenu() {
    const isOpen=shell.classList.contains('sidebar-open');
    overlay.hidden=!(mobile.matches&&isOpen);
    toggle.setAttribute('aria-expanded',String(mobile.matches?isOpen:!shell.classList.contains('sidebar-collapsed')));
    sidebar.inert=mobile.matches&&!isOpen;
    document.body.style.overflow=mobile.matches&&isOpen?'hidden':'';
  }
  toggle.addEventListener('click',()=>{
    if(mobile.matches) {shell.classList.toggle('sidebar-open');updateMenu();if(shell.classList.contains('sidebar-open'))sidebar.querySelector('a').focus();}
    else {shell.classList.toggle('sidebar-collapsed');persist(shell.classList.contains('sidebar-collapsed'));updateMenu();}
  });
  function closeMenu(){shell.classList.remove('sidebar-open');updateMenu();toggle.focus();}
  overlay.addEventListener('click',closeMenu);
  document.addEventListener('keydown',event=>{
    if(!mobile.matches||!shell.classList.contains('sidebar-open'))return;
    if(event.key==='Escape'){closeMenu();return;}
    if(event.key==='Tab'){
      const links=[...sidebar.querySelectorAll('a,button,input:not([type="hidden"])')].filter(el=>el.offsetParent!==null);
      const first=links[0],last=links[links.length-1];
      if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
      if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
    }
  });
  mobile.addEventListener('change',()=>{shell.classList.remove('sidebar-open');updateMenu();});updateMenu();
  const image=document.getElementById('image');
  image?.addEventListener('change',()=>{document.getElementById('image-preview').src=image.dataset.imageBase+encodeURIComponent(image.value);});
})();
