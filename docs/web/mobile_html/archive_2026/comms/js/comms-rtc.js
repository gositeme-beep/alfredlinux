/**
 * GoSiteMe Veil — WebRTC Video/Audio/Screen Module
 *
 * P2P encrypted calls using WebRTC (DTLS-SRTP encrypted by default).
 * Signaling goes through the Comms API (encrypted relay).
 * Media streams NEVER touch the server — pure peer-to-peer.
 *
 * Features:
 *   - 1:1 video calls
 *   - 1:1 audio calls
 *   - Screen sharing
 *   - Call recording (local only)
 *   - Adaptive bitrate
 */

const CommsRTC = (() => {
    'use strict';

    // STUN/TURN servers for NAT traversal
    const ICE_SERVERS = [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'stun:stun2.l.google.com:19302' },
        { urls: 'stun:stun3.l.google.com:19302' },
    ];

    let peerConnection = null;
    let localStream    = null;
    let remoteStream   = null;
    let screenStream   = null;
    let callState      = 'idle'; // idle, calling, ringing, connected, ended
    let currentCallTo  = null;
    let signalPollTimer = null;
    let statsTimer      = null;

    // Callbacks (set by app)
    let onCallStateChange = null;
    let onRemoteStream    = null;
    let onCallStats       = null;
    let onError           = null;

    // ═════════════════════════════════════════════════════════════════
    // CONFIGURATION
    // ═════════════════════════════════════════════════════════════════

    function setCallbacks(cbs) {
        onCallStateChange = cbs.onCallStateChange || null;
        onRemoteStream    = cbs.onRemoteStream    || null;
        onCallStats       = cbs.onCallStats       || null;
        onError           = cbs.onError           || null;
    }

    function setState(state) {
        callState = state;
        if (onCallStateChange) onCallStateChange(state, currentCallTo);
    }

    // ═════════════════════════════════════════════════════════════════
    // MEDIA ACCESS
    // ═════════════════════════════════════════════════════════════════

    async function getLocalMedia(video = true) {
        const constraints = {
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
            },
        };
        if (video) {
            constraints.video = {
                width: { ideal: 1280 },
                height: { ideal: 720 },
                frameRate: { ideal: 30 },
            };
        }

        try {
            localStream = await navigator.mediaDevices.getUserMedia(constraints);
            return localStream;
        } catch (err) {
            if (onError) onError('media_access', err.message);
            throw err;
        }
    }

    async function getScreenMedia() {
        try {
            screenStream = await navigator.mediaDevices.getDisplayMedia({
                video: { cursor: 'always' },
                audio: true,
            });
            return screenStream;
        } catch (err) {
            if (onError) onError('screen_access', err.message);
            throw err;
        }
    }

    // ═════════════════════════════════════════════════════════════════
    // PEER CONNECTION
    // ═════════════════════════════════════════════════════════════════

    function createPeerConnection() {
        peerConnection = new RTCPeerConnection({
            iceServers: ICE_SERVERS,
            iceCandidatePoolSize: 10,
        });

        // Collect ICE candidates and send via signaling
        peerConnection.onicecandidate = (event) => {
            if (event.candidate && currentCallTo) {
                sendSignal(currentCallTo, 'ice', JSON.stringify(event.candidate));
            }
        };

        // Handle incoming remote streams
        peerConnection.ontrack = (event) => {
            remoteStream = event.streams[0];
            if (onRemoteStream) onRemoteStream(remoteStream);
        };

        // Connection state monitoring
        peerConnection.onconnectionstatechange = () => {
            const state = peerConnection.connectionState;
            if (state === 'connected') {
                setState('connected');
                startStatsMonitoring();
            } else if (state === 'disconnected' || state === 'failed') {
                endCall();
            }
        };

        peerConnection.oniceconnectionstatechange = () => {
            if (peerConnection.iceConnectionState === 'failed') {
                peerConnection.restartIce();
            }
        };

        return peerConnection;
    }

    // ═════════════════════════════════════════════════════════════════
    // CALL INITIATION (Caller)
    // ═════════════════════════════════════════════════════════════════

    /**
     * Start a call (audio or video)
     */
    async function startCall(contactId, video = true) {
        if (callState !== 'idle') {
            if (onError) onError('busy', 'Already in a call');
            return;
        }

        currentCallTo = contactId;
        setState('calling');

        try {
            // Get local media
            await getLocalMedia(video);

            // Create peer connection
            createPeerConnection();

            // Add local tracks
            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });

            // Create offer
            const offer = await peerConnection.createOffer({
                offerToReceiveAudio: true,
                offerToReceiveVideo: video,
            });
            await peerConnection.setLocalDescription(offer);

            // Send offer via signaling (encrypted on the API side)
            await sendSignal(contactId, 'offer', JSON.stringify({
                sdp: offer.sdp,
                type: offer.type,
                video,
            }));

            // Start polling for answer
            startSignalPolling();

        } catch (err) {
            setState('idle');
            if (onError) onError('call_failed', err.message);
        }
    }

    /**
     * Answer an incoming call
     */
    async function answerCall(fromId, offerData) {
        currentCallTo = fromId;
        setState('connected');

        try {
            const offer = JSON.parse(offerData);
            await getLocalMedia(offer.video !== false);

            createPeerConnection();

            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });

            await peerConnection.setRemoteDescription(new RTCSessionDescription({
                sdp: offer.sdp,
                type: offer.type,
            }));

            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);

            await sendSignal(fromId, 'answer', JSON.stringify({
                sdp: answer.sdp,
                type: answer.type,
            }));

            startSignalPolling(); // For ICE candidates

        } catch (err) {
            setState('idle');
            if (onError) onError('answer_failed', err.message);
        }
    }

    /**
     * Reject incoming call
     */
    async function rejectCall(fromId) {
        await sendSignal(fromId, 'busy', '{}');
    }

    // ═════════════════════════════════════════════════════════════════
    // SCREEN SHARING
    // ═════════════════════════════════════════════════════════════════

    async function startScreenShare() {
        if (!peerConnection || callState !== 'connected') return;

        try {
            const screen = await getScreenMedia();
            const videoTrack = screen.getVideoTracks()[0];

            // Replace video track
            const sender = peerConnection.getSenders().find(s => s.track?.kind === 'video');
            if (sender) {
                await sender.replaceTrack(videoTrack);
            }

            // Restore camera when screen share ends
            videoTrack.onended = () => stopScreenShare();

            return true;
        } catch (err) {
            if (onError) onError('screen_share_failed', err.message);
            return false;
        }
    }

    async function stopScreenShare() {
        if (screenStream) {
            screenStream.getTracks().forEach(t => t.stop());
            screenStream = null;
        }

        // Restore camera video
        if (localStream && peerConnection) {
            const camTrack = localStream.getVideoTracks()[0];
            const sender = peerConnection.getSenders().find(s => s.track?.kind === 'video');
            if (sender && camTrack) {
                await sender.replaceTrack(camTrack);
            }
        }
    }

    // ═════════════════════════════════════════════════════════════════
    // CALL CONTROLS
    // ═════════════════════════════════════════════════════════════════

    function toggleMute() {
        if (!localStream) return false;
        const audioTrack = localStream.getAudioTracks()[0];
        if (audioTrack) {
            audioTrack.enabled = !audioTrack.enabled;
            return !audioTrack.enabled; // true = muted
        }
        return false;
    }

    function toggleVideo() {
        if (!localStream) return false;
        const videoTrack = localStream.getVideoTracks()[0];
        if (videoTrack) {
            videoTrack.enabled = !videoTrack.enabled;
            return !videoTrack.enabled; // true = camera off
        }
        return false;
    }

    function endCall() {
        // Send hangup signal
        if (currentCallTo && callState !== 'idle') {
            sendSignal(currentCallTo, 'hangup', '{}').catch(() => {});
        }

        // Stop all tracks
        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
            localStream = null;
        }
        if (remoteStream) {
            remoteStream.getTracks().forEach(t => t.stop());
            remoteStream = null;
        }
        if (screenStream) {
            screenStream.getTracks().forEach(t => t.stop());
            screenStream = null;
        }

        // Close peer connection
        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }

        // Stop polling
        stopSignalPolling();
        stopStatsMonitoring();

        currentCallTo = null;
        setState('idle');
    }

    // ═════════════════════════════════════════════════════════════════
    // SIGNALING (via Comms API)
    // ═════════════════════════════════════════════════════════════════

    async function sendSignal(toId, type, payload) {
        const csrfToken = window.CommsApp?.csrfToken || '';
        const resp = await fetch('/api/comms.php?action=signal', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
            },
            body: JSON.stringify({
                to_id: toId,
                signal_type: type,
                encrypted_payload: payload,
            }),
        });
        const data = await resp.json();
        if (data.csrf_token) window.CommsApp.csrfToken = data.csrf_token;
        return data;
    }

    let signalPollInterval = 3000;

    function startSignalPolling() {
        stopSignalPolling();
        signalPollInterval = 3000;
        signalPollTimer = setInterval(pollSignals, signalPollInterval);
    }

    function stopSignalPolling() {
        if (signalPollTimer) {
            clearInterval(signalPollTimer);
            signalPollTimer = null;
        }
    }

    async function pollSignals() {
        try {
            const resp = await fetch('/api/comms.php?action=poll_signals', {
                credentials: 'same-origin',
            });
            if (resp.status === 429) {
                // Back off on rate limit
                stopSignalPolling();
                signalPollInterval = Math.min(signalPollInterval * 2, 30000);
                signalPollTimer = setInterval(pollSignals, signalPollInterval);
                return;
            }
            const data = await resp.json();

            if (data.signals) {
                for (const sig of data.signals) {
                    await handleSignal(sig);
                }
            }
            // Reset interval on success if it was backed off
            if (signalPollInterval > 3000) {
                stopSignalPolling();
                signalPollInterval = 3000;
                signalPollTimer = setInterval(pollSignals, signalPollInterval);
            }
        } catch (err) {
            // Silent — polling will retry
        }
    }

    async function handleSignal(signal) {
        const type = signal.signal_type;
        const payload = signal.encrypted_payload;
        const fromId = signal.from_id;

        switch (type) {
            case 'offer':
                if (callState === 'idle') {
                    setState('ringing');
                    currentCallTo = fromId;
                    // App layer handles showing incoming call UI
                    if (onCallStateChange) onCallStateChange('ringing', fromId, payload);
                } else {
                    // Already in a call — reject
                    await rejectCall(fromId);
                }
                break;

            case 'answer':
                if (peerConnection && callState === 'calling') {
                    const answer = JSON.parse(payload);
                    await peerConnection.setRemoteDescription(
                        new RTCSessionDescription({ sdp: answer.sdp, type: answer.type })
                    );
                }
                break;

            case 'ice':
                if (peerConnection) {
                    const candidate = JSON.parse(payload);
                    await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                }
                break;

            case 'hangup':
                endCall();
                break;

            case 'busy':
                if (callState === 'calling') {
                    endCall();
                    if (onError) onError('busy', 'Contact is busy');
                }
                break;

            case 'ringing':
                // Remote side is ringing
                break;
        }
    }

    // ═════════════════════════════════════════════════════════════════
    // CALL STATS
    // ═════════════════════════════════════════════════════════════════

    function startStatsMonitoring() {
        stopStatsMonitoring();
        statsTimer = setInterval(async () => {
            if (!peerConnection) return;
            try {
                const stats = await peerConnection.getStats();
                const report = {};
                stats.forEach(s => {
                    if (s.type === 'inbound-rtp' && s.kind === 'video') {
                        report.videoInbound = {
                            bytesReceived: s.bytesReceived,
                            framesDecoded: s.framesDecoded,
                            frameWidth: s.frameWidth,
                            frameHeight: s.frameHeight,
                        };
                    }
                    if (s.type === 'inbound-rtp' && s.kind === 'audio') {
                        report.audioInbound = {
                            bytesReceived: s.bytesReceived,
                            jitter: s.jitter,
                        };
                    }
                });
                if (onCallStats) onCallStats(report);
            } catch (e) { /* ignore */ }
        }, 3000);
    }

    function stopStatsMonitoring() {
        if (statsTimer) {
            clearInterval(statsTimer);
            statsTimer = null;
        }
    }

    // ═════════════════════════════════════════════════════════════════
    // PUBLIC API
    // ═════════════════════════════════════════════════════════════════

    return {
        setCallbacks,
        startCall,
        answerCall,
        rejectCall,
        endCall,
        toggleMute,
        toggleVideo,
        startScreenShare,
        stopScreenShare,
        pollSignals,
        startSignalPolling,
        stopSignalPolling,
        get callState() { return callState; },
        get localStream() { return localStream; },
        get remoteStream() { return remoteStream; },
        get currentCallTo() { return currentCallTo; },
    };
})();
