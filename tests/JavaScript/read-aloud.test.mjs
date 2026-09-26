import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/read-aloud.js', import.meta.url), 'utf8');
const flush = () => new Promise(resolve => setImmediate(resolve));
function deferred() {
    let resolve, reject;
    const promise = new Promise((yes, no) => { resolve = yes; reject = no; });
    return { promise, resolve, reject };
}
function harness({ duration = 60, canRecord = true, permission, startError, response } = {}) {
    const elements = new Map();
    function element(id) {
        if (!elements.has(id)) {
            const handlers = new Map(), classes = new Set();
            elements.set(id, {
                textContent: '', style: {}, disabled: false, hidden: true, dataset: {},
                classList: {
                    add: (...names) => names.forEach(x => classes.add(x)),
                    remove: (...names) => names.forEach(x => classes.delete(x)),
                    toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name),
                    contains: name => classes.has(name),
                },
                addEventListener: (name, fn) => {
                    if (!handlers.has(name)) handlers.set(name, []);
                    handlers.get(name).push(fn);
                },
                emit: (name, extra = {}) => {
                    for (const fn of handlers.get(name) || []) fn({ type: name, preventDefault() {}, ...extra });
                },
                setAttribute(name, value) { this[name] = value; },
                getAttribute: () => '/student/read-aloud/1/upload',
                querySelectorAll: () => [],
            });
        }
        return elements.get(id);
    }
    const root = element('read-aloud-recorder');
    root.dataset = { durationSeconds: String(duration), canRecord: String(canRecord), indexUrl: '/student/read-aloud', debug: 'false' };
    const window = element('window');
    window.isSecureContext = true;
    window.matchMedia = () => ({ matches: false });
    window.location = { href: '' };
    const document = element('document');
    document.getElementById = element;
    let now = 0, nextTimer = 0, microphoneRequests = 0;
    const timers = new Map(), recorders = [], requests = [];
    const track = { stopped: false, stop() { this.stopped = true; } };
    const stream = { getTracks: () => [track] };
    class Recorder {
        static isTypeSupported() { return true; }
        constructor() { this.stream = stream; this.mimeType = 'audio/webm'; this.state = 'inactive'; this.stops = 0; recorders.push(this); }
        start() { if (startError) throw startError; this.state = 'recording'; }
        stop() { this.stops++; this.state = 'inactive'; }
        chunk(text) { this.ondataavailable({ data: new Blob([text], { type: this.mimeType }) }); }
        finish(text = 'final audio') { if (text) this.chunk(text); this.onstop(); }
        fail() { this.state = 'inactive'; this.onerror({ error: new Error('device lost') }); }
    }
    class Form {
        constructor() { this.values = new Map([['_token', 'csrf'], ['recording_token', 'stable-attempt-token']]); }
        set(name, value) { this.values.set(name, value); }
        get(name) { return this.values.get(name); }
    }
    const ok = () => ({ ok: true, status: 201, redirected: false, headers: { get: () => 'application/json' }, text: async () => JSON.stringify({ status: 'pending', recording_id: 1 }) });
    const context = {
        window, document, navigator: { mediaDevices: { getUserMedia: async () => { microphoneRequests++; return permission ? permission.promise : stream; } } },
        MediaRecorder: Recorder, Blob, FormData: Form, TypeError, performance: { now: () => now },
        setInterval: (fn, ms) => { const id = ++nextTimer; timers.set(id, { fn, ms, next: now + ms }); return id; },
        clearInterval: id => timers.delete(id),
        fetch: async (url, options) => { requests.push(options.body); return response ? response(requests.length) : ok(); },
        console: { log() {}, error() {} },
    };
    vm.runInNewContext(source, context);
    const advance = ms => {
        const end = now + ms;
        while (timers.size) {
            const due = [...timers].filter(([, t]) => t.next <= end).sort((a, b) => a[1].next - b[1].next)[0];
            if (!due) break;
            now = due[1].next;
            due[1].next += due[1].ms;
            due[1].fn();
        }
        now = end;
    };
    return {
        element, window, document, stream, track, recorders, requests, timers, advance, ok,
        microphoneRequests: () => microphoneRequests,
        start: async () => { element('mic-btn').emit('click'); await flush(); },
        jump: ms => { now += ms; document.emit('visibilitychange'); },
        time: () => element('mic-timer').textContent,
    };
}

