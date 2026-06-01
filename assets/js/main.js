document.addEventListener("DOMContentLoaded", function() {
    // Bersihkan kelas pramuat anti-flicker setelah DOM siap dieksekusi oleh JS engine
    document.documentElement.classList.remove('preload-collapsed', 'preload-expanded');

    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const mobileMenuTriggerBtn = document.getElementById('mobileMenuTriggerBtn');
    const mobileProfileTabBtn = document.getElementById('mobileProfileTabBtn');
    const mobileProfileFlyout = document.getElementById('mobileProfileFlyout');
    const bodyElement = document.body;

    // Inisialisasi Deteksi Kanvas Responsif Awal Lintas Perangkat
    if (window.innerWidth <= 768) {
        bodyElement.classList.remove('sidebar-expanded');
        bodyElement.classList.add('sidebar-collapsed');
    } else {
        bodyElement.classList.remove('sidebar-collapsed');
        bodyElement.classList.add('sidebar-expanded');
    }

    function handleSidebarToggle() {
        if (bodyElement.classList.contains('sidebar-expanded')) {
            bodyElement.classList.remove('sidebar-expanded');
            bodyElement.classList.add('sidebar-collapsed');
            if (mobileMenuTriggerBtn) mobileMenuTriggerBtn.classList.remove('active');
        } else {
            bodyElement.classList.remove('sidebar-collapsed');
            bodyElement.classList.add('sidebar-expanded');
            if (mobileMenuTriggerBtn) mobileMenuTriggerBtn.classList.add('active');
            
            // Amankan flyout profil jika menu sidebar dibuka
            if (mobileProfileFlyout) mobileProfileFlyout.classList.remove('show');
            if (mobileProfileTabBtn) mobileProfileTabBtn.classList.remove('active');
        }
    }

    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', function(e) { 
            e.stopPropagation(); 
            handleSidebarToggle(); 
        });
    }

    if (mobileMenuTriggerBtn) {
        mobileMenuTriggerBtn.addEventListener('click', function(e) { 
            e.stopPropagation(); 
            handleSidebarToggle(); 
        });
    }

    if (mobileProfileTabBtn) {
        mobileProfileTabBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            bodyElement.classList.remove('sidebar-expanded');
            bodyElement.classList.add('sidebar-collapsed');
            if (mobileMenuTriggerBtn) mobileMenuTriggerBtn.classList.remove('active');
            
            // Toggle highlight keadaan aktif melayang murni
            mobileProfileTabBtn.classList.toggle('active');
            if (mobileProfileFlyout) mobileProfileFlyout.classList.toggle('show');
        });
    }

    const notifBellBtn = document.getElementById('notifBellBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const profileMenuBtn = document.getElementById('profileMenuBtn');
    const accountDropdown = document.getElementById('accountDropdown');

    if (notifBellBtn) {
        notifBellBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (accountDropdown) accountDropdown.classList.remove('show');
            if (notifDropdown) notifDropdown.classList.toggle('show');
        });
    }

    if (profileMenuBtn) {
        profileMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (notifDropdown) notifDropdown.classList.remove('show');
            if (accountDropdown) accountDropdown.classList.toggle('show');
        });
    }

    document.addEventListener('click', function() {
        if (notifDropdown) notifDropdown.classList.remove('show');
        if (accountDropdown) accountDropdown.classList.remove('show');
        if (mobileProfileFlyout) mobileProfileFlyout.classList.remove('show');
        if (mobileProfileTabBtn) mobileProfileTabBtn.classList.remove('active');
        
        if (window.innerWidth <= 768) {
            bodyElement.classList.remove('sidebar-expanded');
            bodyElement.classList.add('sidebar-collapsed');
            if (mobileMenuTriggerBtn) mobileMenuTriggerBtn.classList.remove('active');
        }
    });
});

function toggleSubmenu(element) {
    element.classList.toggle('expanded');
    const submenu = element.nextElementSibling;
    if (submenu && submenu.classList.contains('submenu-items')) {
        const arrow = element.querySelector('.arrow');
        if (arrow.classList.contains('down')) {
            arrow.classList.remove('down');
            submenu.style.display = 'none';
        } else {
            arrow.classList.add('down');
            submenu.style.display = 'flex';
        }
    }
}

// Logika Input Password Toggle Eye
const passwordInput = document.getElementById('password');
const togglePasswordBtn = document.getElementById('togglePassword');
const eyeIcon = document.getElementById('eyeIcon');
const eyeOpenPath = "M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z";
const eyeClosedPath = "M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.82l2.92 2.92c1.51-1.26 2.7-2.89 3.44-4.74-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-4 .7l2.18 2.18C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15c.01-.06.01-.11.01-.17 0-1.66-1.34-3-3-3-.06 0-.11 0-.17.02z";

if (togglePasswordBtn && passwordInput) {
    togglePasswordBtn.addEventListener('click', function () {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.innerHTML = `<path d="${eyeClosedPath}"/>`;
        } else {
            passwordInput.type = 'password';
            eyeIcon.innerHTML = `<path d="${eyeOpenPath}"/>`;
        }
    });
}