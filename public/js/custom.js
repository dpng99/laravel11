src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
const sidebar = document.getElementById('sidebar');
     const content = document.getElementById('content');
     const navbar = document.getElementById('navbar');
     const togglerBtn = document.getElementById('toggler-btn');
     const submenuToggle = document.getElementById('toggle-submenu');
     const submenu = document.getElementById('submenu');
     const arrowIcon = togglerBtn.querySelector('i');
     const submenuArrow = document.querySelector('#toggle-submenu .arrow-icon');

     // Toggle sidebar size
     togglerBtn.addEventListener('click', () => {
         sidebar.classList.toggle('minimized');
         content.classList.toggle('shrink');
         navbar.classList.toggle('shrink');
         togglerBtn.classList.toggle('shrink');

         // Change toggle button arrow direction
         if (sidebar.classList.contains('minimized')) {
             arrowIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
         } else {
             arrowIcon.classList.replace('fa-chevron-right', 'fa-chevron-left');
         }
     });

     // Toggle submenu visibility and arrow direction
     submenuToggle.addEventListener('click', () => {
         submenu.classList.toggle('d-block');
         submenuToggle.classList.toggle('submenu-open');

         // Change submenu arrow direction
         if (submenu.classList.contains('d-block')) {
             submenuArrow.style.transform = 'rotate(90deg)';
         } else {
             submenuArrow.style.transform = 'rotate(0)';
         }
     });
     