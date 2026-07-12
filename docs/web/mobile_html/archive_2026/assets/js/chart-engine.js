/**
 * GoSiteMe Chart Engine v1.0
 * ──────────────────────────
 * Lightweight Canvas-based chart library. No dependencies.
 * Supports: line, bar, pie, doughnut, area, sparkline.
 *
 * Usage:
 *   const chart = new ChartEngine(canvasElement, {
 *     type: 'line',
 *     data: { labels: ['Jan','Feb','Mar'], datasets: [{ label: 'Revenue', data: [100,200,150], color: '#00d4ff' }] },
 *     options: { animate: true, grid: true }
 *   });
 *   chart.update({ datasets: [{ data: [120,250,180] }] });
 *
 * @since v14.0
 */
'use strict';

class ChartEngine {
  static COLORS = [
    '#00d4ff', '#ff6b6b', '#51cf66', '#ffd43b', '#cc5de8',
    '#ff922b', '#20c997', '#748ffc', '#f06595', '#a9e34b'
  ];

  constructor(canvas, config = {}) {
    if (typeof canvas === 'string') canvas = document.querySelector(canvas);
    if (!canvas || canvas.tagName !== 'CANVAS') throw new Error('ChartEngine: valid <canvas> required');

    this.canvas = canvas;
    this.ctx = canvas.getContext('2d');
    this.type = config.type || 'line';
    this.data = config.data || { labels: [], datasets: [] };
    this.options = {
      animate:      config.options?.animate ?? true,
      grid:         config.options?.grid ?? true,
      legend:       config.options?.legend ?? true,
      tooltip:      config.options?.tooltip ?? true,
      padding:      config.options?.padding ?? 40,
      fontSize:     config.options?.fontSize ?? 12,
      fontFamily:   config.options?.fontFamily ?? 'system-ui, sans-serif',
      gridColor:    config.options?.gridColor ?? 'rgba(255,255,255,0.08)',
      textColor:    config.options?.textColor ?? 'rgba(255,255,255,0.6)',
      bgColor:      config.options?.bgColor ?? 'transparent',
      yAxisFormat:  config.options?.yAxisFormat ?? null, // (v) => '$' + v
      ...config.options
    };

    this._animFrame = null;
    this._resize();
    this._bindResize();
    this.render();
  }

  /* ── Public API ──────────────────────────────────── */

  update(newData) {
    if (newData.labels) this.data.labels = newData.labels;
    if (newData.datasets) {
      newData.datasets.forEach((ds, i) => {
        if (this.data.datasets[i]) Object.assign(this.data.datasets[i], ds);
        else this.data.datasets.push(ds);
      });
    }
    this.render();
  }

  destroy() {
    cancelAnimationFrame(this._animFrame);
    window.removeEventListener('resize', this._resizeHandler);
    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
  }

  render() {
    this._resize();
    const ctx = this.ctx;
    const { width, height } = this.canvas;

    ctx.clearRect(0, 0, width, height);
    if (this.options.bgColor !== 'transparent') {
      ctx.fillStyle = this.options.bgColor;
      ctx.fillRect(0, 0, width, height);
    }

    switch (this.type) {
      case 'line':  case 'area':  this._drawLineChart(); break;
      case 'bar':                  this._drawBarChart();  break;
      case 'pie': case 'doughnut': this._drawPieChart();  break;
      case 'sparkline':            this._drawSparkline(); break;
    }

    if (this.options.legend && this.type !== 'sparkline') this._drawLegend();
  }

  /* ── Internal Rendering ─────────────────────────── */

