/* ═══════════════════════════════════════
   CIRCUIT SIMULATOR v5.0 SPICE + MNA ENGINE
   ═══════════════════════════════════════ */
const csApp = (() => {
    const { PHYS, FORMULAS, SimulationEngine, SimulationEngineV4, Oscilloscope, FormulaPanel, ZPE_TEMPLATES, EXTENDED_COMP_DEFS, FFT, BodePlot, TransientEngine, CircuitGraph } = window.CSEngine;

    // ── State ──
    const state = {
        components: [],
        wires: [],
        nodes: new Map(),
        selectedId: null,
        mode: 'select',
        simulating: false,
        zoom: 1,
        panX: 0, panY: 0,
        wireStart: null,
        wireEnd: null,
        dragging: null,
        dragOffset: { x: 0, y: 0 },
        panning: false,
        panStart: { x: 0, y: 0 },
        nextId: 1,
        energy: 0,
        circuitsBuilt: 0,
        undoStack: [],
        redoStack: [],
        simTime: 0,
        gridSize: 20,
        multiSelected: [],
        clipboard: [],
        contextTarget: null,
        showJunctions: true,
        showNodeVoltages: false,
        lastResult: null,
    };

    const canvas = document.getElementById('circuitCanvas');
    const ctx = canvas.getContext('2d');
    const engine = new SimulationEngineV4();
    const scope = new Oscilloscope('scopeCanvas');
    const formulaPanel = new FormulaPanel('formulaPanel');
    const bodePlot = new BodePlot();

    // ── Component Definitions (Merged) ──
    const COMP_DEFS = {
        battery:        { unit: 'V',  color: '#00FF88', w: 60, h: 40, terminals: 2 },
        ac_source:      { unit: 'V',  color: '#FFD700', w: 60, h: 40, terminals: 2 },
        ground:         { unit: 'V',  color: '#888',    w: 30, h: 40, terminals: 1 },
        resistor:       { unit: 'Ω',  color: '#FFB800', w: 70, h: 30, terminals: 2 },
        capacitor:      { unit: 'F',  color: '#00D4FF', w: 50, h: 30, terminals: 2 },
        inductor:       { unit: 'H',  color: '#7D00FF', w: 60, h: 30, terminals: 2 },
        potentiometer:  { unit: 'Ω',  color: '#FF8800', w: 60, h: 35, terminals: 3 },
        led:            { unit: 'V',  color: '#FF3366', w: 40, h: 40, terminals: 2 },
        bulb:           { unit: 'W',  color: '#FFD700', w: 40, h: 40, terminals: 2 },
        motor:          { unit: 'V',  color: '#00AAFF', w: 50, h: 50, terminals: 2 },
        buzzer:         { unit: 'V',  color: '#FF6600', w: 40, h: 40, terminals: 2 },
        switch:         { unit: '',   color: '#00FF88', w: 60, h: 30, terminals: 2 },
        push_button:    { unit: '',   color: '#00CCFF', w: 50, h: 30, terminals: 2 },
        diode:          { unit: 'V',  color: '#FF6600', w: 50, h: 30, terminals: 2 },
        fuse:           { unit: 'A',  color: '#FF3366', w: 50, h: 25, terminals: 2 },
        npn_transistor: { unit: 'β',  color: '#00FF88', w: 50, h: 60, terminals: 3 },
        pnp_transistor: { unit: 'β',  color: '#00AAFF', w: 50, h: 60, terminals: 3 },
        op_amp:         { unit: '',   color: '#FFD700', w: 60, h: 50, terminals: 3 },
        voltmeter:      { unit: 'V',  color: '#00D4FF', w: 45, h: 45, terminals: 2 },
        ammeter:        { unit: 'A',  color: '#FF8800', w: 45, h: 45, terminals: 2 },
        wire:           { unit: '',   color: '#00D4FF', w: 10, h: 10, terminals: 2 },
        ...EXTENDED_COMP_DEFS
    };

    // ── Format value with SI prefix ──
    function formatValue(val, unit) {
        if (!unit) return '';
        const abs = Math.abs(val);
        if (abs >= 1e9) return (val/1e9).toFixed(1) + 'G' + unit;
        if (abs >= 1e6) return (val/1e6).toFixed(1) + 'M' + unit;
        if (abs >= 1e3) return (val/1e3).toFixed(1) + 'k' + unit;
        if (abs >= 1) return val.toFixed(1) + unit;
        if (abs >= 1e-3) return (val*1e3).toFixed(1) + 'm' + unit;
        if (abs >= 1e-6) return (val*1e6).toFixed(1) + 'µ' + unit;
        if (abs >= 1e-9) return (val*1e9).toFixed(1) + 'n' + unit;
        if (abs >= 1e-12) return (val*1e12).toFixed(1) + 'p' + unit;
        return val.toExponential(1) + unit;
    }

    // ── Canvas Setup ──
    function resizeCanvas() {
        const wrap = document.getElementById('canvasWrap');
        canvas.width = wrap.clientWidth * window.devicePixelRatio;
        canvas.height = wrap.clientHeight * window.devicePixelRatio;
        canvas.style.width = wrap.clientWidth + 'px';
        canvas.style.height = wrap.clientHeight + 'px';
        ctx.setTransform(window.devicePixelRatio, 0, 0, window.devicePixelRatio, 0, 0);
        render();
    }

    function saveState() {
        state.undoStack.push(JSON.stringify({ components: state.components, wires: state.wires }));
        if (state.undoStack.length > 50) state.undoStack.shift();
        state.redoStack = [];
    }

    // ── Terminal Positions ──
    function getTerminals(comp) {
        const def = COMP_DEFS[comp.type];
        if (!def) return [];
        const rad = (comp.rotation || 0) * Math.PI / 180;
        const cx = comp.x, cy = comp.y;
        const hw = def.w / 2, hh = def.h / 2;
        const terminals = [];
        const rot = (lx, ly) => ({
            x: cx + lx * Math.cos(rad) - ly * Math.sin(rad),
            y: cy + lx * Math.sin(rad) + ly * Math.cos(rad)
        });
        if (def.terminals >= 2) {
            terminals.push(rot(-hw - 5, 0));
            terminals.push(rot(hw + 5, 0));
        }
        if (def.terminals >= 3) {
            terminals.push(rot(0, hh + 5));
        }
        if (def.terminals >= 4) {
            terminals.push(rot(0, -hh - 5));
        }
        if (def.terminals === 1) {
            terminals.push(rot(0, -hh - 5));
        }
        return terminals;
    }

    function findNearestTerminal(x, y, excludeCompId) {
        let best = null, bestDist = 15;
        for (const comp of state.components) {
            if (comp.id === excludeCompId) continue;
            const terms = getTerminals(comp);
            for (let i = 0; i < terms.length; i++) {
                const d = Math.hypot(terms[i].x - x, terms[i].y - y);
                if (d < bestDist) { bestDist = d; best = { compId: comp.id, termIndex: i, x: terms[i].x, y: terms[i].y }; }
            }
        }
        return best;
    }

    function snapToGrid(val) {
        return Math.round(val / state.gridSize) * state.gridSize;
    }

    // ── Draw Component ──
    function drawComponent(comp) {
        const def = COMP_DEFS[comp.type];
        if (!def) return;

        const hw = def.w / 2, hh = def.h / 2;
        const isSelected = state.selectedId === comp.id;
        const isSim = state.simulating;

        // Terminal dots
        const terms = getTerminals(comp);
        terms.forEach(t => {
            ctx.beginPath();
            ctx.arc(t.x, t.y, 4, 0, Math.PI * 2);
            ctx.fillStyle = isSelected ? '#00D4FF' : 'rgba(0,212,255,0.4)';
            ctx.fill();
        });

        ctx.save();
        ctx.translate(comp.x, comp.y);
        ctx.rotate((comp.rotation || 0) * Math.PI / 180);

        if (isSelected) { ctx.shadowColor = '#00D4FF'; ctx.shadowBlur = 15; }

        switch(comp.type) {
            case 'resistor':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.moveTo(-hw, 0);
                const zw = hw * 0.7, segs = 6;
                ctx.lineTo(-zw, 0);
                for (let i = 0; i < segs; i++) {
                    ctx.lineTo(-zw + (2*zw/segs) * (i + 0.5), (i % 2 === 0 ? -10 : 10));
                }
                ctx.lineTo(zw, 0); ctx.lineTo(hw, 0); ctx.stroke();
                break;

            case 'capacitor':
            case 'high_voltage_cap':
                ctx.strokeStyle = comp.type === 'high_voltage_cap' ? '#FF4444' : def.color;
                ctx.lineWidth = comp.type === 'high_voltage_cap' ? 3 : 2;
                ctx.beginPath();
                ctx.moveTo(-hw, 0); ctx.lineTo(-6, 0);
                ctx.moveTo(-6, -12); ctx.lineTo(-6, 12);
                ctx.moveTo(6, -12); ctx.lineTo(6, 12);
                ctx.moveTo(6, 0); ctx.lineTo(hw, 0);
                ctx.stroke();
                if (comp.type === 'high_voltage_cap') {
                    ctx.fillStyle = '#FF4444'; ctx.font = '8px sans-serif';
                    ctx.textAlign = 'center'; ctx.fillText('HV', 0, -16);
                }
                break;

            case 'inductor':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.moveTo(-hw, 0); ctx.lineTo(-hw*0.6, 0);
                for (let i = 0; i < 4; i++) {
                    const sx = -hw*0.6 + (hw*1.2/4) * i;
                    ctx.arc(sx + hw*1.2/8, 0, hw*1.2/8, Math.PI, 0, false);
                }
                ctx.lineTo(hw, 0); ctx.stroke();
                break;

            case 'battery':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(-hw, 0); ctx.lineTo(-8, 0);
                ctx.moveTo(-8, -15); ctx.lineTo(-8, 15);
                ctx.moveTo(-2, -8); ctx.lineTo(-2, 8);
                ctx.lineWidth = 3;
                ctx.moveTo(4, -15); ctx.lineTo(4, 15);
                ctx.lineWidth = 2;
                ctx.moveTo(10, -8); ctx.lineTo(10, 8);
                ctx.moveTo(10, 0); ctx.lineTo(hw, 0);
                ctx.stroke();
                ctx.fillStyle = def.color; ctx.font = '10px sans-serif'; ctx.textAlign = 'center';
                ctx.fillText('+', hw - 5, -10); ctx.fillText('−', -hw + 5, -10);
                break;

            case 'ac_source':
            case 'signal_generator':
            case 'rf_generator':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.arc(0, 0, 16, 0, Math.PI * 2); ctx.stroke();
                // Sine wave inside
                ctx.beginPath();
                for (let i = -10; i <= 10; i++) {
                    const px = i, py = Math.sin(i * 0.6) * 6;
                    if (i === -10) ctx.moveTo(px, py); else ctx.lineTo(px, py);
                }
                ctx.stroke();
                ctx.beginPath(); ctx.moveTo(-hw, 0); ctx.lineTo(-16, 0);
                ctx.moveTo(16, 0); ctx.lineTo(hw, 0); ctx.stroke();
                if (comp.type === 'rf_generator') {
                    ctx.fillStyle = '#FF00FF'; ctx.font = '7px sans-serif'; ctx.textAlign = 'center';
                    ctx.fillText('RF', 0, -20);
                } else if (comp.type === 'signal_generator') {
                    ctx.fillStyle = '#FFAA00'; ctx.font = '7px sans-serif'; ctx.textAlign = 'center';
                    ctx.fillText('SIG', 0, -20);
                }
                break;

            case 'led':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.moveTo(-hw, 0); ctx.lineTo(-8, 0);
                ctx.moveTo(-8, -10); ctx.lineTo(8, 0); ctx.lineTo(-8, 10); ctx.closePath(); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(8, -12); ctx.lineTo(8, 12);
                ctx.moveTo(8, 0); ctx.lineTo(hw, 0); ctx.stroke();
                if (isSim && comp.simData && comp.simData.on) {
                    ctx.strokeStyle = 'rgba(255,51,102,0.6)';
                    ctx.beginPath(); ctx.moveTo(2, -14); ctx.lineTo(-2, -20);
                    ctx.moveTo(8, -16); ctx.lineTo(6, -22); ctx.stroke();
                    ctx.shadowColor = '#FF3366'; ctx.shadowBlur = 20;
                    ctx.beginPath(); ctx.arc(0, 0, 3, 0, Math.PI*2);
                    ctx.fillStyle = '#FF3366'; ctx.fill();
                }
                break;

            case 'switch':
            case 'reed_switch':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(-hw, 0); ctx.lineTo(-10, 0);
                ctx.arc(-10, 0, 3, 0, Math.PI*2); ctx.stroke();
                ctx.beginPath(); ctx.arc(10, 0, 3, 0, Math.PI*2);
                ctx.moveTo(10, 0); ctx.lineTo(hw, 0); ctx.stroke();
                ctx.beginPath();
                if (comp.closed) { ctx.moveTo(-7, 0); ctx.lineTo(7, 0); }
                else { ctx.moveTo(-7, 0); ctx.lineTo(7, -12); }
                ctx.lineWidth = 2.5; ctx.stroke();
                if (comp.type === 'reed_switch') {
                    ctx.fillStyle = '#00FFAA'; ctx.font = '7px sans-serif'; ctx.textAlign = 'center';
                    ctx.fillText('MAG', 0, 16);
                }
                break;

            case 'diode':
            case 'zener_diode':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.moveTo(-hw, 0); ctx.lineTo(-8, 0);
                ctx.moveTo(-8, -10); ctx.lineTo(8, 0); ctx.lineTo(-8, 10); ctx.closePath(); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(8, -12); ctx.lineTo(8, 12);
                if (comp.type === 'zener_diode') {
                    ctx.moveTo(8, -12); ctx.lineTo(4, -12);
                    ctx.moveTo(8, 12); ctx.lineTo(12, 12);
                }
                ctx.moveTo(8, 0); ctx.lineTo(hw, 0); ctx.stroke();
                break;

            case 'npn_transistor':
            case 'pnp_transistor':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.moveTo(-hw, 0); ctx.lineTo(-5, 0);
                ctx.moveTo(-5, -15); ctx.lineTo(-5, 15); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(-5, -8); ctx.lineTo(hw, -hh + 5); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(-5, 8); ctx.lineTo(hw, hh - 5); ctx.stroke();
                if (comp.type === 'npn_transistor') {
                    ctx.beginPath();
                    ctx.moveTo(hw - 12, hh - 12); ctx.lineTo(hw, hh - 5); ctx.lineTo(hw - 8, hh - 2);
                    ctx.fillStyle = def.color; ctx.fill();
                }
                break;

            case 'mosfet_n':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.moveTo(-hw, 0); ctx.lineTo(-8, 0);
                ctx.moveTo(-8, -18); ctx.lineTo(-8, 18); ctx.stroke();
                ctx.setLineDash([4,3]);
                ctx.beginPath(); ctx.moveTo(-4, -18); ctx.lineTo(-4, 18); ctx.stroke();
                ctx.setLineDash([]);
                ctx.beginPath();
                ctx.moveTo(-4, -12); ctx.lineTo(hw, -12); ctx.lineTo(hw, -hh);
                ctx.moveTo(-4, 0); ctx.lineTo(hw, 0);
                ctx.moveTo(-4, 12); ctx.lineTo(hw, 12); ctx.lineTo(hw, hh);
                ctx.stroke();
                // Arrow
                ctx.beginPath(); ctx.moveTo(2, 0); ctx.lineTo(-4, -4); ctx.lineTo(-4, 4); ctx.closePath();
                ctx.fillStyle = def.color; ctx.fill();
                break;

            case 'op_amp':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(-hw, -hh); ctx.lineTo(hw, 0); ctx.lineTo(-hw, hh); ctx.closePath(); ctx.stroke();
                ctx.font = '12px sans-serif'; ctx.fillStyle = def.color;
                ctx.fillText('−', -hw + 8, -8); ctx.fillText('+', -hw + 8, 12);
                break;

            case 'voltmeter':
            case 'ammeter':
            case 'wattmeter':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.arc(0, 0, 18, 0, Math.PI * 2); ctx.stroke();
                ctx.font = 'bold 14px sans-serif'; ctx.fillStyle = def.color;
                ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
                const mLabel = comp.type === 'voltmeter' ? 'V' : comp.type === 'ammeter' ? 'A' : 'W';
                ctx.fillText(mLabel, 0, 0);
                ctx.beginPath(); ctx.moveTo(-hw, 0); ctx.lineTo(-18, 0);
                ctx.moveTo(18, 0); ctx.lineTo(hw, 0); ctx.stroke();
                break;

            case 'ground':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(0, -hh); ctx.lineTo(0, 0);
                ctx.moveTo(-12, 0); ctx.lineTo(12, 0);
                ctx.moveTo(-8, 5); ctx.lineTo(8, 5);
                ctx.moveTo(-4, 10); ctx.lineTo(4, 10);
                ctx.stroke();
                break;

            case 'motor':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.arc(0, 0, 20, 0, Math.PI * 2); ctx.stroke();
                ctx.font = 'bold 14px sans-serif'; ctx.fillStyle = def.color;
                ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
                ctx.fillText('M', 0, 0);
                ctx.beginPath(); ctx.moveTo(-hw, 0); ctx.lineTo(-20, 0);
                ctx.moveTo(20, 0); ctx.lineTo(hw, 0); ctx.stroke();
                break;

            case 'bulb':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.arc(0, -5, 14, 0, Math.PI * 2); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(-8, -13); ctx.lineTo(8, 3);
                ctx.moveTo(8, -13); ctx.lineTo(-8, 3); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(-hw, 0); ctx.lineTo(-14, 0);
                ctx.moveTo(14, 0); ctx.lineTo(hw, 0); ctx.stroke();
                if (isSim && comp.simData && comp.simData.on) {
                    ctx.shadowColor = '#FFD700'; ctx.shadowBlur = 25;
                    ctx.beginPath(); ctx.arc(0, -5, 10, 0, Math.PI*2);
                    ctx.fillStyle = 'rgba(255,215,0,0.3)'; ctx.fill();
                }
                break;

            // ═══ NEW COMPONENT DRAWINGS ═══

            case 'transformer':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                // Primary coils (left)
                ctx.beginPath(); ctx.moveTo(-hw, -15); ctx.lineTo(-hw + 10, -15);
                for (let i = 0; i < 4; i++) {
                    ctx.arc(-hw + 10, -15 + (30/4)*i + (30/8), 30/8, -Math.PI/2, Math.PI/2, false);
                }
                ctx.lineTo(-hw, 15); ctx.stroke();
                // Core lines
                ctx.strokeStyle = 'rgba(255,215,0,0.4)';
                ctx.beginPath(); ctx.moveTo(-2, -hh); ctx.lineTo(-2, hh); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(2, -hh); ctx.lineTo(2, hh); ctx.stroke();
                // Secondary coils (right)
                ctx.strokeStyle = def.color;
                ctx.beginPath(); ctx.moveTo(hw, -15); ctx.lineTo(hw - 10, -15);
                for (let i = 0; i < 4; i++) {
                    ctx.arc(hw - 10, -15 + (30/4)*i + (30/8), 30/8, -Math.PI/2, Math.PI/2, true);
                }
                ctx.lineTo(hw, 15); ctx.stroke();
                break;

            case 'tesla_primary':
                ctx.strokeStyle = '#FF3366'; ctx.lineWidth = 2.5;
                // Thick spiral
                ctx.beginPath();
                for (let a = 0; a < Math.PI * 6; a += 0.1) {
                    const r = 5 + a * 2;
                    const px = Math.cos(a) * Math.min(r, hw - 5);
                    const py = Math.sin(a) * Math.min(r, hh - 5) * 0.5;
                    if (a === 0) ctx.moveTo(px, py); else ctx.lineTo(px, py);
                }
                ctx.stroke();
                ctx.fillStyle = '#FF3366'; ctx.font = '8px sans-serif';
                ctx.textAlign = 'center'; ctx.fillText('T₁', 0, hh + 12);
                break;

            case 'tesla_secondary':
                ctx.strokeStyle = '#FF6699'; ctx.lineWidth = 1.5;
                // Tall coil
                ctx.beginPath();
                for (let i = 0; i < 12; i++) {
                    const yy = -hh + 5 + (hh*2-10) / 12 * i;
                    ctx.arc(0, yy + (hh*2-10)/24, (hh*2-10)/24, Math.PI, 0, i % 2 === 0);
                }
                ctx.stroke();
                ctx.beginPath(); ctx.moveTo(0, -hh - 5); ctx.lineTo(0, -hh); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(0, hh); ctx.lineTo(0, hh + 5); ctx.stroke();
                ctx.fillStyle = '#FF6699'; ctx.font = '8px sans-serif';
                ctx.textAlign = 'center'; ctx.fillText('T₂', hw + 10, 0);
                break;

            case 'top_load':
                ctx.strokeStyle = '#FFD700'; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.arc(0, 0, 16, 0, Math.PI * 2); ctx.stroke();
                ctx.beginPath(); ctx.arc(0, 0, 10, 0, Math.PI * 2); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(0, 16); ctx.lineTo(0, hh + 5); ctx.stroke();
                if (isSim) {
                    ctx.shadowColor = '#FFD700'; ctx.shadowBlur = 30;
                    ctx.beginPath(); ctx.arc(0, 0, 8, 0, Math.PI*2);
                    ctx.fillStyle = 'rgba(255,215,0,0.2)'; ctx.fill();
                }
                break;

            case 'spark_gap':
                ctx.strokeStyle = '#FF8800'; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.moveTo(-hw, 0); ctx.lineTo(-8, 0); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(8, 0); ctx.lineTo(hw, 0); ctx.stroke();
                // Spark electrodes
                ctx.beginPath(); ctx.moveTo(-8, -8); ctx.lineTo(-8, 8); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(8, -8); ctx.lineTo(8, 8); ctx.stroke();
                // Arc
                if (isSim) {
                    ctx.strokeStyle = '#FFDD00'; ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.moveTo(-8, 0);
                    for (let i = -7; i <= 7; i++) {
                        ctx.lineTo(i, (Math.random() - 0.5) * 10);
                    }
                    ctx.lineTo(8, 0); ctx.stroke();
                }
                break;

            case 'bifilar_coil':
                ctx.lineWidth = 2;
                // Two interleaved coils
                ctx.strokeStyle = '#CC00FF';
                ctx.beginPath();
                for (let i = 0; i < 6; i++) {
                    const y = -hh + 5 + i * (def.h - 10) / 6;
                    ctx.arc(-5, y + (def.h-10)/12, (def.h-10)/12, Math.PI, 0, false);
                }
                ctx.stroke();
                ctx.strokeStyle = '#9933FF';
                ctx.beginPath();
                for (let i = 0; i < 6; i++) {
                    const y = -hh + 5 + i * (def.h - 10) / 6;
                    ctx.arc(5, y + (def.h-10)/12, (def.h-10)/12, 0, Math.PI, false);
                }
                ctx.stroke();
                break;

            case 'caduceus_coil':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                // Intertwined helices
                ctx.beginPath();
                for (let t = 0; t < Math.PI * 4; t += 0.15) {
                    const px = Math.sin(t) * 12;
                    const py = -hh + 5 + (t / (Math.PI * 4)) * (def.h - 10);
                    if (t === 0) ctx.moveTo(px, py); else ctx.lineTo(px, py);
                }
                ctx.stroke();
                ctx.strokeStyle = '#6633CC';
                ctx.beginPath();
                for (let t = 0; t < Math.PI * 4; t += 0.15) {
                    const px = Math.sin(t + Math.PI) * 12;
                    const py = -hh + 5 + (t / (Math.PI * 4)) * (def.h - 10);
                    if (t === 0) ctx.moveTo(px, py); else ctx.lineTo(px, py);
                }
                ctx.stroke();
                break;

            case 'toroidal_inductor':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                // Torus shape
                ctx.beginPath(); ctx.arc(0, 0, 20, 0, Math.PI * 2); ctx.stroke();
                ctx.beginPath(); ctx.arc(0, 0, 12, 0, Math.PI * 2); ctx.stroke();
                // Windings (tick marks)
                for (let a = 0; a < Math.PI * 2; a += Math.PI / 8) {
                    ctx.beginPath();
                    ctx.moveTo(Math.cos(a) * 12, Math.sin(a) * 12);
                    ctx.lineTo(Math.cos(a) * 20, Math.sin(a) * 20);
                    ctx.stroke();
                }
                break;

            case 'casimir_plates':
                ctx.strokeStyle = '#00FFCC'; ctx.lineWidth = 2;
                // Two plates with gap
                ctx.beginPath(); ctx.moveTo(-15, -hh); ctx.lineTo(-15, hh); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(15, -hh); ctx.lineTo(15, hh); ctx.stroke();
                // Vacuum fluctuations (wavy lines)
                ctx.strokeStyle = 'rgba(0,255,204,0.3)'; ctx.lineWidth = 1;
                for (let y = -hh + 5; y < hh; y += 8) {
                    ctx.beginPath();
                    for (let x = -12; x <= 12; x++) {
                        const yy = y + Math.sin(x * 0.8 + state.simTime * 5) * 2;
                        if (x === -12) ctx.moveTo(x, yy); else ctx.lineTo(x, yy);
                    }
                    ctx.stroke();
                }
                ctx.fillStyle = '#00FFCC'; ctx.font = '7px sans-serif';
                ctx.textAlign = 'center'; ctx.fillText('CASIMIR', 0, hh + 10);
                break;

            case 'crystal_cell':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                // Crystal shape
                ctx.beginPath();
                ctx.moveTo(0, -hh); ctx.lineTo(hw, -5); ctx.lineTo(hw, 5);
                ctx.lineTo(0, hh); ctx.lineTo(-hw, 5); ctx.lineTo(-hw, -5); ctx.closePath();
                ctx.stroke();
                ctx.fillStyle = 'rgba(136,255,0,0.1)'; ctx.fill();
                ctx.fillStyle = '#88FF00'; ctx.font = '8px sans-serif';
                ctx.textAlign = 'center'; ctx.fillText('XTL', 0, 3);
                break;

            case 'schumann_antenna':
                ctx.strokeStyle = '#00DDFF'; ctx.lineWidth = 2;
                // Antenna with earth symbol
                ctx.beginPath(); ctx.moveTo(0, hh); ctx.lineTo(0, -5); ctx.stroke();
                // Dipole
                ctx.beginPath(); ctx.moveTo(-18, -5); ctx.lineTo(18, -5); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(-18, -5); ctx.lineTo(-18, -12); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(18, -5); ctx.lineTo(18, -12); ctx.stroke();
                // Earth waves
                ctx.strokeStyle = 'rgba(0,221,255,0.3)';
                ctx.beginPath(); ctx.arc(0, 5, 10, 0, Math.PI); ctx.stroke();
                ctx.beginPath(); ctx.arc(0, 5, 16, 0, Math.PI); ctx.stroke();
                ctx.fillStyle = '#00DDFF'; ctx.font = '7px sans-serif';
                ctx.textAlign = 'center'; ctx.fillText('7.83Hz', 0, hh + 12);
                break;

            case 'oscilloscope':
                ctx.strokeStyle = '#00FF88'; ctx.lineWidth = 2;
                ctx.strokeRect(-hw, -hh, def.w, def.h);
                // Mini waveform
                ctx.strokeStyle = '#00FF88'; ctx.lineWidth = 1;
                ctx.beginPath();
                for (let x = -hw + 5; x < hw - 5; x++) {
                    const y = Math.sin((x + state.simTime * 100) * 0.3) * (hh * 0.5);
                    if (x === -hw + 5) ctx.moveTo(x, y); else ctx.lineTo(x, y);
                }
                ctx.stroke();
                ctx.fillStyle = '#00FF88'; ctx.font = '7px sans-serif';
                ctx.textAlign = 'center'; ctx.fillText('OSC', 0, hh + 10);
                break;

            case 'em_field_probe':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.arc(0, 0, 16, 0, Math.PI * 2); ctx.stroke();
                // Field lines
                ctx.strokeStyle = 'rgba(255,136,255,0.4)';
                for (let a = 0; a < Math.PI * 2; a += Math.PI / 3) {
                    ctx.beginPath();
                    ctx.moveTo(Math.cos(a) * 10, Math.sin(a) * 10);
                    ctx.lineTo(Math.cos(a) * 20, Math.sin(a) * 20);
                    ctx.stroke();
                }
                ctx.fillStyle = '#FF88FF'; ctx.font = '8px sans-serif';
                ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText('EM', 0, 0);
                break;

            case 'leyden_jar':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                // Jar shape
                ctx.beginPath();
                ctx.moveTo(-10, -hh); ctx.lineTo(-10, hh - 5);
                ctx.quadraticCurveTo(-10, hh, 0, hh);
                ctx.quadraticCurveTo(10, hh, 10, hh - 5);
                ctx.lineTo(10, -hh); ctx.stroke();
                // Rod
                ctx.beginPath(); ctx.moveTo(0, -hh - 5); ctx.lineTo(0, -5); ctx.stroke();
                // Inner foil
                ctx.strokeStyle = 'rgba(255,170,0,0.3)';
                ctx.beginPath(); ctx.moveTo(-6, 0); ctx.lineTo(-6, hh - 8); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(6, 0); ctx.lineTo(6, hh - 8); ctx.stroke();
                break;

            case 'ic_555':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.strokeRect(-hw, -hh, def.w, def.h);
                ctx.fillStyle = def.color; ctx.font = 'bold 10px sans-serif';
                ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
                ctx.fillText('555', 0, 0);
                // Notch
                ctx.beginPath(); ctx.arc(-hw, 0, 4, -Math.PI/2, Math.PI/2); ctx.stroke();
                break;

            case 'voltage_regulator':
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.strokeRect(-hw, -hh, def.w, def.h);
                ctx.fillStyle = def.color; ctx.font = '9px sans-serif';
                ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
                ctx.fillText('REG', 0, 0);
                break;

            default:
                // Generic box with label
                ctx.strokeStyle = def.color; ctx.lineWidth = 2;
                ctx.strokeRect(-hw, -hh, def.w, def.h);
                ctx.font = '9px sans-serif'; ctx.fillStyle = def.color;
                ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
                ctx.fillText(def.label || comp.type.replace(/_/g, ' '), 0, 0);
                ctx.beginPath();
                ctx.moveTo(-hw - 5, 0); ctx.lineTo(-hw, 0);
                ctx.moveTo(hw, 0); ctx.lineTo(hw + 5, 0);
                ctx.stroke();
        }

        // Value label
        ctx.restore();
        ctx.save();
        ctx.fillStyle = 'rgba(255,255,255,0.5)';
        ctx.font = '10px "JetBrains Mono", monospace';
        ctx.textAlign = 'center';
        const label = formatValue(comp.value, COMP_DEFS[comp.type]?.unit);
        if (label) ctx.fillText(label, comp.x, comp.y + (COMP_DEFS[comp.type]?.h/2 || 20) + 14);
        ctx.restore();
    }

    // ── Draw Wire ──
    function drawWire(wire) {
        ctx.save();
        ctx.strokeStyle = state.simulating ? '#00FF88' : 'rgba(0,212,255,0.6)';
        ctx.lineWidth = state.simulating ? 2.5 : 2;
        if (state.simulating) {
            ctx.setLineDash([8, 4]);
            ctx.lineDashOffset = -state.simTime * 3;
        }
        ctx.beginPath();
        ctx.moveTo(wire.x1, wire.y1);
        if (Math.abs(wire.x2 - wire.x1) > Math.abs(wire.y2 - wire.y1)) {
            const midX = (wire.x1 + wire.x2) / 2;
            ctx.lineTo(midX, wire.y1); ctx.lineTo(midX, wire.y2);
        } else {
            const midY = (wire.y1 + wire.y2) / 2;
            ctx.lineTo(wire.x1, midY); ctx.lineTo(wire.x2, midY);
        }
        ctx.lineTo(wire.x2, wire.y2);
        ctx.stroke();
        ctx.setLineDash([]);
        ctx.restore();
    }

    // ── Render ──
    function render() {
        const w = canvas.width / window.devicePixelRatio;
        const h = canvas.height / window.devicePixelRatio;
        ctx.clearRect(0, 0, w, h);

        ctx.save();
        ctx.translate(state.panX, state.panY);
        ctx.scale(state.zoom, state.zoom);

        state.wires.forEach(drawWire);

        // v3.0: Junction dots where wires share endpoints
        if (state.showJunctions) {
            const junctions = new Map();
            state.wires.forEach(w => {
                const k1 = Math.round(w.x1) + ',' + Math.round(w.y1);
                const k2 = Math.round(w.x2) + ',' + Math.round(w.y2);
                junctions.set(k1, (junctions.get(k1) || 0) + 1);
                junctions.set(k2, (junctions.get(k2) || 0) + 1);
            });
            ctx.fillStyle = '#00D4FF';
            junctions.forEach((count, key) => {
                if (count >= 3) {
                    const [jx, jy] = key.split(',').map(Number);
                    ctx.beginPath(); ctx.arc(jx, jy, 4, 0, Math.PI * 2); ctx.fill();
                }
            });
        }

        // v3.0: Multi-select highlight
        state.multiSelected.forEach(id => {
            const c = state.components.find(cc => cc.id === id);
            if (c) {
                const def = COMP_DEFS[c.type];
                if (def) {
                    ctx.strokeStyle = 'rgba(0,212,255,0.5)';
                    ctx.lineWidth = 2;
                    ctx.setLineDash([6, 4]);
                    ctx.strokeRect(c.x - def.w/2 - 8, c.y - def.h/2 - 8, def.w + 16, def.h + 16);
                    ctx.setLineDash([]);
                }
            }
        });

        if (state.wireStart && state.wireEnd) {
            ctx.strokeStyle = 'rgba(0,212,255,0.4)';
            ctx.lineWidth = 2;
            ctx.setLineDash([5, 5]);
            ctx.beginPath();
            ctx.moveTo(state.wireStart.x, state.wireStart.y);
            ctx.lineTo(state.wireEnd.x, state.wireEnd.y);
            ctx.stroke();
            ctx.setLineDash([]);
        }

        state.components.forEach(drawComponent);

        // v4.0: Node voltage labels
        if (state.showNodeVoltages && state.simulating && state.lastResult) {
            ctx.font = '9px "JetBrains Mono", monospace';
            state.components.forEach(comp => {
                const data = state.lastResult.componentData ? state.lastResult.componentData.get(comp.id) : null;
                if (data && data.voltage != null) {
                    const def = COMP_DEFS[comp.type];
                    if (!def) return;
                    const v = typeof data.voltage === 'number' ? data.voltage : 0;
                    const label = Math.abs(v) >= 1 ? v.toFixed(2) + 'V' : (v * 1000).toFixed(1) + 'mV';
                    ctx.fillStyle = 'rgba(0,255,136,0.85)';
                    ctx.textAlign = 'center';
                    ctx.fillText(label, comp.x, comp.y - (def.h / 2) - 6);
                }
            });
        }

        ctx.restore();

        document.getElementById('statComponents').textContent = state.components.length;
        document.getElementById('statWires').textContent = state.wires.length;
        document.getElementById('statNodes').textContent = state.nodes.size;

        if (state.simulating) {
            state.simTime += 0.016;
            engine.step();
            requestAnimationFrame(render);
        }
    }

    // ── Simulation ──
    function simulate() {
        const result = engine.solve(state.components, state.wires);
        state.lastResult = result;

        // Update component simData
        for (const c of state.components) {
            const data = result.componentData.get(c.id);
            if (data) c.simData = data;
        }

        // Update readouts
        document.getElementById('readVoltage').textContent = Math.abs(result.totalVoltage).toFixed(2);
        document.getElementById('readCurrent').textContent = (result.totalCurrent * 1000).toFixed(1) + 'm';
        document.getElementById('readPower').textContent = (result.totalPower * 1000).toFixed(1) + 'm';
        document.getElementById('readResistance').textContent = result.totalResistance > 0 ? formatValue(result.totalResistance, 'Ω') : '∞';

        // AC readouts
        const acPanel = document.getElementById('acReadouts');
        if (result.impedance || result.resonantFreq) {
            acPanel.style.display = 'grid';
            document.getElementById('readFreq').textContent = result.frequency ? formatValue(result.frequency, 'Hz') : '—';
            document.getElementById('readPhase').textContent = result.phaseAngle ? result.phaseAngle.toFixed(1) + '°' : '—';
            document.getElementById('readResonance').textContent = result.resonantFreq ? formatValue(result.resonantFreq, 'Hz') : '—';
            document.getElementById('readQFactor').textContent = result.qualityFactor ? result.qualityFactor.toFixed(1) : '—';
        } else {
            acPanel.style.display = 'none';
        }

        // Status
        if (result.warnings.length > 0) {
            document.getElementById('statStatus').textContent = '⚠ ' + result.warnings[0];
            document.getElementById('statStatus').style.color = '#FFB800';
        } else if (result.totalCurrent > 0) {
            document.getElementById('statStatus').textContent = '⚡ Running';
            document.getElementById('statStatus').style.color = '#00FF88';
        } else {
            document.getElementById('statStatus').textContent = '⚡ Running';
            document.getElementById('statStatus').style.color = '#00FF88';
        }

        // v4.0: MNA solver stats
        if (result.mnaActive && result.solverStats) {
            const statsPanel = document.getElementById('mnaSolverStats');
            statsPanel.style.display = 'block';
            document.getElementById('mnaMatrixSize').textContent = result.solverStats.matrixSize + '×' + result.solverStats.matrixSize;
            document.getElementById('mnaNRIter').textContent = result.solverStats.iterations;
            const convEl = document.getElementById('mnaConverged');
            convEl.textContent = result.solverStats.converged ? '✓ Yes' : '✗ No';
            convEl.style.color = result.solverStats.converged ? '#00FF88' : '#FF3366';
            const dtUsed = result.solverStats.dtUsed || 1e-6;
            document.getElementById('mnaTimestep').textContent = dtUsed >= 1e-3 ? (dtUsed*1e3).toFixed(2)+'ms' : dtUsed >= 1e-6 ? (dtUsed*1e6).toFixed(1)+'µs' : (dtUsed*1e9).toFixed(0)+'ns';
        }

        // Update formula panel
        formulaPanel.update(result.formulas, result.warnings);

        // Update oscilloscope
        scope.render(engine.waveforms);

        // Scope channel readouts
        if (engine.waveforms.main && engine.waveforms.main.length > 0) {
            const last = engine.waveforms.main[engine.waveforms.main.length - 1];
            document.getElementById('scopeCH1').textContent = last.v.toFixed(2) + ' V';
            document.getElementById('scopeCH2').textContent = last.i.toFixed(2) + ' mA';
            if (result.frequency) {
                document.getElementById('scopeFreqRead').textContent = formatValue(result.frequency, 'Hz');
                document.getElementById('scopePeriodRead').textContent = formatValue(1/result.frequency, 's');
            }
        }

        // v3.0: Bode plot
        if (result.bodeData) {
            engine.bode.render(document.getElementById('bodeCanvas'));
            // Update Bode analysis readouts
            const bData = engine.bode.data;
            if (bData.magDb.length > 0) {
                document.getElementById('bodeDCGain').textContent = bData.magDb[0].toFixed(1) + ' dB';
                // Find -3dB cutoff
                const dcDb = bData.magDb[0];
                let cutoffIdx = -1;
                for (let bi = 0; bi < bData.magDb.length; bi++) {
                    if (bData.magDb[bi] <= dcDb - 3) { cutoffIdx = bi; break; }
                }
                if (cutoffIdx >= 0) {
                    document.getElementById('bodeCutoff').textContent = formatValue(bData.freq[cutoffIdx], 'Hz');
                } else {
                    document.getElementById('bodeCutoff').textContent = '—';
                }
                // Phase margin at 0dB crossover
                let zeroDbIdx = -1;
                for (let bi = 0; bi < bData.magDb.length; bi++) {
                    if (bData.magDb[bi] <= 0) { zeroDbIdx = bi; break; }
                }
                if (zeroDbIdx >= 0) {
                    document.getElementById('bodePhaseMargin').textContent = (180 + bData.phaseDeg[zeroDbIdx]).toFixed(1) + '°';
                } else {
                    document.getElementById('bodePhaseMargin').textContent = '—';
                }
                // Determine filter type
                const hasC = state.components.some(c => ['capacitor','high_voltage_cap','leyden_jar'].includes(c.type));
                const hasL = state.components.some(c => ['inductor','bifilar_coil','caduceus_coil','toroidal_inductor','tesla_primary','tesla_secondary'].includes(c.type));
                let fType = '—';
                if (hasC && hasL) fType = 'RLC Band';
                else if (hasC) fType = 'Low-Pass RC';
                else if (hasL) fType = 'High-Pass RL';
                document.getElementById('bodeFilterType').textContent = fType;
            }
        }

        // v3.0: Node/edge stats
        document.getElementById('statNodes').textContent = result.nodeCount || state.nodes.size;
    }

    // ── Mouse Coords ──
    function canvasCoords(e) {
        const rect = canvas.getBoundingClientRect();
        return {
            x: (e.clientX - rect.left - state.panX) / state.zoom,
            y: (e.clientY - rect.top - state.panY) / state.zoom
        };
    }

    function hitTest(x, y) {
        for (let i = state.components.length - 1; i >= 0; i--) {
            const c = state.components[i];
            const def = COMP_DEFS[c.type];
            if (!def) continue;
            const hw = def.w / 2 + 10, hh = def.h / 2 + 10;
            if (x >= c.x - hw && x <= c.x + hw && y >= c.y - hh && y <= c.y + hh) return c;
        }
        return null;
    }

    // ── Add Component ──
    function addComponent(type, x, y, value, extraProps) {
        saveState();
        const comp = {
            id: state.nextId++,
            type,
            x: snapToGrid(x),
            y: snapToGrid(y),
            value: parseFloat(value) || 0,
            rotation: 0,
            closed: (type === 'switch' || type === 'push_button' || type === 'reed_switch') ? false : undefined,
            blown: false,
            simData: {},
            ...extraProps
        };
        state.components.push(comp);
        state.selectedId = comp.id;
        updateSelectedUI(comp);
        addEnergy(1);
        document.getElementById('dropHint').classList.add('hidden');
        if (state.simulating) simulate();
        render();
        return comp;
    }

    function addEnergy(amount) {
        state.energy += amount;
        state.circuitsBuilt++;
        document.getElementById('energyLabel').textContent = state.energy.toFixed(0) + ' eV';
        document.getElementById('energyContrib').textContent = state.energy.toFixed(0) + ' eV';
        document.getElementById('energyCircuits').textContent = state.circuitsBuilt;
        document.getElementById('energyFill').style.width = Math.min(state.energy / 100 * 100, 100) + '%';
    }

    function updateSelectedUI(comp) {
        const panel = document.getElementById('selectedProps');
        if (!comp) { panel.style.display = 'none'; return; }
        panel.style.display = 'block';
        const def = COMP_DEFS[comp.type];
        document.getElementById('propType').textContent = comp.type.replace(/_/g, ' ');
        document.getElementById('propValue').value = comp.value;
        document.getElementById('propUnit').textContent = def?.unit || '—';
        document.getElementById('propRotation').value = comp.rotation || 0;

        // Frequency row for AC/RF sources
        const freqRow = document.getElementById('propFreqRow');
        if (['ac_source','rf_generator','signal_generator'].includes(comp.type)) {
            freqRow.style.display = 'flex';
            document.getElementById('propFreq').value = comp.frequency || 60;
        } else {
            freqRow.style.display = 'none';
        }

        // Turns row for transformer
        const turnsRow = document.getElementById('propTurnsRow');
        if (comp.type === 'transformer') {
            turnsRow.style.display = 'flex';
            document.getElementById('propTurns').value = comp.secondary_turns || 1000;
        } else {
            turnsRow.style.display = 'none';
        }

        if (state.simulating && comp.simData) {
            document.getElementById('propCurrentRow').style.display = 'flex';
            document.getElementById('propVoltageRow').style.display = 'flex';
            document.getElementById('propCurrent').textContent = comp.simData.current ?
                formatValue(comp.simData.current, 'A') : '—';
            document.getElementById('propVoltDrop').textContent = comp.simData.voltage ?
                formatValue(comp.simData.voltage, 'V') : '—';
        } else {
            document.getElementById('propCurrentRow').style.display = 'none';
            document.getElementById('propVoltageRow').style.display = 'none';
        }
    }

    // ── Drag & Drop ──
    document.getElementById('palette').addEventListener('dragstart', e => {
        const el = e.target.closest('.cs-component');
        if (!el) return;
        e.dataTransfer.setData('text/plain', JSON.stringify({
            type: el.dataset.type,
            value: el.dataset.value
        }));
        e.dataTransfer.effectAllowed = 'copy';
    });

    document.getElementById('canvasWrap').addEventListener('dragover', e => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });

    document.getElementById('canvasWrap').addEventListener('drop', e => {
        e.preventDefault();
        try {
            const data = JSON.parse(e.dataTransfer.getData('text/plain'));
            const coords = canvasCoords(e);
            const extra = {};
            if (['ac_source'].includes(data.type)) extra.frequency = 60;
            if (['rf_generator'].includes(data.type)) extra.frequency = 1e6;
            if (['signal_generator'].includes(data.type)) extra.frequency = 1000;
            if (data.type === 'transformer') extra.secondary_turns = 1000;
            addComponent(data.type, coords.x, coords.y, data.value, extra);
        } catch(err) {}
    });

    // ── Mouse Events ──
    canvas.addEventListener('contextmenu', e => {
        e.preventDefault();
        const coords = canvasCoords(e);
        const comp = hitTest(coords.x, coords.y);
        state.contextTarget = comp;
        const menu = document.getElementById('contextMenu');
        menu.style.left = e.clientX + 'px';
        menu.style.top = e.clientY + 'px';
        menu.classList.add('show');
    });

    document.addEventListener('click', () => {
        document.getElementById('contextMenu').classList.remove('show');
    });

    canvas.addEventListener('mousedown', e => {
        const coords = canvasCoords(e);

        if (e.button === 1 || (e.button === 0 && e.altKey)) {
            state.panning = true;
            state.panStart = { x: e.clientX - state.panX, y: e.clientY - state.panY };
            canvas.style.cursor = 'grabbing';
            return;
        }

        if (state.mode === 'wire') {
            const term = findNearestTerminal(coords.x, coords.y);
            state.wireStart = term ? { x: term.x, y: term.y, compId: term.compId, termIndex: term.termIndex }
                                   : { x: snapToGrid(coords.x), y: snapToGrid(coords.y) };
            state.wireEnd = null;
            return;
        }

        if (state.mode === 'delete') {
            const comp = hitTest(coords.x, coords.y);
            if (comp) {
                saveState();
                state.components = state.components.filter(c => c.id !== comp.id);
                state.wires = state.wires.filter(w => w.comp1 !== comp.id && w.comp2 !== comp.id);
                if (state.selectedId === comp.id) { state.selectedId = null; updateSelectedUI(null); }
                if (state.simulating) simulate();
                render();
            }
            return;
        }

        const comp = hitTest(coords.x, coords.y);
        if (comp) {
            // v3.0: Shift+click multi-select
            if (e.shiftKey) {
                const idx = state.multiSelected.indexOf(comp.id);
                if (idx >= 0) state.multiSelected.splice(idx, 1);
                else state.multiSelected.push(comp.id);
                render();
                return;
            }
            state.multiSelected = [];
            state.selectedId = comp.id;
            state.dragging = comp;
            state.dragOffset = { x: coords.x - comp.x, y: coords.y - comp.y };
            updateSelectedUI(comp);

            if (['switch','push_button','reed_switch'].includes(comp.type)) {
                if (comp._lastClick && Date.now() - comp._lastClick < 300) {
                    comp.closed = !comp.closed;
                    if (state.simulating) simulate();
                }
                comp._lastClick = Date.now();
            }
        } else {
            state.selectedId = null;
            updateSelectedUI(null);
        }
        render();
    });

    canvas.addEventListener('mousemove', e => {
        const coords = canvasCoords(e);
        if (state.panning) {
            state.panX = e.clientX - state.panStart.x;
            state.panY = e.clientY - state.panStart.y;
            render(); return;
        }
        if (state.mode === 'wire' && state.wireStart) {
            state.wireEnd = { x: snapToGrid(coords.x), y: snapToGrid(coords.y) };
            render(); return;
        }
        if (state.dragging) {
            saveState();
            state.dragging.x = snapToGrid(coords.x - state.dragOffset.x);
            state.dragging.y = snapToGrid(coords.y - state.dragOffset.y);
            state.wires.forEach(w => {
                if (w.comp1 === state.dragging.id) {
                    const terms = getTerminals(state.dragging);
                    if (terms[w.term1]) { w.x1 = terms[w.term1].x; w.y1 = terms[w.term1].y; }
                }
                if (w.comp2 === state.dragging.id) {
                    const terms = getTerminals(state.dragging);
                    if (terms[w.term2]) { w.x2 = terms[w.term2].x; w.y2 = terms[w.term2].y; }
                }
            });
            render();
        }
    });

    canvas.addEventListener('mouseup', e => {
        if (state.panning) { state.panning = false; canvas.style.cursor = ''; return; }

        if (state.mode === 'wire' && state.wireStart && state.wireEnd) {
            saveState();
            const coords = canvasCoords(e);
            const term = findNearestTerminal(coords.x, coords.y, state.wireStart.compId);
            const wire = {
                id: state.nextId++,
                x1: state.wireStart.x, y1: state.wireStart.y,
                x2: term ? term.x : snapToGrid(coords.x),
                y2: term ? term.y : snapToGrid(coords.y),
                comp1: state.wireStart.compId || null,
                comp2: term ? term.compId : null,
                term1: state.wireStart.termIndex || 0,
                term2: term ? term.termIndex : 0
            };
            if (Math.hypot(wire.x2 - wire.x1, wire.y2 - wire.y1) > 10) {
                state.wires.push(wire);
                addEnergy(0.5);
            }
            state.wireStart = null; state.wireEnd = null;
            if (state.simulating) simulate();
            render();
        }
        state.dragging = null;
    });

    // ── Keyboard ──
    document.addEventListener('keydown', e => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') return;
        switch(e.key.toLowerCase()) {
            case 'v': setMode('select'); break;
            case 'w': setMode('wire'); break;
            case 'delete': case 'backspace':
                if (state.selectedId) {
                    saveState();
                    state.components = state.components.filter(c => c.id !== state.selectedId);
                    state.wires = state.wires.filter(w => w.comp1 !== state.selectedId && w.comp2 !== state.selectedId);
                    state.selectedId = null; updateSelectedUI(null);
                    if (state.simulating) simulate();
                    render();
                }
                break;
            case 'r':
                if (state.selectedId) {
                    const comp = state.components.find(c => c.id === state.selectedId);
                    if (comp) { saveState(); comp.rotation = ((comp.rotation || 0) + 90) % 360; updateSelectedUI(comp); render(); }
                }
                break;
            case ' ': e.preventDefault(); toggleSimulation(); break;
            case 'z': if (e.ctrlKey || e.metaKey) { e.preventDefault(); undo(); } break;
            case 'y': if (e.ctrlKey || e.metaKey) { e.preventDefault(); redo(); } break;
            case 'c': if (e.ctrlKey || e.metaKey) { e.preventDefault(); copySelected(); } break;
            case 'v': if (e.ctrlKey || e.metaKey) { e.preventDefault(); pasteClipboard(); } else { setMode('select'); } break;
            case 'd': if (e.ctrlKey || e.metaKey) { e.preventDefault(); duplicateSelected(); } break;
            case 'a': if (e.ctrlKey || e.metaKey) { e.preventDefault(); selectAll(); } break;
            case '?': showKBHelp(); break;
            case 'escape':
                state.wireStart = null; state.wireEnd = null;
                state.multiSelected = [];
                hideKBHelp();
                setMode('select'); render();
                break;
        }
    });

    canvas.addEventListener('wheel', e => {
        e.preventDefault();
        state.zoom = Math.max(0.2, Math.min(3, state.zoom * (e.deltaY > 0 ? 0.9 : 1.1)));
        render();
    });

    // ── Public API ──
    function setMode(mode) {
        state.mode = mode;
        document.getElementById('modeSelect').classList.toggle('active', mode === 'select');
        document.getElementById('modeWire').classList.toggle('active', mode === 'wire');
        document.getElementById('modeDelete').classList.toggle('active', mode === 'delete');
        canvas.style.cursor = mode === 'wire' ? 'crosshair' : mode === 'delete' ? 'not-allowed' : 'default';
    }

    function toggleSimulation() {
        state.simulating = !state.simulating;
        const btn = document.getElementById('simToggle');
        if (state.simulating) {
            btn.textContent = '⏸ Pause';
            btn.classList.remove('cs-btn-energy'); btn.classList.add('cs-btn-primary');
            engine.reset();
            simulate();
            render();
            addEnergy(2);

            // Start continuous simulation updates
            if (!state.simInterval) {
                state.simInterval = setInterval(() => {
                    if (state.simulating) simulate();
                }, 50);
            }
        } else {
            btn.textContent = '▶ Simulate';
            btn.classList.add('cs-btn-energy'); btn.classList.remove('cs-btn-primary');
            document.getElementById('statStatus').textContent = 'Ready';
            document.getElementById('statStatus').style.color = 'var(--cs-text-muted)';
            if (state.simInterval) { clearInterval(state.simInterval); state.simInterval = null; }
            render();
        }
    }

    function undo() {
        if (state.undoStack.length === 0) return;
        state.redoStack.push(JSON.stringify({ components: state.components, wires: state.wires }));
        const prev = JSON.parse(state.undoStack.pop());
        state.components = prev.components; state.wires = prev.wires;
        if (state.simulating) simulate();
        render();
    }

    function redo() {
        if (state.redoStack.length === 0) return;
        state.undoStack.push(JSON.stringify({ components: state.components, wires: state.wires }));
        const next = JSON.parse(state.redoStack.pop());
        state.components = next.components; state.wires = next.wires;
        if (state.simulating) simulate();
        render();
    }

    function clearAll() {
        if (state.components.length === 0 && state.wires.length === 0) return;
        saveState();
        state.components = []; state.wires = []; state.selectedId = null;
        updateSelectedUI(null);
        document.getElementById('dropHint').classList.remove('hidden');
        if (state.simulating) toggleSimulation();
        engine.reset();
        render();
    }

    function zoomIn() { state.zoom = Math.min(3, state.zoom * 1.2); render(); }
    function zoomOut() { state.zoom = Math.max(0.2, state.zoom / 1.2); render(); }
    function fitView() { state.zoom = 1; state.panX = 0; state.panY = 0; render(); }
    function toggleNodeVoltages() {
        state.showNodeVoltages = !state.showNodeVoltages;
        document.getElementById('modeNodeV').classList.toggle('active', state.showNodeVoltages);
        render();
    }

    function updateSelectedValue(val) {
        const comp = state.components.find(c => c.id === state.selectedId);
        if (comp) { saveState(); comp.value = parseFloat(val) || 0; if (state.simulating) simulate(); render(); }
    }
    function updateSelectedRotation(val) {
        const comp = state.components.find(c => c.id === state.selectedId);
        if (comp) { saveState(); comp.rotation = parseInt(val) || 0; render(); }
    }
    function updateSelectedFreq(val) {
        const comp = state.components.find(c => c.id === state.selectedId);
        if (comp) { saveState(); comp.frequency = parseFloat(val) || 60; if (state.simulating) simulate(); render(); }
    }
    function updateSelectedTurns(val) {
        const comp = state.components.find(c => c.id === state.selectedId);
        if (comp) { saveState(); comp.secondary_turns = parseInt(val) || 1000; if (state.simulating) simulate(); render(); }
    }

    function exportCircuit() {
        const data = {
            version: '5.0',
            app: 'GoSiteMe Circuit Simulator',
            components: state.components.map(c => ({...c, simData: undefined, _lastClick: undefined})),
            wires: state.wires,
            meta: { exported: new Date().toISOString(), count: state.components.length }
        };
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a'); a.href = url;
        a.download = 'circuit-' + Date.now() + '.json'; a.click();
        URL.revokeObjectURL(url);
        addEnergy(3);
    }

    function importCircuit() {
        const input = document.createElement('input');
        input.type = 'file'; input.accept = '.json';
        input.onchange = e => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = ev => {
                try {
                    const data = JSON.parse(ev.target.result);
                    if (data.components) {
                        clearAll();
                        data.components.forEach(c => {
                            c.id = state.nextId++;
                            c.simData = {};
                            state.components.push(c);
                        });
                        if (data.wires) {
                            data.wires.forEach(w => { w.id = state.nextId++; state.wires.push(w); });
                        }
                        document.getElementById('dropHint').classList.add('hidden');
                        render();
                        addEnergy(5);
                    }
                } catch(err) {
                    console.error('Import failed:', err);
                }
            };
            reader.readAsText(file);
        };
        input.click();
    }

    function exportNetlist() {
        let netlist = '* GoSiteMe Circuit Simulator v5.0 SPICE Netlist\n';
        netlist += '* Generated: ' + new Date().toISOString() + '\n\n';
        let nodeCounter = 1;
        state.components.forEach((c, i) => {
            const n1 = nodeCounter++, n2 = nodeCounter++;
            switch(c.type) {
                case 'resistor': netlist += 'R' + i + ' ' + n1 + ' ' + n2 + ' ' + c.value + '\n'; break;
                case 'capacitor': netlist += 'C' + i + ' ' + n1 + ' ' + n2 + ' ' + c.value + '\n'; break;
                case 'inductor': netlist += 'L' + i + ' ' + n1 + ' ' + n2 + ' ' + c.value + '\n'; break;
                case 'battery': netlist += 'V' + i + ' ' + n1 + ' ' + n2 + ' DC ' + c.value + '\n'; break;
                case 'ac_source': netlist += 'V' + i + ' ' + n1 + ' ' + n2 + ' AC ' + c.value + '\n'; break;
                case 'diode': netlist += 'D' + i + ' ' + n1 + ' ' + n2 + ' DMOD\n'; break;
                case 'led': netlist += 'D' + i + ' ' + n1 + ' ' + n2 + ' LED\n'; break;
                default: netlist += '* ' + c.type + ' (id=' + c.id + ') val=' + c.value + '\n';
            }
        });
        netlist += '\n.end\n';
        const blob = new Blob([netlist], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a'); a.href = url;
        a.download = 'circuit-' + Date.now() + '.spice'; a.click();
        URL.revokeObjectURL(url);
        addEnergy(3);
    }

    function shareCircuit() {
        const data = btoa(JSON.stringify({
            c: state.components.map(c => {
                const arr = [c.type, c.x, c.y, c.value, c.rotation];
                if (c.frequency) arr.push(c.frequency);
                if (c.secondary_turns) arr.push(c.secondary_turns);
                return arr;
            }),
            w: state.wires.map(w => [w.x1, w.y1, w.x2, w.y2])
        }));
        const url = window.location.origin + '/circuit-simulator.php?circuit=' + data;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(() => {
                const s = document.getElementById('statStatus');
                s.textContent = '✓ Link copied!'; s.style.color = '#00FF88';
                setTimeout(() => {
                    s.textContent = state.simulating ? '⚡ Running' : 'Ready';
                    s.style.color = state.simulating ? '#00FF88' : 'var(--cs-text-muted)';
                }, 2000);
            });
        }
        addEnergy(2);
    }

    // ── Prebuilt Circuits ──
    function loadPrebuilt(name) {
        clearAll();
        const cx = 400, cy = 300;

        switch(name) {
            case 'led_basic':
                addComponent('battery', cx - 150, cy, 9);
                addComponent('resistor', cx, cy - 80, 330);
                addComponent('led', cx + 150, cy, 2);
                state.wires.push(
                    { id: state.nextId++, x1: cx - 120, y1: cy, x2: cx - 35, y2: cy - 80 },
                    { id: state.nextId++, x1: cx + 35, y1: cy - 80, x2: cx + 130, y2: cy },
                    { id: state.nextId++, x1: cx + 170, y1: cy, x2: cx + 200, y2: cy + 80 },
                    { id: state.nextId++, x1: cx + 200, y1: cy + 80, x2: cx - 180, y2: cy }
                );
                break;
            case 'voltage_divider':
                addComponent('battery', cx - 150, cy, 12);
                addComponent('resistor', cx + 50, cy - 80, 10000);
                addComponent('resistor', cx + 50, cy + 80, 10000);
                addComponent('voltmeter', cx + 200, cy, 0);
                break;
            case 'rc_filter':
                addComponent('ac_source', cx - 150, cy, 5, { frequency: 1000 });
                addComponent('resistor', cx, cy - 60, 1000);
                addComponent('capacitor', cx + 150, cy, 0.000001);
                break;
            case 'transistor_switch':
                addComponent('battery', cx - 180, cy - 60, 9);
                addComponent('resistor', cx - 40, cy + 60, 10000);
                addComponent('npn_transistor', cx + 60, cy, 100);
                addComponent('led', cx + 60, cy - 120, 2);
                addComponent('resistor', cx + 60, cy - 180, 330);
                addComponent('switch', cx - 120, cy + 60, 1);
                break;
            case 'full_bridge':
                addComponent('ac_source', cx - 180, cy, 12, { frequency: 60 });
                addComponent('diode', cx - 60, cy - 60, 0.7);
                addComponent('diode', cx + 60, cy - 60, 0.7);
                addComponent('diode', cx - 60, cy + 60, 0.7);
                addComponent('diode', cx + 60, cy + 60, 0.7);
                addComponent('resistor', cx + 180, cy, 1000);
                break;
            case '555_timer':
                addComponent('battery', cx - 200, cy, 9);
                addComponent('resistor', cx - 60, cy - 100, 1000);
                addComponent('resistor', cx - 60, cy, 4700);
                addComponent('capacitor', cx - 60, cy + 100, 0.0001);
                addComponent('ic_555', cx + 60, cy, 0);
                addComponent('led', cx + 180, cy - 60, 2);
                addComponent('resistor', cx + 180, cy + 40, 330);
                break;
            case 'joule_thief':
                addComponent('battery', cx - 150, cy, 1.5);
                addComponent('resistor', cx - 20, cy - 80, 1000);
                addComponent('bifilar_coil', cx + 80, cy - 40, 0.001);
                addComponent('npn_transistor', cx + 80, cy + 60, 100);
                addComponent('led', cx + 200, cy, 3.2);
                break;
        }
        addEnergy(5);
        render();
    }

    // ── Load ZPE Template ──
    function loadZPETemplate(index) {
        if (index < 0 || index >= ZPE_TEMPLATES.length) return;
        clearAll();
        const tmpl = ZPE_TEMPLATES[index];
        tmpl.components.forEach(c => {
            addComponent(c.type, c.x, c.y, c.value, {
                frequency: c.frequency,
                secondary_turns: c.secondary_turns,
                capacitance: c.capacitance,
                inductance: c.inductance,
                area: c.area
            });
        });
        addEnergy(10);
        render();

        // Show info toast
        const status = document.getElementById('statStatus');
        status.textContent = '⚛ ' + tmpl.name;
        status.style.color = '#00FFCC';
    }

    // ── Populate ZPE Template List ──
    function populateZPETemplates() {
        const list = document.getElementById('zpeTemplateList');
        if (!list) return;
        list.innerHTML = ZPE_TEMPLATES.map((t, i) => {
            const cat = t.category === 'tesla' ? 'tesla-item' : t.category === 'zpe' ? 'zpe-item' : '';
            const icon = t.category === 'tesla' ? '⚡' : t.category === 'zpe' ? '⚛' : '🔬';
            const badge = t.category === 'zpe' ? '<span class="cs-badge cs-badge-zpe">ZPE</span>' :
                          t.category === 'tesla' ? '<span class="cs-badge cs-badge-tesla">TESLA</span>' : '';
            return '<div class="cs-prebuilt-item ' + cat + '" onclick="csApp.loadZPETemplate(' + i + ')">' +
                '<span class="cs-prebuilt-icon">' + icon + '</span>' +
                '<div><div style="font-weight:600;">' + t.name + ' ' + badge + '</div>' +
                '<div class="cs-researcher">' + t.researcher + '</div>' +
                '<div style="font-size:0.62rem;color:var(--cs-text-muted);margin-top:2px;">' + t.description.substring(0, 60) + '...</div></div></div>';
        }).join('');
    }

    // ── Tab Switching ──
    function switchPaletteTab(tab) {
        document.querySelectorAll('.cs-palette-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.cs-palette-panel').forEach(p => p.classList.remove('active'));
        const tabMap = { standard: 'paletteStandard', advanced: 'paletteAdvanced', zpe: 'paletteZpe' };
        document.getElementById(tabMap[tab])?.classList.add('active');
        document.querySelectorAll('.cs-palette-tab').forEach(t => {
            if (t.textContent.toLowerCase().includes(tab.substring(0, 3))) t.classList.add('active');
        });
    }

    function switchRightTab(tab) {
        document.querySelectorAll('.cs-right-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.cs-right-panel').forEach(p => p.classList.remove('active'));
        const tabMap = { sim: 'rightSim', scope: 'rightScope', bode: 'rightBode', formulas: 'rightFormulas', circuits: 'rightCircuits' };
        document.getElementById(tabMap[tab])?.classList.add('active');
        document.querySelectorAll('.cs-right-tab').forEach(t => {
            if (t.textContent.toLowerCase().includes(tab.substring(0, 3))) t.classList.add('active');
        });
    }

    // ── Scope Controls ──
    function scopeZoomIn() { if (scope) scope.timeDiv = Math.max(0.0001, scope.timeDiv / 2); }
    function scopeZoomOut() { if (scope) scope.timeDiv = Math.min(1, scope.timeDiv * 2); }
    function scopeVoltUp() { if (scope) scope.voltDiv = Math.min(1000, scope.voltDiv * 2); }
    function scopeVoltDown() { if (scope) scope.voltDiv = Math.max(0.1, scope.voltDiv / 2); }
    function scopeTogglePause() { if (scope) scope.paused = !scope.paused; }

    // ── v3.0: Scope Mode Controls ──
    function scopeSetMode(mode) {
        if (!scope) return;
        scope.showFFT = (mode === 'fft');
        scope.xyMode = (mode === 'xy');
        document.getElementById('scopeModeNorm').classList.toggle('active', mode === 'normal');
        document.getElementById('scopeModeFFT').classList.toggle('active', mode === 'fft');
        document.getElementById('scopeModeXY').classList.toggle('active', mode === 'xy');
    }
    function scopeTogglePersistence() {
        if (!scope) return;
        scope.persistence = !scope.persistence;
        if (!scope.persistence) scope.prevTraces = [];
        document.getElementById('scopeModePersist').classList.toggle('active', scope.persistence);
    }
    function scopeToggleCursors() {
        if (!scope) return;
        if (scope.cursorA != null) {
            scope.cursorA = null; scope.cursorB = null;
        } else {
            scope.cursorA = 50; scope.cursorB = 150;
        }
        document.getElementById('scopeModeCursor').classList.toggle('active', scope.cursorA != null);
    }

    // ── v3.0: Copy/Paste/Duplicate ──
    function copySelected() {
        const ids = state.multiSelected.length > 0 ? state.multiSelected : (state.selectedId ? [state.selectedId] : []);
        state.clipboard = ids.map(id => {
            const c = state.components.find(cc => cc.id === id);
            return c ? {...c, simData: undefined, _lastClick: undefined} : null;
        }).filter(Boolean);
    }
    function pasteClipboard() {
        if (state.clipboard.length === 0) return;
        saveState();
        state.clipboard.forEach(c => {
            addComponent(c.type, c.x + 40, c.y + 40, c.value, {
                rotation: c.rotation, frequency: c.frequency, secondary_turns: c.secondary_turns
            });
        });
        render();
    }
    function duplicateSelected() {
        copySelected();
        pasteClipboard();
    }
    function selectAll() {
        state.multiSelected = state.components.map(c => c.id);
        render();
    }

    // ── v3.0: Context Menu ──
    function ctxRotate() {
        const comp = state.contextTarget || state.components.find(c => c.id === state.selectedId);
        if (comp) { saveState(); comp.rotation = ((comp.rotation || 0) + 90) % 360; updateSelectedUI(comp); render(); }
    }
    function ctxDuplicate() {
        if (state.contextTarget) { state.selectedId = state.contextTarget.id; state.multiSelected = []; }
        duplicateSelected();
    }
    function ctxCopy() {
        if (state.contextTarget) { state.selectedId = state.contextTarget.id; state.multiSelected = []; }
        copySelected();
    }
    function ctxPaste() { pasteClipboard(); }
    function ctxProps() {
        if (state.contextTarget) {
            state.selectedId = state.contextTarget.id;
            updateSelectedUI(state.contextTarget);
            switchRightTab('sim');
        }
    }
    function ctxDelete() {
        const comp = state.contextTarget || state.components.find(c => c.id === state.selectedId);
        if (comp) {
            saveState();
            state.components = state.components.filter(c => c.id !== comp.id);
            state.wires = state.wires.filter(w => w.comp1 !== comp.id && w.comp2 !== comp.id);
            if (state.selectedId === comp.id) { state.selectedId = null; updateSelectedUI(null); }
            if (state.simulating) simulate();
            render();
        }
    }

    // ── v3.0: Palette Search ──
    function filterPalette(query) {
        const q = query.toLowerCase().trim();
        document.querySelectorAll('.cs-component').forEach(el => {
            if (!q) { el.style.display = ''; return; }
            const name = (el.dataset.type || '').toLowerCase() + ' ' + (el.querySelector('.cs-component-name')?.textContent || '').toLowerCase();
            el.style.display = name.includes(q) ? '' : 'none';
        });
    }

    // ── v3.0: Keyboard Help ──
    function showKBHelp() { document.getElementById('kbOverlay').classList.add('show'); }
    function hideKBHelp() { document.getElementById('kbOverlay').classList.remove('show'); }

    // ── v3.0: PNG Export ──
    function exportPNG() {
        const tempCanvas = document.createElement('canvas');
        const w = canvas.width, h = canvas.height;
        tempCanvas.width = w; tempCanvas.height = h;
        const tCtx = tempCanvas.getContext('2d');
        tCtx.fillStyle = '#060612';
        tCtx.fillRect(0, 0, w, h);
        tCtx.drawImage(canvas, 0, 0);
        // Watermark
        tCtx.fillStyle = 'rgba(0,212,255,0.3)';
        tCtx.font = '12px sans-serif';
        tCtx.textAlign = 'right';
        tCtx.fillText('GoSiteMe Circuit Simulator v5.0', w - 10, h - 10);
        const link = document.createElement('a');
        link.download = 'circuit-' + Date.now() + '.png';
        link.href = tempCanvas.toDataURL('image/png');
        link.click();
        addEnergy(3);
    }

    // ── v3.0: Touch Events ──
    canvas.addEventListener('touchstart', e => {
        e.preventDefault();
        const touch = e.touches[0];
        const mouseEvent = new MouseEvent('mousedown', { clientX: touch.clientX, clientY: touch.clientY, button: 0 });
        canvas.dispatchEvent(mouseEvent);
    }, { passive: false });
    canvas.addEventListener('touchmove', e => {
        e.preventDefault();
        const touch = e.touches[0];
        const mouseEvent = new MouseEvent('mousemove', { clientX: touch.clientX, clientY: touch.clientY });
        canvas.dispatchEvent(mouseEvent);
    }, { passive: false });
    canvas.addEventListener('touchend', e => {
        e.preventDefault();
        const mouseEvent = new MouseEvent('mouseup', {});
        canvas.dispatchEvent(mouseEvent);
    }, { passive: false });

    // ── Load from URL ──
    function loadFromURL() {
        const params = new URLSearchParams(window.location.search);
        const circuitData = params.get('circuit');
        if (circuitData) {
            try {
                const data = JSON.parse(atob(circuitData));
                if (data.c) {
                    data.c.forEach(arr => {
                        const [type, x, y, value, rotation, freq, turns] = arr;
                        const comp = {
                            id: state.nextId++, type, x, y,
                            value: parseFloat(value) || 0,
                            rotation: rotation || 0,
                            closed: ['switch','push_button','reed_switch'].includes(type) ? false : undefined,
                            blown: false, simData: {}
                        };
                        if (freq) comp.frequency = freq;
                        if (turns) comp.secondary_turns = turns;
                        state.components.push(comp);
                    });
                }
                if (data.w) {
                    data.w.forEach(([x1, y1, x2, y2]) => {
                        state.wires.push({ id: state.nextId++, x1, y1, x2, y2 });
                    });
                }
                document.getElementById('dropHint').classList.add('hidden');
                render();
            } catch(e) {}
        }
    }

    // ── Init ──
    function init() {
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        populateZPETemplates();
        loadFromURL();
    }

    init();

    return {
        setMode, toggleSimulation, undo, redo, clearAll, zoomIn, zoomOut, fitView, toggleNodeVoltages,
        updateSelectedValue, updateSelectedRotation, updateSelectedFreq, updateSelectedTurns,
        exportCircuit, importCircuit, exportNetlist, shareCircuit, exportPNG,
        loadPrebuilt, loadZPETemplate,
        switchPaletteTab, switchRightTab, filterPalette,
        scopeZoomIn, scopeZoomOut, scopeVoltUp, scopeVoltDown, scopeTogglePause,
        scopeSetMode, scopeTogglePersistence, scopeToggleCursors,
        copySelected, pasteClipboard: pasteClipboard, duplicateSelected, selectAll,
        ctxRotate, ctxDuplicate, ctxCopy, ctxPaste, ctxProps, ctxDelete,
        showKBHelp, hideKBHelp
    };
})();
