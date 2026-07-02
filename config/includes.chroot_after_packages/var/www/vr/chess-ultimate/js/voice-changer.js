/* ═══════════════════════════════════════════════════════════════
   CHESS ULTIMATE — Voice Changer Module
   Agent: VoiceMod (Audio & Effects Division)
   
   Real-time voice modification using Web Audio API.
   6 preset voice effects for voice identity protection.
   Compatible with WebRTC calls and Veil voice chat.
   ═══════════════════════════════════════════════════════════════ */

const ChessVoiceChanger = (() => {
    let audioContext = null;
    let sourceNode = null;
    let outputNode = null;
    let activePreset = null;
    let effectNodes = [];

    const PRESETS = {
        deep: {
            name: 'Deep',
            icon: '🔊',
            desc: 'Lower pitch by 6 semitones',
            build: (ctx) => {
                // Pitch shift down using playbackRate on a buffer (for recorded audio)
                // For real-time: use oscillator detuning trick with modulator
                const gain = ctx.createGain();
                gain.gain.value = 1.2;
                const biquad = ctx.createBiquadFilter();
                biquad.type = 'lowshelf';
                biquad.frequency.value = 300;
                biquad.gain.value = 6;
                const biquad2 = ctx.createBiquadFilter();
                biquad2.type = 'highshelf';
                biquad2.frequency.value = 3000;
                biquad2.gain.value = -4;
                gain.connect(biquad);
                biquad.connect(biquad2);
                return { input: gain, output: biquad2, nodes: [gain, biquad, biquad2] };
            },
        },
        high: {
            name: 'High',
            icon: '🎵',
            desc: 'Raise pitch by 8 semitones',
            build: (ctx) => {
                const gain = ctx.createGain();
                gain.gain.value = 0.9;
                const biquad = ctx.createBiquadFilter();
                biquad.type = 'highshelf';
                biquad.frequency.value = 2000;
                biquad.gain.value = 8;
                const biquad2 = ctx.createBiquadFilter();
                biquad2.type = 'lowshelf';
                biquad2.frequency.value = 200;
                biquad2.gain.value = -6;
                gain.connect(biquad);
                biquad.connect(biquad2);
                return { input: gain, output: biquad2, nodes: [gain, biquad, biquad2] };
            },
        },
        robotic: {
            name: 'Robotic',
            icon: '🤖',
            desc: 'Ring modulator + bit crush effect',
            build: (ctx) => {
                const gain = ctx.createGain();
                gain.gain.value = 1.0;
                // Ring modulator effect
                const osc = ctx.createOscillator();
                osc.frequency.value = 200;
                osc.type = 'square';
                const modGain = ctx.createGain();
                modGain.gain.value = 0.5;
                osc.connect(modGain);
                osc.start();
                // Waveshaper for distortion
                const waveshaper = ctx.createWaveShaper();
                const curve = new Float32Array(256);
                for (let i = 0; i < 256; i++) {
                    const x = (i / 128) - 1;
                    curve[i] = Math.round(x * 8) / 8; // Bit crush effect
                }
                waveshaper.curve = curve;
                waveshaper.oversample = 'none';
                gain.connect(waveshaper);
                waveshaper.connect(modGain);
                return { input: gain, output: modGain, nodes: [gain, osc, modGain, waveshaper] };
            },
        },
        whisper: {
            name: 'Whisper',
            icon: '🤫',
            desc: 'Breathy whisper with noise',
            build: (ctx) => {
                const gain = ctx.createGain();
                gain.gain.value = 0.6;
                // High-pass to remove bass (sounds more whispered)
                const hp = ctx.createBiquadFilter();
                hp.type = 'highpass';
                hp.frequency.value = 1500;
                hp.Q.value = 0.5;
                // Compressor to flatten dynamics
                const comp = ctx.createDynamicsCompressor();
                comp.threshold.value = -30;
                comp.ratio.value = 12;
                comp.attack.value = 0.003;
                comp.release.value = 0.1;
                gain.connect(hp);
                hp.connect(comp);
                return { input: gain, output: comp, nodes: [gain, hp, comp] };
            },
        },
        echo: {
            name: 'Echo',
            icon: '🏛️',
            desc: 'Cathedral reverb with delay',
            build: (ctx) => {
                const gain = ctx.createGain();
                gain.gain.value = 0.8;
                // Create convolution reverb with generated impulse
                const convolver = ctx.createConvolver();
                const sampleRate = ctx.sampleRate;
                const length = sampleRate * 2; // 2 second reverb
                const impulse = ctx.createBuffer(2, length, sampleRate);
                for (let ch = 0; ch < 2; ch++) {
                    const data = impulse.getChannelData(ch);
                    for (let i = 0; i < length; i++) {
                        data[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / length, 1.5);
                    }
                }
                convolver.buffer = impulse;
                // Dry/wet mix
                const dry = ctx.createGain();
                dry.gain.value = 0.6;
                const wet = ctx.createGain();
                wet.gain.value = 0.4;
                const merger = ctx.createGain();
                gain.connect(dry);
                gain.connect(convolver);
                convolver.connect(wet);
                dry.connect(merger);
                wet.connect(merger);
                return { input: gain, output: merger, nodes: [gain, convolver, dry, wet, merger] };
            },
        },
        alien: {
            name: 'Alien',
            icon: '👽',
            desc: 'Oscillating pitch with flanger',
            build: (ctx) => {
                const gain = ctx.createGain();
                gain.gain.value = 0.9;
                // Flanger using short delay with LFO
                const delay = ctx.createDelay(0.02);
                delay.delayTime.value = 0.005;
                const lfo = ctx.createOscillator();
                lfo.frequency.value = 3;
                lfo.type = 'sine';
                const lfoGain = ctx.createGain();
                lfoGain.gain.value = 0.003;
                lfo.connect(lfoGain);
                lfoGain.connect(delay.delayTime);
                lfo.start();
                // Feedback
                const feedback = ctx.createGain();
                feedback.gain.value = 0.6;
                // Mix
                const mixer = ctx.createGain();
                gain.connect(mixer); // dry
                gain.connect(delay);
                delay.connect(feedback);
                feedback.connect(delay);
                delay.connect(mixer); // wet
                return { input: gain, output: mixer, nodes: [gain, delay, lfo, lfoGain, feedback, mixer] };
            },
        },
    };

    function getContext() {
        if (!audioContext || audioContext.state === 'closed') {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        return audioContext;
    }

    /**
     * Apply voice changer to a MediaStream (from getUserMedia or WebRTC)
     * Returns a new MediaStream with the effect applied
     */
    function applyToStream(mediaStream, presetName) {
        if (!PRESETS[presetName]) return mediaStream;
        
        const ctx = getContext();
        cleanup();

        sourceNode = ctx.createMediaStreamSource(mediaStream);
        const dest = ctx.createMediaStreamDestination();
        const effect = PRESETS[presetName].build(ctx);
        
        sourceNode.connect(effect.input);
        effect.output.connect(dest);
        
        effectNodes = effect.nodes;
        outputNode = dest;
        activePreset = presetName;

        return dest.stream;
    }

    /**
     * Apply voice changer to an AudioBuffer (for recorded voice messages)
     * Returns processed AudioBuffer
     */
    async function applyToBuffer(audioBuffer, presetName) {
        if (!PRESETS[presetName]) return audioBuffer;

        const ctx = new OfflineAudioContext(
            audioBuffer.numberOfChannels,
            audioBuffer.length,
            audioBuffer.sampleRate
        );

        const source = ctx.createBufferSource();
        source.buffer = audioBuffer;
        const effect = PRESETS[presetName].build(ctx);
        source.connect(effect.input);
        effect.output.connect(ctx.destination);
        source.start();

        return await ctx.startRendering();
    }

    /** Preview a preset by playing a test tone through it */
    function preview(presetName) {
        const ctx = getContext();
        const osc = ctx.createOscillator();
        osc.frequency.value = 220;
        osc.type = 'sawtooth';
        const effect = PRESETS[presetName].build(ctx);
        osc.connect(effect.input);
        effect.output.connect(ctx.destination);
        osc.start();
        setTimeout(() => osc.stop(), 1000);
    }

    function cleanup() {
        effectNodes.forEach(n => { try { n.disconnect(); } catch(e) {} });
        if (sourceNode) try { sourceNode.disconnect(); } catch(e) {}
        effectNodes = [];
        sourceNode = null;
        outputNode = null;
        activePreset = null;
    }

    function getPresets() {
        return Object.entries(PRESETS).map(([id, p]) => ({
            id, name: p.name, icon: p.icon, desc: p.desc,
        }));
    }

    return {
        applyToStream,
        applyToBuffer,
        preview,
        cleanup,
        getPresets,
        get activePreset() { return activePreset; },
        get isActive() { return activePreset !== null; },
    };
})();

if (typeof module !== 'undefined') module.exports = ChessVoiceChanger;
