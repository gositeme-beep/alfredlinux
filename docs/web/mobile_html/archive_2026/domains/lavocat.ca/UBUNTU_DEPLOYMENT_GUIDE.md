# Ubuntu Deployment Instructions

## 🚀 Quick Setup

1. **Install Node.js 18+**
   ```bash
   curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
   sudo apt-get install -y nodejs
   ```

2. **Install System Dependencies**
   ```bash
   sudo apt-get update
   sudo apt-get install -y build-essential python3 ca-certificates fonts-liberation libasound2 libatk-bridge2.0-0 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc1 libglib2.0-0 libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 lsb-release wget xdg-utils
   ```

3. **Extract and Setup**
   ```bash
   unzip ubuntu-deployment-package.zip
   cd ubuntu-deployment-package
   npm install
   npx prisma generate
   npx prisma migrate deploy
   ```

4. **Configure Environment**
   ```bash
   cp .env.local .env
   nano .env  # Edit with your server settings
   ```

5. **Build and Start**
   ```bash
   npm run build
   npm start  # Production
   # or
   npm run dev  # Development
   ```

## 📋 Environment Variables to Update

- `DATABASE_URL`: Update for your database
- `NEXTAUTH_URL`: Update for your domain
- `SMTP_*`: Update email settings
- `STRIPE_*`: Update payment settings

## 🔧 Production Recommendations

- Use PM2 for process management
- Set up Nginx as reverse proxy
- Use PostgreSQL instead of SQLite
- Configure SSL certificates
- Set up automatic backups

## 📁 Package Contents

- ✅ Complete source code (src/)
- ✅ Static assets (public/)
- ✅ Database schema and migrations (prisma/)
- ✅ All scripts and utilities (scripts/)
- ✅ Documentation (docs/)
- ✅ Configuration files
- ✅ Environment templates
- ✅ SSL certificates (if needed)

## ❌ Excluded Files

- 🚫 Build artifacts (.next/)
- 🚫 Dependencies (node_modules/)
- 🚫 Test reports and results
- 🚫 Backup files
- 🚫 Deployment-specific files
- 🚫 Large upload files

## 🆘 Troubleshooting

- **Port issues**: Check if ports 3000/3443 are available
- **Permission errors**: Run with sudo or fix file permissions
- **Database errors**: Verify SQLite file permissions or switch to PostgreSQL
- **Build errors**: Ensure all system dependencies are installed

## 📞 Support

Check the docs/ directory for detailed documentation about specific features.
