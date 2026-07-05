import './bootstrap';

document.addEventListener('DOMContentLoaded', function () {

    /* ── Accordion sidebar ───────────────────────────────────────── */

    // Open active group immediately (no animation on first load)
    document.querySelectorAll('.nav-group.open').forEach(function (group) {
        var submenu = group.querySelector('.nav-submenu');
        if (submenu) {
            submenu.style.maxHeight = submenu.scrollHeight + 'px';
        }
    });

    document.querySelectorAll('.nav-parent-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var group   = btn.closest('.nav-group');
            var submenu = group.querySelector('.nav-submenu');
            var isOpen  = group.classList.contains('open');

            // Close all open groups
            document.querySelectorAll('.nav-group.open').forEach(function (g) {
                var s = g.querySelector('.nav-submenu');
                g.classList.remove('open');
                if (s) s.style.maxHeight = '0';
            });

            // Open clicked group if it was closed
            if (!isOpen) {
                group.classList.add('open');
                if (submenu) submenu.style.maxHeight = submenu.scrollHeight + 'px';
            }
        });
    });

    /* ── Mobile sidebar toggle ───────────────────────────────────── */

    var toggleBtn = document.getElementById('sidebar-toggle');
    var sidebar   = document.getElementById('sidebar');
    var overlay   = document.getElementById('sidebar-overlay');

    function openSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('opacity-0', 'pointer-events-none');
        overlay.classList.add('opacity-100');
    }

    function closeSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.add('-translate-x-full');
        overlay.classList.remove('opacity-100');
        overlay.classList.add('opacity-0', 'pointer-events-none');
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (overlay)   overlay.addEventListener('click', closeSidebar);

    /* ── Auto-dismiss flash messages ─────────────────────────────── */

    document.querySelectorAll('.flash-msg').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            el.style.opacity    = '0';
            el.style.transform  = 'translateY(-6px)';
            setTimeout(function () { el.remove(); }, 400);
        }, 5000);
    });

});
