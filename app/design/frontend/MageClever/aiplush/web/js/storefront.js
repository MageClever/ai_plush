/**
 * AI Plush — storefront global behaviors (theme-wide, loaded via requirejs deps):
 *  - Header: collapse row 1 on scroll-down past a threshold, expand on scroll-up.
 *    (The glassmorphic header background is always-on via CSS.)
 *  - Announcement bar: dismissible, remembered in localStorage.
 *  - Back-to-top control.
 * Compositor-friendly (class toggles), rAF-throttled, passive listeners.
 */
define([], function () {
    'use strict';

    var COLLAPSE_AT = 220;
    var STORAGE_KEY = 'aip_announce_dismissed';

    function initHeaderCollapse() {
        var header = document.querySelector('.page-header');

        if (!header) {
            return;
        }

        var lastY = window.scrollY;
        var ticking = false;

        function update() {
            var y = window.scrollY;

            if (y > COLLAPSE_AT && y > lastY) {
                header.classList.add('is-collapsed');
            } else if (y < lastY - 6 || y <= COLLAPSE_AT) {
                header.classList.remove('is-collapsed');
            }

            lastY = y;
            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                window.requestAnimationFrame(update);
                ticking = true;
            }
        }, { passive: true });
    }

    function initAnnouncement() {
        var bar = document.querySelector('[data-aip-announce]');

        if (!bar) {
            return;
        }

        var dismissed = false;

        try {
            dismissed = window.localStorage.getItem(STORAGE_KEY) === '1';
        } catch (e) {
            dismissed = false;
        }

        if (dismissed) {
            bar.hidden = true;
            return;
        }

        var close = bar.querySelector('[data-aip-announce-close]');

        if (close) {
            close.addEventListener('click', function () {
                bar.hidden = true;
                try {
                    window.localStorage.setItem(STORAGE_KEY, '1');
                } catch (e) {
                    // storage unavailable — dismiss for this view only
                }
            });
        }
    }

    function initBackToTop() {
        var btn = document.querySelector('[data-aip-backtotop]');

        if (!btn) {
            return;
        }

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function init() {
        initHeaderCollapse();
        initAnnouncement();
        initBackToTop();
    }

    if (document.readyState !== 'loading') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
});
