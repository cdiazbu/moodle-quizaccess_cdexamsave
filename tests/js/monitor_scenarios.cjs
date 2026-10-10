/**
 * Browser-state regression tests. These simulate DOM/API contracts, not a real browser.
 *
 * @copyright 2026 Carlos Díaz Bueno
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
module.exports = async function runScenarios(source) {
    'use strict';
    const results = [];
    function assert(condition, message) {
        if (!condition) {
            throw new Error(message);
        }
    }
    async function settled() {
        for (let i = 0; i < 30; i++) {
            await Promise.resolve();
        }
    }
    function environment(overrides, transport) {
        let now = 100000;
        let counter = 1;
        let serial = 0;
        const timers = new Map();
        const intervals = new Map();
        const calls = [];
        const storage = new Map();
        let doc;
        class Target {
            constructor(tag) {
                this.tagName = tag;
                this.listeners = {};
                this.children = [];
                this.parent = null;
                this.attributes = {};
                this.open = false;
                this.textContent = '';
            }
            addEventListener(type, listener) {
                (this.listeners[type] || (this.listeners[type] = [])).push(listener);
            }
            dispatch(type, properties) {
                const event = Object.assign({
                    type, target: this, defaultPrevented: false,
                    preventDefault() { this.defaultPrevented = true; }
                }, properties);
                (this.listeners[type] || []).forEach(listener => listener(event));
                return event;
            }
            appendChild(child) {
                this.children.push(child);
                child.parent = this;
                return child;
            }
            insertBefore(child, before) {
                const index = this.children.indexOf(before);
                this.children.splice(index < 0 ? 0 : index, 0, child);
                child.parent = this;
                return child;
            }
            setAttribute(name, value) { this.attributes[name] = value; }
            focus() { doc.activeElement = this; }
            showModal() { this.open = true; }
            close() { this.open = false; this.dispatch('close'); }
            remove() {
                if (this.parent) {
                    this.parent.children = this.parent.children.filter(child => child !== this);
                }
            }
            closest() { return this.tagName === 'a' ? this : null; }
        }
        doc = new Target('document');
        doc.body = new Target('body');
        doc.documentElement = new Target('html');
        doc.visibilityState = 'visible';
        doc.fullscreenEnabled = true;
        doc.fullscreenElement = null;
        doc.focused = true;
        doc.getElementById = () => null;
        doc.hasFocus = () => doc.focused;
        doc.createElement = tag => new Target(tag);
        doc.querySelector = selector => doc.body.children.find(child =>
            selector === '.cdexamcontrol-monitor-badge' && child.className === 'cdexamcontrol-monitor-badge');
        doc.documentElement.requestFullscreen = () => {
            doc.fullscreenElement = doc.documentElement;
            doc.dispatch('fullscreenchange');
            return Promise.resolve();
        };
        const win = new Target('window');
        win.crypto = {getRandomValues(bytes) {
            for (let i = 0; i < bytes.length; i++) {
                bytes[i] = (serial + i) % 256;
            }
            serial++;
            return bytes;
        }};
        win.performance = {now: () => now};
        win.sessionStorage = {
            getItem: key => storage.get(key) || null,
            setItem: (key, value) => storage.set(key, value)
        };
        win.setTimeout = (fn, delay) => {
            const id = counter++;
            timers.set(id, {fn, at: now + delay});
            return id;
        };
        win.clearTimeout = id => timers.delete(id);
        win.setInterval = fn => {
            const id = counter++;
            intervals.set(id, fn);
            return id;
        };
        win.clearInterval = id => intervals.delete(id);
        const config = Object.assign({
            attemptId: 5, cmId: 7, userId: 11, heartbeatMs: 10000,
            gracePeriodMs: 1000, requireFullscreen: false, blockShortcuts: false,
            warnStudent: false,
            strings: {
                badge: 'connected', connecting: 'connecting', pending: 'pending',
                stopped: 'stopped', queueFull: 'full', fullscreenTitle: 'Fullscreen',
                fullscreenText: 'Enter fullscreen', fullscreenButton: 'Enter',
                fullscreenError: 'Retry', fullscreenUnsupported: 'Unsupported',
                warningTitle: 'Observation', warningText: 'Observed', duration: '{$a}',
                continue: 'Continue', shortcut: 'Intercepted'
            }
        }, overrides);
        const Ajax = {call(batch) {
            const data = batch[0].args;
            calls.push(data);
            return [transport ? transport(data) : Promise.resolve({accepted: true})];
        }};
        class ClockDate extends Date {
            static now() { return now; }
        }
        let plugin;
        new Function('define', 'window', 'document', 'navigator', 'M', 'Date', source)(
            (dependencies, factory) => { plugin = factory(Ajax); },
            win, doc, {sendBeacon: () => true},
            {cfg: {wwwroot: 'https://example.invalid', sesskey: 'test'}}, ClockDate
        );
        return {
            start: () => plugin.init(config), doc, win, calls, storage,
            actions: () => calls.map(call => call.action),
            tick(milliseconds) {
                const end = now + milliseconds;
                while (true) {
                    const due = Array.from(timers.entries()).filter(entry => entry[1].at <= end)
                        .sort((left, right) => left[1].at - right[1].at)[0];
                    if (!due) { break; }
                    now = due[1].at;
                    timers.delete(due[0]);
                    due[1].fn();
                }
                now = end;
            },
            heartbeat() { intervals.forEach(fn => fn()); },
            dialogs: () => doc.body.children.filter(child => child.tagName === 'dialog')
        };
    }
    async function test(name, fn) {
        await fn();
        results.push(name);
    }
    await test('listeners are ready while initial AJAX is pending', async () => {
        let resolveInit;
        const env = environment({}, data => data.action === 'init' ?
            new Promise(resolve => { resolveInit = resolve; }) : Promise.resolve({accepted: true}));
        env.start();
        await settled();
        env.doc.focused = false;
        env.win.dispatch('blur');
        env.tick(1200);
        env.doc.focused = true;
        env.win.dispatch('focus');
        resolveInit({accepted: true});
        await settled();
        assert(env.actions().join(',') === 'init,lost,returned', 'A pending init lost an observation');
    });
    await test('sub-grace focus change creates no observation', async () => {
        const env = environment();
        env.start();
        await settled();
        env.doc.focused = false;
        env.win.dispatch('blur');
        env.tick(500);
        env.doc.focused = true;
        env.win.dispatch('focus');
        await settled();
        assert(env.actions().join(',') === 'init', 'Grace period produced a false observation');
    });
    await test('hidden tab and window blur share one incident', async () => {
        const env = environment();
        env.start();
        await settled();
        env.doc.focused = false;
        env.win.dispatch('blur');
        env.doc.visibilityState = 'hidden';
        env.doc.dispatch('visibilitychange');
        env.tick(1100);
        await settled();
        env.doc.visibilityState = 'visible';
        env.doc.dispatch('visibilitychange');
        await settled();
        assert(!env.actions().includes('returned'), 'Visible but unfocused tab closed the loss');
        env.doc.focused = true;
        env.win.dispatch('focus');
        await settled();
        const events = env.calls.filter(call => call.eventuuid);
        assert(events.length === 2 && events[0].eventuuid === events[1].eventuuid, 'Loss duplicated');
    });
    await test('window focus cannot close a fullscreen loss', async () => {
        const env = environment({requireFullscreen: true});
        env.start();
        env.dialogs()[0].children[2].dispatch('click');
        await settled();
        assert(env.dialogs().length === 0, 'Fullscreen gate failed to close');
        env.doc.fullscreenElement = null;
        env.doc.dispatch('fullscreenchange');
        env.tick(1200);
        env.win.dispatch('focus');
        await settled();
        assert(!env.actions().includes('returned'), 'Fullscreen loss closed prematurely');
        env.dialogs()[0].children[2].dispatch('click');
        await settled();
        assert(env.actions().filter(action => action === 'returned').length === 1, 'Fullscreen recovery missing');
    });
    await test('offline observations retry in order without overwriting the queue', async () => {
        let offline = false;
        const env = environment({}, () => offline ?
            Promise.reject(new Error('offline')) : Promise.resolve({accepted: true}));
        env.start();
        await settled();
        offline = true;
        env.doc.focused = false;
        env.win.dispatch('blur');
        env.tick(1200);
        await settled();
        env.doc.focused = true;
        env.win.dispatch('focus');
        await settled();
        const queued = JSON.parse(Array.from(env.storage.values())[0]);
        assert(queued.map(item => item.payload.action).join(',') === 'lost,returned', 'Offline queue was corrupted');
        offline = false;
        env.win.dispatch('online');
        await settled();
        assert(JSON.parse(Array.from(env.storage.values())[0]).length === 0, 'Acknowledged queue not cleared');
        assert(env.actions().slice(-2).join(',') === 'lost,returned', 'Return overtook loss');
    });
    await test('shortcuts preserve clipboard zoom and keyboard link navigation', async () => {
        const env = environment({blockShortcuts: true});
        env.start();
        await settled();
        for (const key of ['c', 'v', '+', '-', 'Tab']) {
            const anchor = env.doc.createElement('a');
            anchor.target = '_blank';
            const event = env.doc.dispatch('keydown', {key, ctrlKey: true, target: anchor});
            assert(!event.defaultPrevented, 'Accessibility shortcut blocked: ' + key);
        }
        const event = env.doc.dispatch('keydown', {key: 't', ctrlKey: true});
        await settled();
        assert(event.defaultPrevented, 'Configured shortcut not intercepted');
        assert(env.calls.filter(call => call.action === 'observed').length === 1, 'Shortcut not instantaneous');
        assert(!env.actions().includes('lost'), 'Shortcut counted as time away');
    });
    await test('unsupported fullscreen allows answering with a visible limitation', async () => {
        const env = environment({requireFullscreen: true});
        env.doc.fullscreenEnabled = false;
        env.start();
        await settled();
        assert(env.dialogs().length === 0, 'Unsupported fullscreen trapped the student');
        assert(env.doc.querySelector('.cdexamcontrol-monitor-badge').textContent === 'Unsupported',
            'Fullscreen limitation was hidden');
    });
    await test('cancelled Moodle submission resumes monitoring', async () => {
        const env = environment();
        env.start();
        await settled();
        const form = env.doc.createElement('form');
        form.id = 'responseform';
        const submit = env.doc.dispatch('submit', {target: form});
        submit.preventDefault();
        env.tick(0);
        env.doc.focused = false;
        env.win.dispatch('blur');
        env.tick(1200);
        await settled();
        assert(env.actions().includes('lost'), 'Cancelled submission disabled monitoring');
    });
    await test('Moodle navigation creates no closure false positive', async () => {
        const env = environment();
        env.start();
        await settled();
        const form = env.doc.createElement('form');
        form.id = 'responseform';
        env.doc.dispatch('submit', {target: form});
        env.win.dispatch('pagehide');
        await settled();
        assert(env.actions().join(',') === 'init', 'Normal quiz navigation created an incident');
    });
    await test('successful beacon retains payload until acknowledgement', async () => {
        let offline = false;
        const env = environment({}, () => offline ?
            Promise.reject(new Error('offline')) : Promise.resolve({accepted: true}));
        env.start();
        await settled();
        offline = true;
        env.win.dispatch('pagehide');
        await settled();
        assert(JSON.parse(Array.from(env.storage.values())[0]).length === 1,
            'Unacknowledged beacon deleted the durable observation');
    });
    return results;
};
