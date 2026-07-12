/* ═══════════════════════════════════════════════════════════════
   GoSiteMe — Circuit Simulator Physics Engine v5.0
   MNA Solver, Newton-Raphson, SPICE Models, FFT, Bode, Transient
   New: JFET, P-MOSFET, IGBT, SCR, 555, LDO, Schottky, Photodiode
   ═══════════════════════════════════════════════════════════════ */

// ── Complex Number Math ──
class Complex {
    constructor(re = 0, im = 0) { this.re = re; this.im = im; }
    add(c) { return new Complex(this.re + c.re, this.im + c.im); }
    sub(c) { return new Complex(this.re - c.re, this.im - c.im); }
    mul(c) { return new Complex(this.re*c.re - this.im*c.im, this.re*c.im + this.im*c.re); }
    div(c) { const d = c.re*c.re + c.im*c.im; return d === 0 ? new Complex(0,0) : new Complex((this.re*c.re+this.im*c.im)/d, (this.im*c.re-this.re*c.im)/d); }
    mag() { return Math.sqrt(this.re*this.re + this.im*this.im); }
    phase() { return Math.atan2(this.im, this.re); }
    conj() { return new Complex(this.re, -this.im); }
    scale(s) { return new Complex(this.re * s, this.im * s); }
    static polar(r, theta) { return new Complex(r * Math.cos(theta), r * Math.sin(theta)); }
    static fromReal(r) { return new Complex(r, 0); }
}

// ── Physical Constants ──
const PHYS = {
    c: 299792458, h: 6.62607015e-34, hbar: 1.054571817e-34,
    k_B: 1.380649e-23, e: 1.602176634e-19, mu_0: 1.2566370614e-6,
    eps_0: 8.854187817e-12, G: 6.67430e-11, m_e: 9.1093837015e-31,
    N_A: 6.02214076e23, sigma: 5.670374419e-8, phi: 1.6180339887,
    ZPE_coefficient: 0.5, R_earth: 6.371e6, V_T: 0.02585
};

// ── Formulas (73 total) ──
const FORMULAS = {
    ohmsLaw: { name: "Ohm's Law", formula: 'V = IR', calc: (V,I,R) => ({V:I*R, I:V/R, R:V/I}) },
    powerDC: { name: 'DC Power', formula: 'P = IV', calc: (I,V) => I*V },
    powerAC: { name: 'AC Power', formula: 'P = VIcos(phi)', calc: (V,I,phi) => V*I*Math.cos(phi) },
    seriesR: { name: 'Series R', formula: 'R = R1+R2+...', calc: (...r) => r.reduce((a,b) => a+b, 0) },
    parallelR: { name: 'Parallel R', formula: '1/R = 1/R1+1/R2+...', calc: (...r) => 1/r.reduce((a,b) => a+1/b, 0) },
    seriesC: { name: 'Series C', formula: '1/C = 1/C1+1/C2+...', calc: (...c) => 1/c.reduce((a,b) => a+1/b, 0) },
    parallelC: { name: 'Parallel C', formula: 'C = C1+C2+...', calc: (...c) => c.reduce((a,b) => a+b, 0) },
    seriesL: { name: 'Series L', formula: 'L = L1+L2+...', calc: (...l) => l.reduce((a,b) => a+b, 0) },
    parallelL: { name: 'Parallel L', formula: '1/L = 1/L1+1/L2+...', calc: (...l) => 1/l.reduce((a,b) => a+1/b, 0) },
    voltageDivider: { name: 'Voltage Divider', formula: 'Vout = Vin*R2/(R1+R2)', calc: (Vin,R1,R2) => Vin*R2/(R1+R2) },
    currentDivider: { name: 'Current Divider', formula: 'I1 = I*R2/(R1+R2)', calc: (I,R1,R2) => I*R2/(R1+R2) },
    capacitiveReactance: { name: 'Xc', formula: 'Xc = 1/(2*pi*f*C)', calc: (f,C) => 1/(2*Math.PI*f*C) },
    inductiveReactance: { name: 'Xl', formula: 'Xl = 2*pi*f*L', calc: (f,L) => 2*Math.PI*f*L },
    impedanceRLC: { name: 'Impedance', formula: 'Z = sqrt(R^2 + (Xl-Xc)^2)', calc: (R,Xl,Xc) => Math.sqrt(R*R + (Xl-Xc)*(Xl-Xc)) },
    impedanceComplex: { name: 'Complex Z', formula: 'Z = R + j(Xl-Xc)', calc: (R,Xl,Xc) => new Complex(R, Xl-Xc) },
    resonantFrequency: { name: 'Resonance', formula: 'f0 = 1/(2*pi*sqrt(LC))', calc: (L,C) => 1/(2*Math.PI*Math.sqrt(L*C)) },
    qualityFactor: { name: 'Q Factor', formula: 'Q = (1/R)*sqrt(L/C)', calc: (R,L,C) => (1/R)*Math.sqrt(L/C) },
    bandwidth: { name: 'Bandwidth', formula: 'BW = f0/Q', calc: (f0,Q) => f0/Q },
    skinDepth: { name: 'Skin Depth', formula: 'delta = sqrt(rho/(pi*f*mu))', calc: (rho,f,mu) => Math.sqrt(rho/(Math.PI*f*mu)) },
    turnsRatio: { name: 'Turns Ratio', formula: 'a = N2/N1', calc: (N1,N2) => N2/N1 },
    transformerVoltage: { name: 'Transformer V', formula: 'V2 = V1*N2/N1', calc: (V1,N1,N2) => V1*N2/N1 },
    transformerCurrent: { name: 'Transformer I', formula: 'I2 = I1*N1/N2', calc: (I1,N1,N2) => I1*N1/N2 },
    mutualInductance: { name: 'Mutual L', formula: 'M = k*sqrt(L1*L2)', calc: (k,L1,L2) => k*Math.sqrt(L1*L2) },
    couplingCoefficient: { name: 'Coupling k', formula: 'k = M/sqrt(L1*L2)', calc: (M,L1,L2) => M/Math.sqrt(L1*L2) },
    teslaCoilFrequency: { name: 'Tesla f0', formula: 'f = 1/(2*pi*sqrt(LC))', calc: (L,C) => 1/(2*Math.PI*Math.sqrt(L*C)) },
    teslaCoilVoltage: { name: 'Tesla Vout', formula: 'Vout = Vin*sqrt(Cp/Cs)', calc: (Vin,Cp,Cs) => Vin*Math.sqrt(Cp/Cs) },
    topLoadCapacitance: { name: 'Top Load C', formula: 'C = 4*pi*eps0*R', calc: (R) => 4*Math.PI*PHYS.eps_0*R },
    solenoidInductance: { name: 'Solenoid L', formula: 'L = mu0*N^2*A/l', calc: (N,A,l) => PHYS.mu_0*N*N*A/l },
    teslaPowerTransfer: { name: 'Tesla Power', formula: 'P = 0.5*C*V^2*f', calc: (C,V,f) => 0.5*C*V*V*f },
    magneticFieldSolenoid: { name: 'B Solenoid', formula: 'B = mu0*N*I/l', calc: (N,I,l) => PHYS.mu_0*N*I/l },
    magneticFlux: { name: 'Magnetic Phi', formula: 'Phi = B*A', calc: (B,A) => B*A },
    faradayEMF: { name: 'Faraday EMF', formula: 'emf = -N*dPhi/dt', calc: (N,dPhi,dt) => -N*dPhi/dt },
    inductorEnergy: { name: 'Inductor E', formula: 'E = 0.5*L*I^2', calc: (L,I) => 0.5*L*I*I },
    capacitorEnergy: { name: 'Capacitor E', formula: 'E = 0.5*C*V^2', calc: (C,V) => 0.5*C*V*V },
    wavelength: { name: 'Wavelength', formula: 'lambda = c/f', calc: (f) => PHYS.c/f },
    antennaLength: { name: 'Antenna lambda/4', formula: 'L = c/(4f)', calc: (f) => PHYS.c/(4*f) },
    radiationResistance: { name: 'Rrad', formula: 'Rrad = 80*pi^2*(L/lambda)^2', calc: (L,lam) => 80*Math.PI*Math.PI*(L/lam)*(L/lam) },
    casimirForce: { name: 'Casimir Force', formula: 'F = -pi^2*hbar*c*A/(240*d^4)', calc: (A,d) => -Math.PI*Math.PI*PHYS.hbar*PHYS.c*A/(240*Math.pow(d,4)) },
    casimirEnergy: { name: 'Casimir Energy', formula: 'E = -pi^2*hbar*c*A/(720*d^3)', calc: (A,d) => -Math.PI*Math.PI*PHYS.hbar*PHYS.c*A/(720*Math.pow(d,3)) },
    zeroPointEnergy: { name: 'ZPE', formula: 'E0 = 0.5*hbar*omega', calc: (omega) => 0.5*PHYS.hbar*omega },
    vacuumEnergyDensity: { name: 'rho_vac', formula: 'rho = hbar*omega^4/(2*pi^2*c^3)', calc: (omega) => PHYS.hbar*Math.pow(omega,4)/(2*Math.PI*Math.PI*Math.pow(PHYS.c,3)) },
    lambShift: { name: 'Lamb Shift', formula: 'dE = alpha^5*m*c^2/(4*pi)', calc: (m) => Math.pow(1/137.036,5)*m*PHYS.c*PHYS.c/(4*Math.PI) },
    schumannResonance: { name: 'Schumann', formula: 'fn = (c/2*pi*R)*sqrt(n(n+1))', calc: (n) => (PHYS.c/(2*Math.PI*PHYS.R_earth))*Math.sqrt(n*(n+1)) },
    hutchison_rfInterference: { name: 'Hutchison RF', formula: 'E_int = E1*E2*cos(dPhi)', calc: (E1,E2,dPhi) => E1*E2*Math.cos(dPhi) },
    scalarWavePotential: { name: 'Scalar Wave', formula: 'Phi_s = E*B/(mu0*eps0)', calc: (E,B) => E*B/(PHYS.mu_0*PHYS.eps_0) },
    magneticVectorPotential: { name: 'Vector A', formula: 'A = mu0*I/(4*pi*r)', calc: (I,r) => PHYS.mu_0*I/(4*Math.PI*r) },
    backEMF: { name: 'Back EMF', formula: 'V_bemf = -L*dI/dt', calc: (L,dI,dt) => -L*dI/dt },
    bediniPulseEnergy: { name: 'Bedini E', formula: 'E = 0.5*L*I^2*eta', calc: (L,I,eta) => 0.5*L*I*I*eta },
    bediniCOP: { name: 'Bedini COP', formula: 'COP = E_out/E_in', calc: (Eout,Ein) => Eout/Ein },
    donSmithResonantStep: { name: 'Don Smith', formula: 'V_step = V*(f2/f1)*(N2/N1)', calc: (V,f1,f2,N1,N2) => V*(f2/f1)*(N2/N1) },
    parametricGain: { name: 'Parametric', formula: 'G = (C1/C2)*(w2/w1)', calc: (C1,C2,w1,w2) => (C1/C2)*(w2/w1) },
    nernstPotential: { name: 'Nernst', formula: 'E = (RT/nF)*ln(Q)', calc: (T,n,Q) => (PHYS.k_B*T*PHYS.N_A/(n*96485))*Math.log(Q) },
    lenzCounterEMF: { name: "Lenz EMF", formula: 'emf = -dPhi/dt', calc: (dPhi,dt) => -dPhi/dt },
    poyntingVector: { name: 'Poynting', formula: 'S = E*B/mu0', calc: (E,B) => E*B/PHYS.mu_0 },
    tunnelingProbability: { name: 'Tunneling', formula: 'T = e^(-2*kappa*d)', calc: (kappa,d) => Math.exp(-2*kappa*d) },
    magneticReluctance: { name: 'Reluctance', formula: 'R = l/(mu*A)', calc: (l,mu,A) => l/(mu*A) },
    flybackDutyCycle: { name: 'Flyback D', formula: 'D = Vout/(Vin+Vout)', calc: (Vin,Vout) => Vout/(Vin+Vout) },
    flybackPeakCurrent: { name: 'Flyback Ipk', formula: 'Ip = 2P/(eta*Vin*D)', calc: (P,eta,Vin,D) => 2*P/(eta*Vin*D) },
    // v3.0 new
    shockleyDiode: { name: 'Shockley Diode', formula: 'I = Is*(e^(V/nVt) - 1)', calc: (Is,V,n) => Is*(Math.exp(V/(n*PHYS.V_T)) - 1) },
    diodeResistance: { name: 'Dynamic Rd', formula: 'rd = n*Vt/Id', calc: (n,Id) => n*PHYS.V_T/Id },
    bjtCollectorCurrent: { name: 'BJT Ic', formula: 'Ic = beta*Ib', calc: (beta,Ib) => beta*Ib },
    bjtEmitterCurrent: { name: 'BJT Ie', formula: 'Ie = Ic + Ib', calc: (Ic,Ib) => Ic + Ib },
    mosfetDrain: { name: 'MOSFET Id', formula: 'Id = K*(Vgs-Vth)^2', calc: (K,Vgs,Vth) => K*Math.pow(Math.max(0,Vgs-Vth),2) },
    rcTimeConstant: { name: 'RC tau', formula: 'tau = R*C', calc: (R,C) => R*C },
    rlTimeConstant: { name: 'RL tau', formula: 'tau = L/R', calc: (L,R) => L/R },
    rcCharging: { name: 'RC Charge', formula: 'V(t) = V0*(1 - e^(-t/tau))', calc: (V0,t,tau) => V0*(1 - Math.exp(-t/tau)) },
    rlCharging: { name: 'RL Charge', formula: 'I(t) = I0*(1 - e^(-t/tau))', calc: (I0,t,tau) => I0*(1 - Math.exp(-t/tau)) },
    lowPassGain: { name: 'LP Gain', formula: 'H(f) = 1/sqrt(1+(f/fc)^2)', calc: (f,fc) => 1/Math.sqrt(1 + Math.pow(f/fc, 2)) },
    highPassGain: { name: 'HP Gain', formula: 'H(f) = (f/fc)/sqrt(1+(f/fc)^2)', calc: (f,fc) => (f/fc)/Math.sqrt(1 + Math.pow(f/fc, 2)) },
    bandPassGain: { name: 'BP Gain', formula: 'H = Q*f0*f/(f0^2+jQ*f0*f-f^2)', calc: (f,f0,Q) => { const r = f/f0; return 1/Math.sqrt(Math.pow(1-r*r,2)+Math.pow(r/Q,2)); } },
    dbGain: { name: 'dB', formula: 'dB = 20*log10(|H|)', calc: (H) => 20*Math.log10(Math.abs(H)) },
    aharonovBohm: { name: 'Aharonov-Bohm', formula: 'dPhi = e*Phi/hbar', calc: (Phi) => PHYS.e*Phi/PHYS.hbar },
    torsionField: { name: 'Torsion Field', formula: 'T = G*omega*I/(c^2*r^2)', calc: (omega,I,r) => PHYS.G*omega*I/(PHYS.c*PHYS.c*r*r) },
    magneticMonopole: { name: 'Monopole', formula: 'g = hbar*c/(2e)', calc: () => PHYS.hbar*PHYS.c/(2*PHYS.e) },
};

// ── FFT Implementation ──
class FFT {
    static transform(signal) {
        const N = signal.length;
        if (N <= 1) return signal.map(v => new Complex(v, 0));
        let n = 1;
        while (n < N) n *= 2;
        const padded = new Array(n).fill(0);
        for (let i = 0; i < N; i++) padded[i] = signal[i];
        return FFT._fft(padded);
    }
    static _fft(x) {
        const N = x.length;
        if (N === 1) return [new Complex(typeof x[0] === 'number' ? x[0] : x[0].re, typeof x[0] === 'number' ? 0 : x[0].im)];
        const even = [], odd = [];
        for (let i = 0; i < N; i++) { if (i % 2 === 0) even.push(x[i]); else odd.push(x[i]); }
        const E = FFT._fft(even);
        const O = FFT._fft(odd);
        const result = new Array(N);
        for (let k = 0; k < N / 2; k++) {
            const angle = -2 * Math.PI * k / N;
            const twiddle = Complex.polar(1, angle).mul(O[k]);
            result[k] = E[k].add(twiddle);
            result[k + N / 2] = E[k].sub(twiddle);
        }
        return result;
    }
    static magnitude(fftResult) { return fftResult.map(c => c.mag()); }
    static powerSpectrum(fftResult, N) {
        const mag = FFT.magnitude(fftResult);
        return mag.slice(0, Math.floor(N / 2)).map(m => (2 * m / N) * (2 * m / N));
    }
    static dominantFrequency(signal, sampleRate) {
        const fftResult = FFT.transform(signal);
        const mag = FFT.magnitude(fftResult);
        const half = Math.floor(mag.length / 2);
        let maxIdx = 1, maxVal = 0;
        for (let i = 1; i < half; i++) { if (mag[i] > maxVal) { maxVal = mag[i]; maxIdx = i; } }
        return maxIdx * sampleRate / mag.length;
    }
}

// ── Bode Plot Generator ──
class BodePlot {
    constructor() { this.data = { freq: [], magDb: [], phaseDeg: [] }; }

