/* ═══════════════════════════════════════════════════════════════
   CHESS MASTERS — Spatial Audio System
   GSM Alfred OS · Project Grandmaster II
   
   Immersive positional audio for the chess room:
   - Wood piece placement sounds (procedural)
   - Capture sounds with weight variation
   - Clock tick / time pressure
   - Room ambiance (fire crackle, clock ticking, rain)
   - Opponent thinking sounds (pen tap, breath)
   - Spatial audio positioning via Web Audio API
   - VR head-tracked spatial
   ═══════════════════════════════════════════════════════════════ */

const ChessAudio = (() => {
    'use strict';

    let audioCtx = null;
    let masterGain = null;
    let ambienceGain = null;
    let sfxGain = null;
    let listener = null;
    let initialized = false;

    let settings = {
        masterVolume: 0.8,
        sfxVolume: 0.9,
        ambienceVolume: 0.35,
        enabled: true,
    };

    // Active ambient sources
    let fireAmbience = null;
    let clockAmbience = null;
    let rainAmbience = null;

    function init() {
        if (initialized) return;
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();

        // Master chain
        masterGain = audioCtx.createGain();
        masterGain.gain.value = settings.masterVolume;
        masterGain.connect(audioCtx.destination);

        sfxGain = audioCtx.createGain();
        sfxGain.gain.value = settings.sfxVolume;
        sfxGain.connect(masterGain);

        ambienceGain = audioCtx.createGain();
        ambienceGain.gain.value = settings.ambienceVolume;
        ambienceGain.connect(masterGain);

        initialized = true;
    }

    function resume() {
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
    }

    // ═══ SYNTHESIZED SOUNDS ═══

    // Wooden piece placement — short thud
    function playMove(isCapture) {
        if (!initialized || !settings.enabled) return;
        resume();

        if (isCapture) {
            playCapture();
            return;
        }

        const now = audioCtx.currentTime;

        // Wood knock body
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        const filter = audioCtx.createBiquadFilter();

        osc.type = 'sine';
        osc.frequency.setValueAtTime(180 + Math.random() * 40, now);
        osc.frequency.exponentialRampToValueAtTime(60, now + 0.08);

        filter.type = 'lowpass';
        filter.frequency.setValueAtTime(800, now);
        filter.frequency.exponentialRampToValueAtTime(200, now + 0.06);

        gain.gain.setValueAtTime(0.4, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.12);

        osc.connect(filter);
        filter.connect(gain);
        gain.connect(sfxGain);

        osc.start(now);
        osc.stop(now + 0.15);

        // Table resonance
        const res = audioCtx.createOscillator();
        const resGain = audioCtx.createGain();

        res.type = 'sine';
        res.frequency.value = 90 + Math.random() * 20;

        resGain.gain.setValueAtTime(0.08, now + 0.02);
        resGain.gain.exponentialRampToValueAtTime(0.001, now + 0.25);

        res.connect(resGain);
        resGain.connect(sfxGain);
        res.start(now + 0.01);
        res.stop(now + 0.3);
    }

    // Capture — heavier impact with slide
    function playCapture() {
        if (!initialized || !settings.enabled) return;
        const now = audioCtx.currentTime;

        // Impact
        const hit = audioCtx.createOscillator();
        const hitGain = audioCtx.createGain();
        const hitFilter = audioCtx.createBiquadFilter();

        hit.type = 'sine';
        hit.frequency.setValueAtTime(250, now);
        hit.frequency.exponentialRampToValueAtTime(70, now + 0.06);

        hitFilter.type = 'lowpass';
        hitFilter.frequency.value = 1200;

        hitGain.gain.setValueAtTime(0.6, now);
        hitGain.gain.exponentialRampToValueAtTime(0.001, now + 0.15);

        hit.connect(hitFilter);
        hitFilter.connect(hitGain);
        hitGain.connect(sfxGain);
        hit.start(now);
        hit.stop(now + 0.2);

        // Slide/scrape
        const noise = createWhiteNoise(0.08);
        const noiseGain = audioCtx.createGain();
        const noiseFilter = audioCtx.createBiquadFilter();

        noiseFilter.type = 'bandpass';
        noiseFilter.frequency.value = 2000;
        noiseFilter.Q.value = 2;

        noiseGain.gain.setValueAtTime(0.12, now + 0.04);
        noiseGain.gain.exponentialRampToValueAtTime(0.001, now + 0.12);

        noise.connect(noiseFilter);
        noiseFilter.connect(noiseGain);
        noiseGain.connect(sfxGain);
        noise.start(now + 0.03);
        noise.stop(now + 0.15);

        // Removed piece settling
        const settle = audioCtx.createOscillator();
        const settleGain = audioCtx.createGain();

        settle.type = 'sine';
        settle.frequency.value = 140;

        settleGain.gain.setValueAtTime(0.15, now + 0.08);
        settleGain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);

        settle.connect(settleGain);
        settleGain.connect(sfxGain);
        settle.start(now + 0.07);
        settle.stop(now + 0.35);
    }

    // Check alert — tense tone
    function playCheck() {
        if (!initialized || !settings.enabled) return;
        const now = audioCtx.currentTime;

        // Two-tone alert
        [440, 554].forEach((freq, i) => {
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();

            osc.type = 'sine';
            osc.frequency.value = freq;

            gain.gain.setValueAtTime(0, now + i * 0.08);
            gain.gain.linearRampToValueAtTime(0.2, now + i * 0.08 + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, now + i * 0.08 + 0.15);

            osc.connect(gain);
            gain.connect(sfxGain);
            osc.start(now + i * 0.08);
            osc.stop(now + i * 0.08 + 0.2);
        });
    }

    // Game over — dramatic chord
    function playGameOver(isWin) {
        if (!initialized || !settings.enabled) return;
        const now = audioCtx.currentTime;

        const freqs = isWin ? [261, 329, 392, 523] : [220, 208, 196, 185];
        freqs.forEach((freq, i) => {
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();

            osc.type = isWin ? 'sine' : 'sawtooth';
            osc.frequency.value = freq;

            gain.gain.setValueAtTime(0, now + i * 0.12);
            gain.gain.linearRampToValueAtTime(0.15, now + i * 0.12 + 0.05);
            gain.gain.exponentialRampToValueAtTime(0.001, now + i * 0.12 + 0.8);

            osc.connect(gain);
            gain.connect(sfxGain);
            osc.start(now + i * 0.12);
            osc.stop(now + i * 0.12 + 1.0);
        });
    }

    // Illegal move — low buzz
    function playIllegal() {
        if (!initialized || !settings.enabled) return;
        const now = audioCtx.currentTime;

        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.value = 100;
        gain.gain.setValueAtTime(0.15, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
        osc.connect(gain);
        gain.connect(sfxGain);
        osc.start(now);
        osc.stop(now + 0.2);
    }

    // Clock tick (for time pressure)
    function playClockTick() {
        if (!initialized || !settings.enabled) return;
        const now = audioCtx.currentTime;

        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.value = 2200;
        gain.gain.setValueAtTime(0.06, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.03);
        osc.connect(gain);
        gain.connect(sfxGain);
        osc.start(now);
        osc.stop(now + 0.05);
    }

    // ═══ AMBIENT SOUNDSCAPES ═══

    function startAmbience() {
        if (!initialized || !settings.enabled) return;
        resume();
        startFireCrackle();
        startClockTick();
    }

    function stopAmbience() {
        if (fireAmbience) { fireAmbience.stop(); fireAmbience = null; }
        if (clockAmbience) { clockAmbience.stop(); clockAmbience = null; }
        if (rainAmbience) { rainAmbience.stop(); rainAmbience = null; }
    }

    function startFireCrackle() {
        if (fireAmbience) return;

        // Continuous brown noise filtered to sound like fire
        const bufferSize = audioCtx.sampleRate * 4;
        const buffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
        const data = buffer.getChannelData(0);
        let lastVal = 0;
        for (let i = 0; i < bufferSize; i++) {
            const white = Math.random() * 2 - 1;
            lastVal = (lastVal + (0.02 * white)) / 1.02;
            data[i] = lastVal * 3.5;
            // Random crackle pops
            if (Math.random() < 0.0003) {
                data[i] = (Math.random() - 0.5) * 0.6;
            }
        }

        const source = audioCtx.createBufferSource();
        source.buffer = buffer;
        source.loop = true;

        const filter = audioCtx.createBiquadFilter();
        filter.type = 'bandpass';
        filter.frequency.value = 400;
        filter.Q.value = 0.8;

        const gain = audioCtx.createGain();
        gain.gain.value = 0.15;

        source.connect(filter);
        filter.connect(gain);
        gain.connect(ambienceGain);
        source.start();
        fireAmbience = source;
    }

    function startClockTick() {
        if (clockAmbience) return;

        // Rhythmic soft tick every 1 second using scheduled oscillators
        const interval = setInterval(() => {
            if (!initialized || !settings.enabled) return;
            const now = audioCtx.currentTime;
            const tick = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            tick.type = 'sine';
            tick.frequency.value = 1800;
            gain.gain.setValueAtTime(0.02, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.015);
            tick.connect(gain);
            gain.connect(ambienceGain);
            tick.start(now);
            tick.stop(now + 0.02);
        }, 1000);

        clockAmbience = { stop: () => clearInterval(interval) };
    }

    function toggleRain(enabled) {
        if (enabled && !rainAmbience) {
            const bufferSize = audioCtx.sampleRate * 6;
            const buffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
            const data = buffer.getChannelData(0);
            for (let i = 0; i < bufferSize; i++) {
                data[i] = (Math.random() * 2 - 1) * 0.3;
            }
            const source = audioCtx.createBufferSource();
            source.buffer = buffer;
            source.loop = true;

            const filter = audioCtx.createBiquadFilter();
            filter.type = 'lowpass';
            filter.frequency.value = 600;

            const gain = audioCtx.createGain();
            gain.gain.value = 0.08;

            source.connect(filter);
            filter.connect(gain);
            gain.connect(ambienceGain);
            source.start();
            rainAmbience = source;
        } else if (!enabled && rainAmbience) {
            rainAmbience.stop();
            rainAmbience = null;
        }
    }

    // ═══ HELPERS ═══
    function createWhiteNoise(duration) {
        const sampleCount = audioCtx.sampleRate * duration;
        const buffer = audioCtx.createBuffer(1, sampleCount, audioCtx.sampleRate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < sampleCount; i++) {
            data[i] = Math.random() * 2 - 1;
        }
        const source = audioCtx.createBufferSource();
        source.buffer = buffer;
        return source;
    }

    function setVolume(type, value) {
        const v = Math.max(0, Math.min(1, value));
        switch (type) {
            case 'master': settings.masterVolume = v; if (masterGain) masterGain.gain.value = v; break;
            case 'sfx': settings.sfxVolume = v; if (sfxGain) sfxGain.gain.value = v; break;
            case 'ambience': settings.ambienceVolume = v; if (ambienceGain) ambienceGain.gain.value = v; break;
        }
    }

    function setEnabled(enabled) {
        settings.enabled = enabled;
        if (!enabled) stopAmbience();
    }

    function destroy() {
        stopAmbience();
        if (audioCtx) {
            audioCtx.close();
            audioCtx = null;
        }
        initialized = false;
    }

    return {
        init,
        resume,
        playMove,
        playCapture,
        playCheck,
        playGameOver,
        playIllegal,
        playClockTick,
        startAmbience,
        stopAmbience,
        toggleRain,
        setVolume,
        setEnabled,
        destroy,
    };
})();