test('full one-minute timer stays idle on page load and throughout microphone permission', async () => {
    const permission = deferred(), h = harness({ permission });
    assert.equal(h.time(), '01:00');
    h.advance(20000);
    assert.equal(h.time(), '01:00');
    assert.equal(h.microphoneRequests(), 0);
    await h.start();
    h.advance(30000);
    assert.equal(h.time(), '01:00');
    assert.equal(h.recorders.length, 0);
    permission.resolve(h.stream);
    await flush();
    assert.equal(h.recorders[0].state, 'recording');
    assert.equal(h.time(), '01:00');
    h.advance(1000);
    assert.equal(h.time(), '00:59');
});

test('manual stop at 00:40 freezes the timer and submits every chunk only after onstop', async () => {
    const h = harness();
    await h.start();
    h.recorders[0].chunk('first ');
    h.advance(20000);
    assert.equal(h.time(), '00:40');
    h.element('mic-btn').emit('click');
    h.advance(5000);
    assert.equal(h.time(), '00:40');
    assert.equal(h.requests.length, 0);
    h.recorders[0].finish('last');
    await flush();
    assert.equal(h.requests.length, 1);
    assert.equal(await h.requests[0].get('recording').text(), 'first last');
    assert.equal(h.element('success-overlay').style.display, 'flex');
});

