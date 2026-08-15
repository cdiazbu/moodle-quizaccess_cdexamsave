// This file is part of Moodle - http://moodle.org/

/**
 * Near-real-time teacher report renderer.
 *
 * @module     quizaccess_cdexamsave/live_report
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax'], function(Ajax) {
    'use strict';

    var METHOD_NAME = 'quizaccess_cdexamsave_get_live_data';

    /**
     * Create a cell with text-only content.
     *
     * @param {String|Number} value Cell value.
     * @param {String} className Optional class.
     * @return {HTMLTableCellElement}
     */
    function cell(value, className) {
        var element = document.createElement('td');
        element.textContent = value;
        if (className) {
            element.className = className;
        }
        return element;
    }

    /**
     * Add a one-cell empty-state row.
     *
     * @param {HTMLElement} body Table body.
     * @param {String} message Empty-state text.
     * @param {Number} columns Column count.
     */
    function emptyRow(body, message, columns) {
        var row = document.createElement('tr');
        var item = cell(message, 'text-center text-muted py-4');
        item.colSpan = columns;
        row.appendChild(item);
        body.appendChild(row);
    }

    /**
     * Create a configured report instance.
     *
     * @param {Object} config Report configuration.
     * @return {Object}
     */
    function createReport(config) {
        var paused = false;
        var busy = false;
        var timer = null;
        var firstSnapshot = true;
        var knownIncidentIds = {};
        var participants = [];
        var elements = {
            state: document.getElementById('cdexamsave-live-state'),
            updated: document.getElementById('cdexamsave-updated'),
            refresh: document.getElementById('cdexamsave-refresh'),
            pause: document.getElementById('cdexamsave-pause'),
            notifications: document.getElementById('cdexamsave-notifications'),
            error: document.getElementById('cdexamsave-error'),
            search: document.getElementById('cdexamsave-search'),
            filter: document.getElementById('cdexamsave-filter'),
            participants: document.getElementById('cdexamsave-participants-body'),
            incidents: document.getElementById('cdexamsave-incidents-body'),
            active: document.getElementById('cdexamsave-count-active'),
            attention: document.getElementById('cdexamsave-count-attention'),
            review: document.getElementById('cdexamsave-count-review'),
            connected: document.getElementById('cdexamsave-count-connected'),
            totalincidents: document.getElementById('cdexamsave-count-incidents')
        };

        /**
         * Test one participant against client-side controls.
         *
         * @param {Object} participant Participant data.
         * @return {Boolean}
         */
        function isVisible(participant) {
            var query = elements.search.value.trim().toLocaleLowerCase();
            var filter = elements.filter.value;
            if (query && participant.fullname.toLocaleLowerCase().indexOf(query) === -1) {
                return false;
            }
            if (filter === 'review') {
                return participant.needsreview;
            }
            if (filter === 'attention') {
                return participant.focuslost;
            }
            if (filter === 'disconnected') {
                return participant.status === 'disconnected';
            }
            return true;
        }

        /**
         * Render participant rows safely using textContent.
         */
        function renderParticipants() {
            var visible = participants.filter(isVisible);
            elements.participants.textContent = '';
            if (!visible.length) {
                emptyRow(
                    elements.participants,
                    participants.length ? config.strings.noFilteredAttempts : config.strings.noAttempts,
                    8
                );
                return;
            }
            visible.forEach(function(participant) {
                var row = document.createElement('tr');
                row.className = 'cdexamsave-participant-' + participant.status;
                if (participant.needsreview) {
                    row.classList.add('cdexamsave-participant-review');
                }
                row.appendChild(cell(participant.fullname, 'font-weight-bold'));
                row.appendChild(cell(participant.attempt));

                var connection = cell(participant.statustext);
                var connectionBadge = document.createElement('span');
                connectionBadge.className = 'cdexamsave-status cdexamsave-status-' + participant.status;
                connectionBadge.textContent = participant.statustext;
                connection.textContent = '';
                connection.appendChild(connectionBadge);
                row.appendChild(connection);

                row.appendChild(cell(
                    participant.focustext,
                    participant.focuslost ? 'font-weight-bold text-danger' : ''
                ));

                var review = cell(participant.reviewtext);
                var reviewBadge = document.createElement('span');
                reviewBadge.className = 'cdexamsave-review cdexamsave-review-' +
                    (participant.needsreview ? 'recommended' : 'normal');
                reviewBadge.textContent = participant.reviewtext;
                review.textContent = '';
                review.appendChild(reviewBadge);
                row.appendChild(review);

                row.appendChild(cell(participant.incidentcount));
                row.appendChild(cell(participant.totaldurationtext));
                row.appendChild(cell(participant.lastheartbeattext));
                elements.participants.appendChild(row);
            });
        }

        /**
         * Render incident rows safely using textContent.
         *
         * @param {Array} incidents Incident data.
         */
        function renderIncidents(incidents) {
            elements.incidents.textContent = '';
            if (!incidents.length) {
                emptyRow(elements.incidents, config.strings.noIncidents, 6);
                return;
            }
            incidents.forEach(function(incident) {
                var row = document.createElement('tr');
                if (incident.active) {
                    row.className = 'cdexamsave-incident-active';
                }
                row.appendChild(cell(incident.fullname, 'font-weight-bold'));
                row.appendChild(cell(incident.attempt));
                row.appendChild(cell(incident.startedtext));
                row.appendChild(cell(incident.endedtext, incident.active ? 'text-danger font-weight-bold' : ''));
                row.appendChild(cell(incident.durationtext));
                row.appendChild(cell(incident.reasontext));
                elements.incidents.appendChild(row);
            });
        }

        /**
         * Notify the teacher about incidents first seen after the initial
         * snapshot. Permission is requested only from an explicit button.
         *
         * @param {Array} incidents Incident data.
         */
        function notifyNewIncidents(incidents) {
            incidents.forEach(function(incident) {
                var key = String(incident.id);
                if (!firstSnapshot && !knownIncidentIds[key] && window.Notification &&
                        window.Notification.permission === 'granted') {
                    var body = config.strings.notificationBody
                        .replace('{$student}', function() {
                            return incident.fullname;
                        })
                        .replace('{$reason}', function() {
                            return incident.reasontext;
                        });
                    var notification = new window.Notification(config.strings.notificationTitle, {body: body});
                    notification.onclick = function() {
                        window.focus();
                        notification.close();
                    };
                }
                knownIncidentIds[key] = true;
            });
            firstSnapshot = false;
        }

        /**
         * Apply one complete server payload.
         *
         * @param {Object} data Live data.
         */
        function render(data) {
            elements.active.textContent = data.summary.activeAttempts;
            elements.attention.textContent = data.summary.attentionNow;
            elements.review.textContent = data.summary.needsReview;
            elements.connected.textContent = data.summary.connectedAttempts;
            elements.totalincidents.textContent = data.summary.totalIncidents;
            elements.updated.textContent = config.strings.lastUpdated.replace('{$a}', data.serverTimeText);
            participants = data.participants || [];
            renderParticipants();
            notifyNewIncidents(data.incidents || []);
            renderIncidents(data.incidents || []);
        }

        /**
         * Schedule the next automatic request.
         *
         * @param {Number} delay Delay in milliseconds.
         */
        function schedule(delay) {
            window.clearTimeout(timer);
            if (!paused) {
                timer = window.setTimeout(load, delay);
            }
        }

        /**
         * Fetch one live snapshot through Moodle's AJAX external-service API.
         *
         * @return {Promise}
         */
        function load() {
            if (busy || paused || document.visibilityState === 'hidden') {
                schedule(config.refreshMs);
                return Promise.resolve();
            }
            busy = true;
            return Ajax.call([{
                methodname: METHOD_NAME,
                args: {
                    cmid: config.cmId,
                    groupid: config.groupId
                }
            }])[0].then(function(data) {
                elements.error.classList.add('d-none');
                elements.error.textContent = '';
                render(data);
                schedule(config.refreshMs);
                return null;
            }).catch(function() {
                elements.error.textContent = config.strings.pollError;
                elements.error.classList.remove('d-none');
                schedule(Math.max(config.refreshMs * 2, 5000));
                return null;
            }).finally(function() {
                busy = false;
            });
        }

        /**
         * Toggle automatic polling.
         */
        function togglePause() {
            paused = !paused;
            elements.state.textContent = paused ? config.strings.paused : config.strings.live;
            elements.state.classList.toggle('cdexamsave-live-paused', paused);
            elements.pause.textContent = paused ? config.strings.resume : config.strings.pause;
            if (paused) {
                window.clearTimeout(timer);
            } else {
                load();
            }
        }

        /**
         * Ask for browser notification permission after a teacher action.
         */
        function enableNotifications() {
            if (!window.Notification) {
                elements.notifications.classList.add('d-none');
                return;
            }
            window.Notification.requestPermission().then(function(permission) {
                elements.notifications.textContent = permission === 'granted' ?
                    config.strings.notificationsEnabled : config.strings.notificationsDenied;
                elements.notifications.disabled = true;
                return null;
            }).catch(function() {
                elements.notifications.textContent = config.strings.notificationsDenied;
                elements.notifications.disabled = true;
                return null;
            });
        }

        /**
         * Bind controls and load the first snapshot.
         */
        function start() {
            elements.refresh.addEventListener('click', function() {
                var waspaused = paused;
                paused = false;
                load().finally(function() {
                    paused = waspaused;
                }).catch(function() {
                    return null;
                });
            });
            elements.pause.addEventListener('click', togglePause);
            elements.search.addEventListener('input', renderParticipants);
            elements.filter.addEventListener('change', renderParticipants);
            if (window.Notification) {
                elements.notifications.addEventListener('click', enableNotifications);
                if (window.Notification.permission === 'granted') {
                    elements.notifications.textContent = config.strings.notificationsEnabled;
                    elements.notifications.disabled = true;
                } else if (window.Notification.permission === 'denied') {
                    elements.notifications.textContent = config.strings.notificationsDenied;
                    elements.notifications.disabled = true;
                }
            } else {
                elements.notifications.classList.add('d-none');
            }
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible' && !paused) {
                    load();
                }
            });
            load();
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
            if (!config || !config.cmId) {
                return;
            }
            createReport(config).start();
        }
    };
});