    sweep(components, fStart, fEnd, numPoints) {
        fStart = fStart || 1; fEnd = fEnd || 1e6; numPoints = numPoints || 200;
        this.data = { freq: [], magDb: [], phaseDeg: [] };
        let totalR = 0, totalC = 0, totalL = 0;
        for (const c of components) {
            if (['resistor','potentiometer','thermistor'].includes(c.type)) totalR += c.value;
            if (['capacitor','high_voltage_cap','leyden_jar'].includes(c.type)) totalC += c.value;
            if (['inductor','bifilar_coil','caduceus_coil','toroidal_inductor','tesla_primary','tesla_secondary'].includes(c.type)) totalL += c.value;
        }
        if (totalR === 0) totalR = 1;
        const logStart = Math.log10(Math.max(fStart, 0.1));
        const logEnd = Math.log10(fEnd);
        for (let i = 0; i < numPoints; i++) {
            const f = Math.pow(10, logStart + (logEnd - logStart) * i / (numPoints - 1));
            const omega = 2 * Math.PI * f;
            var Xc = totalC > 0 ? 1 / (omega * totalC) : 0;
            var Xl = totalL > 0 ? omega * totalL : 0;
            var H;
            if (totalC > 0 && totalL === 0) {
                var Zc = new Complex(0, -Xc);
                H = Zc.div(new Complex(totalR, -Xc));
            } else if (totalL > 0 && totalC === 0) {
                var ZL = new Complex(0, Xl);
                H = ZL.div(new Complex(totalR, Xl));
            } else if (totalL > 0 && totalC > 0) {
                var Z = new Complex(totalR, Xl - Xc);
                H = Complex.fromReal(totalR).div(Z);
            } else {
                H = Complex.fromReal(1);
            }
            this.data.freq.push(f);
            this.data.magDb.push(20 * Math.log10(Math.max(H.mag(), 1e-10)));
            this.data.phaseDeg.push(H.phase() * 180 / Math.PI);
        }
        return this.data;
    }

    render(canvas) {
        if (!canvas || this.data.freq.length === 0) return;
        var ctx = canvas.getContext('2d');
        var w = canvas.width, h = canvas.height;
        var pad = { t: 30, b: 40, l: 55, r: 55 };
        var pw = w - pad.l - pad.r, ph = h - pad.t - pad.b;
        ctx.fillStyle = '#0a0a14';
        ctx.fillRect(0, 0, w, h);
        ctx.strokeStyle = 'rgba(0,212,255,0.1)'; ctx.lineWidth = 0.5;
        var logMin = Math.log10(this.data.freq[0]);
        var logMax = Math.log10(this.data.freq[this.data.freq.length - 1]);
        ctx.fillStyle = 'rgba(255,255,255,0.4)'; ctx.font = '9px JetBrains Mono, monospace'; ctx.textAlign = 'center';
        for (var d = Math.ceil(logMin); d <= Math.floor(logMax); d++) {
            var x = pad.l + pw * (d - logMin) / (logMax - logMin);
            ctx.beginPath(); ctx.moveTo(x, pad.t); ctx.lineTo(x, h - pad.b); ctx.stroke();
            var fval = Math.pow(10, d);
            ctx.fillText(fval >= 1e6 ? (fval/1e6)+'M' : fval >= 1e3 ? (fval/1e3)+'k' : fval+'', x, h - pad.b + 14);
        }
        ctx.fillText('Frequency (Hz)', w / 2, h - 5);
        var magMin = Math.min.apply(null, this.data.magDb);
        var magMax = Math.max.apply(null, this.data.magDb);
        var dbMin = Math.floor(Math.min(magMin, -3) / 10) * 10 - 10;
        var dbMax = Math.ceil(Math.max(magMax, 3) / 10) * 10 + 10;
        ctx.textAlign = 'right';
        for (var db = dbMin; db <= dbMax; db += 10) {
            var y = pad.t + ph * (1 - (db - dbMin) / (dbMax - dbMin));
            ctx.beginPath(); ctx.moveTo(pad.l, y); ctx.lineTo(w - pad.r, y); ctx.stroke();
            ctx.fillStyle = 'rgba(0,212,255,0.5)';
            ctx.fillText(db + 'dB', pad.l - 5, y + 3);
        }
        ctx.strokeStyle = '#00D4FF'; ctx.lineWidth = 2;
        ctx.beginPath();
        for (var i = 0; i < this.data.freq.length; i++) {
            var x2 = pad.l + pw * (Math.log10(this.data.freq[i]) - logMin) / (logMax - logMin);
            var y2 = pad.t + ph * (1 - (this.data.magDb[i] - dbMin) / (dbMax - dbMin));
            if (i === 0) ctx.moveTo(x2, y2); else ctx.lineTo(x2, y2);
        }
        ctx.stroke();
        ctx.strokeStyle = '#FF8800'; ctx.lineWidth = 1.5;
        ctx.setLineDash([4, 3]);
        ctx.beginPath();
        for (var j = 0; j < this.data.freq.length; j++) {
            var x3 = pad.l + pw * (Math.log10(this.data.freq[j]) - logMin) / (logMax - logMin);
            var y3 = pad.t + ph * (0.5 - this.data.phaseDeg[j] / 360);
            if (j === 0) ctx.moveTo(x3, y3); else ctx.lineTo(x3, y3);
        }
        ctx.stroke(); ctx.setLineDash([]);
        ctx.fillStyle = 'rgba(255,136,0,0.5)'; ctx.textAlign = 'left';
        ctx.fillText('90deg', w - pad.r + 5, pad.t + 3);
        ctx.fillText('0deg', w - pad.r + 5, pad.t + ph / 2 + 3);
        ctx.fillText('-90deg', w - pad.r + 5, h - pad.b + 3);
        ctx.font = '10px sans-serif';
        ctx.fillStyle = '#00D4FF';
        ctx.fillText('Magnitude (dB)', pad.l + 5, pad.t - 10);
        ctx.fillStyle = '#FF8800';
        ctx.fillText('Phase (deg)', pad.l + 140, pad.t - 10);
        var y3dB = pad.t + ph * (1 - (-3 - dbMin) / (dbMax - dbMin));
        if (y3dB > pad.t && y3dB < h - pad.b) {
            ctx.strokeStyle = 'rgba(255,51,102,0.4)'; ctx.lineWidth = 1;
            ctx.setLineDash([6, 4]);
            ctx.beginPath(); ctx.moveTo(pad.l, y3dB); ctx.lineTo(w - pad.r, y3dB); ctx.stroke();
            ctx.setLineDash([]);
            ctx.fillStyle = 'rgba(255,51,102,0.6)'; ctx.textAlign = 'right';
            ctx.fillText('-3dB', pad.l - 5, y3dB + 3);
        }
    }
}

// ── Transient Analysis Engine ──
class TransientEngine {
    constructor() {
        this.capacitorVoltages = new Map();
        this.inductorCurrents = new Map();
        this.diodeStates = new Map();
    }
    reset() {
        this.capacitorVoltages.clear();
        this.inductorCurrents.clear();
        this.diodeStates.clear();
    }
    step(components, dt, totalVoltage, totalCurrent) {
        var transientData = new Map();
        for (var ci = 0; ci < components.length; ci++) {
            var c = components[ci];
            if (['capacitor','high_voltage_cap','leyden_jar'].includes(c.type) && c.value > 0) {
                var prevV = this.capacitorVoltages.get(c.id) || 0;
                var dV = totalCurrent * dt / c.value;
                var newV = prevV + dV;
                this.capacitorVoltages.set(c.id, newV);
                transientData.set(c.id, { voltage: newV, current: c.value * dV / dt, energy: 0.5 * c.value * newV * newV, charging: dV > 0 });
            }
            if (['inductor','bifilar_coil','caduceus_coil','toroidal_inductor','tesla_primary','tesla_secondary'].includes(c.type) && c.value > 0) {
                var prevI = this.inductorCurrents.get(c.id) || 0;
                var dI = totalVoltage * dt / c.value;
                var newI = prevI + dI;
                this.inductorCurrents.set(c.id, newI);
                transientData.set(c.id, { current: newI, voltage: c.value * dI / dt, energy: 0.5 * c.value * newI * newI, energizing: Math.abs(newI) > Math.abs(prevI) });
            }
            if (['diode','led','zener_diode'].includes(c.type)) {
                var Is = 1e-12;
                var n = c.type === 'led' ? 2 : 1;
                var Vd = c.value || 0.7;
                var Id = totalVoltage > 0 ? Is * (Math.exp(Math.min(totalVoltage / (n * PHYS.V_T), 40)) - 1) : 0;
                var rd = n * PHYS.V_T / Math.max(Id, 1e-12);
                this.diodeStates.set(c.id, { forward: totalVoltage > Vd * 0.5, current: Id, resistance: rd });
                transientData.set(c.id, { current: Id, voltage: Vd, dynamicR: rd, forward: totalVoltage > 0 });
            }
        }
        return transientData;
    }
}

// ── Node Graph Analysis ──
class CircuitGraph {
    constructor() { this.nodes = new Map(); this.edges = []; }
    build(components, wires) {
        this.nodes.clear(); this.edges = [];
        var terminalMap = new Map();
        var nodeIdx = 0;
        this.nodes.set(0, { id: 0, voltage: 0, components: [], isGround: true });
        for (var gi = 0; gi < components.length; gi++) {
            if (components[gi].type === 'ground') {
                terminalMap.set(components[gi].id + ':0', 0);
            }
        }
        for (var ci = 0; ci < components.length; ci++) {
            var comp = components[ci];
            if (comp.type === 'ground') continue;
            var numT = (comp.type.includes('transistor') || comp.type === 'op_amp' || comp.type === 'potentiometer') ? 3 : 2;
            for (var t = 0; t < numT; t++) {
                var key = comp.id + ':' + t;
                if (!terminalMap.has(key)) {
                    nodeIdx++;
                    terminalMap.set(key, nodeIdx);
                    this.nodes.set(nodeIdx, { id: nodeIdx, voltage: 0, components: [{ compId: comp.id, terminal: t }], isGround: false });
                }
            }
        }
        for (var wi = 0; wi < wires.length; wi++) {
            var wire = wires[wi];
            if (wire.comp1 != null && wire.comp2 != null) {
                var key1 = wire.comp1 + ':' + (wire.term1 || 0);
                var key2 = wire.comp2 + ':' + (wire.term2 || 0);
                var n1 = terminalMap.get(key1);
                var n2 = terminalMap.get(key2);
                if (n1 != null && n2 != null && n1 !== n2) {
                    var keep = (n1 === 0 || n2 !== 0) ? n1 : n2;
                    var remove = keep === n1 ? n2 : n1;
                    for (var entry of terminalMap) { if (entry[1] === remove) terminalMap.set(entry[0], keep); }
                    var keepNode = this.nodes.get(keep);
                    var removeNode = this.nodes.get(remove);
                    if (keepNode && removeNode) {
                        keepNode.components = keepNode.components.concat(removeNode.components);
                        keepNode.isGround = keepNode.isGround || removeNode.isGround;
                    }
                    this.nodes.delete(remove);
                }
            }
        }
        for (var ei = 0; ei < components.length; ei++) {
            var eComp = components[ei];
            if (eComp.type === 'ground' || eComp.type === 'wire') continue;
            var eKey0 = eComp.id + ':0', eKey1 = eComp.id + ':1';
            var eN0 = terminalMap.get(eKey0), eN1 = terminalMap.get(eKey1);
            if (eN0 != null && eN1 != null) {
                this.edges.push({ from: eN0, to: eN1, component: eComp });
            }
        }
        return { nodes: this.nodes, edges: this.edges, terminalMap: terminalMap };
    }
    detectParallelResistors(components, wires) {
        var gd = this.build(components, wires);
        var parallel = [];
        var rEdges = gd.edges.filter(function(e) { return ['resistor','potentiometer','thermistor'].includes(e.component.type); });
        for (var i = 0; i < rEdges.length; i++) {
            for (var j = i + 1; j < rEdges.length; j++) {
                if ((rEdges[i].from === rEdges[j].from && rEdges[i].to === rEdges[j].to) ||
                    (rEdges[i].from === rEdges[j].to && rEdges[i].to === rEdges[j].from)) {
                    parallel.push([rEdges[i].component, rEdges[j].component]);
                }
            }
        }
        return parallel;
    }
    getNodeCount() { return this.nodes.size; }
    getEdgeCount() { return this.edges.length; }
}

// ── Simulation Engine v3.0 ──
class SimulationEngine {
    constructor() {
        this.time = 0; this.dt = 1e-6; this.frequency = 60; this.running = false;
        this.waveforms = {}; this.maxSamples = 2000; this.analysisMode = 'dc';
        this.graph = new CircuitGraph(); this.transient = new TransientEngine();
        this.bode = new BodePlot(); this.fftData = null; this.spectrumData = null;
    }

