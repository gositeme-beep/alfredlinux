/* ===== VOICE CLONING ENGINE ===== */
const VoiceClone = (() => {
    const SENTENCES = [
        "The quick brown fox jumps over the lazy dog near the riverbank.",
        "Please hold while I transfer you to the appropriate department.",
        "Your appointment has been confirmed for next Tuesday at three PM.",
        "Thank you for calling our customer support line today.",
        "I understand your concern and I will help you resolve this issue.",
        "Our business hours are Monday through Friday, nine to five.",
        "Would you like me to send a confirmation email to your address?",
        "Let me check the status of your order right away.",
        "The total amount due on your account is forty-seven dollars.",
        "I am going to connect you with a specialist who can help.",
        "Please verify your identity by providing your date of birth.",
        "We appreciate your patience while we look into this matter.",
        "Your subscription has been successfully renewed for another year.",
        "Is there anything else I can assist you with today?",
        "The refund has been processed and should appear within five days.",
        "I will create a support ticket for your request right now.",
        "Our team is available twenty-four hours a day, seven days a week.",
        "You can also reach us through our online chat portal.",
        "The system maintenance is scheduled for this Saturday evening.",
        "Your password has been reset and a new one has been sent.",
        "Let me walk you through the steps to resolve this problem.",
        "We value your feedback and take it very seriously.",
        "The product you are looking for is currently in stock.",
        "I will escalate this to our management team immediately.",
        "Please note that this call may be recorded for quality assurance.",
        "Your account has been updated with the new information.",
        "The estimated delivery time is three to five business days.",
        "I apologize for the inconvenience you have experienced.",
        "Our premium plan includes unlimited storage and priority support.",
        "The promotion code has been applied to your purchase.",
        "Could you please spell your last name for me?",
        "We offer a thirty-day money-back guarantee on all products.",
        "The technical team has been notified about the outage.",
        "Your payment method on file has been charged successfully.",
        "I recommend upgrading to our professional tier for better features.",
        "The warranty on your device expires at the end of this month.",
        "Please allow up to twenty-four hours for the changes to take effect.",
        "We are committed to providing the best service possible.",
        "The features you requested will be available in our next update.",
        "I can offer you a fifteen percent discount on your next order.",
        "Please make sure to save your work before the system update.",
        "Our security team has reviewed and approved your access request.",
        "The conference call has been scheduled for tomorrow at noon.",
        "Your invoice for this month has been generated and is ready.",
        "I will send you a follow-up email with all the details.",
        "The application process typically takes about ten minutes.",
        "We have received your documents and they are under review.",
        "The server migration will begin at midnight Eastern time.",
        "Thank you for being a loyal customer for over three years.",
        "Is there a preferred time for us to call you back?"
    ];

    let currentSentence = 0;
    let recordings = [];
    let mediaRecorder = null;
    let audioContext = null;
    let analyser = null;
    let animFrame = null;
    let isRecording = false;
    let recordingStart = 0;
    let timerInterval = null;
    let audioChunks = [];

    const scriptText = document.getElementById('vcScriptText');
    const counter = document.getElementById('vcCounter');
    const progressText = document.getElementById('vcProgressText');
    const progressFill = document.getElementById('vcProgressFill');
    const qualityEl = document.getElementById('vcQuality');
    const recBtn = document.getElementById('vcRecBtn');
    const recLabel = document.getElementById('vcRecLabel');
    const uploadBtn = document.getElementById('vcUploadBtn');
    const timerEl = document.getElementById('vcTimer');
    const waveCanvas = document.getElementById('vcWaveformCanvas');
    const waveCtx = waveCanvas ? waveCanvas.getContext('2d') : null;

    function init() {
        updateScript();
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
    }

    function resizeCanvas() {
        if (!waveCanvas) return;
        const wrap = waveCanvas.parentElement;
        waveCanvas.width = wrap.clientWidth - 32;
        waveCanvas.height = wrap.clientHeight - 32;
        drawIdleWaveform();
    }

    function drawIdleWaveform() {
        if (!waveCtx) return;
        const w = waveCanvas.width, h = waveCanvas.height;
        waveCtx.clearRect(0, 0, w, h);
        waveCtx.strokeStyle = 'rgba(108,92,231,.3)';
        waveCtx.lineWidth = 1;
        waveCtx.beginPath();
        waveCtx.moveTo(0, h/2);
        for (let x = 0; x < w; x++) {
            waveCtx.lineTo(x, h/2 + Math.sin(x * 0.02) * 3);
        }
        waveCtx.stroke();
    }

    function updateScript() {
        if (scriptText) scriptText.textContent = SENTENCES[currentSentence] || 'All sentences completed!';
        if (counter) counter.textContent = `${currentSentence + 1} / ${SENTENCES.length}`;
        updateProgress();
    }

    function updateProgress() {
        const recorded = recordings.length;
        if (progressText) progressText.textContent = `${recorded} / ${SENTENCES.length} sentences`;
        if (progressFill) progressFill.style.width = ((recorded / SENTENCES.length) * 100) + '%';
        if (recorded >= SENTENCES.length && uploadBtn) uploadBtn.classList.add('visible');
    }

    function prevSentence() {
        if (currentSentence > 0) { currentSentence--; updateScript(); }
    }

    function nextSentence() {
        if (currentSentence < SENTENCES.length - 1) { currentSentence++; updateScript(); }
    }

    async function toggleRecord() {
        if (isRecording) {
            stopRecording();
        } else {
            await startRecording();
        }
    }

    async function startRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioContext.createAnalyser();
            analyser.fftSize = 2048;
            const source = audioContext.createMediaStreamSource(stream);
            source.connect(analyser);

            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];
            mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) audioChunks.push(e.data); };
            mediaRecorder.onstop = () => {
                const blob = new Blob(audioChunks, { type: 'audio/webm' });
                recordings.push({ sentence: currentSentence, blob: blob });
                stream.getTracks().forEach(t => t.stop());
                updateProgress();
                if (currentSentence < SENTENCES.length - 1) {
                    currentSentence++;
                    updateScript();
                }
            };

            mediaRecorder.start();
            isRecording = true;
            recBtn.classList.add('recording');
            if (recLabel) recLabel.textContent = 'Recording...';

            recordingStart = Date.now();
            timerInterval = setInterval(updateTimer, 100);

            drawWaveform();
            updateQuality('good');
        } catch (err) {
            alert('Microphone access is required for voice cloning. Please allow microphone permissions.');
        }
    }

    function stopRecording() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        isRecording = false;
        recBtn.classList.remove('recording');
        if (recLabel) recLabel.textContent = 'Tap to Record';
        clearInterval(timerInterval);
        cancelAnimationFrame(animFrame);
        if (audioContext) { audioContext.close(); audioContext = null; }
        updateQuality('idle');
        drawIdleWaveform();
    }

    function updateTimer() {
        const elapsed = Math.floor((Date.now() - recordingStart) / 1000);
        const m = Math.floor(elapsed / 60);
        const s = elapsed % 60;
        if (timerEl) timerEl.textContent = `${m}:${s.toString().padStart(2, '0')}`;
    }

    function drawWaveform() {
        if (!analyser || !waveCtx) return;
        const bufLen = analyser.frequencyBinCount;
        const dataArray = new Uint8Array(bufLen);
        const w = waveCanvas.width, h = waveCanvas.height;

        function draw() {
            animFrame = requestAnimationFrame(draw);
            analyser.getByteTimeDomainData(dataArray);

            waveCtx.fillStyle = 'rgba(18,18,30,.3)';
            waveCtx.fillRect(0, 0, w, h);

            waveCtx.lineWidth = 2;
            waveCtx.strokeStyle = '#a29bfe';
            waveCtx.beginPath();

            const sliceWidth = w / bufLen;
            let x = 0;
            let maxVolume = 0;
            for (let i = 0; i < bufLen; i++) {
                const v = dataArray[i] / 128.0;
                const y = v * h / 2;
                if (Math.abs(v - 1) > maxVolume) maxVolume = Math.abs(v - 1);
                if (i === 0) waveCtx.moveTo(x, y);
                else waveCtx.lineTo(x, y);
                x += sliceWidth;
            }
            waveCtx.lineTo(w, h / 2);
            waveCtx.stroke();

            // Update quality based on volume
            if (maxVolume > 0.1) updateQuality('good');
            else if (maxVolume > 0.02) updateQuality('good');
            else updateQuality('poor');
        }
        draw();
    }

    function updateQuality(level) {
        if (!qualityEl) return;
        qualityEl.className = 'vc-quality ' + level;
        const labels = { good: '<i class="fa-solid fa-signal"></i> <span>Good Quality</span>', poor: '<i class="fa-solid fa-signal"></i> <span>Speak Louder</span>', idle: '<i class="fa-solid fa-signal"></i> <span>Ready</span>' };
        qualityEl.innerHTML = labels[level] || labels.idle;
    }

    let currentProfileId = 0;

    async function upload() {
        if (recordings.length === 0) {
            alert('Please record at least one sentence before uploading.');
            return;
        }
        if (recordings.length < SENTENCES.length) {
            if (!confirm(`You have recorded ${recordings.length}/${SENTENCES.length} sentences. Upload anyway?`)) return;
        }
        if (uploadBtn) {
            uploadBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
            uploadBtn.disabled = true;
        }
        try {
            let uploaded = 0;
            for (const rec of recordings) {
                const fd = new FormData();
                fd.append('audio', rec.blob, 'recording.webm');
                fd.append('sentence_index', rec.sentence);
                fd.append('profile_name', 'My Voice Profile');
                fd.append('language', 'en');
                if (currentProfileId) fd.append('profile_id', currentProfileId);
                const resp = await fetch('/api/voice-clone.php?action=upload', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await resp.json();
                if (data.success) {
                    currentProfileId = data.profile_id;
                    uploaded++;
                    if (uploadBtn) uploadBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Uploading ${uploaded}/${recordings.length}...`;
                } else {
                    throw new Error(data.error || 'Upload failed');
                }
            }
            if (uploadBtn) {
                uploadBtn.innerHTML = '<i class="fa-solid fa-check"></i> Upload Complete!';
                uploadBtn.style.background = '#00b894';
            }
            showToast(`${uploaded} voice samples uploaded! Training will begin shortly (~30 min).`);
        } catch (err) {
            if (uploadBtn) {
                uploadBtn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Upload & Train';
                uploadBtn.disabled = false;
                uploadBtn.style.background = '';
            }
            showToast('Upload failed: ' + err.message);
        }
    }

    function showToast(msg) {
        if (window.GDSToast) return GDSToast.success(msg);
    }

    // Init
    if (document.getElementById('vcRecBtn')) init();

    return { toggleRecord, prevSentence, nextSentence, upload };
})();

// Animation
const vcStyle = document.createElement('style');
vcStyle.textContent = '@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}';
document.head.appendChild(vcStyle);
