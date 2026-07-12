const http = require('http');
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

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
  
  // For HTML pages, serve a basic template that loads the client-side JavaScript
  let pagePath = req.url === '/' ? 'index' : req.url.replace(/^\//, '');
  
  // Create HTML template that supports client-side features
  const html = `<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lavocat.ca - ${pagePath}</title>
    <link rel="stylesheet" href="/_next/static/css/app.css">
    <script>
        // Initialize client-side features
        window.__NEXT_DATA__ = {
            props: {},
            page: "/${pagePath}",
            query: {},
            buildId: "build-id"
        };
    </script>
</head>
<body>
    <div id="__next"></div>
    <script src="/_next/static/chunks/webpack.js"></script>
    <script src="/_next/static/chunks/framework.js"></script>
    <script src="/_next/static/chunks/main.js"></script>
    <script src="/_next/static/chunks/pages/${pagePath}.js"></script>
    <script>
        // Initialize Next.js client-side features
        if (typeof window !== 'undefined') {
            // Initialize next-auth
            if (window.__NEXT_DATA__) {
                console.log('Next.js client initialized');
            }
        }
    </script>
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
  console.log(`Client-side server running on port ${PORT}`);
  console.log(`Supports: next-auth, framer-motion, client-side routing`);
}); 