    solve(components, wires) {
        var result = {
            totalVoltage: 0, totalCurrent: 0, totalPower: 0, totalResistance: 0,
            impedance: null, resonantFreq: null, qualityFactor: null,
            frequency: null, phaseAngle: null,
            componentData: new Map(), formulas: [], warnings: [],
            nodeCount: 0, edgeCount: 0, parallelGroups: [],
            bodeData: null, fftData: null, transientData: null
        };
        if (!components || components.length === 0) return result;

        var graphData = this.graph.build(components, wires);
        result.nodeCount = this.graph.getNodeCount();
        result.edgeCount = this.graph.getEdgeCount();
        result.parallelGroups = this.graph.detectParallelResistors(components, wires);

        var sources = [], resistors = [], capacitors = [], inductors = [], loads = [];
        var transformers = [], teslaCoils = [], meters = [], diodes = [], transistors = [];
        var zpeSources = [];

        for (var ci = 0; ci < components.length; ci++) {
            var c = components[ci];
            if (c.type === 'wire' || c.type === 'ground') continue;
            if (['battery'].includes(c.type)) sources.push(c);
            else if (['ac_source','signal_generator','rf_generator'].includes(c.type)) { sources.push(c); this.analysisMode = 'ac'; }
            else if (['resistor','potentiometer','thermistor'].includes(c.type)) resistors.push(c);
            else if (['capacitor','high_voltage_cap','leyden_jar'].includes(c.type)) capacitors.push(c);
            else if (['inductor','bifilar_coil','caduceus_coil','toroidal_inductor','tesla_primary','tesla_secondary'].includes(c.type)) inductors.push(c);
            else if (['led','bulb','motor','buzzer','speaker'].includes(c.type)) loads.push(c);
            else if (['transformer'].includes(c.type)) transformers.push(c);
            else if (['top_load','spark_gap'].includes(c.type)) teslaCoils.push(c);
            else if (['voltmeter','ammeter','wattmeter','oscilloscope','em_field_probe','spectrum_analyzer'].includes(c.type)) meters.push(c);
            else if (['diode','zener_diode'].includes(c.type)) diodes.push(c);
            else if (['npn_transistor','pnp_transistor','mosfet_n'].includes(c.type)) transistors.push(c);
            else if (['casimir_plates','crystal_cell','schumann_antenna'].includes(c.type)) zpeSources.push(c);
        }

        var totalVoltage = 0, hasAC = false, acFreq = 60;
        for (var si = 0; si < sources.length; si++) {
            var s = sources[si];
            if (s.type === 'battery') {
                totalVoltage += s.value;
                result.formulas.push({ name: 'DC Source', value: s.value + ' V' });
            } else {
                hasAC = true;
                acFreq = s.frequency || 60;
                var omega = 2 * Math.PI * acFreq;
                totalVoltage += s.value * Math.sin(omega * this.time);
                this.frequency = acFreq;
                result.frequency = acFreq;
                result.formulas.push({ name: 'AC Source', value: s.value.toFixed(1) + 'V @ ' + acFreq + 'Hz' });
            }
        }

        for (var swi = 0; swi < components.length; swi++) {
            var sw = components[swi];
            if (['switch','push_button','reed_switch'].includes(sw.type) && !sw.closed) {
                result.warnings.push('Open switch detected');
                result.totalVoltage = totalVoltage;
                return result;
            }
            if (sw.type === 'fuse' && sw.blown) {
                result.warnings.push('Blown fuse');
                result.totalVoltage = totalVoltage;
                return result;
            }
        }

        var totalResistance = 0;
        for (var ri = 0; ri < resistors.length; ri++) totalResistance += resistors[ri].value;
        for (var li = 0; li < loads.length; li++) {
            var ld = loads[li];
            if (ld.type === 'led') totalResistance += 20;
            else if (ld.type === 'bulb') totalResistance += ld.value > 0 ? Math.pow(ld.value, 2) / ld.value : 100;
            else if (ld.type === 'motor') totalResistance += 50;
            else if (ld.type === 'buzzer' || ld.type === 'speaker') totalResistance += 32;
            else totalResistance += 100;
        }
        if (totalResistance < 0.01) totalResistance = 0.01;

        if (result.parallelGroups.length > 0) {
            for (var pg = 0; pg < result.parallelGroups.length; pg++) {
                var grp = result.parallelGroups[pg];
                var r1 = grp[0].value, r2 = grp[1].value;
                var rP = (r1 * r2) / (r1 + r2);
                totalResistance -= (r1 + r2);
                totalResistance += rP;
                result.formulas.push({ name: 'Parallel R', value: rP.toFixed(1) + ' Ohm' });
            }
            if (totalResistance < 0.01) totalResistance = 0.01;
        }

        var totalC = 0, totalL = 0;
        for (var cci = 0; cci < capacitors.length; cci++) totalC += capacitors[cci].value;
        for (var lli = 0; lli < inductors.length; lli++) totalL += inductors[lli].value;
        var impedanceMag = totalResistance, phaseAngle = 0;

        if (hasAC && (totalC > 0 || totalL > 0)) {
            var omega2 = 2 * Math.PI * acFreq;
            var Xc = totalC > 0 ? 1 / (omega2 * totalC) : 0;
            var Xl = totalL > 0 ? omega2 * totalL : 0;
            var Z = new Complex(totalResistance, Xl - Xc);
            impedanceMag = Z.mag();
            phaseAngle = Z.phase() * 180 / Math.PI;
            result.impedance = impedanceMag;
            result.phaseAngle = phaseAngle;
            result.formulas.push(
                { name: 'Xc', value: Xc > 0 ? Xc.toFixed(1) + ' Ohm' : 'N/A' },
                { name: 'Xl', value: Xl > 0 ? Xl.toFixed(1) + ' Ohm' : 'N/A' },
                { name: '|Z|', value: impedanceMag.toFixed(2) + ' Ohm' },
                { name: 'Phase', value: phaseAngle.toFixed(1) + ' deg' }
            );
            if (totalC > 0) result.formulas.push({ name: 'RC tau', value: (totalResistance * totalC * 1e3).toFixed(3) + ' ms' });
            if (totalL > 0) result.formulas.push({ name: 'RL tau', value: (totalL / totalResistance * 1e6).toFixed(3) + ' us' });
        }

        if (totalC > 0 && totalL > 0) {
            var f0 = FORMULAS.resonantFrequency.calc(totalL, totalC);
            var Q = FORMULAS.qualityFactor.calc(totalResistance, totalL, totalC);
            var bw = FORMULAS.bandwidth.calc(f0, Q);
            result.resonantFreq = f0;
            result.qualityFactor = Q;
            result.formulas.push(
                { name: 'f0 Resonance', value: f0.toFixed(1) + ' Hz' },
                { name: 'Q Factor', value: Q.toFixed(2) },
                { name: 'Bandwidth', value: bw.toFixed(1) + ' Hz' }
            );
        }

        for (var xfi = 0; xfi < transformers.length; xfi++) {
            var xf = transformers[xfi];
            var t1 = 100, t2 = xf.secondary_turns || 1000;
            var ratio = t2 / t1;
            result.formulas.push(
                { name: 'Turns Ratio', value: t1 + ':' + t2 },
                { name: 'V Secondary', value: (totalVoltage * ratio).toFixed(1) + ' V' }
            );
            totalVoltage *= ratio;
        }

        if (teslaCoils.length > 0 || inductors.some(function(l) { return ['tesla_primary','tesla_secondary'].includes(l.type); })) {
            var tPrimary = inductors.find(function(l) { return l.type === 'tesla_primary'; });
            var tSecondary = inductors.find(function(l) { return l.type === 'tesla_secondary'; });
            if (tPrimary && tSecondary && totalC > 0) {
                var teslaF = FORMULAS.teslaCoilFrequency.calc(tPrimary.value, totalC);
                var topLoad = teslaCoils.find(function(t) { return t.type === 'top_load'; });
                var Cs = topLoad ? FORMULAS.topLoadCapacitance.calc(topLoad.value) : 1e-12;
                var Vout = FORMULAS.teslaCoilVoltage.calc(Math.abs(totalVoltage), totalC, Cs);
                result.formulas.push(
                    { name: 'Tesla f0', value: teslaF.toFixed(0) + ' Hz' },
                    { name: 'Tesla Vout', value: (Vout/1000).toFixed(1) + ' kV' }
                );
                totalVoltage = Vout;
            }
        }

        var totalCurrent = Math.abs(totalVoltage) / impedanceMag;
        var totalPower = totalCurrent * Math.abs(totalVoltage);
        result.totalVoltage = totalVoltage;
        result.totalCurrent = totalCurrent;
        result.totalPower = totalPower;
        result.totalResistance = totalResistance;

        var transientData = this.transient.step(components, this.dt, totalVoltage, totalCurrent);
        result.transientData = transientData;

        for (var di = 0; di < diodes.length; di++) {
            var dComp = diodes[di];
            var dIs = 1e-12;
            var dN = dComp.type === 'led' ? 2 : 1;
            var dId = dIs * (Math.exp(Math.min(Math.abs(totalVoltage) / (dN * PHYS.V_T), 40)) - 1);
            var dRd = dN * PHYS.V_T / Math.max(dId, 1e-12);
            result.formulas.push({ name: dComp.type + ' Id', value: (dId * 1000).toFixed(3) + ' mA' });
            result.componentData.set(dComp.id, { current: dId, voltage: dComp.value, power: dId * dComp.value, on: totalVoltage > dComp.value * 0.5, dynamicR: dRd, forward: totalVoltage > 0 });
        }

        for (var tri = 0; tri < transistors.length; tri++) {
            var tr = transistors[tri];
            if (tr.type === 'npn_transistor' || tr.type === 'pnp_transistor') {
                var beta = tr.value || 100;
                var Ib = totalCurrent / (1 + beta);
                var Ic = beta * Ib;
                var Ie = Ic + Ib;
                var Vce = Math.max(0.2, Math.abs(totalVoltage) - totalCurrent * totalResistance);
                result.componentData.set(tr.id, { current: Ic, voltage: Vce, power: Ic * Vce, Ib: Ib, Ic: Ic, Ie: Ie, beta: beta, mode: Vce > 0.2 ? 'Active' : 'Saturation' });
                result.formulas.push({ name: 'BJT Ic', value: (Ic * 1000).toFixed(2) + ' mA' }, { name: 'BJT Mode', value: Vce > 0.2 ? 'Active' : 'Saturation' });
            } else if (tr.type === 'mosfet_n') {
                var mK = 0.5, mVth = 2;
                var mVgs = Math.abs(totalVoltage) * 0.3;
                var mId = mK * Math.pow(Math.max(0, mVgs - mVth), 2);
                result.componentData.set(tr.id, { current: mId, voltage: mVgs, power: mId * mVgs, Vgs: mVgs, Vth: mVth, mode: mVgs > mVth ? 'On' : 'Off' });
                result.formulas.push({ name: 'MOSFET Id', value: (mId * 1000).toFixed(2) + ' mA' });
            }
        }

        for (var pi = 0; pi < components.length; pi++) {
            var comp = components[pi];
            if (result.componentData.has(comp.id)) continue;
            var data = { current: totalCurrent, voltage: 0, power: 0, on: false };
            if (sources.indexOf(comp) !== -1) { data.voltage = comp.value; data.power = comp.value * totalCurrent; data.on = true; }
            else if (resistors.indexOf(comp) !== -1) { data.voltage = totalCurrent * comp.value; data.power = totalCurrent * totalCurrent * comp.value; }
            else if (capacitors.indexOf(comp) !== -1) { var td = transientData.get(comp.id); if (td) Object.assign(data, td); }
            else if (inductors.indexOf(comp) !== -1) { var td2 = transientData.get(comp.id); if (td2) Object.assign(data, td2); }
            else if (comp.type === 'led' || comp.type === 'bulb') { data.on = totalCurrent > 0.001; data.voltage = comp.type === 'led' ? comp.value : totalCurrent * 100; data.power = data.voltage * totalCurrent; }
            else if (comp.type === 'motor') { data.on = totalCurrent > 0.01; data.voltage = totalCurrent * 50; data.power = data.voltage * totalCurrent; }
            else if (comp.type === 'fuse') { data.current = totalCurrent; if (totalCurrent > comp.value) { comp.blown = true; data.blown = true; result.warnings.push('Fuse blown!'); } }
            else if (['switch','push_button','reed_switch'].includes(comp.type)) { data.on = comp.closed; data.current = comp.closed ? totalCurrent : 0; }
            else if (comp.type === 'voltmeter') { data.voltage = Math.abs(totalVoltage); data.reading = Math.abs(totalVoltage); }
            else if (comp.type === 'ammeter') { data.current = totalCurrent; data.reading = totalCurrent; }
            else if (comp.type === 'wattmeter') { data.power = totalPower; data.reading = totalPower; }
            result.componentData.set(comp.id, data);
        }

        for (var zi = 0; zi < zpeSources.length; zi++) {
            var zpe = zpeSources[zi];
            if (zpe.type === 'casimir_plates') {
                var gap = zpe.value || 1e-7;
                var area = zpe.area || 0.01;
                var cF = FORMULAS.casimirForce.calc(area, gap);
                var cE = FORMULAS.casimirEnergy.calc(area, gap);
                result.formulas.push({ name: 'Casimir Force', value: Math.abs(cF).toExponential(3) + ' N' }, { name: 'Casimir Energy', value: Math.abs(cE).toExponential(3) + ' J' });
                result.componentData.set(zpe.id, { force: cF, energy: cE, voltage: Math.abs(cE) * 1e12, current: 0 });
            } else if (zpe.type === 'schumann_antenna') {
                var sn = zpe.value || 1;
                var sf = FORMULAS.schumannResonance.calc(sn);
                result.formulas.push({ name: 'Schumann f' + sn, value: sf.toFixed(2) + ' Hz' });
                result.componentData.set(zpe.id, { frequency: sf, voltage: 0.001, current: 1e-9 });
            } else if (zpe.type === 'crystal_cell') {
                var cV = zpe.value || 0.4;
                var cI = cV / 100000;
                result.formulas.push({ name: 'Crystal Cell', value: cV + ' V' });
                result.componentData.set(zpe.id, { voltage: cV, current: cI, power: cV * cI, on: true });
            }
        }

        if (!this.waveforms.main) this.waveforms.main = [];
        this.waveforms.main.push({ t: this.time, v: totalVoltage, i: totalCurrent * 1000 });
        if (this.waveforms.main.length > this.maxSamples) this.waveforms.main.shift();

        if (this.waveforms.main.length >= 64 && hasAC) {
            var voltages = this.waveforms.main.slice(-256).map(function(w) { return w.v; });
            var fftResult = FFT.transform(voltages);
            var spectrum = FFT.powerSpectrum(fftResult, voltages.length);
            this.fftData = spectrum;
            this.spectrumData = { bins: spectrum, sampleRate: 1 / this.dt, dominant: FFT.dominantFrequency(voltages, 1 / this.dt) };
            result.fftData = this.spectrumData;
        }

        if (hasAC && (totalC > 0 || totalL > 0)) {
            result.bodeData = this.bode.sweep(components);
        }

        if (resistors.length > 0) result.formulas.push({ name: "Ohm's Law", value: 'V=' + Math.abs(totalVoltage).toFixed(2) + ' I=' + (totalCurrent*1000).toFixed(2) + 'mA R=' + totalResistance.toFixed(1) + 'Ohm' });
        if (totalPower > 0) result.formulas.push({ name: 'Power', value: (totalPower*1000).toFixed(2) + ' mW' });

        return result;
    }

    step() { this.time += this.dt; }
    reset() { this.time = 0; this.waveforms = {}; this.transient.reset(); this.fftData = null; this.spectrumData = null; }
}

// ── Oscilloscope v3.0 ──
class Oscilloscope {
    constructor(canvasId) {
        this.canvasId = canvasId; this.timeDiv = 0.01; this.voltDiv = 5;
        this.paused = false; this.trigger = 'auto'; this.triggerLevel = 0;
        this.cursorA = null; this.cursorB = null;
        this.showFFT = false; this.xyMode = false;
        this.persistence = false; this.prevTraces = [];
    }
    render(waveforms) {
        var canvas = document.getElementById(this.canvasId);
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var w = canvas.width, h = canvas.height;
        ctx.fillStyle = '#0a0a14'; ctx.fillRect(0, 0, w, h);

        if (this.persistence && this.prevTraces.length > 0) {
            for (var t = 0; t < this.prevTraces.length; t++) {
                var alpha = 0.05 + 0.05 * t / this.prevTraces.length;
                ctx.strokeStyle = 'rgba(0,212,255,' + alpha + ')';
                ctx.lineWidth = 1;
                var trace = this.prevTraces[t];
                if (trace && trace.length > 1) {
                    ctx.beginPath();
                    for (var ti = 0; ti < trace.length; ti++) { if (ti === 0) ctx.moveTo(trace[ti].x, trace[ti].y); else ctx.lineTo(trace[ti].x, trace[ti].y); }
                    ctx.stroke();
                }
            }
        }

        ctx.strokeStyle = 'rgba(0,212,255,0.12)'; ctx.lineWidth = 0.5;
        for (var gi = 1; gi < 10; gi++) { var gx = w * gi / 10; ctx.beginPath(); ctx.moveTo(gx, 0); ctx.lineTo(gx, h); ctx.stroke(); }
        for (var gj = 1; gj < 8; gj++) { var gy = h * gj / 8; ctx.beginPath(); ctx.moveTo(0, gy); ctx.lineTo(w, gy); ctx.stroke(); }
        ctx.strokeStyle = 'rgba(0,212,255,0.25)'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(w/2, 0); ctx.lineTo(w/2, h); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(0, h/2); ctx.lineTo(w, h/2); ctx.stroke();

        if (this.trigger !== 'auto') {
            var ty = h / 2 - (this.triggerLevel / this.voltDiv) * (h / 8);
            ctx.strokeStyle = 'rgba(255,136,0,0.4)'; ctx.setLineDash([4, 4]);
            ctx.beginPath(); ctx.moveTo(0, ty); ctx.lineTo(w, ty); ctx.stroke();
            ctx.setLineDash([]);
        }

        if (!waveforms || !waveforms.main || waveforms.main.length < 2) {
            ctx.fillStyle = 'rgba(255,255,255,0.15)'; ctx.font = '14px sans-serif'; ctx.textAlign = 'center';
            ctx.fillText('No signal. Press Space to simulate', w/2, h/2);
            return;
        }
        if (this.paused) return;

        var data = waveforms.main;
        var points = Math.min(data.length, 500);
        var start = data.length - points;

        if (this.showFFT && data.length >= 64) {
            var voltages = data.slice(-256).map(function(d) { return d.v; });
            var fftResult = FFT.transform(voltages);
            var spectrum = FFT.magnitude(fftResult).slice(0, Math.floor(voltages.length / 2));
            var maxMag = Math.max.apply(null, spectrum.concat([0.001]));
            ctx.strokeStyle = '#FF00FF'; ctx.fillStyle = 'rgba(255,0,255,0.15)'; ctx.lineWidth = 1.5;
            ctx.beginPath(); ctx.moveTo(0, h);
            for (var fi = 0; fi < spectrum.length; fi++) { ctx.lineTo((fi / spectrum.length) * w, h - (spectrum[fi] / maxMag) * (h * 0.85)); }
            ctx.lineTo(w, h); ctx.fill();
            ctx.beginPath();
            for (var fj = 0; fj < spectrum.length; fj++) { var fx = (fj / spectrum.length) * w; var fy = h - (spectrum[fj] / maxMag) * (h * 0.85); if (fj === 0) ctx.moveTo(fx, fy); else ctx.lineTo(fx, fy); }
            ctx.stroke();
            ctx.fillStyle = '#FF00FF'; ctx.font = '10px JetBrains Mono, monospace'; ctx.textAlign = 'left'; ctx.fillText('FFT Spectrum', 8, 15);
            return;
        }

        if (this.xyMode && data.length > 10) {
            ctx.strokeStyle = '#00FF88'; ctx.lineWidth = 1.5;
            ctx.beginPath();
            for (var xi = start; xi < data.length; xi++) {
                var xx = w / 2 + (data[xi].v / this.voltDiv) * (w / 10);
                var xy = h / 2 - (data[xi].i / (this.voltDiv * 10)) * (h / 8);
                if (xi === start) ctx.moveTo(xx, xy); else ctx.lineTo(xx, xy);
            }
            ctx.stroke();
            ctx.fillStyle = '#00FF88'; ctx.font = '10px JetBrains Mono, monospace'; ctx.fillText('XY Mode: V vs I', 8, 15);
            return;
        }

        var currentTrace = [];
        ctx.strokeStyle = '#00D4FF'; ctx.lineWidth = 2;
        ctx.beginPath();
        for (var vi = start; vi < data.length; vi++) {
            var vx = ((vi - start) / points) * w;
            var vy = h / 2 - (data[vi].v / this.voltDiv) * (h / 8);
            if (vi === start) ctx.moveTo(vx, vy); else ctx.lineTo(vx, vy);
            currentTrace.push({ x: vx, y: vy });
        }
        ctx.stroke();

        ctx.strokeStyle = '#00FF88'; ctx.lineWidth = 1.5;
        ctx.beginPath();
        for (var ii = start; ii < data.length; ii++) {
            var ix = ((ii - start) / points) * w;
            var iy = h / 2 - (data[ii].i / (this.voltDiv * 10)) * (h / 8);
            if (ii === start) ctx.moveTo(ix, iy); else ctx.lineTo(ix, iy);
        }
        ctx.stroke();

        if (this.persistence) { this.prevTraces.push(currentTrace.slice()); if (this.prevTraces.length > 20) this.prevTraces.shift(); }

        if (this.cursorA != null) {
            var cax = (this.cursorA / points) * w;
            ctx.strokeStyle = 'rgba(255,215,0,0.7)'; ctx.lineWidth = 1; ctx.setLineDash([3, 3]);
            ctx.beginPath(); ctx.moveTo(cax, 0); ctx.lineTo(cax, h); ctx.stroke(); ctx.setLineDash([]);
            ctx.fillStyle = '#FFD700'; ctx.font = '9px JetBrains Mono, monospace'; ctx.textAlign = 'left';
            var aIdx = Math.min(start + this.cursorA, data.length - 1);
            ctx.fillText('A: ' + data[aIdx].v.toFixed(2) + 'V', cax + 4, 14);
        }
        if (this.cursorB != null) {
            var cbx = (this.cursorB / points) * w;
            ctx.strokeStyle = 'rgba(255,136,0,0.7)'; ctx.lineWidth = 1; ctx.setLineDash([3, 3]);
            ctx.beginPath(); ctx.moveTo(cbx, 0); ctx.lineTo(cbx, h); ctx.stroke(); ctx.setLineDash([]);
            ctx.fillStyle = '#FF8800'; ctx.font = '9px JetBrains Mono, monospace';
            var bIdx = Math.min(start + this.cursorB, data.length - 1);
            ctx.fillText('B: ' + data[bIdx].v.toFixed(2) + 'V', cbx + 4, 26);
        }
        if (this.cursorA != null && this.cursorB != null) {
            var daI = Math.min(start + this.cursorA, data.length - 1);
            var dbI = Math.min(start + this.cursorB, data.length - 1);
            ctx.fillStyle = '#FFD700'; ctx.fillText('dV: ' + Math.abs(data[daI].v - data[dbI].v).toFixed(3) + 'V', 8, h - 20);
        }

        ctx.fillStyle = '#00D4FF'; ctx.font = '10px JetBrains Mono, monospace'; ctx.textAlign = 'left';
        ctx.fillText('CH1: ' + this.voltDiv + 'V/div', 8, 15);
        ctx.fillStyle = '#00FF88';
        ctx.fillText('CH2: ' + (this.voltDiv * 10) + 'mA/div', 8, 28);
        ctx.fillStyle = 'rgba(255,255,255,0.3)'; ctx.textAlign = 'right';
        ctx.fillText('Time: ' + (this.timeDiv * 1000).toFixed(1) + 'ms/div', w - 8, 15);
    }
}

