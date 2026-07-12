const http = require('http');
const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');

const server = http.createServer((req, res) => {
  console.log(`Request: ${req.method} ${req.url}`);
  
  // Handle static files from .next/static
  if (req.url.startsWith('/_next/static/')) {
    const filePath = path.join(__dirname, '.next', req.url);
    if (fs.existsSync(filePath)) {
      const ext = path.extname(filePath);
      let contentType = 'application/octet-stream';
      
      switch (ext) {
        case '.css':
          contentType = 'text/css; charset=utf-8';
          break;
        case '.js':
          contentType = 'application/javascript; charset=utf-8';
          break;
        case '.woff2':
          contentType = 'font/woff2';
          break;
        case '.woff':
          contentType = 'font/woff';
          break;
        case '.png':
          contentType = 'image/png';
          break;
        case '.jpg':
        case '.jpeg':
          contentType = 'image/jpeg';
          break;
        case '.svg':
          contentType = 'image/svg+xml';
          break;
      }
      
      res.writeHead(200, { 'Content-Type': contentType });
      fs.createReadStream(filePath).pipe(res);
      return;
    }
  }
  
  // Handle static files from public directory
  if (req.url.startsWith('/fonts/') || req.url.startsWith('/images/')) {
    const filePath = path.join(__dirname, 'public', req.url);
    if (fs.existsSync(filePath)) {
      const ext = path.extname(filePath);
      let contentType = 'application/octet-stream';
      
      switch (ext) {
        case '.woff2':
          contentType = 'font/woff2';
          break;
        case '.woff':
          contentType = 'font/woff';
          break;
        case '.png':
          contentType = 'image/png';
          break;
        case '.jpg':
        case '.jpeg':
          contentType = 'image/jpeg';
          break;
      }
      
      res.writeHead(200, { 'Content-Type': contentType });
      fs.createReadStream(filePath).pipe(res);
      return;
    }
  }
  
  // For HTML pages, we need to render them server-side
  // Since we can't easily render Next.js pages without the full framework,
  // let's serve a simple HTML template that loads the JavaScript
  let pagePath = req.url === '/' ? 'index' : req.url.replace(/^\//, '');
  
  // Create a simple HTML template that loads the Next.js JavaScript
  const html = `<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lavocat.ca</title>
    <link rel="stylesheet" href="/_next/static/css/app.css">
</head>
<body>
    <div id="__next"></div>
    <script src="/_next/static/chunks/main.js"></script>
    <script src="/_next/static/chunks/webpack.js"></script>
    <script src="/_next/static/chunks/pages/${pagePath}.js"></script>
</body>
</html>`;
  
  res.writeHead(200, { 
    'Content-Type': 'text/html; charset=utf-8',
    'X-UA-Compatible': 'IE=edge'
  });
  res.end(html);
});

const PORT = 3000;
server.listen(PORT, () => {
  console.log(`Simple server running on port ${PORT}`);
}); 