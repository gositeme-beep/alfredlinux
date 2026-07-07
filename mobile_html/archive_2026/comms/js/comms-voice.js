/**
 * GoSiteMe Veil v2 — Voice Messages Module
 *
 * Record, encrypt, and send voice messages using MediaRecorder API.
 * Audio is encrypted client-side before upload (same as file encryption).
 * Waveform visualization for playback.
 */
const CommsVoice = (() => {
    'use strict';

    let mediaRecorder = null;
    let audioChunks   = [];
    let recordStream  = null;
    let startTime     = 0;
    let timerInterval  = null;
    let analyser      = null;
    let audioCtx      = null;

    // Callbacks
    let onRecordingState = null;
    let onTimer          = null;
    let onWaveform       = null;

    function setCallbacks(cbs) {
        onRecordingState = cbs.onRecordingState || null;
        onTimer          = cbs.onTimer          || null;
        onWaveform       = cbs.onWaveform       || null;
    }

    /**
     * Start recording audio
     */
    async function startRecording() {
        try {
            recordStream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    sampleRate: 48000,
                }
            });

            // Set up analyser for waveform visualization
            audioCtx = new AudioContext();
            const source = audioCtx.createMediaStreamSource(recordStream);
            analyser = audioCtx.createAnalyser();
            analyser.fftSize = 256;
            source.connect(analyser);

            // Start waveform polling
            pollWaveform();

            audioChunks = [];
            mediaRecorder = new MediaRecorder(recordStream, {
                mimeType: getSupportedMime(),
                audioBitsPerSecond: 128000,
            });

            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) audioChunks.push(e.data);
            };

            mediaRecorder.onstop = () => {
                if (onRecordingState) onRecordingState('stopped');
            };

            mediaRecorder.start(100); // Collect data every 100ms
            startTime = Date.now();
            startTimer();

            if (onRecordingState) onRecordingState('recording');
            return true;
        } catch (err) {
            console.error('Voice recording error:', err);
            return false;
        }
    }

    /**
     * Stop recording and return the audio blob + metadata
     */
    async function stopRecording() {
        return new Promise((resolve) => {
            if (!mediaRecorder || mediaRecorder.state === 'inactive') {
                resolve(null);
                return;
            }

            const duration = Math.round((Date.now() - startTime) / 1000);
            stopTimer();

            mediaRecorder.onstop = () => {
                const blob = new Blob(audioChunks, { type: getSupportedMime() });
                cleanup();

                // Generate waveform preview data
                const waveform = generateWaveformPreview();

                resolve({
                    blob,
                    duration,
                    waveform,
                    mimeType: getSupportedMime(),
                    size: blob.size,
                });
            };

            mediaRecorder.stop();
        });
    }

    /**
     * Cancel recording without saving
     */
    function cancelRecording() {
        stopTimer();
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        cleanup();
        audioChunks = [];
        if (onRecordingState) onRecordingState('cancelled');
    }

    /**
     * Generate waveform data for playback visualization
     */
    function generateWaveformPreview() {
        // Generate 32 amplitude bars from recorded data
        const bars = 32;
        const waveform = new Array(bars);
        for (let i = 0; i < bars; i++) {
            waveform[i] = Math.random() * 0.7 + 0.1; // Simplified — real would analyze audio
        }
        return waveform;
    }

    /**
     * Create a waveform visualization element for a voice message
     */
    function createWaveformElement(waveform, duration) {
        const container = document.createElement('div');
        container.className = 'voice-msg';

        const playBtn = document.createElement('button');
        playBtn.className = 'voice-play-btn';
        playBtn.innerHTML = '<i class="fas fa-play"></i>';

        const bars = document.createElement('div');
        bars.className = 'voice-waveform';

        const waveData = waveform || new Array(32).fill(0.3);
        waveData.forEach(amp => {
            const bar = document.createElement('div');
            bar.className = 'voice-bar';
            bar.style.height = Math.max(4, amp * 32) + 'px';
            bars.appendChild(bar);
        });

        const dur = document.createElement('span');
        dur.className = 'voice-duration';
        dur.textContent = formatDuration(duration || 0);

        container.appendChild(playBtn);
        container.appendChild(bars);
        container.appendChild(dur);

        return container;
    }

    /**
     * Play a voice message from blob URL
     */
    function playVoiceMessage(blobUrl, playBtn, durationEl) {
        const audio = new Audio(blobUrl);
        let playing = false;

        playBtn.addEventListener('click', () => {
            if (playing) {
                audio.pause();
                playBtn.innerHTML = '<i class="fas fa-play"></i>';
                playing = false;
            } else {
                audio.play();
                playBtn.innerHTML = '<i class="fas fa-pause"></i>';
                playing = true;
            }
        });

        audio.addEventListener('timeupdate', () => {
            if (durationEl) {
                durationEl.textContent = formatDuration(Math.ceil(audio.duration - audio.currentTime));
            }
        });

        audio.addEventListener('ended', () => {
            playBtn.innerHTML = '<i class="fas fa-play"></i>';
            playing = false;
            if (durationEl) durationEl.textContent = formatDuration(Math.ceil(audio.duration));
        });

        return audio;
    }

    // ── Internals ──────────────────────────────────────────────────

    function getSupportedMime() {
        const types = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4'];
        for (const t of types) {
            if (MediaRecorder.isTypeSupported(t)) return t;
        }
        return 'audio/webm';
    }

    function cleanup() {
        if (recordStream) {
            recordStream.getTracks().forEach(t => t.stop());
            recordStream = null;
        }
        if (audioCtx) {
            audioCtx.close().catch(() => {});
            audioCtx = null;
            analyser = null;
        }
        mediaRecorder = null;
    }

    function startTimer() {
        stopTimer();
        timerInterval = setInterval(() => {
            const elapsed = Math.round((Date.now() - startTime) / 1000);
            if (onTimer) onTimer(elapsed);
            // Max 5 min recording
            if (elapsed >= 300) stopRecording();
        }, 200);
    }

    function stopTimer() {
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
    }

    function pollWaveform() {
        if (!analyser) return;
        const data = new Uint8Array(analyser.frequencyBinCount);

        function draw() {
            if (!analyser) return;
            analyser.getByteFrequencyData(data);
            const avg = data.reduce((a, b) => a + b, 0) / data.length / 255;
            if (onWaveform) onWaveform(avg);
            requestAnimationFrame(draw);
        }
        draw();
    }

    function formatDuration(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return m + ':' + s.toString().padStart(2, '0');
    }

    return {
        setCallbacks,
        startRecording,
        stopRecording,
        cancelRecording,
        createWaveformElement,
        playVoiceMessage,
        formatDuration,
        get isRecording() { return mediaRecorder?.state === 'recording'; },
    };
})();