// ── Formula Display Panel ──
class FormulaPanel {
    constructor(containerId) { this.containerId = containerId; }
    update(formulas, warnings) {
        var el = document.getElementById(this.containerId);
        if (!el) return;
        var html = '';
        if (warnings.length > 0) {
            for (var wi = 0; wi < warnings.length; wi++) {
                html += '<div style="color:#FFB800;font-size:0.75rem;padding:0.3rem;background:rgba(255,184,0,0.08);border-radius:6px;margin-bottom:0.3rem;">Warning: ' + this._esc(warnings[wi]) + '</div>';
            }
        }
        if (formulas.length === 0) { html += '<div class="cs-formula-empty">Run simulation to see formulas</div>'; }
        else { for (var fi = 0; fi < formulas.length; fi++) html += this._formatFormula(formulas[fi]); }
        el.innerHTML = html;
    }
    _esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    _formatFormula(f) {
        var val = String(f.value || '').replace(/([0-9.]+)/g, '<strong style="color:#00D4FF;">$1</strong>').replace(/(V|A|W|Hz|F|H|J|N|m|s)/g, '<span style="color:#FFB800;">$1</span>');
        return '<div class="cs-formula-item"><span class="cs-formula-name">' + this._esc(f.name) + '</span><span class="cs-formula-value">' + val + '</span></div>';
    }
}

// ── ZPE Templates (10) ──
var ZPE_TEMPLATES = [
    { name: 'Tesla Coil Classic', category: 'tesla', researcher: 'Nikola Tesla (1891)', description: 'Classic air-core resonant transformer with spark gap, primary, secondary, and toroid top load.', formula: 'f0 = 1/(2*pi*sqrt(LC)), Vout = Vin*sqrt(Cp/Cs)', components: [
        { type: 'ac_source', x: 100, y: 300, value: 120, frequency: 60 }, { type: 'high_voltage_cap', x: 220, y: 200, value: 1e-9 }, { type: 'spark_gap', x: 340, y: 200, value: 15000 }, { type: 'tesla_primary', x: 460, y: 300, value: 0.0001 }, { type: 'tesla_secondary', x: 580, y: 300, value: 0.025 }, { type: 'top_load', x: 580, y: 150, value: 0.2, capacitance: 1e-12 }, { type: 'ground', x: 460, y: 420, value: 0 }
    ] },
    { name: 'Hutchison Interference Zone', category: 'zpe', researcher: 'John Hutchison (1979)', description: 'Overlapping RF fields from Tesla coil and Van de Graaff analog, creating an interference zone.', formula: 'E_int = E1*E2*cos(dPhi)', components: [
        { type: 'rf_generator', x: 100, y: 250, value: 50, frequency: 1e6 }, { type: 'tesla_primary', x: 250, y: 250, value: 0.0002 }, { type: 'tesla_secondary', x: 400, y: 250, value: 0.05 }, { type: 'rf_generator', x: 600, y: 250, value: 30, frequency: 2.45e9 }, { type: 'em_field_probe', x: 400, y: 400, value: 0 }, { type: 'spectrum_analyzer', x: 550, y: 400, value: 0 }, { type: 'ground', x: 250, y: 400, value: 0 }
    ] },
    { name: 'Bedini SSG Motor', category: 'zpe', researcher: 'John Bedini (1984)', description: 'Simplified School Girl motor with back-EMF capture, charging secondary battery via radiant energy.', formula: 'COP = E_out/E_in, E = 0.5*L*I^2*eta', components: [
        { type: 'battery', x: 100, y: 300, value: 12 }, { type: 'resistor', x: 200, y: 200, value: 680 }, { type: 'bifilar_coil', x: 350, y: 250, value: 0.1 }, { type: 'npn_transistor', x: 350, y: 380, value: 100 }, { type: 'diode', x: 500, y: 200, value: 0.7 }, { type: 'capacitor', x: 500, y: 350, value: 0.001 }, { type: 'battery', x: 650, y: 300, value: 12 }, { type: 'ground', x: 350, y: 480, value: 0 }
    ] },
    { name: 'Don Smith Resonance Device', category: 'zpe', researcher: 'Don Smith (1990s)', description: 'Resonant step-up with L-C tanks, parametric amplification via frequency multiplication.', formula: 'V_step = V*(f2/f1)*(N2/N1)', components: [
        { type: 'ac_source', x: 100, y: 300, value: 12, frequency: 60 }, { type: 'transformer', x: 250, y: 300, value: 100, secondary_turns: 2000 }, { type: 'high_voltage_cap', x: 400, y: 200, value: 5e-9 }, { type: 'inductor', x: 400, y: 400, value: 0.01 }, { type: 'spark_gap', x: 550, y: 300, value: 10000 }, { type: 'tesla_secondary', x: 650, y: 300, value: 0.03 }, { type: 'ground', x: 250, y: 420, value: 0 }
    ] },
    { name: 'Crystal Cell Earth Battery', category: 'zpe', researcher: 'Various (historical)', description: 'Galvanic crystal cell generating millivolt potential from dissimilar materials and ionic migration.', formula: 'E = (RT/nF)*ln(Q)', components: [
        { type: 'crystal_cell', x: 200, y: 300, value: 0.4 }, { type: 'crystal_cell', x: 350, y: 300, value: 0.4 }, { type: 'crystal_cell', x: 500, y: 300, value: 0.4 }, { type: 'led', x: 350, y: 160, value: 1.8 }, { type: 'resistor', x: 200, y: 160, value: 100 }, { type: 'voltmeter', x: 500, y: 160, value: 0 }, { type: 'ground', x: 350, y: 420, value: 0 }
    ] },
    { name: 'Casimir Cavity Experiment', category: 'zpe', researcher: 'H. Casimir (1948)', description: 'Parallel plate Casimir cavity measuring vacuum fluctuation pressure between nanoscale surfaces.', formula: 'F = -pi^2*hbar*c*A/(240*d^4)', components: [
        { type: 'casimir_plates', x: 300, y: 250, value: 1e-7, area: 0.01 }, { type: 'casimir_plates', x: 500, y: 250, value: 5e-8, area: 0.01 }, { type: 'em_field_probe', x: 400, y: 350, value: 0 }, { type: 'ammeter', x: 300, y: 400, value: 0 }, { type: 'voltmeter', x: 500, y: 400, value: 0 }, { type: 'ground', x: 400, y: 460, value: 0 }
    ] },
    { name: 'Scalar Wave Transmitter', category: 'zpe', researcher: 'K. Meyl / T.E. Bearden', description: 'Longitudinal scalar wave transmitter using bifilar Tesla pancake coils, transmitter-receiver pair.', formula: 'Phi_s = E*B/(mu0*eps0)', components: [
        { type: 'signal_generator', x: 100, y: 300, value: 10, frequency: 7.83 }, { type: 'bifilar_coil', x: 250, y: 300, value: 0.5 }, { type: 'caduceus_coil', x: 400, y: 300, value: 0.5 }, { type: 'bifilar_coil', x: 550, y: 300, value: 0.5 }, { type: 'oscilloscope', x: 550, y: 180, value: 0 }, { type: 'em_field_probe', x: 400, y: 180, value: 0 }, { type: 'ground', x: 250, y: 420, value: 0 }
    ] },
    { name: 'Rodin Vortex Coil', category: 'zpe', researcher: 'Marko Rodin', description: 'Toroidal vortex-based coil wound in 3-6-9 pattern, exploring magnetic field vortex mathematics.', formula: 'Flux pattern: 1-2-4-8-7-5 / 3-6-9 vortex', components: [
        { type: 'ac_source', x: 100, y: 300, value: 12, frequency: 5000 }, { type: 'toroidal_inductor', x: 300, y: 300, value: 0.5 }, { type: 'toroidal_inductor', x: 500, y: 300, value: 0.5 }, { type: 'capacitor', x: 400, y: 180, value: 1e-6 }, { type: 'em_field_probe', x: 400, y: 420, value: 0 }, { type: 'ground', x: 100, y: 420, value: 0 }
    ] },
    { name: 'Joule Thief', category: 'tesla', researcher: 'Z. Kaparnik (1999)', description: 'Blocking oscillator that lights an LED from a nearly dead 1.5V battery, demonstrating voltage boosting.', formula: 'V_out = V_in * N2/N1 (during flyback)', components: [
        { type: 'battery', x: 100, y: 300, value: 1.5 }, { type: 'resistor', x: 200, y: 200, value: 1000 }, { type: 'bifilar_coil', x: 350, y: 250, value: 0.001 }, { type: 'npn_transistor', x: 350, y: 380, value: 100 }, { type: 'led', x: 500, y: 250, value: 3.2 }, { type: 'ground', x: 350, y: 460, value: 0 }
    ] },
    { name: 'Schumann Resonance Detector', category: 'zpe', researcher: 'W.O. Schumann (1952)', description: 'Earth-ionosphere cavity resonance detector tuned to 7.83 Hz fundamental and harmonics.', formula: 'fn = (c/2*pi*R)*sqrt(n(n+1))', components: [
        { type: 'schumann_antenna', x: 300, y: 180, value: 1 }, { type: 'inductor', x: 300, y: 300, value: 10 }, { type: 'capacitor', x: 450, y: 300, value: 0.04 }, { type: 'resistor', x: 450, y: 180, value: 100000 }, { type: 'op_amp', x: 600, y: 300, value: 100000 }, { type: 'oscilloscope', x: 600, y: 180, value: 0 }, { type: 'ground', x: 300, y: 420, value: 0 }
    ] }
];

// ── Extended Component Definitions (46) ──
var EXTENDED_COMP_DEFS = {
    signal_generator: { unit: 'V', color: '#FFAA00', w: 60, h: 40, terminals: 2, label: 'SIG GEN' },
    thermistor: { unit: 'Ohm', color: '#FF4444', w: 60, h: 30, terminals: 2, label: 'NTC' },
    speaker: { unit: 'Ohm', color: '#00CCFF', w: 50, h: 50, terminals: 2, label: 'SPEAKER' },
    relay: { unit: 'V', color: '#FF9900', w: 60, h: 40, terminals: 4, label: 'RELAY' },
    zener_diode: { unit: 'V', color: '#FF00AA', w: 50, h: 30, terminals: 2 },
    transformer: { unit: 'turns', color: '#FFD700', w: 80, h: 50, terminals: 4, label: 'TRANSFORMER' },
    tesla_primary: { unit: 'H', color: '#FF3366', w: 60, h: 40, terminals: 2, label: 'T1 PRIMARY' },
    tesla_secondary: { unit: 'H', color: '#FF6699', w: 40, h: 70, terminals: 2, label: 'T2 SECONDARY' },
    top_load: { unit: 'm', color: '#FFD700', w: 50, h: 50, terminals: 1, label: 'TOP LOAD' },
    spark_gap: { unit: 'V', color: '#FF8800', w: 60, h: 30, terminals: 2, label: 'SPARK GAP' },
    bifilar_coil: { unit: 'H', color: '#CC00FF', w: 50, h: 60, terminals: 2, label: 'BIFILAR' },
    caduceus_coil: { unit: 'H', color: '#9933FF', w: 50, h: 60, terminals: 2, label: 'CADUCEUS' },
    toroidal_inductor: { unit: 'H', color: '#6600CC', w: 50, h: 50, terminals: 2, label: 'TOROID' },
    casimir_plates: { unit: 'm', color: '#00FFCC', w: 40, h: 50, terminals: 2, label: 'CASIMIR' },
    crystal_cell: { unit: 'V', color: '#88FF00', w: 50, h: 40, terminals: 2, label: 'CRYSTAL' },
    schumann_antenna: { unit: 'n', color: '#00DDFF', w: 50, h: 50, terminals: 1, label: 'SCHUMANN' },
    high_voltage_cap: { unit: 'F', color: '#FF4444', w: 50, h: 30, terminals: 2, label: 'HV CAP' },
    leyden_jar: { unit: 'F', color: '#FFAA00', w: 40, h: 50, terminals: 2, label: 'LEYDEN' },
    rf_generator: { unit: 'V', color: '#FF00FF', w: 60, h: 40, terminals: 2, label: 'RF GEN' },
    crystal_osc: { unit: 'Hz', color: '#66FFCC', w: 40, h: 30, terminals: 2, label: 'XTAL' },
    varactor: { unit: 'F', color: '#CC66FF', w: 50, h: 30, terminals: 2, label: 'VARACTOR' },
    reed_switch: { unit: '', color: '#00FFAA', w: 60, h: 30, terminals: 2, label: 'REED SW' },
    em_field_probe: { unit: '', color: '#FF88FF', w: 45, h: 45, terminals: 1, label: 'EM PROBE' },
    spectrum_analyzer: { unit: '', color: '#FF66CC', w: 50, h: 40, terminals: 1, label: 'SPECTRUM' },
    thyristor: { unit: 'A', color: '#FF3300', w: 50, h: 30, terminals: 3, label: 'SCR' },
    ic_555: { unit: '', color: '#FFD700', w: 50, h: 40, terminals: 3, label: '555' },
    voltage_regulator: { unit: 'V', color: '#00DD00', w: 50, h: 30, terminals: 3, label: 'REG' },
    mosfet_n: { unit: 'A', color: '#00CC66', w: 50, h: 60, terminals: 3 },
    pnp_transistor: { unit: 'beta', color: '#00AAFF', w: 50, h: 60, terminals: 3 },
    oscilloscope: { unit: '', color: '#00FF88', w: 50, h: 40, terminals: 2, label: 'SCOPE' },
    wattmeter: { unit: 'W', color: '#FFCC00', w: 45, h: 45, terminals: 2 },
    buzzer: { unit: 'V', color: '#FF6600', w: 40, h: 40, terminals: 2, label: 'BUZZER' },
    push_button: { unit: '', color: '#00CCFF', w: 50, h: 30, terminals: 2 },
    fuse: { unit: 'A', color: '#FF3366', w: 50, h: 25, terminals: 2, label: 'FUSE' },
    // v5.0 additions
    mosfet_p: { unit: 'A', color: '#33CC99', w: 50, h: 60, terminals: 3, label: 'P-MOS' },
    jfet_n: { unit: 'A', color: '#44BB88', w: 50, h: 60, terminals: 3, label: 'N-JFET' },
    jfet_p: { unit: 'A', color: '#55AA77', w: 50, h: 60, terminals: 3, label: 'P-JFET' },
    igbt: { unit: 'A', color: '#FF8844', w: 50, h: 60, terminals: 3, label: 'IGBT' },
    schottky_diode: { unit: 'V', color: '#FFCC33', w: 50, h: 30, terminals: 2, label: 'SCHOTTKY' },
    photodiode: { unit: 'W/m²', color: '#88EEFF', w: 50, h: 30, terminals: 2, label: 'PHOTO' },
    current_source: { unit: 'A', color: '#FF6688', w: 50, h: 40, terminals: 2, label: 'I SRC' },
    ldr: { unit: 'Ohm', color: '#AADDFF', w: 50, h: 30, terminals: 2, label: 'LDR' },
};

// ══════════════════════════════════════════════════════
// ═══ v4.0: Modified Nodal Analysis (MNA) Engine ═══
// ══════════════════════════════════════════════════════

