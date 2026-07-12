#!/bin/bash

# GoCodeMe.com Setup Script

echo "🚀 Setting up GoCodeMe.com..."

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js first."
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm first."
    exit 1
fi

echo "✅ Node.js and npm found"

# Install Node.js dependencies
echo "📦 Installing Node.js dependencies..."
npm install

if [ $? -ne 0 ]; then
    echo "❌ Failed to install Node.js dependencies"
    exit 1
fi

echo "✅ Node.js dependencies installed"

# Install code-server
echo "🔧 Installing code-server..."
curl -fsSL https://code-server.dev/install.sh | sh

if [ $? -ne 0 ]; then
    echo "❌ Failed to install code-server"
    exit 1
fi

echo "✅ code-server installed"

# Create environment file
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp env.example .env
    echo "✅ .env file created"
    echo "⚠️  Please edit .env file with your OpenRouter API key"
else
    echo "✅ .env file already exists"
fi

# Create logs directory
mkdir -p logs

# Set permissions
chmod +x setup.sh

echo ""
echo "🎉 GoCodeMe.com setup complete!"
echo ""
echo "Next steps:"
echo "1. Edit .env file with your OpenRouter API key"
echo "2. Run: npm run dev"
echo "3. Open: http://localhost:3000"
echo ""
echo "For production deployment:"
echo "1. Configure your domain"
echo "2. Set up SSL certificates"
echo "3. Run: npm start" 