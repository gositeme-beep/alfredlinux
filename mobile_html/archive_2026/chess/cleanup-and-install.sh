#!/bin/bash

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to detect OS
detect_os() {
    case "$(uname -s)" in
        Linux*)     echo "linux";;
        Darwin*)    echo "macos";;
        CYGWIN*|MINGW*|MSYS*) echo "windows";;
        *)          echo "unknown";;
    esac
}

# Function to stop Node.js processes
stop_node_processes() {
    local os=$(detect_os)
    case $os in
        "windows")
            taskkill /F /IM node.exe >/dev/null 2>&1 || true
            ;;
        *)
            pkill -f "node" >/dev/null 2>&1 || true
            ;;
    esac
}

# Check for required commands
for cmd in unzip npm node; do
    if ! command_exists $cmd; then
        echo "Error: $cmd is required but not installed."
        exit 1
    fi
done

# Extract the zip file if it exists
if [ -f "chess-arena-stockfish-fixed.zip" ]; then
    echo "Extracting chess-arena-stockfish-fixed.zip..."
    unzip -o chess-arena-stockfish-fixed.zip
else
    echo "Error: chess-arena-stockfish-fixed.zip not found."
    exit 1
fi

# Create necessary directories
echo "Creating directory structure..."
mkdir -p public/stockfish
mkdir -p public/pieces
mkdir -p public/assets
mkdir -p src/components/features/ChessBoard/utils/aiEvaluation
mkdir -p src/components/features/ChessBoard/hooks
mkdir -p src/types

# Move files to correct locations
echo "Moving files to correct locations..."
mv stockfish/* public/stockfish/ 2>/dev/null || true
mv pieces/* public/pieces/ 2>/dev/null || true
mv assets/* public/assets/ 2>/dev/null || true
mv aiEvaluation/* src/components/features/ChessBoard/utils/aiEvaluation/ 2>/dev/null || true
mv hooks/* src/components/features/ChessBoard/hooks/ 2>/dev/null || true
mv types/* src/types/ 2>/dev/null || true

# Verify package.json exists
if [ ! -f "package.json" ]; then
    echo "Error: package.json not found after extraction."
    exit 1
fi

# Stop any running Node.js processes
echo "Stopping any running Node.js processes..."
stop_node_processes

# Clean up previous installation
echo "Cleaning up previous installation..."
rm -rf node_modules dist .next

# Clean npm cache
echo "Cleaning npm cache..."
npm cache clean --force

# Install dependencies
echo "Installing dependencies..."
npm install

# Build the application
echo "Building the application..."
npm run build

# Create .htaccess file for Apache
echo "Creating .htaccess file..."
cat > .htaccess << 'EOL'
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-XSS-Protection "1; mode=block"
  Header set Referrer-Policy "strict-origin-when-cross-origin"
  Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:;"
</IfModule>
EOL

# Copy built files to the correct location
echo "Copying built files..."
if [ -d "dist" ]; then
    cp -r dist/* .
    cp -r dist/assets/* public/assets/
else
    echo "Warning: dist directory not found. Build may have failed."
fi

# Set proper permissions
echo "Setting file permissions..."
chmod -R 755 public
chmod -R 755 dist
chmod 644 .htaccess
chmod 644 index.html

echo "Setup completed successfully!"
echo "The application has been built and is ready to be served through your web server."
echo "Make sure to configure your web server to serve the contents of the 'dist' directory." 