// ── Sparse Matrix for MNA ──
class SparseMatrix {
    constructor(size) {
        this.size = size;
        this.data = new Map();
    }
    set(r, c, v) {
        if (r < 0 || c < 0 || r >= this.size || c >= this.size) return;
        this.data.set(r * this.size + c, v);
    }
    get(r, c) {
        return this.data.get(r * this.size + c) || 0;
    }
    add(r, c, v) {
        if (r < 0 || c < 0 || r >= this.size || c >= this.size) return;
        const key = r * this.size + c;
        this.data.set(key, (this.data.get(key) || 0) + v);
    }
    clear() { this.data.clear(); }
    toDense() {
        const m = [];
        for (let i = 0; i < this.size; i++) {
            m[i] = new Float64Array(this.size);
            for (let j = 0; j < this.size; j++) m[i][j] = this.get(i, j);
        }
        return m;
    }
    // Solve Ax = b using LU decomposition with partial pivoting
    static solve(A, b) {
        const n = A.length;
        const aug = A.map((row, i) => [...row, b[i]]);
        // Forward elimination with partial pivoting
        for (let col = 0; col < n; col++) {
            let maxRow = col, maxVal = Math.abs(aug[col][col]);
            for (let row = col + 1; row < n; row++) {
                if (Math.abs(aug[row][col]) > maxVal) { maxVal = Math.abs(aug[row][col]); maxRow = row; }
            }
            if (maxRow !== col) { const tmp = aug[col]; aug[col] = aug[maxRow]; aug[maxRow] = tmp; }
            const pivot = aug[col][col];
            if (Math.abs(pivot) < 1e-18) { aug[col][col] = 1e-18; continue; }
            for (let row = col + 1; row < n; row++) {
                const factor = aug[row][col] / pivot;
                for (let j = col; j <= n; j++) aug[row][j] -= factor * aug[col][j];
            }
        }
        // Back substitution
        const x = new Float64Array(n);
        for (let i = n - 1; i >= 0; i--) {
            let sum = aug[i][n];
            for (let j = i + 1; j < n; j++) sum -= aug[i][j] * x[j];
            x[i] = sum / (Math.abs(aug[i][i]) > 1e-18 ? aug[i][i] : 1e-18);
        }
        return x;
    }
}

// ── MNA Stamper ──
class MNAStamper {
    constructor() {
        this.nodeMap = new Map();  // component terminal → node index
        this.nodeCount = 0;
        this.vsrcCount = 0;       // voltage source branch count
        this.vsrcMap = new Map(); // voltage source id → branch index
    }

    // Build node map from components and wires
    buildNodeMap(components, wires) {
        this.nodeMap.clear();
        this.nodeCount = 0;
        this.vsrcCount = 0;
        this.vsrcMap.clear();

        // Union-Find for wire connections
        const parent = new Map();
        const find = (k) => { while (parent.get(k) !== k) { parent.set(k, parent.get(parent.get(k))); k = parent.get(k); } return k; };
        const union = (a, b) => { const ra = find(a), rb = find(b); if (ra !== rb) parent.set(ra, rb); };

        // Initialize terminals
        for (const comp of components) {
            if (comp.type === 'wire') continue;
            const numT = this._terminalCount(comp);
            for (let t = 0; t < numT; t++) {
                const key = comp.id + ':' + t;
                parent.set(key, key);
            }
        }

        // Ground nodes → node 0 (reference)
        const groundKey = '__ground__';
        parent.set(groundKey, groundKey);
        for (const comp of components) {
            if (comp.type === 'ground') {
                const key = comp.id + ':0';
                if (parent.has(key)) union(key, groundKey);
            }
        }

        // Wire connections
        for (const wire of wires) {
            if (wire.comp1 != null && wire.comp2 != null) {
                const k1 = wire.comp1 + ':' + (wire.term1 || 0);
                const k2 = wire.comp2 + ':' + (wire.term2 || 0);
                if (parent.has(k1) && parent.has(k2)) union(k1, k2);
            }
        }

        // Assign node indices (ground = -1 means row 0 is node 1)
        const rootToNode = new Map();
        const groundRoot = parent.has(groundKey) ? find(groundKey) : null;
        if (groundRoot) rootToNode.set(groundRoot, -1); // ground = -1

        let nextNode = 0;
        for (const comp of components) {
            if (comp.type === 'wire' || comp.type === 'ground') continue;
            const numT = this._terminalCount(comp);
            for (let t = 0; t < numT; t++) {
                const key = comp.id + ':' + t;
                if (!parent.has(key)) continue;
                const root = find(key);
                if (!rootToNode.has(root)) {
                    rootToNode.set(root, nextNode++);
                }
                this.nodeMap.set(key, rootToNode.get(root));
            }
        }
        this.nodeCount = nextNode;

        // Count voltage sources for MNA extended rows
        for (const comp of components) {
            if (this._isVoltageSource(comp)) {
                this.vsrcMap.set(comp.id, this.vsrcCount++);
            }
        }
    }

    _terminalCount(comp) {
        if (['npn_transistor','pnp_transistor','mosfet_n','mosfet_p','op_amp','potentiometer','thyristor','ic_555','voltage_regulator','jfet_n','jfet_p','igbt'].includes(comp.type)) return 3;
        if (['transformer','relay'].includes(comp.type)) return 4;
        if (['ground','top_load','schumann_antenna','em_field_probe','spectrum_analyzer'].includes(comp.type)) return 1;
        return 2;
    }

    _isVoltageSource(comp) {
        return ['battery','ac_source','signal_generator','rf_generator'].includes(comp.type);
    }

    // Get node indices for a component's terminals
    getNodes(comp) {
        const nodes = [];
        const numT = this._terminalCount(comp);
        for (let t = 0; t < numT; t++) {
            const idx = this.nodeMap.get(comp.id + ':' + t);
            nodes.push(idx != null ? idx : -1);
        }
        return nodes;
    }

    // Stamp conductance (resistor) between nodes n1, n2
    stampConductance(G, n1, n2, g) {
        if (n1 >= 0) G.add(n1, n1, g);
        if (n2 >= 0) G.add(n2, n2, g);
        if (n1 >= 0 && n2 >= 0) { G.add(n1, n2, -g); G.add(n2, n1, -g); }
    }

    // Stamp voltage source: V(n+) - V(n-) = v, branch current i_k
    stampVoltageSource(G, rhs, n1, n2, branchIdx, voltage, matSize) {
        const row = this.nodeCount + branchIdx;
        if (row >= matSize) return;
        if (n1 >= 0) { G.add(n1, row, 1); G.add(row, n1, 1); }
        if (n2 >= 0) { G.add(n2, row, -1); G.add(row, n2, -1); }
        rhs[row] = voltage;
    }

    // Stamp current source from n1 to n2
    stampCurrentSource(rhs, n1, n2, current) {
        if (n1 >= 0) rhs[n1] -= current;
        if (n2 >= 0) rhs[n2] += current;
    }

    getMatrixSize() { return this.nodeCount + this.vsrcCount; }
}

// ── Newton-Raphson Nonlinear Solver ──
class NRSolver {
    constructor() {
        this.maxIter = 50;
        this.absTol = 1e-9;
        this.relTol = 1e-4;
        this.vTol = 1e-6;
        this.gmin = 1e-12; // minimum conductance for convergence
    }

    // Diode companion model (trapezoidal)
    diodeCompanion(Vd, Is, N, Vt) {
        const Vte = N * Vt;
        const eVd = Math.exp(Math.min(Vd / Vte, 40));
        const Id = Is * (eVd - 1);
        const Gd = Is * eVd / Vte + this.gmin;
        const Ieq = Id - Gd * Vd; // Norton equivalent current
        return { Id, Gd, Ieq };
    }

    // Schottky diode (lower Vf, higher Is)
    schottkyCompanion(Vd) {
        const Is = 1e-6;  // much higher saturation current
        const N = 1.05;   // near-ideal
        return this.diodeCompanion(Vd, Is, N, PHYS.V_T);
    }

    // Zener diode with reverse breakdown
    zenerCompanion(Vd, Vz) {
        // Forward: standard diode
        if (Vd >= 0) return this.diodeCompanion(Vd, 1e-12, 1, PHYS.V_T);
        // Reverse: breakdown at -Vz
        const Vr = -Vd;
        if (Vr < Vz * 0.95) {
            // Below breakdown — tiny leakage
            const Gd = this.gmin;
            return { Id: -1e-9, Gd, Ieq: -1e-9 - Gd * Vd };
        }
        // In breakdown — clamp near Vz
        const Iz = 1e-6 * Math.exp(Math.min((Vr - Vz) / (0.05 * PHYS.V_T * 2), 40));
        const Gz = 1e-6 * Math.exp(Math.min((Vr - Vz) / (0.05 * PHYS.V_T * 2), 40)) / (0.05 * PHYS.V_T * 2) + this.gmin;
        return { Id: -Iz, Gd: Gz, Ieq: -Iz - Gz * Vd };
    }

    // Photodiode companion (light-dependent current source + diode)
    photodiodeCompanion(Vd, irradiance) {
        const Is = 1e-12;
        const Iph = (irradiance || 0.5) * 1e-3; // photocurrent proportional to light
        const { Id, Gd, Ieq } = this.diodeCompanion(Vd, Is, 1, PHYS.V_T);
        return { Id: Id - Iph, Gd, Ieq: Ieq - Iph };
    }

    // BJT companion model (Ebers-Moll)
    bjtCompanion(Vbe, Vbc, beta, Is) {
        const Vt = PHYS.V_T;
        const bf = beta, br = 1;
        const Ise = Is, Isc = Is;
        const eVbe = Math.exp(Math.min(Vbe / Vt, 40));
        const eVbc = Math.exp(Math.min(Vbc / Vt, 40));
        const If = Ise * (eVbe - 1);
        const Ir = Isc * (eVbc - 1);
        const Ic = If - Ir * (1 + 1/br);
        const Ib = If / bf + Ir / br;
        const Ie = -(Ic + Ib);
        const gbe = Ise * eVbe / Vt + this.gmin;
        const gbc = Isc * eVbc / Vt + this.gmin;
        const gm = Ise * eVbe / Vt;
        return { Ic, Ib, Ie, gbe, gbc, gm, If, Ir };
    }

    // MOSFET Level 1 companion
    mosfetCompanion(Vgs, Vds, Kp, Vth, lambda) {
        lambda = lambda || 0;
        let Id, gm, gds;
        if (Vgs <= Vth) {
            // Cutoff
            Id = 0; gm = 0; gds = this.gmin;
        } else if (Vds < Vgs - Vth) {
            // Linear (triode)
            Id = Kp * ((Vgs - Vth) * Vds - 0.5 * Vds * Vds) * (1 + lambda * Vds);
            gm = Kp * Vds * (1 + lambda * Vds);
            gds = Kp * ((Vgs - Vth) - Vds) * (1 + lambda * Vds) + lambda * Kp * ((Vgs - Vth) * Vds - 0.5 * Vds * Vds);
        } else {
            // Saturation
            const Idsat = 0.5 * Kp * Math.pow(Vgs - Vth, 2);
            Id = Idsat * (1 + lambda * Vds);
            gm = Kp * (Vgs - Vth) * (1 + lambda * Vds);
            gds = lambda * Idsat + this.gmin;
        }
        return { Id, gm, gds: Math.max(gds, this.gmin) };
    }

    // P-MOSFET companion (CSB-014 simplified)
    pmosfetCompanion(Vsg, Vsd, Kp, Vth, lambda) {
        // P-channel: mirror of N-channel with Vsg, Vsd
        return this.mosfetCompanion(Vsg, Vsd, Kp || 0.3, Vth || 2, lambda || 0.01);
    }

    // JFET N-channel companion (CSB-015)
    jfetCompanion(Vgs, Vds, Idss, Vp) {
        Idss = Idss || 0.01;   // 10mA default IDSS
        Vp = Vp || -4;         // pinch-off voltage (negative for N-ch)
        let Id, gm, gds;
        if (Vgs <= Vp) {
            // Cutoff
            Id = 0; gm = 0; gds = this.gmin;
        } else if (Vds < Vgs - Vp) {
            // Linear
            const k = Idss / (Vp * Vp);
            Id = k * (2 * (Vgs - Vp) * Vds - Vds * Vds);
            gm = 2 * k * Vds;
            gds = 2 * k * (Vgs - Vp - Vds) + this.gmin;
        } else {
            // Saturation
            Id = Idss * Math.pow(1 - Vgs / Vp, 2);
            gm = -2 * Idss * (1 - Vgs / Vp) / Vp;
            gds = this.gmin;
        }
        return { Id: Math.max(Id, 0), gm: Math.abs(gm), gds: Math.max(gds, this.gmin) };
    }

    // IGBT companion (CSB-017) — simplified MOS-gated BJT
    igbtCompanion(Vge, Vce, Kp, Vth) {
        Kp = Kp || 0.8;
        Vth = Vth || 5;
        let Ic, gm, gce;
        if (Vge <= Vth) {
            Ic = 0; gm = 0; gce = this.gmin;
        } else {
            // Active mode (simplified — saturation behavior like MOSFET with BJT output)
            Ic = Kp * Math.pow(Vge - Vth, 2) * (1 + 0.005 * Math.max(Vce, 0));
            gm = 2 * Kp * (Vge - Vth) * (1 + 0.005 * Math.max(Vce, 0));
            gce = 0.005 * Kp * Math.pow(Vge - Vth, 2) + this.gmin;
        }
        return { Ic, gm, gce: Math.max(gce, this.gmin) };
    }

    // SCR / Thyristor companion (CSB-018) — gate-triggered latch
    scrCompanion(Vak, Ig, Ih) {
        Ih = Ih || 0.01;  // holding current 10mA
        let Ia, Gak, latched;
        // Simplified two-transistor model
        if (Ig > 0.001 && Vak > 0.7) {
            // Triggered ON — low impedance
            latched = true;
            Ia = Vak / 0.02;  // Ron ~ 20 mOhm
            Gak = 1 / 0.02;
        } else if (Vak > 0.7 && Ig <= 0) {
            // Check if holding current maintained (latched state)
            const testI = Vak / 0.02;
            if (testI >= Ih) {
                latched = true;
                Ia = testI;
                Gak = 1 / 0.02;
            } else {
                latched = false;
                Ia = 0;
                Gak = this.gmin;
            }
        } else {
            latched = false;
            Ia = 0;
            Gak = this.gmin;
        }
        return { Ia, Gak, latched, Ieq: Ia - Gak * Vak };
    }

    // 555 Timer behavioral model (CSB-030)
    // Returns output voltage based on threshold/trigger comparator states
    ic555Model(Vtrig, Vthresh, Vcc, state555) {
        const Vth_hi = Vcc * 2 / 3;  // upper threshold
        const Vth_lo = Vcc * 1 / 3;  // lower threshold (trigger)
        let output = state555 ? state555.output : 0;
        let discharge = state555 ? state555.discharge : false;

        if (Vtrig < Vth_lo) {
            output = Vcc - 0.2;   // SET (output high)
            discharge = false;
        } else if (Vthresh > Vth_hi) {
            output = 0.1;         // RESET (output low)
            discharge = true;
        }
        // Otherwise, hold previous state (SR latch)
        return { output, discharge, Vth_hi, Vth_lo };
    }

    // Voltage regulator / LDO model (CSB-029)
    regulatorModel(Vin, Vout_set, Iload) {
        const dropout = 0.3;       // LDO dropout voltage
        const Rout = 0.01;         // output impedance
        const Ilimit = 1.5;        // current limit (A)
        let Vout, mode;

        if (Vin < Vout_set + dropout) {
            // Dropout — can't regulate
            Vout = Math.max(Vin - dropout, 0);
            mode = 'dropout';
        } else if (Iload > Ilimit) {
            // Current limiting
            Vout = Vout_set - Rout * (Iload - Ilimit) * 10;
            mode = 'current-limit';
        } else {
            // Normal regulation
            Vout = Vout_set - Rout * Iload;
            mode = 'regulating';
        }
        return { Vout: Math.max(Vout, 0), mode, dropout, Gout: 1 / (Rout + 0.001) };
    }

    // Op-amp realistic model (CSB-022: finite GBW, slew, saturation)
    opAmpRealistic(Vdiff, Vcc, Vee, gain, gbw, slewRate, prevVout, dt) {
        gain = gain || 100000;
        gbw = gbw || 1e6;         // 1 MHz GBW default
        slewRate = slewRate || 0.5; // 0.5 V/us default
        Vcc = Vcc || 15;
        Vee = Vee || -15;

        // Ideal output
        let Vout = Vdiff * gain;

        // Slew rate limiting
        if (prevVout != null && dt > 0) {
            const maxDV = slewRate * 1e6 * dt; // slewRate is V/us
            const dV = Vout - prevVout;
            if (Math.abs(dV) > maxDV) {
                Vout = prevVout + Math.sign(dV) * maxDV;
            }
        }

        // Output saturation (CSB-023)
        const Vsat_hi = Vcc - 1.5;   // output can't reach rail
        const Vsat_lo = Vee + 1.5;
        const saturated = Vout > Vsat_hi || Vout < Vsat_lo;
        Vout = Math.max(Vsat_lo, Math.min(Vsat_hi, Vout));

        return { Vout, saturated, gain, gbw };
    }

    // Check convergence
    converged(xNew, xOld) {
        for (let i = 0; i < xNew.length; i++) {
            const diff = Math.abs(xNew[i] - xOld[i]);
            if (diff > this.absTol + this.relTol * Math.abs(xNew[i])) return false;
        }
        return true;
    }
}

// ── Circuit Validator (CSA-062 to CSA-066) ──
class CircuitValidator {
    constructor() {
        this.warnings = [];
        this.errors = [];
    }

