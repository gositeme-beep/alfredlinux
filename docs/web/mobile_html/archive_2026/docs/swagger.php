<?php
$page_title = 'Interactive API Reference — Alfred AI | GoSiteMe';
$page_description = 'Explore the Alfred AI API interactively. Try endpoints, see request/response schemas, and generate code snippets.';
$page_canonical = 'https://gositeme.com/docs/swagger.php';
include __DIR__ . '/../includes/site-header.inc.php';
?>
<style>
  body { background: #0a0a14; }
  .swagger-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem 1.5rem 4rem;
  }
  .swagger-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    margin-bottom: 1rem;
  }
  .swagger-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #e2e8f0;
  }
  .swagger-header .links {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
  }
  .swagger-header .links a {
    color: #a78bfa;
    text-decoration: none;
  }
  .swagger-header .links a:hover { color: #fff; }
  /* Redoc dark theme overrides */
  .redoc-wrap { background: #0a0a14 !important; }
</style>

<div class="swagger-container">
  <div class="swagger-header">
    <h1><i class="fas fa-book-open" style="color:#a78bfa;margin-right:.5rem"></i> Interactive API Reference</h1>
    <div class="links">
      <a href="/docs/api-reference.php"><i class="fas fa-file-alt"></i> Manual Docs</a>
      <a href="/developer-portal.php"><i class="fas fa-code"></i> Developer Portal</a>
      <a href="/docs/openapi.yaml" download><i class="fas fa-download"></i> Download OpenAPI Spec</a>
    </div>
  </div>
  <div id="redoc-container"></div>
</div>

<script src="/assets/js/vendor/redoc.standalone.js"></script>
<script>
  Redoc.init('/docs/openapi.yaml', {
    theme: {
      colors: {
        primary: { main: '#a78bfa' },
        text: { primary: '#e2e8f0', secondary: '#94a3b8' },
        http: {
          get: '#10b981', post: '#3b82f6', put: '#f59e0b',
          delete: '#ef4444', patch: '#8b5cf6'
        }
      },
      typography: {
        fontFamily: '"Segoe UI", system-ui, -apple-system, sans-serif',
        headings: { fontFamily: '"Space Grotesk", "Segoe UI", system-ui, sans-serif' },
        code: { fontFamily: '"JetBrains Mono", "Fira Code", monospace' }
      },
      sidebar: {
        backgroundColor: '#0f0f1a',
        textColor: '#94a3b8',
        activeTextColor: '#a78bfa',
      },
      rightPanel: { backgroundColor: '#11111b' },
      schema: { nestedBackground: '#0f0f1a' }
    },
    scrollYOffset: 60,
    hideDownloadButton: false,
    expandResponses: '200',
    pathInMiddlePanel: true,
    nativeScrollbars: true,
  }, document.getElementById('redoc-container'));
</script>

<?php include __DIR__ . '/../includes/site-footer.inc.php'; ?>
