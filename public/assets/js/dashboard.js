/*
 * Simpels — dashboard interactions.
 * Sidebar toggle, desktop minimize, and notification read handling.
 */

document.addEventListener('DOMContentLoaded', function () {
    // -----------------------------------------------------------------
    // 1. Mobile sidebar toggle + backdrop overlay
    // -----------------------------------------------------------------
    const sidebar = document.querySelector('.sidebar-wrapper');
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');

    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show', sidebar.classList.contains('show'));
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // -----------------------------------------------------------------
    // 2. Desktop sidebar minimize
    // -----------------------------------------------------------------
    const desktopToggleBtn = document.querySelector('#desktop-sidebar-toggle');
    if (desktopToggleBtn) {
        desktopToggleBtn.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-minimized');

            const icon = desktopToggleBtn.querySelector('i');
            if (icon) {
                icon.className = document.body.classList.contains('sidebar-minimized')
                    ? 'bi bi-chevron-bar-right'
                    : 'bi bi-chevron-bar-left';
            }
        });
    }

    // -----------------------------------------------------------------
    // 3. Mark notifications read when the bell dropdown opens
    // -----------------------------------------------------------------
    const notificationsBtn = document.getElementById('btn-notifications');

    if (notificationsBtn) {
        notificationsBtn.addEventListener('show.bs.dropdown', function () {
            const badge = notificationsBtn.querySelector('.navbar-action-badge');
            const readUrl = notificationsBtn.dataset.notificationsReadUrl;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            document.querySelectorAll('.notification-unread-dot').forEach((dot) => dot.remove());

            if (badge) {
                badge.remove();
            }

            if (!readUrl || !csrfToken) {
                return;
            }

            fetch(readUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                keepalive: true,
            }).catch(() => {});
        });
    }

    // -----------------------------------------------------------------
    // 4. Auto-submit table filter forms (GET)
    // -----------------------------------------------------------------
    document.querySelectorAll('[data-filter-form]').forEach(function (form) {
        let timer;

        form.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', function () {
                form.submit();
            });
        });

        form.querySelectorAll('input[type="text"], input[type="search"]').forEach(function (input) {
            input.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    form.submit();
                }, 400);
            });
        });
    });
});