    validate(components, wires, stamper) {
        this.warnings = [];
        this.errors = [];

        if (!components || components.length === 0) return this;

        const hasGround = components.some(c => c.type === 'ground');
        const hasSrc = components.some(c =>
            ['battery','ac_source','signal_generator','rf_generator','crystal_cell'].includes(c.type)
        );

        // CSA-062: topology validation
        if (!hasGround) this.warnings.push('No ground reference — results may be inaccurate');
        if (!hasSrc) this.warnings.push('No voltage/current source in circuit');

        // CSA-063: floating node detection
        if (stamper && stamper.nodeMap.size > 0) {
            const connectedNodes = new Set();
            for (const wire of wires) {
                if (wire.comp1 != null && wire.comp2 != null) {
                    connectedNodes.add(wire.comp1);
                    connectedNodes.add(wire.comp2);
                }
            }
            for (const comp of components) {
                if (comp.type === 'wire' || comp.type === 'ground') continue;
                if (!connectedNodes.has(comp.id) && components.length > 1) {
                    this.warnings.push('Floating component: ' + (comp.label || comp.type) + ' (not connected)');
                }
            }
        }

        // CSA-064: short circuit detection
        for (const wire of wires) {
            if (wire.comp1 != null && wire.comp2 != null) {
                const c1 = components.find(c => c.id === wire.comp1);
                const c2 = components.find(c => c.id === wire.comp2);
                if (c1 && c2 &&
                    ['battery','ac_source'].includes(c1.type) &&
                    c2.type === 'ground' &&
                    wire.term1 === 0 && wire.term2 === 0) {
                    // Direct source+ to ground is a short
                    this.warnings.push('Possible short circuit: ' + c1.type + ' directly to ground');
                }
            }
        }

        // CSA-065: inductor loop without resistance
        const inductors = components.filter(c =>
            ['inductor','bifilar_coil','caduceus_coil','toroidal_inductor','tesla_primary','tesla_secondary'].includes(c.type)
        );
        const resistors = components.filter(c =>
            ['resistor','potentiometer','thermistor'].includes(c.type)
        );
        if (inductors.length > 0 && resistors.length === 0) {
            this.warnings.push('Inductor loop without resistance — may cause numerical instability');
        }

        // CSA-066: capacitor cutset (series capacitors without DC path)
        const caps = components.filter(c =>
            ['capacitor','high_voltage_cap','leyden_jar'].includes(c.type)
        );
        if (caps.length > 1 && !hasSrc && resistors.length === 0) {
            this.warnings.push('Capacitor cutset detected — no DC path');
        }

        return this;
    }
}

// ── Adaptive Timestep Controller ──
class TimestepController {
    constructor() {
        this.dtMin = 1e-12;
        this.dtMax = 1e-3;
        this.dt = 1e-6;
        this.lteTol = 1e-4;  // Local truncation error tolerance
        this.prevState = null;
        this.prevDeriv = null;
        this.rejectCount = 0;
        this.maxRejects = 5;
    }

    // Estimate LTE and adjust timestep
    adapt(state, deriv, dt) {
        if (!this.prevState || !this.prevDeriv) {
            this.prevState = state.slice();
            this.prevDeriv = deriv.slice();
            return dt;
        }
        // Estimate second derivative from derivative change
        let maxLTE = 0;
        for (let i = 0; i < Math.min(state.length, this.prevState.length); i++) {
            const d2 = Math.abs(deriv[i] - this.prevDeriv[i]) / dt;
            const lte = 0.5 * d2 * dt * dt; // trapezoidal LTE estimate
            const scale = Math.max(Math.abs(state[i]), 1e-6);
            maxLTE = Math.max(maxLTE, lte / scale);
        }
        this.prevState = state.slice();
        this.prevDeriv = deriv.slice();

        if (maxLTE < 1e-15) return Math.min(dt * 2, this.dtMax);

        let newDt = dt * Math.pow(this.lteTol / maxLTE, 1/3);
        newDt = Math.max(this.dtMin, Math.min(newDt, this.dtMax));
        newDt = Math.max(newDt, dt * 0.25); // don't shrink too fast
        newDt = Math.min(newDt, dt * 4);    // don't grow too fast
        return newDt;
    }

    reset() {
        this.dt = 1e-6;
        this.prevState = null;
        this.prevDeriv = null;
        this.rejectCount = 0;
    }
}

// ── MNA-based Simulation Engine v4.0 ──
class SimulationEngineV4 extends SimulationEngine {
    constructor() {
        super();
        this.stamper = new MNAStamper();
        this.nr = new NRSolver();
        this.tsControl = new TimestepController();
        this.nodeVoltages = null;      // solution vector
        this.branchCurrents = null;
        this.prevNodeVoltages = null;
        this.useMNA = true;
        this.capStates = new Map();    // capacitor voltages for trapezoidal
        this.indStates = new Map();    // inductor currents for trapezoidal
        this.iteration = 0;
        this.converged = false;
        this.solverStats = { iterations: 0, converged: false, matrixSize: 0, dtUsed: 0 };
    }

    solve(components, wires) {
        // Fall back to v3 solve for circuits without proper connectivity
        if (!components || components.length === 0) return super.solve(components, wires);

        // Build node map
        this.stamper.buildNodeMap(components, wires);
        const matSize = this.stamper.getMatrixSize();

        // If matrix is too small (no real nodes), fall back to v3
        if (matSize < 1) return super.solve(components, wires);

        this.solverStats.matrixSize = matSize;

        // Classify components
        const classified = this._classify(components);

        // Determine time for AC sources
        const hasAC = classified.acSources.length > 0;
        const acFreq = hasAC ? (classified.acSources[0].frequency || 60) : 0;

        // Initialize node voltages if needed
        if (!this.nodeVoltages || this.nodeVoltages.length !== matSize) {
            this.nodeVoltages = new Float64Array(matSize);
            this.prevNodeVoltages = new Float64Array(matSize);
        }

        // Save previous for convergence check
        this.prevNodeVoltages.set(this.nodeVoltages);

        // Newton-Raphson iteration
        let convergedIter = false;
        for (let iter = 0; iter < this.nr.maxIter; iter++) {
            const G = new SparseMatrix(matSize);
            const rhs = new Float64Array(matSize);

            // Add GMIN to ground (diagonal loading for convergence)
            for (let i = 0; i < this.stamper.nodeCount; i++) {
                G.add(i, i, this.nr.gmin);
            }

            // Stamp all components
            this._stampAll(G, rhs, components, classified, matSize);

            // Solve
            const A = G.toDense();
            const x = SparseMatrix.solve(A, Array.from(rhs));

            // Check convergence
            if (iter > 0 && this.nr.converged(x, this.nodeVoltages)) {
                convergedIter = true;
                this.nodeVoltages = new Float64Array(x);
                this.solverStats.iterations = iter + 1;
                break;
            }
            this.nodeVoltages = new Float64Array(x);
        }
        this.solverStats.converged = convergedIter;
        this.converged = convergedIter;

        // Extract results into v3-compatible format
        const result = this._extractResults(components, wires, classified, hasAC, acFreq);

        // Adaptive timestep
        if (hasAC) {
            const capDerivs = [];
            this.capStates.forEach((v, id) => capDerivs.push(v));
            const capVoltages = [];
            this.capStates.forEach((v, id) => capVoltages.push(v));
            if (capVoltages.length > 0) {
                this.dt = this.tsControl.adapt(capVoltages, capDerivs, this.dt);
            }
        }
        this.solverStats.dtUsed = this.dt;

        return result;
    }

    _classify(components) {
        const c = {
            resistors: [], capacitors: [], inductors: [],
            dcSources: [], acSources: [], diodes: [],
            transistors: [], loads: [], transformers: [],
            teslaCoils: [], meters: [], zpeSources: [],
            switches: [], fuses: [], opAmps: [],
            // v5.0 additions
            jfets: [], pmosfets: [], igbts: [],
            scrs: [], timers555: [], regulators: [],
            schottkyDiodes: [], zenerDiodes: [], photodiodes: [],
            currentSources: []
        };
        for (const comp of components) {
            if (comp.type === 'wire' || comp.type === 'ground') continue;
            if (comp.type === 'battery') c.dcSources.push(comp);
            else if (['ac_source','signal_generator','rf_generator'].includes(comp.type)) c.acSources.push(comp);
            else if (['resistor','potentiometer','thermistor'].includes(comp.type)) c.resistors.push(comp);
            else if (['capacitor','high_voltage_cap','leyden_jar'].includes(comp.type)) c.capacitors.push(comp);
            else if (['inductor','bifilar_coil','caduceus_coil','toroidal_inductor','tesla_primary','tesla_secondary'].includes(comp.type)) c.inductors.push(comp);
            else if (comp.type === 'diode' || comp.type === 'led') c.diodes.push(comp);
            else if (comp.type === 'zener_diode') c.zenerDiodes.push(comp);
            else if (comp.type === 'schottky_diode') c.schottkyDiodes.push(comp);
            else if (comp.type === 'photodiode') c.photodiodes.push(comp);
            else if (['npn_transistor','pnp_transistor'].includes(comp.type)) c.transistors.push(comp);
            else if (comp.type === 'mosfet_n') c.transistors.push(comp);
            else if (comp.type === 'mosfet_p') c.pmosfets.push(comp);
            else if (['jfet_n','jfet_p'].includes(comp.type)) c.jfets.push(comp);
            else if (comp.type === 'igbt') c.igbts.push(comp);
            else if (comp.type === 'op_amp') c.opAmps.push(comp);
            else if (['bulb','motor','buzzer','speaker'].includes(comp.type)) c.loads.push(comp);
            else if (comp.type === 'transformer') c.transformers.push(comp);
            else if (['top_load','spark_gap'].includes(comp.type)) c.teslaCoils.push(comp);
            else if (['voltmeter','ammeter','wattmeter','oscilloscope','em_field_probe','spectrum_analyzer'].includes(comp.type)) c.meters.push(comp);
            else if (['casimir_plates','crystal_cell','schumann_antenna'].includes(comp.type)) c.zpeSources.push(comp);
            else if (['switch','push_button','reed_switch'].includes(comp.type)) c.switches.push(comp);
            else if (comp.type === 'fuse') c.fuses.push(comp);
            else if (comp.type === 'thyristor') c.scrs.push(comp);
            else if (comp.type === 'ic_555') c.timers555.push(comp);
            else if (comp.type === 'voltage_regulator') c.regulators.push(comp);
            else if (comp.type === 'current_source') c.currentSources.push(comp);
        }
        return c;
    }

