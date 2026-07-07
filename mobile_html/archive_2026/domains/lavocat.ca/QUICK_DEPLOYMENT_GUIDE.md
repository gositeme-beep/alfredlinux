# 🚀 QUICK DEPLOYMENT GUIDE

## **IMMEDIATE DEPLOYMENT**

### **Step 1: Start Production Server**
```bash
cd /home/gositeme/domains/lavocat.ca/public_html/avocat.quebec-ubuntu-deployment-package
./start-production.sh
```

### **Step 2: Access Your Application**
- **HTTP**: http://15.235.50.60:3000
- **HTTPS**: https://15.235.50.60:3443
- **Local**: http://localhost:3000 or https://localhost:3443

### **Step 3: Test Features**
- ✅ Authentication system
- ✅ Database connections
- ✅ WebSocket real-time features
- ✅ File uploads
- ✅ Payment system (with test keys)

## **BACKGROUND PROCESS MANAGEMENT**

### **Option 1: Using PM2 (Recommended)**
```bash
# Install PM2
npm install -g pm2

# Start with PM2
pm2 start server-https.js --name "avocat-quebec"

# Save PM2 configuration
pm2 save
pm2 startup
```

### **Option 2: Using Screen**
```bash
# Install screen
sudo apt-get install screen

# Start in screen session
screen -S avocat-quebec
./start-production.sh

# Detach: Ctrl+A, then D
# Reattach: screen -r avocat-quebec
```

### **Option 3: Using Systemd Service**
```bash
# Create service file
sudo nano /etc/systemd/system/avocat-quebec.service

# Content:
[Unit]
Description=Avocat Quebec Application
After=network.target

[Service]
Type=simple
User=gositeme
WorkingDirectory=/home/gositeme/domains/lavocat.ca/public_html/avocat.quebec-ubuntu-deployment-package
ExecStart=/usr/bin/node server-https.js
Restart=always
Environment=NODE_ENV=production

[Install]
WantedBy=multi-user.target

# Enable and start
sudo systemctl enable avocat-quebec
sudo systemctl start avocat-quebec
```

## **DOMAIN CONFIGURATION**

### **Update for lavocat.ca Domain**
Edit `.env.production`:
```bash
NEXTAUTH_URL=https://lavocat.ca
NEXT_PUBLIC_APP_URL=https://lavocat.ca
NEXT_PUBLIC_WS_URL=wss://lavocat.ca
COOKIE_DOMAIN=lavocat.ca
```

### **SSL Certificate Setup**
```bash
# Install Certbot
sudo apt-get install certbot

# Get SSL certificate
sudo certbot certonly --standalone -d lavocat.ca

# Update certificate paths in server-https.js
```

## **MONITORING**

### **Check Server Status**
```bash
# Check if server is running
ps aux | grep node

# Check ports
netstat -tlnp | grep :3000
netstat -tlnp | grep :3443

# Check logs
tail -f ~/.pm2/logs/avocat-quebec-out.log
```

### **Database Backup**
```bash
# Backup SQLite database
cp production.db production.db.backup.$(date +%Y%m%d)

# Restore if needed
cp production.db.backup.YYYYMMDD production.db
```

## **TROUBLESHOOTING**

### **Common Issues**
1. **Port already in use**: `sudo lsof -i :3000` and kill process
2. **Permission denied**: `chmod +x start-production.sh`
3. **SSL certificate errors**: Check certificate paths
4. **Database locked**: Restart server

### **Logs Location**
- **PM2**: `~/.pm2/logs/`
- **Systemd**: `sudo journalctl -u avocat-quebec`
- **Application**: Check console output

## **SECURITY CHECKLIST**
- ✅ Dependencies installed
- ✅ Security vulnerabilities fixed
- ✅ SSL certificates configured
- ✅ Environment variables set
- ✅ Database permissions correct
- ⚠️ Consider upgrading Node.js to v20+
- ⚠️ Consider PostgreSQL for production

---
**Status**: ✅ READY TO DEPLOY
**Last Updated**: $(date) 