// This file is part of Moodle - http://moodle.org/

/**
 * Browser observations and accessible controls for an active quiz attempt.
 *
 * @module     quizaccess_cdexamcontrol/monitor
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax'], function(Ajax) {
    'use strict';

    var METHOD = 'quizaccess_cdexamcontrol_record_signal';
    var MAX_ITEMS = 100;
    var MAX_AGE = 30 * 60 * 1000;

    /**
     * Return a random UUID without recording a device fingerprint.
     *
     * @return {String}
     */
    function uuid() {
        var bytes = new Uint8Array(16);
        window.crypto.getRandomValues(bytes);
        bytes[6] = (bytes[6] % 16) + 64;
        bytes[8] = (bytes[8] % 64) + 128;
        return Array.from(bytes, function(value) {
            return value.toString(16).padStart(2, '0');
        }).join('').replace(/^(.{8})(.{4})(.{4})(.{4})(.{12})$/, '$1-$2-$3-$4-$5');
    }

    /**
     * Create one monitor. Browser controls are advisory, never an OS lock.
     *
     * @param {Object} config Server configuration.
     * @return {Object}
     */
    function createMonitor(config) {
        var session = uuid();
        var key = 'quizaccess_cdexamcontrol_v2_' + config.userId + '_' + config.attemptId;
        var queue = [];
        var draining = null;
        var initialised = false;
        var stopped = false;
        var loss = null;
        var navigation = false;
        var heartbeat = null;
        var gate = null;
        var warning = null;
        var enteredFullscreen = false;
        var fullscreenSupported = document.fullscreenEnabled !== false &&
            typeof document.documentElement.requestFullscreen === 'function';
        var badge = document.createElement('div');
        badge.className = 'cdexamcontrol-monitor-badge';
        badge.setAttribute('role', 'status');
        badge.setAttribute('aria-live', 'polite');

        /**
         * Reflect acknowledgement and pending delivery accurately.
         *
         * @param {String} message Optional operational message.
         */
        function status(message) {
            var next = config.strings.connecting;
            if (message) {
                next = message;
            } else if (stopped) {
                next = config.strings.stopped;
            } else if (queue.length >= MAX_ITEMS) {
                next = config.strings.queueFull;
            } else if (queue.length) {
                next = config.strings.pending;
            } else if (!fullscreenSupported && config.requireFullscreen) {
                next = config.strings.fullscreenUnsupported;
            } else if (initialised) {
                next = config.strings.badge;
            }
            if (badge.textContent !== next) {
                badge.textContent = next;
            }
        }

        /**
         * Keep the active queue head stable while bounded messages are pending.
         *
         * @param {Object} complete Collector payload.
         */
        function append(complete) {
            if (queue.length >= MAX_ITEMS) {
                status(config.strings.queueFull);
                return;
            }
            queue.push({payload: complete, queuedat: Date.now()});
            persist();
        }


        /**
         * Persist only this user's attempt in this tab, without a session key.
         */
        function persist() {
            try {
                window.sessionStorage.setItem(key, JSON.stringify(queue));
            } catch (error) {
                // Online collection continues when browser storage is disabled.
            }
        }

        try {
            var saved = JSON.parse(window.sessionStorage.getItem(key) || '[]');
            if (Array.isArray(saved)) {
                queue = saved.filter(function(item) {
                    return item && item.payload &&
                        item.payload.attemptid === config.attemptId && item.payload.cmid === config.cmId &&
                        ['lost', 'returned', 'observed'].includes(item.payload.action) &&
                        typeof item.queuedat === 'number' && Date.now() - item.queuedat >= 0 &&
                        Date.now() - item.queuedat < MAX_AGE;
                }).slice(-MAX_ITEMS);
            }
        } catch (error) {
            // Storage is optional.
        }

        /**
         * Add request identity. Moodle validates ownership on the server.
         *
         * @param {Object} data Observation-specific values.
         * @return {Object}
         */
        function payload(data) {
            return Object.assign({
                attemptid: config.attemptId,
                cmid: config.cmId,
                pagesessionid: session,
                clienttime: Math.floor(Date.now() / 1000)
            }, data);
        }

        /**
         * Make an acknowledged Moodle AJAX request.
         *
         * @param {Object} data Complete payload.
         * @return {Promise}
         */
        function request(data) {
            return Promise.resolve(Ajax.call([{methodname: METHOD, args: data}])[0]).then(function(result) {
                if (!result || !result.accepted) {
                    throw new Error('Observation not acknowledged');
                }
                return result;
            });
        }

        /**
         * Initialise the current page, then drain observations in order.
         * A failure keeps the head item; new items cannot overtake it.
         *
         * @return {Promise}
         */
        function drain() {
            if (draining || stopped) {
                return draining || Promise.resolve();
            }
            draining = flush();
            return draining;
        }

        /**
         * Flush observations sequentially and always release the drain flag.
         *
         * @return {Promise}
         */
        async function flush() {
            // Publish the drain promise even when an initialised queue is empty.
            await Promise.resolve();
            try {
                if (!initialised) {
                    await request(payload({action: 'init'}));
                    initialised = true;
                }
                while (queue.length) {
                    var item = queue[0];
                    if (Date.now() - item.queuedat < MAX_AGE) {
                        await request(item.payload);
                    }
                    queue.shift();
                    persist();
                }
            } catch (error) {
                if (error && ['attemptnotmonitorable', 'monitoringdisabled', 'incidentlimitreached']
                        .includes(error.errorcode)) {
                    stopped = true;
                    queue = [];
                    persist();
                    dismissGate();
                    if (heartbeat) {
                        window.clearInterval(heartbeat);
                    }
                }
                // Connection failures must never interrupt answering or saving.
            } finally {
                draining = null;
                status();
            }
        }

        /**
         * Queue a durable observation. A beacon is not an acknowledgement.
         *
         * @param {Object} data Observation values.
         * @param {Boolean} beacon Also attempt lifecycle delivery.
         */
        function collect(data, beacon) {
            if (stopped) {
                return;
            }
            var complete = payload(data);
            append(complete);
            status();
            if (beacon && navigator.sendBeacon) {
                try {
                    var endpoint = M.cfg.wwwroot + '/lib/ajax/service.php?sesskey=' +
                        encodeURIComponent(M.cfg.sesskey) + '&info=' + METHOD;
                    navigator.sendBeacon(endpoint, new Blob([JSON.stringify([{
                        index: 0, methodname: METHOD, args: complete
                    }])], {type: 'application/json'}));
                } catch (error) {
                    // Retain the observation for an acknowledged retry.
                }
            }
            drain();
        }

        /**
         * Close the fullscreen prompt.
         */
        function dismissGate() {
            if (gate) {
                if (gate.open && typeof gate.close === 'function') {
                    gate.close();
                }
                gate.remove();
                gate = null;
            }
        }

        /**
         * Create a native dialog with focus containment and keyboard support.
         *
         * @param {String} title Dialog heading.
         * @param {String} text Dialog message.
         * @param {String} label Button label.
         * @return {Object}
         */
        function dialog(title, text, label) {
            var element = document.createElement('dialog');
            element.className = 'cdexamcontrol-control-dialog';
            var heading = document.createElement('h2');
            heading.id = 'cdexamcontrol-dialog-' + uuid();
            heading.textContent = title;
            element.setAttribute('aria-labelledby', heading.id);
            var paragraph = document.createElement('p');
            paragraph.textContent = text;
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-primary';
            button.textContent = label;
            element.appendChild(heading);
            element.appendChild(paragraph);
            element.appendChild(button);
            document.body.appendChild(element);
            if (typeof element.showModal === 'function') {
                element.showModal();
            } else {
                element.setAttribute('open', '');
                element.setAttribute('role', 'dialog');
            }
            button.focus();
            return {element: element, button: button, message: paragraph};
        }

        /**
         * Ask for fullscreen through a user gesture, with recoverable failures.
         */
        function showGate() {
            if (!config.requireFullscreen || gate || navigation || stopped || document.fullscreenElement) {
                return;
            }
            if (!fullscreenSupported) {
                status(config.strings.fullscreenUnsupported);
                return;
            }
            if (warning) {
                warning.close();
                warning = null;
            }
            var view = dialog(config.strings.fullscreenTitle, config.strings.fullscreenText,
                config.strings.fullscreenButton);
            gate = view.element;
            gate.addEventListener('cancel', function(event) {
                event.preventDefault();
            });
            view.button.addEventListener('click', async function() {
                view.button.disabled = true;
                try {
                    // Invoke before asynchronous work; browsers require a gesture.
                    await document.documentElement.requestFullscreen();
                    if (document.fullscreenElement) {
                        enteredFullscreen = true;
                        dismissGate();
                        checkFocus('fullscreen_exit', false);
                    } else {
                        view.message.textContent = config.strings.fullscreenError;
                    }
                } catch (error) {
                    view.message.textContent = config.strings.fullscreenError;
                } finally {
                    view.button.disabled = false;
                }
            });
        }

        /**
         * Commit the current loss once after its grace period.
         *
         * @param {Boolean} beacon Lifecycle transport.
         */
        function commitLoss(beacon) {
            if (!loss || loss.sent || navigation || stopped) {
                return;
            }
            loss.sent = true;
            collect({
                action: 'lost', eventuuid: loss.id, reason: loss.reason,
                clienttime: Math.floor(loss.wallstart / 1000)
            }, beacon);
        }

        /**
         * Complete a loss only after every monitored cause has ended.
         */
        function recover() {
            if (!loss) {
                return;
            }
            window.clearTimeout(loss.timer);
            var ended = loss;
            loss = null;
            var milliseconds = Math.max(0, window.performance.now() - ended.start);
            if (milliseconds < config.gracePeriodMs && !ended.sent) {
                return;
            }
            collect({
                action: 'returned', eventuuid: ended.id, reason: ended.reason,
                duration: Math.round(milliseconds / 1000)
            }, false);
            if (config.warnStudent && !gate && !stopped && !navigation) {
                if (warning) {
                    warning.close();
                }
                var view = dialog(config.strings.warningTitle, config.strings.warningText +
                    ' ' + config.strings.duration.replace('{$a}', Math.round(milliseconds / 1000) + ' s'),
                config.strings.continue);
                warning = view.element;
                view.button.addEventListener('click', function() {
                    view.element.close();
                });
                view.element.addEventListener('close', function() {
                    view.element.remove();
                    warning = null;
                });
            }
        }

        /**
         * Combine visibility, window focus and fullscreen without double counts.
         *
         * @param {String} reason Cause of this check.
         * @param {Boolean} immediate Lifecycle events bypass the grace delay.
         */
        function checkFocus(reason, immediate) {
            if (stopped || navigation ||
                    (config.requireFullscreen && fullscreenSupported && !enteredFullscreen)) {
                return;
            }
            var away = immediate || document.visibilityState === 'hidden' || !document.hasFocus() ||
                (config.requireFullscreen && fullscreenSupported && enteredFullscreen && !document.fullscreenElement);
            if (!away) {
                recover();
                return;
            }
            if (!loss) {
                loss = {
                    id: uuid(), reason: reason, start: window.performance.now(),
                    wallstart: Date.now(), sent: false, timer: null
                };
                loss.timer = window.setTimeout(function() {
                    commitLoss(false);
                }, config.gracePeriodMs);
            }
            if (immediate || config.gracePeriodMs === 0) {
                window.clearTimeout(loss.timer);
                commitLoss(immediate);
            }
        }

        /**
         * Prevent only optional new browsing contexts, preserving answer controls.
         *
         * @param {Event} event Browser event.
         */
        function restrictContext(event) {
            if (!config.blockShortcuts || stopped || navigation || event.defaultPrevented || event.repeat) {
                return;
            }
            var keyboard = event.type === 'keydown' && (event.ctrlKey || event.metaKey) &&
                !event.altKey && ['t', 'n'].includes(String(event.key).toLowerCase());
            if (keyboard || opensContext(event)) {
                event.preventDefault();
                if (!event.defaultPrevented) {
                    return;
                }
                collect({action: 'observed', eventuuid: uuid(), reason: 'shortcut_blocked'}, false);
                status(config.strings.shortcut);
            }
        }

        /**
         * Recognise link clicks opening another browsing context.
         *
         * @param {Event} event Browser event.
         * @return {Boolean}
         */
        function opensContext(event) {
            if (!['click', 'auxclick'].includes(event.type)) {
                return false;
            }
            var anchor = event.target && event.target.closest ? event.target.closest('a[href]') : null;
            if (!anchor || anchor.download) {
                return false;
            }
            var target = anchor.target && !['_self', '_top', '_parent'].includes(anchor.target);
            return Boolean(target || event.ctrlKey || event.metaKey || event.shiftKey || event.button === 1);
        }

        /**
         * Start synchronously, before any network request can delay listeners.
         */
        function start() {
            var host = document.getElementById('region-main') || document.body;
            host.insertBefore(badge, host.firstChild);
            status();
            document.addEventListener('visibilitychange', function() {
                checkFocus('visibility_hidden', false);
            });
            window.addEventListener('blur', function() {
                checkFocus('window_blur', false);
            });
            window.addEventListener('focus', function() {
                checkFocus('window_blur', false);
            });
            document.addEventListener('fullscreenchange', function() {
                if (document.fullscreenElement) {
                    enteredFullscreen = true;
                    dismissGate();
                } else {
                    showGate();
                }
                checkFocus('fullscreen_exit', false);
            });
            window.addEventListener('pagehide', function() {
                checkFocus('pagehide', true);
            });
            window.addEventListener('pageshow', function() {
                navigation = false;
                checkFocus('pagehide', false);
                showGate();
                drain();
            });
            document.addEventListener('freeze', function() {
                checkFocus('freeze', true);
            });
            window.addEventListener('online', drain);
            document.addEventListener('submit', function(event) {
                if (!event.target || event.target.id !== 'responseform') {
                    return;
                }
                navigation = true;
                if (loss) {
                    window.clearTimeout(loss.timer);
                    recover();
                }
                // Moodle's validation and AJAX handlers can cancel the navigation.
                window.setTimeout(function() {
                    if (event.defaultPrevented) {
                        navigation = false;
                        checkFocus('window_blur', false);
                        showGate();
                    }
                }, 0);
                window.setTimeout(function() {
                    navigation = false;
                }, 1500);
            }, true);
            document.addEventListener('keydown', restrictContext, true);
            document.addEventListener('click', restrictContext, true);
            document.addEventListener('auxclick', restrictContext, true);
            drain();
            showGate();
            checkFocus('window_blur', false);
            heartbeat = window.setInterval(async function() {
                if (stopped || navigation) {
                    return;
                }
                checkFocus('window_blur', false);
                if (queue.length || !initialised) {
                    await drain();
                } else if (document.visibilityState === 'visible' && !draining) {
                    try {
                        await request(payload({action: 'heartbeat'}));
                        status();
                    } catch (error) {
                        status(config.strings.pending);
                    }
                }
            }, config.heartbeatMs);
        }

        return {start: start, drain: drain};
    }

    return {
        /**
         * Initialise a monitor once per attempt page.
         *
         * @param {Object} config Server configuration.
         */
        init: function(config) {
            if (!config || !config.attemptId || !config.userId || !window.crypto ||
                    !window.performance || document.querySelector('.cdexamcontrol-monitor-badge')) {
                return;
            }
            createMonitor(config).start();
        }
    };
});
