/**
 * ══════════════════════════════════════════════════════════════════
 * GoSiteMe Adaptive Quality v1.0
 * Shared across ALL VR games & experiences
 * ══════════════════════════════════════════════════════════════════
 *
 * Auto-detects display capability (1080p → 4K → 8K+) and provides
 * optimal rendering settings. Load this BEFORE your game script.
 *
 * Usage:
 *   renderer.setPixelRatio(GoSiteMeQuality.dpr);
 *   shadowMap.set(GoSiteMeQuality.shadowSize, GoSiteMeQuality.shadowSize);
 *
 * Properties:
 *   .tier        — 'standard' | 'high' | 'ultra' | '4k' | '8k'
 *   .dpr         — optimal device pixel ratio for this display
 *   .shadowSize  — recommended shadow map resolution
 *   .spotShadow  — recommended spotlight shadow resolution
 *   .texSize     — recommended procedural texture resolution
 *   .maxTexSize  — GPU's maximum texture dimension
 *   .screenMax   — largest screen dimension in physical pixels
 *   .isVR        — whether VR headset is detected
 *   .gpuTier     — estimated GPU capability
 *
 * Events:
 *   GoSiteMeQuality.on('vrdetected', () => { ... })
 *
 * Copyright © 2026 GoSiteMe Inc.
 * ══════════════════════════════════════════════════════════════════
 */

(function(root) {
    'use strict';

    var dpr = root.devicePixelRatio || 1;
    var sw = root.screen ? root.screen.width : 1920;
    var sh = root.screen ? root.screen.height : 1080;
    var maxDim = Math.max(sw, sh) * dpr;

    // Probe GPU limits
    var maxTexSize = 4096;
    var gpuRenderer = 'unknown';
    try {
        var c = document.createElement('canvas');
        var gl = c.getContext('webgl2') || c.getContext('webgl');
        if (gl) {
            maxTexSize = gl.getParameter(gl.MAX_TEXTURE_SIZE);
            var dbg = gl.getExtension('WEBGL_debug_renderer_info');
            if (dbg) gpuRenderer = gl.getParameter(dbg.UNMASKED_RENDERER_WEBGL);
        }
    } catch(e) {}

    // Estimate GPU tier from renderer string
    var gpuTier = 'mid';
    var gpuLower = gpuRenderer.toLowerCase();
    if (/rtx\s?(40[89]0|50[89]0)|radeon\s?rx\s?7[89]00|apple\s?m[34]/i.test(gpuLower)) gpuTier = 'ultra';
    else if (/rtx\s?(30[67890]0|40[567]0)|radeon\s?rx\s?(6[89]00|7[0-6]00)|apple\s?m[12]/i.test(gpuLower)) gpuTier = 'high';
    else if (/gt\s?1030|radeon\s?rx\s?5[0-5]0|mali|adreno|powervr/i.test(gpuLower)) gpuTier = 'low';

    // Determine quality tier
    var tier, optDpr, shadowSize, spotShadow, texSize;

    if (maxDim >= 7680 && gpuTier !== 'low') {
        tier = '8k';
        optDpr = Math.min(dpr, maxTexSize >= 8192 ? dpr : 4);
        shadowSize = Math.min(8192, maxTexSize);
        spotShadow = 4096;
        texSize = Math.min(4096, maxTexSize);
    } else if (maxDim >= 3840 && gpuTier !== 'low') {
        tier = '4k';
        optDpr = Math.min(dpr, 4);
        shadowSize = 8192;
        spotShadow = 2048;
        texSize = 2048;
    } else if (maxDim >= 2560) {
        tier = 'ultra';
        optDpr = Math.min(dpr, 3);
        shadowSize = 4096;
        spotShadow = 2048;
        texSize = 1024;
    } else if (maxDim >= 1920) {
        tier = 'high';
        optDpr = Math.min(dpr, 2);
        shadowSize = 4096;
        spotShadow = 1024;
        texSize = 1024;
    } else {
        tier = 'standard';
        optDpr = Math.min(dpr, 1.5);
        shadowSize = 2048;
        spotShadow = 512;
        texSize = 512;
    }

    // Low GPU override — protect from GPU overload
    if (gpuTier === 'low') {
        optDpr = Math.min(dpr, 1.5);
        shadowSize = Math.min(shadowSize, 2048);
        spotShadow = Math.min(spotShadow, 1024);
        texSize = Math.min(texSize, 512);
        if (tier === '8k' || tier === '4k') tier = 'high';
    }

    // Event system
    var handlers = {};

    // VR detection — async, may cap DPR after init
    var isVR = false;
    if (root.navigator && root.navigator.xr) {
        root.navigator.xr.isSessionSupported('immersive-vr').then(function(supported) {
            if (supported) {
                isVR = true;
                quality.isVR = true;
                if (quality.dpr > 2) quality.dpr = 2; // VR perf cap
                fire('vrdetected');
            }
        }).catch(function() {});
    }

    function fire(evt) {
        (handlers[evt] || []).forEach(function(fn) { try { fn(quality); } catch(e) {} });
    }

    var quality = {
        tier: tier,
        dpr: optDpr,
        shadowSize: shadowSize,
        spotShadow: spotShadow,
        texSize: texSize,
        maxTexSize: maxTexSize,
        screenMax: maxDim,
        gpuTier: gpuTier,
        gpuRenderer: gpuRenderer,
        isVR: isVR,
        version: '1.0.0',

        on: function(evt, fn) {
            (handlers[evt] = handlers[evt] || []).push(fn);
        },

        /** Apply optimal settings to a Three.js renderer */
        applyToRenderer: function(renderer) {
            if (!renderer) return;
            renderer.setPixelRatio(quality.dpr);
            if (renderer.capabilities && renderer.capabilities.maxTextureSize) {
                quality.maxTexSize = renderer.capabilities.maxTextureSize;
            }
        },

        /** Log quality info to console */
        log: function() {
            console.log(
                '[GoSiteMe Quality] Tier: ' + quality.tier +
                ' | DPR: ' + quality.dpr +
                ' | Shadow: ' + quality.shadowSize +
                ' | Tex: ' + quality.texSize +
                ' | GPU: ' + quality.gpuTier +
                ' | Screen: ' + quality.screenMax + 'px' +
                (quality.isVR ? ' | VR detected' : '')
            );
        }
    };

    quality.log();

    root.GoSiteMeQuality = quality;

})(typeof self !== 'undefined' ? self : this);