    _stampAll(G, rhs, components, classified, matSize) {
        const stamper = this.stamper;

        // Stamp resistors
        for (const r of classified.resistors) {
            const [n1, n2] = stamper.getNodes(r);
            const g = 1 / Math.max(r.value, 0.001);
            stamper.stampConductance(G, n1, n2, g);
        }

        // Stamp loads as resistances
        for (const ld of classified.loads) {
            const [n1, n2] = stamper.getNodes(ld);
            let R;
            if (ld.type === 'led') R = 20;
            else if (ld.type === 'bulb') R = Math.max(ld.value, 10);
            else if (ld.type === 'motor') R = 50;
            else if (ld.type === 'buzzer' || ld.type === 'speaker') R = 32;
            else R = 100;
            stamper.stampConductance(G, n1, n2, 1 / R);
        }

        // Stamp switches
        for (const sw of classified.switches) {
            const [n1, n2] = stamper.getNodes(sw);
            if (sw.closed) {
                stamper.stampConductance(G, n1, n2, 1 / 0.01); // near-zero resistance when closed
            } else {
                stamper.stampConductance(G, n1, n2, 1e-12); // very high resistance when open
            }
        }

        // Stamp fuses
        for (const f of classified.fuses) {
            const [n1, n2] = stamper.getNodes(f);
            if (f.blown) {
                stamper.stampConductance(G, n1, n2, 1e-12);
            } else {
                stamper.stampConductance(G, n1, n2, 1 / 0.01);
            }
        }

        // Stamp DC voltage sources
        for (const vs of classified.dcSources) {
            const [n1, n2] = stamper.getNodes(vs);
            const branchIdx = stamper.vsrcMap.get(vs.id);
            if (branchIdx != null) {
                stamper.stampVoltageSource(G, rhs, n1, n2, branchIdx, vs.value, matSize);
            }
        }

        // Stamp AC voltage sources
        for (const vs of classified.acSources) {
            const [n1, n2] = stamper.getNodes(vs);
            const branchIdx = stamper.vsrcMap.get(vs.id);
            const omega = 2 * Math.PI * (vs.frequency || 60);
            const voltage = vs.value * Math.sin(omega * this.time);
            if (branchIdx != null) {
                stamper.stampVoltageSource(G, rhs, n1, n2, branchIdx, voltage, matSize);
            }
        }

        // Stamp capacitors (trapezoidal companion model)
        for (const cap of classified.capacitors) {
            if (cap.value <= 0) continue;
            const [n1, n2] = stamper.getNodes(cap);
            const C = cap.value;
            const dt = this.dt;
            // Trapezoidal: i = (2C/dt)(v_n+1 - v_n) + i_n
            // Companion: G_eq = 2C/dt, I_eq = (2C/dt)*v_n + i_n
            const Geq = 2 * C / dt;
            const prevV = this.capStates.get(cap.id) || 0;
            const prevI = Geq * prevV; // previous companion current
            stamper.stampConductance(G, n1, n2, Geq);
            // Equivalent current source
            if (n1 >= 0) rhs[n1] += prevI;
            if (n2 >= 0) rhs[n2] -= prevI;
        }

        // Stamp inductors (trapezoidal companion model)
        for (const ind of classified.inductors) {
            if (ind.value <= 0) continue;
            const [n1, n2] = stamper.getNodes(ind);
            const L = ind.value;
            const dt = this.dt;
            // Trapezoidal: v = (2L/dt)(i_n+1 - i_n) + v_n
            // Companion: G_eq = dt/(2L), I_eq = i_n + (dt/(2L))*v_n
            const Geq = dt / (2 * L);
            const prevI = this.indStates.get(ind.id) || 0;
            stamper.stampConductance(G, n1, n2, Geq);
            // Equivalent current source
            if (n1 >= 0) rhs[n1] += prevI;
            if (n2 >= 0) rhs[n2] -= prevI;
        }

        // Stamp diodes (Newton-Raphson linearization)
        for (const d of classified.diodes) {
            const [n1, n2] = stamper.getNodes(d);
            const Vd = (n1 >= 0 ? this.nodeVoltages[n1] : 0) - (n2 >= 0 ? this.nodeVoltages[n2] : 0);
            const Is = 1e-12;
            const N = d.type === 'led' ? 2 : 1;
            const { Gd, Ieq } = this.nr.diodeCompanion(Vd, Is, N, PHYS.V_T);
            stamper.stampConductance(G, n1, n2, Gd);
            stamper.stampCurrentSource(rhs, n1, n2, Ieq);
        }

        // Stamp BJTs (simplified Ebers-Moll linearization)
        for (const tr of classified.transistors) {
            if (tr.type === 'mosfet_n') {
                const [nG, nD, nS] = stamper.getNodes(tr);
                const Vgs = (nG >= 0 ? this.nodeVoltages[nG] : 0) - (nS >= 0 ? this.nodeVoltages[nS] : 0);
                const Vds = (nD >= 0 ? this.nodeVoltages[nD] : 0) - (nS >= 0 ? this.nodeVoltages[nS] : 0);
                const { Id, gm, gds } = this.nr.mosfetCompanion(Vgs, Vds, 0.5, 2, 0.01);
                // Stamp drain-source conductance
                stamper.stampConductance(G, nD, nS, gds);
                // Stamp transconductance (gm * Vgs → current from D to S)
                if (nD >= 0 && nG >= 0) { G.add(nD, nG, gm); G.add(nD, nS >= 0 ? nS : nD, -gm); }
                if (nS >= 0 && nG >= 0) { G.add(nS, nG, -gm); G.add(nS, nS, gm); }
                // Norton equivalent current
                const Ieq = Id - gm * Vgs - gds * Vds;
                stamper.stampCurrentSource(rhs, nS, nD, Ieq);
            } else {
                // BJT: terminals are [base, collector, emitter] mapped to [n0, n1, n2]
                const nodes = stamper.getNodes(tr);
                const nB = nodes[0], nC = nodes[1], nE = nodes[2];
                const Vbe = (nB >= 0 ? this.nodeVoltages[nB] : 0) - (nE >= 0 ? this.nodeVoltages[nE] : 0);
                const Vbc = (nB >= 0 ? this.nodeVoltages[nB] : 0) - (nC >= 0 ? this.nodeVoltages[nC] : 0);
                const beta = tr.value || 100;
                const { gbe, gbc, gm } = this.nr.bjtCompanion(Vbe, Vbc, beta, 1e-14);
                stamper.stampConductance(G, nB, nE, gbe);
                stamper.stampConductance(G, nB, nC, gbc);
                // Transconductance
                if (nC >= 0 && nB >= 0) { G.add(nC, nB, gm); if (nE >= 0) G.add(nC, nE, -gm); }
                if (nE >= 0 && nB >= 0) { G.add(nE, nB, -gm); G.add(nE, nE, gm); }
            }
        }

        // Stamp op-amps (VCVS model with large gain)
        for (const op of classified.opAmps) {
            const nodes = stamper.getNodes(op);
            const nInv = nodes[0], nOut = nodes[1], nNonInv = nodes[2];
            const gain = op.value || 100000;
            // Simplified: output drives like voltage source = gain * (V+ - V-)
            // Use a large conductance approach
            const gLarge = 1e6;
            if (nOut >= 0) {
                G.add(nOut, nOut, gLarge);
                if (nNonInv >= 0) G.add(nOut, nNonInv, -gLarge * gain / (gain + 1));
                if (nInv >= 0) G.add(nOut, nInv, gLarge * gain / (gain + 1));
            }
        }

        // Stamp meters (very high impedance)
        for (const m of classified.meters) {
            const [n1, n2] = stamper.getNodes(m);
            if (m.type === 'voltmeter') {
                stamper.stampConductance(G, n1, n2, 1e-12); // near-infinite resistance
            } else if (m.type === 'ammeter') {
                stamper.stampConductance(G, n1, n2, 1 / 0.001); // near-zero resistance
            } else if (m.type === 'wattmeter') {
                stamper.stampConductance(G, n1, n2, 1e-12);
            }
        }

        // Stamp ZPE sources as current sources
        for (const zpe of classified.zpeSources) {
            const nodes = stamper.getNodes(zpe);
            if (zpe.type === 'crystal_cell') {
                const [n1, n2] = nodes;
                const v = zpe.value || 0.4;
                const i = v / 100000;
                stamper.stampCurrentSource(rhs, n2, n1, i);
                stamper.stampConductance(G, n1, n2, 1/100000);
            }
        }

        // ── v5.0 Component Stamps ──

        // Stamp Zener diodes (NR with breakdown)
        for (const z of classified.zenerDiodes) {
            const [n1, n2] = stamper.getNodes(z);
            const Vd = (n1 >= 0 ? this.nodeVoltages[n1] : 0) - (n2 >= 0 ? this.nodeVoltages[n2] : 0);
            const Vz = z.value || 5.1; // rated Zener voltage
            const { Gd, Ieq } = this.nr.zenerCompanion(Vd, Vz);
            stamper.stampConductance(G, n1, n2, Gd);
            stamper.stampCurrentSource(rhs, n1, n2, Ieq);
        }

        // Stamp Schottky diodes
        for (const sd of classified.schottkyDiodes) {
            const [n1, n2] = stamper.getNodes(sd);
            const Vd = (n1 >= 0 ? this.nodeVoltages[n1] : 0) - (n2 >= 0 ? this.nodeVoltages[n2] : 0);
            const { Gd, Ieq } = this.nr.schottkyCompanion(Vd);
            stamper.stampConductance(G, n1, n2, Gd);
            stamper.stampCurrentSource(rhs, n1, n2, Ieq);
        }

        // Stamp Photodiodes
        for (const pd of classified.photodiodes) {
            const [n1, n2] = stamper.getNodes(pd);
            const Vd = (n1 >= 0 ? this.nodeVoltages[n1] : 0) - (n2 >= 0 ? this.nodeVoltages[n2] : 0);
            const irradiance = pd.irradiance || 0.5;
            const { Gd, Ieq } = this.nr.photodiodeCompanion(Vd, irradiance);
            stamper.stampConductance(G, n1, n2, Gd);
            stamper.stampCurrentSource(rhs, n1, n2, Ieq);
        }

        // Stamp P-MOSFETs (3 terminal: gate, drain, source)
        for (const pm of classified.pmosfets) {
            const [nG, nD, nS] = stamper.getNodes(pm);
            const Vsg = (nS >= 0 ? this.nodeVoltages[nS] : 0) - (nG >= 0 ? this.nodeVoltages[nG] : 0);
            const Vsd = (nS >= 0 ? this.nodeVoltages[nS] : 0) - (nD >= 0 ? this.nodeVoltages[nD] : 0);
            const { Id, gm, gds } = this.nr.pmosfetCompanion(Vsg, Vsd);
            // Current flows S→D in P-channel
            stamper.stampConductance(G, nS, nD, gds);
            if (nS >= 0 && nG >= 0) { G.add(nS, nG, -gm); G.add(nS, nS, gm); }
            if (nD >= 0 && nG >= 0) { G.add(nD, nG, gm); G.add(nD, nD >= 0 ? nD : 0, -gm); }
            const Ieq = Id - gm * Vsg - gds * Vsd;
            stamper.stampCurrentSource(rhs, nD, nS, Ieq);
        }

        // Stamp JFETs (3 terminal: gate, drain, source)
        for (const jf of classified.jfets) {
            const [nG, nD, nS] = stamper.getNodes(jf);
            const flip = jf.type === 'jfet_p' ? -1 : 1;
            const Vgs = flip * ((nG >= 0 ? this.nodeVoltages[nG] : 0) - (nS >= 0 ? this.nodeVoltages[nS] : 0));
            const Vds = flip * ((nD >= 0 ? this.nodeVoltages[nD] : 0) - (nS >= 0 ? this.nodeVoltages[nS] : 0));
            const Idss = jf.value || 0.01;
            const Vp = jf.type === 'jfet_p' ? 4 : -4;
            const { Id, gm, gds } = this.nr.jfetCompanion(Vgs, Vds, Idss, Vp);
            stamper.stampConductance(G, nD, nS, gds);
            if (nD >= 0 && nG >= 0) { G.add(nD, nG, gm * flip); if (nS >= 0) G.add(nD, nS, -gm * flip); }
            if (nS >= 0 && nG >= 0) { G.add(nS, nG, -gm * flip); G.add(nS, nS, gm * flip); }
            const Ieq = (Id - gm * Vgs - gds * Vds) * flip;
            stamper.stampCurrentSource(rhs, nS, nD, Ieq);
        }

        // Stamp IGBTs (3 terminal: gate, collector, emitter)
        for (const ig of classified.igbts) {
            const [nG, nC, nE] = stamper.getNodes(ig);
            const Vge = (nG >= 0 ? this.nodeVoltages[nG] : 0) - (nE >= 0 ? this.nodeVoltages[nE] : 0);
            const Vce = (nC >= 0 ? this.nodeVoltages[nC] : 0) - (nE >= 0 ? this.nodeVoltages[nE] : 0);
            const { Ic, gm, gce } = this.nr.igbtCompanion(Vge, Vce);
            stamper.stampConductance(G, nC, nE, gce);
            if (nC >= 0 && nG >= 0) { G.add(nC, nG, gm); if (nE >= 0) G.add(nC, nE, -gm); }
            if (nE >= 0 && nG >= 0) { G.add(nE, nG, -gm); G.add(nE, nE, gm); }
            const Ieq = Ic - gm * Vge - gce * Vce;
            stamper.stampCurrentSource(rhs, nE, nC, Ieq);
        }

        // Stamp SCR / Thyristors (3 terminal: anode, cathode, gate)
        for (const scr of classified.scrs) {
            const [nA, nK, nG_] = stamper.getNodes(scr);
            const Vak = (nA >= 0 ? this.nodeVoltages[nA] : 0) - (nK >= 0 ? this.nodeVoltages[nK] : 0);
            const Ig = nG_ >= 0 ? (this.nodeVoltages[nG_] || 0) * 0.01 : 0;
            const { Gak, Ieq } = this.nr.scrCompanion(Vak, Ig);
            stamper.stampConductance(G, nA, nK, Gak);
            stamper.stampCurrentSource(rhs, nA, nK, Ieq);
        }

        // Stamp 555 Timers (3 terminal: Vcc, output, ground/trigger)
        for (const t5 of classified.timers555) {
            const [nVcc, nOut, nTrig] = stamper.getNodes(t5);
            const Vcc = nVcc >= 0 ? this.nodeVoltages[nVcc] : 5;
            const Vtrig = nTrig >= 0 ? this.nodeVoltages[nTrig] : 0;
            const Vthresh = Vtrig; // simplified — threshold tracks trigger
            const state = this._timer555States ? this._timer555States.get(t5.id) : null;
            const result = this.nr.ic555Model(Vtrig, Vthresh, Vcc, state);
            if (!this._timer555States) this._timer555States = new Map();
            this._timer555States.set(t5.id, result);
            // Drive output as a voltage source via conductance
            if (nOut >= 0) {
                const gDrive = 100; // strong output drive
                G.add(nOut, nOut, gDrive);
                rhs[nOut] += gDrive * result.output;
            }
        }

        // Stamp Voltage Regulators (3 terminal: Vin, Vout, GND)
        for (const reg of classified.regulators) {
            const [nIn, nOut, nGnd] = stamper.getNodes(reg);
            const Vin = (nIn >= 0 ? this.nodeVoltages[nIn] : 0) - (nGnd >= 0 ? this.nodeVoltages[nGnd] : 0);
            const Vout_set = reg.value || 5; // target output voltage
            const Iload = 0.01; // estimated
            const { Vout, Gout } = this.nr.regulatorModel(Vin, Vout_set, Iload);
            // Drive output node to regulated voltage
            if (nOut >= 0) {
                const gDrive = Gout;
                const target = (nGnd >= 0 ? this.nodeVoltages[nGnd] : 0) + Vout;
                G.add(nOut, nOut, gDrive);
                rhs[nOut] += gDrive * target;
            }
            // Input draws current
            if (nIn >= 0) {
                stamper.stampConductance(G, nIn, nGnd >= 0 ? nGnd : -1, 1/100);
            }
        }

        // Stamp current sources
        for (const cs of classified.currentSources) {
            const [n1, n2] = stamper.getNodes(cs);
            stamper.stampCurrentSource(rhs, n1, n2, cs.value || 0.01);
        }
    }

