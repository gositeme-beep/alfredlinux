const fs = require('fs');
const path = require('path');

/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,

  // Allow cross-origin requests from network IP
  allowedDevOrigins: ['10.119.255.188:3000', '10.119.255.188:3443', 'localhost:3000', 'localhost:3443'],
  // Configure images - HTTPS only
  images: {
    remotePatterns: [
      {
        protocol: 'https',
        hostname: 'localhost',
      },
      {
        protocol: 'https',
        hostname: 'action-collective.info',
      },
      {
        protocol: 'https',
        hostname: 'images.unsplash.com',
      },
    ],
    domains: [
      'www.adwavocats.com',
      // add other allowed domains here if needed
    ],
  },
  // Disable experimental features temporarily to fix Watchpack issue
  experimental: {
    // scrollRestoration: true, // Temporarily disabled
  },
  // Configure HTTPS for development
  webpack: (config, { isServer }) => {
    if (!isServer) {
      config.resolve.fallback = {
        ...config.resolve.fallback,
        fs: false,
        net: false,
        tls: false,
      };
    }
    
    // Suppress CSS vendor prefix warnings in development
    if (process.env.NODE_ENV === 'development') {
      config.infrastructureLogging = {
        level: 'error',
      };
    }
    
    return config;
  },
  // Ensure proper asset prefix in development
  assetPrefix: process.env.NODE_ENV === 'development' ? undefined : undefined,
  // Configure base path if needed
  basePath: '',
  // Configure trailing slashes
  trailingSlash: false,
  // Configure headers with enhanced security
  async headers() {
    return [
      {
        source: '/:path*',
        headers: [
          {
            key: 'Strict-Transport-Security',
            value: 'max-age=31536000; includeSubDomains; preload'
          },
          {
            key: 'X-Content-Type-Options',
            value: 'nosniff'
          },
          {
            key: 'X-Frame-Options',
            value: 'DENY'
          },
          {
            key: 'X-XSS-Protection',
            value: '1; mode=block'
          },
          {
            key: 'Referrer-Policy',
            value: 'strict-origin-when-cross-origin'
          },
          {
            key: 'Permissions-Policy',
            value: 'camera=(), microphone=(), geolocation=()'
          }
        ]
      },
      {
        source: '/uploads/:path*',
        headers: [
          {
            key: 'X-Frame-Options',
            value: 'SAMEORIGIN',
          },
        ],
      },
    ];
  }
};

module.exports = nextConfig; 