  _drawLineChart() {
    const { ctx, data, options } = this;
    const p = options.padding;
    const w = this.canvas.width - p * 2;
    const h = this.canvas.height - p * 2 - (options.legend ? 30 : 0);
    const isArea = this.type === 'area';

    const { min, max, stepSize, steps } = this._calcScale(data.datasets);

    // Grid + Y-axis
    if (options.grid) {
      ctx.strokeStyle = options.gridColor;
      ctx.lineWidth = 1;
      ctx.font = `${options.fontSize}px ${options.fontFamily}`;
      ctx.fillStyle = options.textColor;
      ctx.textAlign = 'right';
      for (let i = 0; i <= steps; i++) {
        const y = p + h - (i / steps) * h;
        ctx.beginPath(); ctx.moveTo(p, y); ctx.lineTo(p + w, y); ctx.stroke();
        const val = min + i * stepSize;
        ctx.fillText(options.yAxisFormat ? options.yAxisFormat(val) : this._formatNum(val), p - 8, y + 4);
      }
    }

    // X-axis labels
    const labels = data.labels || [];
    if (labels.length) {
      ctx.fillStyle = options.textColor;
      ctx.textAlign = 'center';
      const step = Math.ceil(labels.length / (w / 60));
      labels.forEach((lbl, i) => {
        if (i % step === 0) {
          const x = p + (i / Math.max(1, labels.length - 1)) * w;
          ctx.fillText(lbl, x, p + h + 20);
        }
      });
    }

    // Datasets
    data.datasets.forEach((ds, di) => {
      const color = ds.color || ChartEngine.COLORS[di % ChartEngine.COLORS.length];
      const points = ds.data.map((v, i) => ({
        x: p + (i / Math.max(1, ds.data.length - 1)) * w,
        y: p + h - ((v - min) / (max - min || 1)) * h
      }));

      // Area fill
      if (isArea) {
        ctx.beginPath();
        ctx.moveTo(points[0].x, p + h);
        points.forEach(pt => ctx.lineTo(pt.x, pt.y));
        ctx.lineTo(points[points.length - 1].x, p + h);
        ctx.closePath();
        ctx.fillStyle = color + '20';
        ctx.fill();
      }

      // Line
      ctx.beginPath();
      ctx.strokeStyle = color;
      ctx.lineWidth = 2;
      ctx.lineJoin = 'round';
      points.forEach((pt, i) => i === 0 ? ctx.moveTo(pt.x, pt.y) : ctx.lineTo(pt.x, pt.y));
      ctx.stroke();

      // Dots
      points.forEach(pt => {
        ctx.beginPath();
        ctx.arc(pt.x, pt.y, 3, 0, Math.PI * 2);
        ctx.fillStyle = color;
        ctx.fill();
      });
    });
  }

  _drawBarChart() {
    const { ctx, data, options } = this;
    const p = options.padding;
    const w = this.canvas.width - p * 2;
    const h = this.canvas.height - p * 2 - (options.legend ? 30 : 0);
    const numSets = data.datasets.length;
    const numBars = data.labels?.length || data.datasets[0]?.data.length || 0;

    const { min, max, stepSize, steps } = this._calcScale(data.datasets);

    // Grid + Y-axis
    if (options.grid) {
      ctx.strokeStyle = options.gridColor;
      ctx.lineWidth = 1;
      ctx.font = `${options.fontSize}px ${options.fontFamily}`;
      ctx.fillStyle = options.textColor;
      ctx.textAlign = 'right';
      for (let i = 0; i <= steps; i++) {
        const y = p + h - (i / steps) * h;
        ctx.beginPath(); ctx.moveTo(p, y); ctx.lineTo(p + w, y); ctx.stroke();
        const val = min + i * stepSize;
        ctx.fillText(options.yAxisFormat ? options.yAxisFormat(val) : this._formatNum(val), p - 8, y + 4);
      }
    }

    const groupW = w / numBars;
    const barW = (groupW * 0.7) / numSets;
    const gap = groupW * 0.15;

    data.datasets.forEach((ds, di) => {
      const color = ds.color || ChartEngine.COLORS[di % ChartEngine.COLORS.length];
      ds.data.forEach((v, i) => {
        const barH = ((v - min) / (max - min || 1)) * h;
        const x = p + i * groupW + gap + di * barW;
        const y = p + h - barH;

        ctx.fillStyle = color;
        ctx.beginPath();
        // Rounded top
        const r = Math.min(4, barW / 2);
        ctx.moveTo(x, y + r);
        ctx.arcTo(x, y, x + barW, y, r);
        ctx.arcTo(x + barW, y, x + barW, y + barH, r);
        ctx.lineTo(x + barW, p + h);
        ctx.lineTo(x, p + h);
        ctx.closePath();
        ctx.fill();
      });
    });

    // X-axis labels
    ctx.fillStyle = options.textColor;
    ctx.textAlign = 'center';
    const labels = data.labels || [];
    labels.forEach((lbl, i) => {
      ctx.fillText(lbl, p + i * groupW + groupW / 2, p + h + 20);
    });
  }

