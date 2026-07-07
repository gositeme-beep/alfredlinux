const express = require('express');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = 3447;

// Serve static files from .next/static
app.use('/_next/static', express.static(path.join(__dirname, '.next/static')));

// Serve other static files
app.use('/fonts', express.static(path.join(__dirname, 'public/fonts')));
app.use('/images', express.static(path.join(__dirname, 'public/images')));
app.use('/favicon.ico', express.static(path.join(__dirname, 'public/favicon.ico')));

// Handle all other requests by proxying to the main server
app.use('*', (req, res) => {
  // Proxy to the main Next.js server
  const https = require('https');
  const options = {
    hostname: 'localhost',
    port: 3446,
    path: req.originalUrl,
    method: req.method,
    headers: req.headers
  };

  const proxyReq = https.request(options, (proxyRes) => {
    res.writeHead(proxyRes.statusCode, proxyRes.headers);
    proxyRes.pipe(res);
  });

  proxyReq.on('error', (err) => {
    console.error('Proxy error:', err);
    res.status(500).send('Proxy error');
  });

  if (req.method === 'POST') {
    req.pipe(proxyReq);
  } else {
    proxyReq.end();
  }
});

app.listen(PORT, () => {
  console.log(`Static file server running on port ${PORT}`);
}); 