    _extractResults(components, wires, classified, hasAC, acFreq) {
        // Build a v3-compatible result object
        const result = {
            totalVoltage: 0, totalCurrent: 0, totalPower: 0, totalResistance: 0,
            impedance: null, resonantFreq: null, qualityFactor: null,
            frequency: hasAC ? acFreq : null, phaseAngle: null,
            componentData: new Map(), formulas: [], warnings: [],
            nodeCount: this.stamper.nodeCount + 1, edgeCount: 0,
            parallelGroups: [], bodeData: null, fftData: null,
            transientData: null,
            // v4.0 additions
            nodeVoltages: this.nodeVoltages ? Array.from(this.nodeVoltages) : [],
            solverStats: { ...this.solverStats },
            mnaActive: true
        };

        const stamper = this.stamper;

        // Check open switches
        for (const sw of classified.switches) {
            if (!sw.closed) result.warnings.push('Open switch detected');
        }
        for (const f of classified.fuses) {
            if (f.blown) result.warnings.push('Blown fuse');
        }

        // Extract per-component data from node voltages
        let maxVoltage = 0, maxCurrent = 0;

        // Voltage sources: get branch currents
        for (const vs of [...classified.dcSources, ...classified.acSources]) {
            const branchIdx = stamper.vsrcMap.get(vs.id);
            const [n1, n2] = stamper.getNodes(vs);
            const vn1 = n1 >= 0 ? this.nodeVoltages[n1] : 0;
            const vn2 = n2 >= 0 ? this.nodeVoltages[n2] : 0;
            const branchI = branchIdx != null && (stamper.nodeCount + branchIdx) < this.nodeVoltages.length
                ? this.nodeVoltages[stamper.nodeCount + branchIdx] : 0;
            const power = Math.abs(vs.value * branchI);
            result.componentData.set(vs.id, { voltage: vs.value, current: Math.abs(branchI), power, on: true });
            maxVoltage = Math.max(maxVoltage, Math.abs(vs.value));
            maxCurrent = Math.max(maxCurrent, Math.abs(branchI));
            if (vs.type === 'battery') {
                result.formulas.push({ name: 'DC Source', value: vs.value + ' V' });
            } else {
                result.formulas.push({ name: 'AC Source', value: vs.value.toFixed(1) + 'V @ ' + (vs.frequency || 60) + 'Hz' });
            }
        }

        // Resistors
        for (const r of classified.resistors) {
            const [n1, n2] = stamper.getNodes(r);
            const v1 = n1 >= 0 ? this.nodeVoltages[n1] : 0;
            const v2 = n2 >= 0 ? this.nodeVoltages[n2] : 0;
            const vDrop = v1 - v2;
            const current = vDrop / Math.max(r.value, 0.001);
            result.componentData.set(r.id, { voltage: Math.abs(vDrop), current: Math.abs(current), power: Math.abs(vDrop * current) });
        }

        // Capacitors: update state
        for (const cap of classified.capacitors) {
            if (cap.value <= 0) continue;
            const [n1, n2] = stamper.getNodes(cap);
            const v1 = n1 >= 0 ? this.nodeVoltages[n1] : 0;
            const v2 = n2 >= 0 ? this.nodeVoltages[n2] : 0;
            const vCap = v1 - v2;
            const prevV = this.capStates.get(cap.id) || 0;
            const current = 2 * cap.value / this.dt * (vCap - prevV);
            this.capStates.set(cap.id, vCap);
            result.componentData.set(cap.id, {
                voltage: vCap, current: Math.abs(current),
                energy: 0.5 * cap.value * vCap * vCap,
                charging: Math.abs(vCap) > Math.abs(prevV)
            });
        }

        // Inductors: update state
        for (const ind of classified.inductors) {
            if (ind.value <= 0) continue;
            const [n1, n2] = stamper.getNodes(ind);
            const v1 = n1 >= 0 ? this.nodeVoltages[n1] : 0;
            const v2 = n2 >= 0 ? this.nodeVoltages[n2] : 0;
            const vInd = v1 - v2;
            const prevI = this.indStates.get(ind.id) || 0;
            const current = prevI + this.dt / (2 * ind.value) * vInd;
            this.indStates.set(ind.id, current);
            result.componentData.set(ind.id, {
                voltage: vInd, current: Math.abs(current),
                energy: 0.5 * ind.value * current * current,
                energizing: Math.abs(current) > Math.abs(prevI)
            });
        }

        // Diodes
        for (const d of classified.diodes) {
            const [n1, n2] = stamper.getNodes(d);
            const v1 = n1 >= 0 ? this.nodeVoltages[n1] : 0;
            const v2 = n2 >= 0 ? this.nodeVoltages[n2] : 0;
            const Vd = v1 - v2;
            const Is = 1e-12;
            const N = d.type === 'led' ? 2 : 1;
            const Id = Is * (Math.exp(Math.min(Vd / (N * PHYS.V_T), 40)) - 1);
            result.componentData.set(d.id, {
                current: Id, voltage: Vd, power: Math.abs(Vd * Id),
                on: Vd > (d.value || 0.7) * 0.5, forward: Vd > 0,
                dynamicR: N * PHYS.V_T / Math.max(Id, 1e-12)
            });
            result.formulas.push({ name: d.type + ' Id', value: (Id * 1000).toFixed(3) + ' mA' });
        }

        // Transistors
        for (const tr of classified.transistors) {
            const nodes = stamper.getNodes(tr);
            if (tr.type === 'mosfet_n') {
                const [nG, nD, nS] = nodes;
                const Vgs = (nG >= 0 ? this.nodeVoltages[nG] : 0) - (nS >= 0 ? this.nodeVoltages[nS] : 0);
                const Vds = (nD >= 0 ? this.nodeVoltages[nD] : 0) - (nS >= 0 ? this.nodeVoltages[nS] : 0);
                const { Id } = this.nr.mosfetCompanion(Vgs, Vds, 0.5, 2, 0.01);
                result.componentData.set(tr.id, {
                    current: Id, voltage: Vds, power: Math.abs(Id * Vds),
                    Vgs, mode: Vgs <= 2 ? 'Off' : (Vds < Vgs - 2 ? 'Linear' : 'Saturation')
                });
                result.formulas.push({ name: 'MOSFET Id', value: (Id * 1000).toFixed(2) + ' mA' });
            } else {
                const [nB, nC, nE] = nodes;
                const Vbe = (nB >= 0 ? this.nodeVoltages[nB] : 0) - (nE >= 0 ? this.nodeVoltages[nE] : 0);
                const Vbc = (nB >= 0 ? this.nodeVoltages[nB] : 0) - (nC >= 0 ? this.nodeVoltages[nC] : 0);
                const Vce = (nC >= 0 ? this.nodeVoltages[nC] : 0) - (nE >= 0 ? this.nodeVoltages[nE] : 0);
                const beta = tr.value || 100;
                const { Ic, Ib, Ie } = this.nr.bjtCompanion(Vbe, Vbc, beta, 1e-14);
                const mode = Vce > 0.2 ? 'Active' : 'Saturation';
                result.componentData.set(tr.id, {
                    current: Math.abs(Ic), voltage: Vce, power: Math.abs(Ic * Vce),
                    Ib: Math.abs(Ib), Ic: Math.abs(Ic), Ie: Math.abs(Ie), beta, mode
                });
                result.formulas.push(
                    { name: 'BJT Ic', value: (Math.abs(Ic) * 1000).toFixed(2) + ' mA' },
                    { name: 'BJT Mode', value: mode }
                );
            }
        }

        // Loads
        for (const ld of classified.loads) {
            if (result.componentData.has(ld.id)) continue;
            const [n1, n2] = stamper.getNodes(ld);
            const v1 = n1 >= 0 ? this.nodeVoltages[n1] : 0;
            const v2 = n2 >= 0 ? this.nodeVoltages[n2] : 0;
            const vDrop = Math.abs(v1 - v2);
            let R = 100;
            if (ld.type === 'led') R = 20;
            else if (ld.type === 'bulb') R = Math.max(ld.value, 10);
            else if (ld.type === 'motor') R = 50;
            else if (ld.type === 'buzzer' || ld.type === 'speaker') R = 32;
            const current = vDrop / R;
            result.componentData.set(ld.id, {
                voltage: vDrop, current, power: vDrop * current,
                on: current > 0.001
            });
        }

        // Meters
        for (const m of classified.meters) {
            const [n1, n2] = stamper.getNodes(m);
            const v1 = n1 >= 0 ? this.nodeVoltages[n1] : 0;
            const v2 = n2 >= 0 ? this.nodeVoltages[n2] : 0;
            if (m.type === 'voltmeter') {
                result.componentData.set(m.id, { voltage: Math.abs(v1 - v2), reading: Math.abs(v1 - v2), current: 0 });
            } else if (m.type === 'ammeter') {
                const I = Math.abs(v1 - v2) / 0.001;
                result.componentData.set(m.id, { current: I, reading: I });
            }
        }

        // ZPE sources
        for (const zpe of classified.zpeSources) {
            if (zpe.type === 'casimir_plates') {
                const gap = zpe.value || 1e-7;
                const area = zpe.area || 0.01;
                const cF = FORMULAS.casimirForce.calc(area, gap);
                const cE = FORMULAS.casimirEnergy.calc(area, gap);
                result.formulas.push(
                    { name: 'Casimir Force', value: Math.abs(cF).toExponential(3) + ' N' },
                    { name: 'Casimir Energy', value: Math.abs(cE).toExponential(3) + ' J' }
                );
                result.componentData.set(zpe.id, { force: cF, energy: cE, voltage: Math.abs(cE) * 1e12, current: 0 });
            } else if (zpe.type === 'schumann_antenna') {
                const sn = zpe.value || 1;
                const sf = FORMULAS.schumannResonance.calc(sn);
                result.formulas.push({ name: 'Schumann f' + sn, value: sf.toFixed(2) + ' Hz' });
                result.componentData.set(zpe.id, { frequency: sf, voltage: 0.001, current: 1e-9 });
            } else if (zpe.type === 'crystal_cell') {
                const [n1, n2] = stamper.getNodes(zpe);
                const v1 = n1 >= 0 ? this.nodeVoltages[n1] : 0;
                const v2 = n2 >= 0 ? this.nodeVoltages[n2] : 0;
                const cV = Math.abs(v1 - v2) || zpe.value || 0.4;
                result.formulas.push({ name: 'Crystal Cell', value: cV.toFixed(3) + ' V' });
                result.componentData.set(zpe.id, { voltage: cV, current: cV / 100000, power: cV * cV / 100000, on: true });
            }
        }

        // Zener Diodes (v5.0)
        for (const z of classified.zenerDiodes) {
            const [n1, n2] = stamper.getNodes(z);
            const v1 = n1 >= 0 ? this.nodeVoltages[n1] : 0;
            const v2 = n2 >= 0 ? this.nodeVoltages[n2] : 0;
            const Vd = v1 - v2;
            const Vz = z.value || 5.1;
            const { Id } = this.nr.zenerCompanion(Vd, Vz);
            const mode = Vd >= 0 ? 'Forward' : (-Vd >= Vz * 0.95 ? 'Breakdown' : 'Reverse');
            result.componentData.set(z.id, {
                current: Math.abs(Id), voltage: Vd, power: Math.abs(Vd * Id),
                on: Math.abs(Id) > 0.001, mode, Vz
            });
            result.formulas.push({ name: 'Zener ' + Vz + 'V', value: mode + ' | ' + (Math.abs(Id) * 1000).toFixed(2) + ' mA' });
        }

        // Schottky Diodes (v5.0)
        for (const sd of classified.schottkyDiodes) {
            const [n1, n2] = stamper.getNodes(sd);
            const Vd = (n1 >= 0 ? this.nodeVoltages[n1] : 0) - (n2 >= 0 ? this.nodeVoltages[n2] : 0);
            const { Id } = this.nr.schottkyCompanion(Vd);
            result.componentData.set(sd.id, {
                current: Math.abs(Id), voltage: Vd, power: Math.abs(Vd * Id),
                on: Vd > 0.2, forward: Vd > 0, Vf: 0.25
            });
            result.formulas.push({ name: 'Schottky Id', value: (Math.abs(Id) * 1000).toFixed(2) + ' mA (Vf≈0.25V)' });
        }

        // Photodiodes (v5.0)
        for (const pd of classified.photodiodes) {
            const [n1, n2] = stamper.getNodes(pd);
            const Vd = (n1 >= 0 ? this.nodeVoltages[n1] : 0) - (n2 >= 0 ? this.nodeVoltages[n2] : 0);
            const irr = pd.irradiance || 0.5;
            const { Id } = this.nr.photodiodeCompanion(Vd, irr);
            result.componentData.set(pd.id, {
                current: Math.abs(Id), voltage: Vd, power: Math.abs(Vd * Id),
                irradiance: irr, photocurrent: irr * 1e-3,
                mode: Vd <= 0 ? 'Photoconductive' : 'Photovoltaic'
            });
            result.formulas.push({ name: 'Photodiode', value: (irr * 1e-3 * 1e6).toFixed(1) + ' µA photo | ' + (Vd <= 0 ? 'PC' : 'PV') + ' mode' });
        }

        // P-MOSFETs (v5.0)
        for (const pm of classified.pmosfets) {
            const [nG, nD, nS] = stamper.getNodes(pm);
            const Vsg = (nS >= 0 ? this.nodeVoltages[nS] : 0) - (nG >= 0 ? this.nodeVoltages[nG] : 0);
            const Vsd = (nS >= 0 ? this.nodeVoltages[nS] : 0) - (nD >= 0 ? this.nodeVoltages[nD] : 0);
            const { Id } = this.nr.pmosfetCompanion(Vsg, Vsd);
            const mode = Vsg <= 2 ? 'Off' : (Vsd < Vsg - 2 ? 'Linear' : 'Saturation');
            result.componentData.set(pm.id, {
                current: Id, voltage: Vsd, power: Math.abs(Id * Vsd), Vsg, mode
            });
            result.formulas.push({ name: 'P-MOS Id', value: (Id * 1000).toFixed(2) + ' mA (' + mode + ')' });
        }

        // JFETs (v5.0)
        for (const jf of classified.jfets) {
            const [nG, nD, nS] = stamper.getNodes(jf);
            const Vgs = (nG >= 0 ? this.nodeVoltages[nG] : 0) - (nS >= 0 ? this.nodeVoltages[nS] : 0);
            const Vds = (nD >= 0 ? this.nodeVoltages[nD] : 0) - (nS >= 0 ? this.nodeVoltages[nS] : 0);
            const Idss = jf.value || 0.01;
            const Vp = jf.type === 'jfet_p' ? 4 : -4;
            const { Id } = this.nr.jfetCompanion(Vgs, Vds, Idss, Vp);
            const mode = (jf.type === 'jfet_n' ? Vgs <= Vp : Vgs >= -Vp) ? 'Cutoff' : 'Active';
            result.componentData.set(jf.id, {
                current: Id, voltage: Vds, power: Math.abs(Id * Vds),
                Vgs, Idss, Vp, channel: jf.type === 'jfet_p' ? 'P' : 'N', mode
            });
            result.formulas.push({ name: (jf.type === 'jfet_p' ? 'P' : 'N') + '-JFET Id', value: (Id * 1000).toFixed(2) + ' mA' });
        }

        // IGBTs (v5.0)
        for (const ig of classified.igbts) {
            const [nG, nC, nE] = stamper.getNodes(ig);
            const Vge = (nG >= 0 ? this.nodeVoltages[nG] : 0) - (nE >= 0 ? this.nodeVoltages[nE] : 0);
            const Vce = (nC >= 0 ? this.nodeVoltages[nC] : 0) - (nE >= 0 ? this.nodeVoltages[nE] : 0);
            const { Ic } = this.nr.igbtCompanion(Vge, Vce);
            result.componentData.set(ig.id, {
                current: Ic, voltage: Vce, power: Math.abs(Ic * Vce),
                Vge, mode: Vge > 5 ? 'ON' : 'OFF'
            });
            result.formulas.push({ name: 'IGBT Ic', value: (Ic * 1000).toFixed(1) + ' mA (' + (Vge > 5 ? 'ON' : 'OFF') + ')' });
        }

        // SCR / Thyristors (v5.0)
        for (const scr of classified.scrs) {
            const [nA, nK, nG_] = stamper.getNodes(scr);
            const Vak = (nA >= 0 ? this.nodeVoltages[nA] : 0) - (nK >= 0 ? this.nodeVoltages[nK] : 0);
            const Ig = nG_ >= 0 ? (this.nodeVoltages[nG_] || 0) * 0.01 : 0;
            const { Ia, latched } = this.nr.scrCompanion(Vak, Ig);
            result.componentData.set(scr.id, {
                current: Math.abs(Ia), voltage: Vak, power: Math.abs(Ia * Vak),
                latched, mode: latched ? 'Conducting' : 'Blocking'
            });
            result.formulas.push({ name: 'SCR', value: (latched ? 'ON ' : 'OFF ') + (Math.abs(Ia) * 1000).toFixed(1) + ' mA' });
        }

        // 555 Timers (v5.0)
        for (const t5 of classified.timers555) {
            const [nVcc, nOut, nTrig] = stamper.getNodes(t5);
            const state = this._timer555States ? this._timer555States.get(t5.id) : null;
            const Vout = state ? state.output : 0;
            const Vcc = nVcc >= 0 ? this.nodeVoltages[nVcc] : 5;
            result.componentData.set(t5.id, {
                voltage: Vout, current: 0.01, power: Vout * 0.01,
                on: Vout > Vcc * 0.5, Vcc,
                discharge: state ? state.discharge : false,
                mode: Vout > Vcc * 0.5 ? 'HIGH' : 'LOW'
            });
            result.formulas.push({ name: '555 Timer', value: (Vout > Vcc * 0.5 ? 'HIGH' : 'LOW') + ' (' + Vout.toFixed(1) + 'V)' });
        }

        // Voltage Regulators (v5.0)
        for (const reg of classified.regulators) {
            const [nIn, nOut, nGnd] = stamper.getNodes(reg);
            const Vin = (nIn >= 0 ? this.nodeVoltages[nIn] : 0) - (nGnd >= 0 ? this.nodeVoltages[nGnd] : 0);
            const Vout_set = reg.value || 5;
            const { Vout, mode: regMode } = this.nr.regulatorModel(Vin, Vout_set, 0.01);
            result.componentData.set(reg.id, {
                voltage: Vout, current: 0.01, power: (Vin - Vout) * 0.01,
                Vin, Vout_set, mode: regMode, dropout: 0.3
            });
            result.formulas.push({ name: 'Regulator', value: Vout.toFixed(2) + 'V (' + regMode + ') dropout=' + (Vin - Vout).toFixed(2) + 'V' });
        }

        // Current Sources (v5.0)
        for (const cs of classified.currentSources) {
            const [n1, n2] = stamper.getNodes(cs);
            const v1 = n1 >= 0 ? this.nodeVoltages[n1] : 0;
            const v2 = n2 >= 0 ? this.nodeVoltages[n2] : 0;
            const I = cs.value || 0.01;
            result.componentData.set(cs.id, {
                voltage: Math.abs(v1 - v2), current: I, power: Math.abs((v1 - v2) * I), on: true
            });
        }

        // Default for any remaining
        for (const comp of components) {
            if (result.componentData.has(comp.id) || comp.type === 'wire' || comp.type === 'ground') continue;
            const [n1, n2] = stamper.getNodes(comp);
            const v1 = n1 >= 0 ? this.nodeVoltages[n1] : 0;
            const v2 = n2 >= 0 ? (this.nodeVoltages[n2] || 0) : 0;
            result.componentData.set(comp.id, { voltage: Math.abs(v1 - v2), current: 0, power: 0, on: false });
        }

        // Compute totals
        let totalV = 0, totalI = 0;
        for (const vs of [...classified.dcSources, ...classified.acSources]) {
            const d = result.componentData.get(vs.id);
            if (d) { totalV += Math.abs(d.voltage); totalI = Math.max(totalI, d.current); }
        }
        result.totalVoltage = totalV;
        result.totalCurrent = totalI;
        result.totalPower = totalV * totalI;

        // Total resistance
        let totalR = 0;
        for (const r of classified.resistors) totalR += r.value;
        result.totalResistance = totalR > 0 ? totalR : (totalI > 0 ? totalV / totalI : 0);

        // AC analysis extras
        if (hasAC) {
            this.frequency = acFreq;
            result.frequency = acFreq;
            let totalC = 0, totalL = 0;
            for (const c of classified.capacitors) totalC += c.value;
            for (const l of classified.inductors) totalL += l.value;
            if (totalC > 0 || totalL > 0) {
                const omega = 2 * Math.PI * acFreq;
                const Xc = totalC > 0 ? 1 / (omega * totalC) : 0;
                const Xl = totalL > 0 ? omega * totalL : 0;
                const Z = new Complex(totalR || 1, Xl - Xc);
                result.impedance = Z.mag();
                result.phaseAngle = Z.phase() * 180 / Math.PI;
                result.formulas.push(
                    { name: '|Z|', value: Z.mag().toFixed(2) + ' Ohm' },
                    { name: 'Phase', value: result.phaseAngle.toFixed(1) + ' deg' }
                );
                if (totalC > 0 && totalL > 0) {
                    const f0 = FORMULAS.resonantFrequency.calc(totalL, totalC);
                    const Q = FORMULAS.qualityFactor.calc(totalR || 1, totalL, totalC);
                    result.resonantFreq = f0;
                    result.qualityFactor = Q;
                    result.formulas.push(
                        { name: 'f₀ Resonance', value: f0.toFixed(1) + ' Hz' },
                        { name: 'Q Factor', value: Q.toFixed(2) }
                    );
                }
            }
            // Bode
            if (classified.capacitors.length > 0 || classified.inductors.length > 0) {
                result.bodeData = this.bode.sweep(components);
            }
        }

        // Transformer formulas
        for (const xf of classified.transformers) {
            const t2 = xf.secondary_turns || 1000;
            result.formulas.push(
                { name: 'Turns Ratio', value: '100:' + t2 },
                { name: 'V Secondary', value: (totalV * t2 / 100).toFixed(1) + ' V' }
            );
        }

        // Tesla coil formulas
        const tPrimary = classified.inductors.find(l => l.type === 'tesla_primary');
        const tSecondary = classified.inductors.find(l => l.type === 'tesla_secondary');
        if (tPrimary && tSecondary) {
            let totalC = 0;
            for (const c of classified.capacitors) totalC += c.value;
            if (totalC > 0) {
                const teslaF = FORMULAS.teslaCoilFrequency.calc(tPrimary.value, totalC);
                const topLoad = classified.teslaCoils.find(t => t.type === 'top_load');
                const Cs = topLoad ? FORMULAS.topLoadCapacitance.calc(topLoad.value) : 1e-12;
                const Vout = FORMULAS.teslaCoilVoltage.calc(Math.abs(totalV), totalC, Cs);
                result.formulas.push(
                    { name: 'Tesla f₀', value: teslaF.toFixed(0) + ' Hz' },
                    { name: 'Tesla Vout', value: (Vout / 1000).toFixed(1) + ' kV' }
                );
            }
        }

        // Add Ohm's Law and power formulas
        if (result.totalResistance > 0 && result.totalCurrent > 0) {
            result.formulas.push({ name: "Ohm's Law", value: 'V=' + result.totalVoltage.toFixed(2) + ' I=' + (result.totalCurrent * 1000).toFixed(2) + 'mA R=' + result.totalResistance.toFixed(1) + 'Ω' });
        }
        if (result.totalPower > 0) {
            result.formulas.push({ name: 'Power', value: (result.totalPower * 1000).toFixed(2) + ' mW' });
        }

        // Solver info formula
        result.formulas.push({ name: 'MNA v5.0', value: this.solverStats.matrixSize + '×' + this.solverStats.matrixSize + ' (' + this.solverStats.iterations + ' iter)' });

        // Waveforms for oscilloscope
        if (!this.waveforms.main) this.waveforms.main = [];
        this.waveforms.main.push({ t: this.time, v: result.totalVoltage, i: result.totalCurrent * 1000 });
        if (this.waveforms.main.length > this.maxSamples) this.waveforms.main.shift();

        // FFT
        if (this.waveforms.main.length >= 64 && hasAC) {
            const voltages = this.waveforms.main.slice(-256).map(w => w.v);
            const fftResult = FFT.transform(voltages);
            const spectrum = FFT.powerSpectrum(fftResult, voltages.length);
            this.fftData = spectrum;
            this.spectrumData = { bins: spectrum, sampleRate: 1 / this.dt, dominant: FFT.dominantFrequency(voltages, 1 / this.dt) };
            result.fftData = this.spectrumData;
        }

        return result;
    }

    reset() {
        super.reset();
        this.nodeVoltages = null;
        this.prevNodeVoltages = null;
        this.capStates.clear();
        this.indStates.clear();
        this.tsControl.reset();
        this.converged = false;
    }
}

// ── Export ──
window.CSEngine = {
    Complex: Complex, PHYS: PHYS, FORMULAS: FORMULAS,
    SimulationEngine: SimulationEngine, SimulationEngineV4: SimulationEngineV4,
    Oscilloscope: Oscilloscope, FormulaPanel: FormulaPanel,
    ZPE_TEMPLATES: ZPE_TEMPLATES, EXTENDED_COMP_DEFS: EXTENDED_COMP_DEFS,
    FFT: FFT, BodePlot: BodePlot, TransientEngine: TransientEngine, CircuitGraph: CircuitGraph,
    SparseMatrix: SparseMatrix, MNAStamper: MNAStamper, NRSolver: NRSolver,
    TimestepController: TimestepController, CircuitValidator: CircuitValidator
};