  _drawPieChart() {
    const { ctx, data, options } = this;
    const isDoughnut = this.type === 'doughnut';
    const ds = data.datasets[0] || { data: [] };
    const total = ds.data.reduce((a, b) => a + b, 0) || 1;
    const labels = data.labels || ds.data.map((_, i) => `Item ${i + 1}`);

    const cx = this.canvas.width / 2;
    const cy = (this.canvas.height - (options.legend ? 30 : 0)) / 2;
    const radius = Math.min(cx, cy) - options.padding;

    let startAngle = -Math.PI / 2;
    ds.data.forEach((v, i) => {
      const sliceAngle = (v / total) * Math.PI * 2;
      const color = ds.colors?.[i] || ChartEngine.COLORS[i % ChartEngine.COLORS.length];

      ctx.beginPath();
      ctx.moveTo(cx, cy);
      ctx.arc(cx, cy, radius, startAngle, startAngle + sliceAngle);
      ctx.closePath();
      ctx.fillStyle = color;
      ctx.fill();

      // Slice border
      ctx.strokeStyle = 'rgba(0,0,0,0.3)';
      ctx.lineWidth = 2;
      ctx.stroke();

      // Label
      const midAngle = startAngle + sliceAngle / 2;
      const lx = cx + Math.cos(midAngle) * radius * 0.65;
      const ly = cy + Math.sin(midAngle) * radius * 0.65;
      const pct = Math.round((v / total) * 100);
      if (pct > 5) {
        ctx.fillStyle = '#fff';
        ctx.font = `bold ${options.fontSize}px ${options.fontFamily}`;
        ctx.textAlign = 'center';
        ctx.fillText(`${pct}%`, lx, ly + 4);
      }

      startAngle += sliceAngle;
    });

    if (isDoughnut) {
      ctx.beginPath();
      ctx.arc(cx, cy, radius * 0.55, 0, Math.PI * 2);
      ctx.fillStyle = options.bgColor === 'transparent' ? '#0d1117' : options.bgColor;
      ctx.fill();
    }
  }

  _drawSparkline() {
    const { ctx, data } = this;
    const ds = data.datasets[0] || { data: [] };
    const vals = ds.data;
    if (!vals.length) return;

    const w = this.canvas.width;
    const h = this.canvas.height;
    const min = Math.min(...vals);
    const max = Math.max(...vals);
    const range = max - min || 1;
    const color = ds.color || ChartEngine.COLORS[0];

    ctx.beginPath();
    ctx.strokeStyle = color;
    ctx.lineWidth = 2;
    ctx.lineJoin = 'round';
    vals.forEach((v, i) => {
      const x = (i / (vals.length - 1)) * w;
      const y = h - ((v - min) / range) * (h * 0.8) - h * 0.1;
      i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
    });
    ctx.stroke();

    // Last value dot
    const lastX = w;
    const lastY = h - ((vals[vals.length - 1] - min) / range) * (h * 0.8) - h * 0.1;
    ctx.beginPath();
    ctx.arc(lastX, lastY, 3, 0, Math.PI * 2);
    ctx.fillStyle = color;
    ctx.fill();
  }

  _drawLegend() {
    const { ctx, data, options } = this;
    const datasets = data.datasets;
    const labels = this.type === 'pie' || this.type === 'doughnut'
      ? data.labels || [] : datasets.map(ds => ds.label || 'Dataset');
    const colors = this.type === 'pie' || this.type === 'doughnut'
      ? (datasets[0]?.colors || ChartEngine.COLORS) : datasets.map((ds, i) => ds.color || ChartEngine.COLORS[i]);

    const y = this.canvas.height - 15;
    let x = options.padding;

    ctx.font = `${options.fontSize - 1}px ${options.fontFamily}`;
    ctx.textAlign = 'left';

    labels.forEach((lbl, i) => {
      const color = colors[i % colors.length];
      ctx.fillStyle = color;
      ctx.fillRect(x, y - 8, 12, 12);
      ctx.fillStyle = options.textColor;
      ctx.fillText(lbl, x + 16, y + 2);
      x += ctx.measureText(lbl).width + 32;
    });
  }

  /* ── Utilities ──────────────────────────────────── */

  _calcScale(datasets) {
    let allVals = datasets.flatMap(ds => ds.data || []);
    if (!allVals.length) allVals = [0];
    let min = Math.min(0, ...allVals);
    let max = Math.max(...allVals);
    if (min === max) max = min + 10;

    const range = max - min;
    const rawStep = range / 5;
    const magnitude = Math.pow(10, Math.floor(Math.log10(rawStep)));
    const stepSize = Math.ceil(rawStep / magnitude) * magnitude;
    min = Math.floor(min / stepSize) * stepSize;
    max = Math.ceil(max / stepSize) * stepSize;
    const steps = Math.round((max - min) / stepSize);

    return { min, max, stepSize, steps: Math.max(1, steps) };
  }

  _formatNum(n) {
    if (Math.abs(n) >= 1e6) return (n / 1e6).toFixed(1) + 'M';
    if (Math.abs(n) >= 1e3) return (n / 1e3).toFixed(1) + 'K';
    return n % 1 === 0 ? n.toString() : n.toFixed(1);
  }

  _resize() {
    const dpr = window.devicePixelRatio || 1;
    const rect = this.canvas.getBoundingClientRect();
    this.canvas.width = rect.width * dpr;
    this.canvas.height = rect.height * dpr;
    this.ctx.scale(dpr, dpr);
    // Keep logical size for drawing
    this.canvas.width = rect.width;
    this.canvas.height = rect.height;
  }

  _bindResize() {
    this._resizeHandler = () => this.render();
    window.addEventListener('resize', this._resizeHandler);
  }
}

if (typeof window !== 'undefined') window.ChartEngine = ChartEngine;
