<!DOCTYPE html>
<html lang="en" class="h-full theme-{{ user_ui_theme() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', platform_brand('name'))</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: Inter, system-ui, sans-serif; }
        .theme-modern .modern-app { min-height: 100vh; min-height: 100dvh; }
        .theme-modern .modern-sidebar { background: transparent; }
        .theme-modern .modern-nav-link { min-height: 44px; }
        .theme-modern main { -webkit-overflow-scrolling: touch; }
        .theme-modern .overflow-x-auto { -webkit-overflow-scrolling: touch; scrollbar-width: thin; }
        @media (max-width: 639px) {
            .theme-modern .modern-metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; }
            .theme-modern .modern-metric-grid > div { padding: 1rem; }
            .theme-modern .modern-metric-grid p.text-2xl { font-size: 1.25rem; }
            .theme-modern header button,
            .theme-modern header a.rounded-lg { min-height: 44px; min-width: 44px; display: inline-flex; align-items: center; justify-content: center; }
            .theme-modern main { padding-left: 1rem; padding-right: 1rem; }
        }

        /* Mobile-friendly modals & slide-overs */
        :root {
            --app-modal-max-height: calc(100dvh - env(safe-area-inset-top, 0px) - env(safe-area-inset-bottom, 0px));
            --app-modal-bottom-offset: 0px;
        }
        body.has-cashier-bottom-nav {
            --app-modal-bottom-offset: 4.5rem;
            --app-modal-max-height: calc(100dvh - env(safe-area-inset-top, 0px) - env(safe-area-inset-bottom, 0px) - var(--app-modal-bottom-offset));
        }
        .app-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 200;
            display: none;
            overflow: hidden;
            background: rgb(17 24 39 / 0.55);
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }
        .app-modal-overlay.is-open {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: stretch;
            padding-top: max(0.5rem, env(safe-area-inset-top, 0px));
            padding-bottom: calc(var(--app-modal-bottom-offset) + max(0.5rem, env(safe-area-inset-bottom, 0px)));
        }
        @media (min-width: 640px) {
            .app-modal-overlay.is-open {
                justify-content: center;
                align-items: center;
                padding: max(1rem, env(safe-area-inset-top, 0px)) 1rem max(1rem, env(safe-area-inset-bottom, 0px));
            }
            body.has-cashier-bottom-nav {
                --app-modal-bottom-offset: 0px;
                --app-modal-max-height: min(90dvh, calc(100dvh - 2rem));
            }
        }
        .app-modal-panel {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 32rem;
            min-height: 0;
            max-height: var(--app-modal-max-height);
            margin-left: auto;
            margin-right: auto;
            border-radius: 1rem 1rem 0 0;
            background: #fff;
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            overflow: hidden;
        }
        @media (min-width: 640px) {
            .app-modal-panel {
                border-radius: 1rem;
                width: 100%;
            }
        }
        .app-modal-panel > form {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }
        .app-modal-header {
            flex: 0 0 auto;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1rem 1.25rem 0.875rem;
            border-bottom: 1px solid #f3f4f6;
            background: #fff;
        }
        .app-modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            padding: 1rem 1.25rem;
        }
        .app-modal-footer {
            flex: 0 0 auto;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: flex-end;
            align-items: center;
            padding: 0.875rem 1.25rem;
            padding-bottom: max(0.875rem, env(safe-area-inset-bottom, 0px));
            border-top: 1px solid #f3f4f6;
            background: #fff;
            box-shadow: 0 -4px 16px rgb(0 0 0 / 0.04);
        }
        .app-modal-footer .w-full {
            flex: 1 1 100%;
        }
        @media (max-width: 639px) {
            .app-modal-footer {
                flex-direction: column-reverse;
            }
            .app-modal-footer > * {
                width: 100%;
            }
        }
        body.app-modal-open {
            overflow: hidden;
            touch-action: none;
        }
        body.app-modal-open .cashier-bottom-nav,
        body.app-modal-open .whatsapp-float-btn {
            visibility: hidden;
            pointer-events: none;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased @stack('body-class')">
    @yield('body')
    @stack('modals')
    @include('layouts.partials.whatsapp-float')
    <script>
    (function () {
        window.mountAppModal = function (modalEl) {
            if (!modalEl || modalEl.dataset.appModalMounted === '1') return;
            if (modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
            modalEl.dataset.appModalMounted = '1';
            modalEl.addEventListener('click', function (e) {
                if (e.target === modalEl) {
                    window.closeAppModal(modalEl);
                }
            });
            var panel = modalEl.querySelector('.app-modal-panel');
            if (panel) {
                panel.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }
        };

        window.openAppModal = function (id) {
            var el = typeof id === 'string' ? document.getElementById(id) : id;
            if (!el) return;
            window.mountAppModal(el);
            el.classList.add('is-open');
            document.body.classList.add('app-modal-open');
        };

        window.closeAppModal = function (target) {
            var el = typeof target === 'string' ? document.getElementById(target) : target;
            if (!el) return;
            if (el.classList.contains('app-modal-overlay')) {
                el.classList.remove('is-open');
            } else {
                var overlay = el.closest('.app-modal-overlay');
                if (overlay) overlay.classList.remove('is-open');
            }
            if (!document.querySelector('.app-modal-overlay.is-open')) {
                document.body.classList.remove('app-modal-open');
            }
        };

        document.querySelectorAll('.app-modal-overlay').forEach(window.mountAppModal);
    })();
    </script>
    @stack('scripts')
    <script>
    (function () {
        document.querySelectorAll('.password-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = btn.parentElement.querySelector('input');
                if (!input) return;
                var open = btn.querySelector('.eye-open');
                var closed = btn.querySelector('.eye-closed');
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                open.classList.toggle('hidden', show);
                closed.classList.toggle('hidden', !show);
            });
        });

        document.querySelectorAll('.alert-dismiss').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var banner = btn.closest('.alert-banner');
                if (banner) banner.remove();
            });
        });

        document.addEventListener('click', function (event) {
            var toggle = event.target.closest('[data-reports-toggle]');
            if (!toggle) return;
            event.preventDefault();
            event.stopPropagation();
            var container = toggle.closest('.reports-nav, .modern-reports-nav');
            var menu = container ? container.querySelector('[data-reports-menu]') : null;
            if (!menu) return;
            menu.classList.toggle('hidden');
            var expanded = !menu.classList.contains('hidden');
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            var chevron = toggle.querySelector('[data-reports-chevron]');
            if (chevron) {
                chevron.classList.toggle('rotate-180', expanded);
            }
        }, { passive: false });

        var countdownEl = document.getElementById('trial-countdown');
        if (countdownEl && countdownEl.dataset.ends) {
            var end = new Date(countdownEl.dataset.ends).getTime();
            function tick() {
                var diff = end - Date.now();
                if (diff <= 0) {
                    countdownEl.textContent = 'Trial expired';
                    return;
                }
                var d = Math.floor(diff / 86400000);
                var h = Math.floor((diff % 86400000) / 3600000);
                var m = Math.floor((diff % 3600000) / 60000);
                var s = Math.floor((diff % 60000) / 1000);
                countdownEl.textContent = d + 'd ' + h + 'h ' + m + 'm ' + s + 's';
            }
            tick();
            setInterval(tick, 1000);
        }

        var modal = document.getElementById('payment-modal');
        var openBtn = document.getElementById('open-payment-modal');
        var closeBtn = document.getElementById('close-payment-modal');
        var form = document.getElementById('payment-form');
        var statusEl = document.getElementById('payment-status');

        function openModal() {
            if (modal) window.openAppModal(modal);
        }
        function closeModal() {
            if (modal) window.closeAppModal(modal);
        }

        openBtn?.addEventListener('click', openModal);
        closeBtn?.addEventListener('click', closeModal);

        var planSummaryEl = document.getElementById('payment-plan-summary');
        var planInputs = form ? form.querySelectorAll('input[name="plan"]') : [];

        function formatPlanAmount(amount) {
            var n = Number(amount);
            if (isNaN(n)) return amount;
            return 'UGX ' + n.toLocaleString('en-UG');
        }

        function updatePlanSummary() {
            if (!planSummaryEl) return;
            var selected = form.querySelector('input[name="plan"]:checked');
            if (!selected) return;
            var amount = selected.getAttribute('data-plan-amount');
            var label = selected.getAttribute('data-plan-label');
            planSummaryEl.textContent = 'Amount: ' + formatPlanAmount(amount) + ' (' + label + ') · You will receive a PIN prompt on your phone.';
        }

        planInputs.forEach(function (input) {
            input.addEventListener('change', updatePlanSummary);
        });
        updatePlanSummary();

        form?.addEventListener('submit', function (e) {
            e.preventDefault();
            var submitBtn = document.getElementById('payment-submit');
            submitBtn.disabled = true;
            statusEl.classList.remove('hidden');
            statusEl.className = 'rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm text-indigo-800';
            statusEl.textContent = 'Sending PIN prompt to your phone…';

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            })
            .then(function (r) {
                return r.text().then(function (text) {
                    var data = {};
                    try {
                        data = text ? JSON.parse(text) : {};
                    } catch (err) {
                        data = { message: r.status >= 500 ? 'Server error (' + r.status + '). Check storage/logs/laravel.log.' : 'Unexpected response from server.' };
                    }
                    return { ok: r.ok, status: r.status, data: data };
                });
            })
            .then(function (res) {
                submitBtn.disabled = false;
                if (res.ok && res.data.success) {
                    statusEl.className = 'rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800';
                    statusEl.textContent = res.data.message || 'PIN prompt sent. Complete payment on your phone.';
                    if (res.data.simulated_checkout_url) {
                        setTimeout(function () { window.location.href = res.data.simulated_checkout_url; }, 1500);
                    }
                } else {
                    statusEl.className = 'rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800';
                    var failMsg = res.data.message;
                    if (!failMsg && res.data.errors) {
                        var firstKey = Object.keys(res.data.errors)[0];
                        failMsg = firstKey ? res.data.errors[firstKey][0] : null;
                    }
                    statusEl.textContent = failMsg || 'Payment request failed.';
                }
            })
            .catch(function () {
                submitBtn.disabled = false;
                statusEl.className = 'rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800';
                statusEl.textContent = 'Network error. Please try again.';
            });
        });
    })();
    </script>
</body>
</html>
