
function setGlobalEventListeners(){
  /** Mobile Menu */
  document.getElementById("mobile-menu-trigger").addEventListener("click", toggleMobileMenu);
}

function toggleMobileMenu(){
  document.getElementById("mobile-menu-trigger").classList.toggle("open");
  document.getElementById("menu").classList.toggle("open");
}


window.addEventListener('DOMContentLoaded', (event) => {
    setGlobalEventListeners();
});
