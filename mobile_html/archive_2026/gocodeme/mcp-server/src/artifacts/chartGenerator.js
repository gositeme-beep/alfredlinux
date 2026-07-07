/**
 * chartGenerator.js — Server-Side Chart Generation
 *
 * Generates Chart.js charts server-side using the canvas package.
 * Outputs PNG images (base64) for embedding in responses.
 *
 * Supported chart types: bar, line, pie, doughnut, radar, scatter, bubble
 */

let ChartJSNodeCanvas = null;

async function getChartCanvas() {
  if (!ChartJSNodeCanvas) {
    try {
      const mod = await import('chartjs-node-canvas');
      ChartJSNodeCanvas = mod.ChartJSNodeCanvas;
    } catch {
      // Fallback: generate chart config as HTML
      return null;
    }
  }
  return ChartJSNodeCanvas;
}

/**
 * Generate an HTML-based chart (works everywhere, no native deps).
 */
function generateHtmlChart(config, width = 800, height = 400) {
  const chartConfig = JSON.stringify(config);
  return `<!DOCTYPE html>
<html><head>
<meta charset="utf-8">
<title>Chart</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<style>body{margin:0;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f5f5f5;font-family:system-ui}</style>
</head><body>
<canvas id="chart" width="${width}" height="${height}"></canvas>
<script>new Chart(document.getElementById('chart'),${chartConfig})</script>
</body></html>`;
}

/**
 * Generate a chart.
 *
 * @param {object} opts
 * @param {string} opts.type — 'bar', 'line', 'pie', 'doughnut', 'radar', 'scatter', 'bubble'
 * @param {object} opts.data — Chart.js data object { labels, datasets }
 * @param {object} [opts.options={}] — Chart.js options
 * @param {number} [opts.width=800] — image width
 * @param {number} [opts.height=400] — image height
 * @returns {Promise<{html: string, base64?: string, mimeType: string}>}
 */
export async function generateChart(opts) {
  const {
    type = 'bar',
    data,
    options = {},
    width = 800,
    height = 400,
  } = opts;

  if (!data || !data.labels || !data.datasets) {
    throw new Error('data with labels and datasets is required');
  }

  const config = {
    type,
    data,
    options: {
      responsive: false,
      ...options,
    },
  };

  const start = Date.now();

  // Try server-side canvas rendering first
  const Canvas = await getChartCanvas();
  let base64 = null;

  if (Canvas) {
    try {
      const canvas = new Canvas({ width, height, backgroundColour: 'white' });
      const buffer = await canvas.renderToBuffer(config);
      base64 = buffer.toString('base64');
    } catch {
      // Fallback to HTML
    }
  }

  // Always generate HTML version
  const html = generateHtmlChart(config, width, height);

  return {
    html,
    base64, // null if canvas rendering unavailable
    mimeType: base64 ? 'image/png' : 'text/html',
    type,
    width,
    height,
    timing: Date.now() - start,
  };
}