test('expiry stops once, waits for the final chunk, and blocks rapid clicks and duplicate stop events', async () => {
    const h = harness();
    for (let i = 0; i < 10; i++) h.element('mic-btn').emit('click');
    await flush();
    assert.equal(h.microphoneRequests(), 1);
    assert.equal(h.recorders.length, 1);
    h.recorders[0].chunk('beginning ');
    h.advance(50000);
    assert.equal(h.time(), '00:10');
    assert.ok(h.element('mic-timer').classList.contains('time-low'));
    assert.match(h.element('rec-status').textContent, /10 seconds/);
    h.advance(10000);
    assert.equal(h.time(), '00:00');
    assert.equal(h.recorders[0].stops, 1);
    assert.match(h.element('rec-status').textContent, /Time's up/);
    for (let i = 0; i < 10; i++) h.element('mic-btn').emit('click');
    h.advance(10000);
    assert.equal(h.requests.length, 0);
    assert.equal(h.recorders.length, 1);
    h.recorders[0].finish('tail');
    await flush();
    h.recorders[0].onstop();
    h.element('retry-upload').emit('click');
    await flush();
    assert.equal(h.requests.length, 1);
    assert.equal(await h.requests[0].get('recording').text(), 'beginning tail');
    assert.equal(h.element('mic-btn').disabled, true);
});

test('microphone denial leaves full time, restores controls and never submits', async () => {
    const permission = deferred(), h = harness({ permission });
    await h.start();
    permission.reject(Object.assign(new Error('denied'), { name: 'NotAllowedError' }));
    await flush();
    h.advance(120000);
    assert.equal(h.time(), '01:00');
    assert.equal(h.element('mic-btn').disabled, false);
    assert.equal(h.requests.length, 0);
    assert.match(h.element('rec-status').textContent, /permission was denied/);
});

test('recorder startup failure stops microphone and never starts countdown', async () => {
    const h = harness({ startError: new Error('start failed') });
    await h.start();
    h.advance(60000);
    assert.equal(h.time(), '01:00');
    assert.equal(h.element('mic-btn').disabled, false);
    assert.equal(h.track.stopped, true);
    assert.equal(h.requests.length, 0);
});

test('each new two-minute attempt starts at 02:00', async () => {
    for (let attempt = 0; attempt < 2; attempt++) {
        const h = harness({ duration: 120 });
        assert.equal(h.time(), '02:00');
        await h.start();
        h.advance(1000);
        assert.equal(h.time(), '01:59');
    }
});

test('delayed browser callbacks use the deadline and do not extend recording', async () => {
    const h = harness();
    await h.start();
    h.jump(65000);
    assert.equal(h.time(), '00:00');
    assert.equal(h.recorders[0].stops, 1);
    assert.equal(h.requests.length, 0);
});

test('network failure preserves exact audio and token for a single retry', async () => {
    const retry = deferred();
    const h = harness({ response: count => {
        if (count === 1) throw new TypeError('offline');
        return retry.promise;
    } });
    await h.start();
    h.advance(60000);
    h.recorders[0].finish('preserve this audio');
    await flush();
    assert.equal(h.element('retry-upload').hidden, false);
    assert.equal(h.element('mic-btn').disabled, true);
    assert.match(h.element('rec-status').textContent, /Check your connection/);
    for (let i = 0; i < 10; i++) h.element('retry-upload').emit('click');
    await flush();
    assert.equal(h.requests.length, 2);
    assert.equal(h.requests[0].get('recording'), h.requests[1].get('recording'));
    assert.equal(h.requests[0].get('recording_token'), h.requests[1].get('recording_token'));
    retry.resolve(h.ok());
    await flush();
    assert.equal(h.element('success-overlay').style.display, 'flex');
});

test('server errors show retry without silently discarding audio', async () => {
    const h = harness({ response: () => ({ ok: false, status: 500, headers: { get: () => 'application/json' }, text: async () => '{}' }) });
    await h.start();
    h.advance(60000);
    h.recorders[0].finish();
    await flush();
    assert.match(h.element('rec-status').textContent, /server could not save/);
    assert.equal(h.element('retry-upload').hidden, false);
    assert.match(h.element('mic-hint').textContent, /audio is kept/);
});

test('invalid durations and disallowed reattempts cannot start a recorder', async () => {
    for (const options of [{ duration: 0 }, { duration: -1 }, { duration: 'invalid' }, { canRecord: false }]) {
        const h = harness(options);
        await h.start();
        assert.equal(h.element('mic-btn').disabled, true);
        assert.equal(h.microphoneRequests(), 0);
        assert.equal(h.requests.length, 0);
    }
});

test('empty or interrupted recordings are not submitted and can restart at full time', async () => {
    for (const fail of [false, true]) {
        const h = harness();
        await h.start();
        h.advance(20000);
        if (fail) h.recorders[0].fail();
        else h.element('mic-btn').emit('click');
        h.recorders[0].finish('');
        await flush();
        assert.equal(h.requests.length, 0);
        assert.equal(h.time(), '01:00');
        assert.equal(h.element('mic-btn').disabled, false);
        await h.start();
        assert.equal(h.time(), '01:00');
        assert.equal(h.recorders.length, 2);
    }
});

test('leaving the page cleans up timers and microphone without submitting partial audio', async () => {
    const h = harness();
    await h.start();
    h.advance(10000);
    h.window.emit('pagehide');
    h.recorders[0].finish();
    await flush();
    assert.equal(h.timers.size, 0);
    assert.equal(h.track.stopped, true);
    assert.equal(h.requests.length, 0);
});

test('leaving during permission prompt releases the eventual microphone stream', async () => {
    const permission = deferred(), h = harness({ permission });
    await h.start();
    h.window.emit('beforeunload');
    permission.resolve(h.stream);
    await flush();
    assert.equal(h.recorders.length, 0);
    assert.equal(h.track.stopped, true);
    assert.equal(h.requests.length, 0);
});

test('touch release during permission prompt never starts a stuck recorder', async () => {
    const permission = deferred(), h = harness({ permission });
    h.element('mic-btn').emit('touchstart');
    h.window.emit('touchend');
    permission.resolve(h.stream);
    await flush();
    assert.equal(h.recorders.length, 0);
    assert.equal(h.time(), '01:00');
    assert.equal(h.element('mic-btn').disabled, false);
});
