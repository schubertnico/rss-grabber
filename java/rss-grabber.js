/**
 * RSS Grabber free v2.0 – Frontend-Logik (abhängigkeitsfrei).
 *
 * Ersetzt die frühere Einbindung von prototype.js + jQuery 1.4.2.
 * - Feed-Synchronisierung (feeds_synchronisieren.php)
 * - Endless-Scroll der Beitragsliste (ausgabe.php)
 */
(function () {
    'use strict';

    var LOADER = '<img src="img/ajax-loader.gif" alt="">';

    function postForm(url, params) {
        var body = new URLSearchParams();
        Object.keys(params).forEach(function (k) { body.set(k, params[k]); });
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (res) { return res.text(); });
    }

    // --- Feed-Synchronisierung ---------------------------------------------
    function initSync() {
        var trigger = document.querySelector('[data-sync-trigger]');
        var target = document.getElementById('update');
        if (!trigger || !target) {
            return;
        }
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            var csrf = trigger.getAttribute('data-csrf') || '';
            target.innerHTML = LOADER + ' Die Synchronisierung beginnt, bitte warten...';

            var done = false;
            function poll() {
                if (done) {
                    return;
                }
                postForm('graber_ajax.php', { csrf: csrf })
                    .then(function (text) {
                        target.innerHTML = text;
                        if (/Fertig|alle\s+\d+\s+Feed/i.test(text)) {
                            done = true;
                        } else {
                            window.setTimeout(poll, 2000);
                        }
                    })
                    .catch(function () { done = true; });
            }
            poll();
        });
    }

    // --- Endless-Scroll ----------------------------------------------------
    function initScroll() {
        var inhalt = document.getElementById('inhalt');
        var anzEl = document.getElementById('anz');
        if (!inhalt || !anzEl) {
            return;
        }
        var maxSteps = parseInt((anzEl.textContent || '0').trim(), 10);
        if (!maxSteps || maxSteps < 1) {
            return;
        }
        var loadDiv = document.getElementById('load');
        var step = 0;
        var loading = false;

        window.addEventListener('scroll', function () {
            if (loading || step >= maxSteps) {
                return;
            }
            var nearBottom = (window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 60);
            if (nearBottom) {
                loadNext();
            }
        });

        function loadNext() {
            loading = true;
            if (loadDiv) {
                loadDiv.innerHTML = LOADER;
            }
            postForm('ausgabe.php', { ajax: String(step) })
                .then(function (html) {
                    inhalt.insertAdjacentHTML('beforeend', '<br>' + html);
                    step += 1;
                    loading = false;
                    if (loadDiv) {
                        loadDiv.innerHTML = '';
                    }
                })
                .catch(function () {
                    loading = false;
                    if (loadDiv) {
                        loadDiv.innerHTML = '';
                    }
                });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSync();
        initScroll();
    });
})();
