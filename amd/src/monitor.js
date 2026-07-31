// This file is part of Moodle - http://moodle.org/

/**
 * Browser-side focus monitor for active quiz attempts.
 *
 * @module     quizaccess_cdexamsave/monitor
 * @copyright  2026 Carlos Díaz Bueno <carlosdiazbueno@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    'use strict';

    var MAX_QUEUE_ITEMS = 100;
    var MAX_QUEUE_AGE_MS = 24 * 60 * 60 * 1000;

    /**
     * Generate a standards-compliant random UUID v4.
     *
     * @return {String}
     */
    function uuid() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        var bytes = new Uint8Array(16);
        window.crypto.getRandomValues(bytes);
        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;
        return Array.from(bytes, function(byte) {
            return byte.toString(16).padStart(2, '0');
        }).join('').replace(/^(.{8})(.{4})(.{4})(.{4})(.{12})$/, '$1-$2-$3-$4-$5');
    }

    /**
     * Create a configured monitor instance.
     *
     * @param {Object} config Server-supplied configuration.
     * @return {Object}
     */
    function createMonitor(config) {
        var pagesessionid = uuid();
        var queuekey = 'quizaccess_cdexamsave_queue_v1';
        var endpoint = config.collectorUrl + '?sesskey=' + encodeURIComponent(M.cfg.sesskey);
        var currentloss = null;
        var intentionalnavigation = false;
        var heartbeattimer = null;

        /**
         * Read the bounded retry queue.
         *
         * @return {Array}
         */
        function readQueue() {
            try {
                var value = JSON.parse(localStorage.getItem(queuekey) || '[]');
                return Array.isArray(value) ? value : [];
            } catch (error) {
                return [];
            }
        }

        /**
         * Persist the retry queue without interrupting the attempt if storage
         * is disabled by the browser.
         *
         * @param {Array} queue Queue items.
         */
        function writeQueue(queue) {
            try {
                localStorage.setItem(queuekey, JSON.stringify(queue.slice(-MAX_QUEUE_ITEMS)));
            } catch (error) {
                // Monitoring still works online when storage is unavailable.
            }
        }

        /**
         * Queue a failed network payload.
         *
         * @param {Object} payload Collector payload.
         */
        function enqueue(payload) {
            var queue = readQueue();
            queue.push({payload: payload, queuedat: Date.now(), attempts: 0});
            writeQueue(queue);
        }

        /**
         * Add common identity fields to a collector message.
         *
         * @param {Object} payload Signal-specific values.
         * @return {Object}
         */
        function completePayload(payload) {
            return Object.assign({
                attemptid: config.attemptId,
                cmid: config.cmId,
                pagesessionid: pagesessionid,
                clienttime: Math.floor(Date.now() / 1000)
            }, payload);
        }

        /**
         * Send one signal. Network and 5xx failures are retried; rejected
         * signals are not, because the server has already validated them.
         *
         * @param {Object} payload Complete collector payload.
         * @param {Boolean} beacon Prefer sendBeacon for page lifecycle events.
         * @param {Boolean} queueonfailure Whether to persist on failure.
         * @return {Promise}
         */
        function send(payload, beacon, queueonfailure) {
            var body = JSON.stringify(payload);
            if (beacon && navigator.sendBeacon) {
                var accepted = navigator.sendBeacon(
                    endpoint,
                    new Blob([body], {type: 'application/json'})
                );
                if (accepted) {
                    return Promise.resolve();
                }
            }

            return fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                keepalive: true,
                headers: {'Content-Type': 'application/json'},
                body: body
            }).then(function(response) {
                if (!response.ok && response.status >= 500) {
                    throw new Error('Temporary collector error');
                }
            }).catch(function(error) {
                if (queueonfailure) {
                    enqueue(payload);
                    return;
                }
                throw error;
            });
        }

        /**
         * Retry recent signals for this attempt.
         *
         * @return {Promise}
         */
        function flushQueue() {
            var now = Date.now();
            var queue = readQueue();
            var retained = [];
            var requests = [];

            queue.forEach(function(item) {
                if (!item.payload || now - item.queuedat > MAX_QUEUE_AGE_MS || item.attempts >= 3) {
                    return;
                }
                if (Number(item.payload.attemptid) !== Number(config.attemptId)) {
                    retained.push(item);
                    return;
                }
                item.attempts++;
                requests.push(send(item.payload, false, false).catch(function() {
                    retained.push(item);
                }));
            });
            return Promise.all(requests).then(function() {
                writeQueue(retained);
            });
        }

        /**
         * Insert the visible monitoring status badge.
         */
        function addBadge() {
            var badge = document.createElement('div');
            badge.className = 'cdexamsave-monitor-badge';
            badge.setAttribute('role', 'status');
            badge.textContent = config.strings.badge;
            document.body.appendChild(badge);
        }

        /**
         * Show the student-facing incident acknowledgement.
         *
         * @param {Number} durationms Incident duration in milliseconds.
         */
        function showWarning(durationms) {
            if (!config.warnStudent) {
                return;
            }
            var existing = document.getElementById('cdexamsave-student-warning');
            if (existing) {
                existing.remove();
            }

            var overlay = document.createElement('div');
            overlay.id = 'cdexamsave-student-warning';
            overlay.className = 'cdexamsave-warning-overlay';
            overlay.setAttribute('role', 'dialog');
            overlay.setAttribute('aria-modal', 'true');
            overlay.setAttribute('aria-labelledby', 'cdexamsave-warning-title');

            var dialog = document.createElement('div');
            dialog.className = 'cdexamsave-warning-dialog';
            var title = document.createElement('h2');
            title.id = 'cdexamsave-warning-title';
            title.textContent = config.strings.warningTitle;
            var text = document.createElement('p');
            text.textContent = config.strings.warningText;
            var duration = document.createElement('p');
            duration.className = 'cdexamsave-warning-duration';
            duration.textContent = config.strings.duration.replace(
                '{$a}',
                Math.max(1, Math.round(durationms / 1000)) + ' s'
            );
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-primary btn-lg';
            button.textContent = config.strings.continue;
            button.addEventListener('click', function() {
                overlay.remove();
            });

            dialog.appendChild(title);
            dialog.appendChild(text);
            dialog.appendChild(duration);
            dialog.appendChild(button);
            overlay.appendChild(dialog);
            document.body.appendChild(overlay);
            button.focus();
        }

        /**
         * Commit the current loss to the server.
         *
         * @param {Boolean} beacon Prefer beacon transport.
         */
        function commitLoss(beacon) {
            if (!currentloss || currentloss.sent || intentionalnavigation) {
                return;
            }
            currentloss.sent = true;
            send(completePayload({
                action: 'lost',
                eventuuid: currentloss.id,
                reason: currentloss.reason,
                clienttime: Math.floor(currentloss.startedat / 1000)
            }), beacon, true);
        }

        /**
         * Begin a possible focus-loss incident.
         *
         * @param {String} reason Detection reason.
         * @param {Boolean} immediate Whether to bypass the grace timer.
         */
        function loseFocus(reason, immediate) {
            if (intentionalnavigation) {
                return;
            }
            if (currentloss) {
                if (reason === 'visibility_hidden' || reason === 'pagehide' || reason === 'freeze') {
                    currentloss.reason = reason;
                }
                if (immediate) {
                    clearTimeout(currentloss.timer);
                    commitLoss(true);
                }
                return;
            }
            currentloss = {
                id: uuid(),
                reason: reason,
                startedat: Date.now(),
                sent: false,
                timer: null
            };
            if (immediate || config.gracePeriodMs <= 0) {
                commitLoss(true);
            } else {
                currentloss.timer = window.setTimeout(function() {
                    commitLoss(true);
                }, config.gracePeriodMs);
            }
        }

        /**
         * End and report the current incident.
         */
        function regainFocus() {
            if (!currentloss || intentionalnavigation) {
                return;
            }
            clearTimeout(currentloss.timer);
            var loss = currentloss;
            var durationms = Math.max(0, Date.now() - loss.startedat);
            currentloss = null;

            if (durationms < config.gracePeriodMs && !loss.sent) {
                return;
            }
            if (!loss.sent) {
                send(completePayload({
                    action: 'lost',
                    eventuuid: loss.id,
                    reason: loss.reason,
                    clienttime: Math.floor(loss.startedat / 1000)
                }), false, true);
            }
            send(completePayload({
                action: 'returned',
                eventuuid: loss.id,
                reason: loss.reason,
                duration: Math.max(1, Math.round(durationms / 1000))
            }), false, true);
            showWarning(durationms);
        }

        /**
         * Attach lifecycle listeners only after the init signal has been sent.
         */
        function attachListeners() {
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'hidden') {
                    loseFocus('visibility_hidden', false);
                } else if (document.visibilityState === 'visible') {
                    regainFocus();
                }
            });
            window.addEventListener('blur', function() {
                loseFocus('window_blur', false);
            });
            window.addEventListener('focus', function() {
                if (document.visibilityState === 'visible') {
                    regainFocus();
                }
            });
            window.addEventListener('pagehide', function() {
                loseFocus('pagehide', true);
            });
            window.addEventListener('pageshow', function() {
                intentionalnavigation = false;
                if (document.visibilityState === 'visible') {
                    regainFocus();
                }
            });
            document.addEventListener('freeze', function() {
                loseFocus('freeze', true);
            });
            document.addEventListener('submit', function(event) {
                intentionalnavigation = true;
                if (currentloss) {
                    clearTimeout(currentloss.timer);
                    currentloss = null;
                }
                // If client-side validation cancels the navigation, resume
                // monitoring instead of leaving the attempt unprotected.
                window.setTimeout(function() {
                    if (event.defaultPrevented) {
                        intentionalnavigation = false;
                    }
                }, 0);
            }, true);
        }

        /**
         * Start the monitor and heartbeat loop.
         */
        function start() {
            addBadge();
            send(completePayload({action: 'init'}), false, false).catch(function() {
                // A later heartbeat or incident will initialise the session.
            }).then(function() {
                attachListeners();
                return flushQueue();
            });
            heartbeattimer = window.setInterval(function() {
                if (document.visibilityState === 'visible') {
                    send(completePayload({action: 'heartbeat'}), false, false);
                }
            }, config.heartbeatMs);
            window.addEventListener('unload', function() {
                if (heartbeattimer) {
                    window.clearInterval(heartbeattimer);
                }
            });
        }

        return {start: start};
    }

    return {
        /**
         * Entry point called by Moodle.
         *
         * @param {Object} config Server-supplied configuration.
         */
        init: function(config) {
            if (!config || !config.attemptId || !config.collectorUrl || !window.fetch || !window.crypto) {
                return;
            }
            createMonitor(config).start();
        }
    };
});
