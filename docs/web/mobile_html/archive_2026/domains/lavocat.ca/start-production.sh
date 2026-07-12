#!/bin/bash

echo "🚀 Starting Avocat.Quebec production server on lavocat.ca..."

# Set production environment
export NODE_ENV=production

# Copy production environment
cp .env.production .env

# Clean previous build
echo "🧹 Cleaning previous build..."
rm -rf .next

# Build the application
echo "📦 Building application..."
npm run build

# Generate Prisma client
echo "🗄️ Generating Prisma client..."
npx prisma generate

# Start the production server
echo "🌐 Starting HTTPS server on lavocat.ca:3002..."
npm run dev:https 