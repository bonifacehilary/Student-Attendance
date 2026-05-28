/**
 * EduAttend — Student Attendance front-end helpers
 */
(function () {
    'use strict';

    window.EduAttend = window.EduAttend || {};
    EduAttend.APP_NAME = 'EduAttend';

    EduAttend.togglePassword = function (inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!input || !icon) return;
        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');
        icon.textContent = isPassword ? 'visibility_off' : 'visibility';
    };

    EduAttend.markActiveNav = function () {
        const path = window.location.pathname;
        document.querySelectorAll('.eduattend-nav-link').forEach(function (link) {
            const href = link.getAttribute('href') || '';
            if (href && path.endsWith(href.replace(/^\//, '').split('/').pop()) || path === href) {
                link.classList.add('active');
            }
        });
    };

    EduAttend.flash = function (message, type) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: type || 'info', title: message, confirmButtonColor: '#10b981' });
        } else {
            alert(message);
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        EduAttend.markActiveNav();
    });
})();
