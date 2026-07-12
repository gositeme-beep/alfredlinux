# GoCodeMe.com Deployment Guide

## Production Deployment

### Prerequisites

- Ubuntu server with DirectAdmin
- Node.js 16+ installed
- OpenRouter API key for Claude AI
- Domain name (gocodeme.com)

### Step 1: Server Setup

1. **Install Node.js** (if not already installed):
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

2. **Install PM2** (process manager):
```bash
sudo npm install -g pm2
```

### Step 2: Project Setup

1. **Clone/Setup Project**:
```bash
cd /home/gositeme/domains/soundstudiopro.com/public_html/gocodeme.com
chmod +x setup.sh
./setup.sh
```

2. **Configure Environment**:
```bash
nano .env
```

Add your OpenRouter API key:
```
OPENROUTER_API_KEY=your_actual_api_key_here
```

### Step 3: Domain Configuration

1. **Create Subdomain** in DirectAdmin:
   - Go to DirectAdmin → Domain Setup
   - Add subdomain: `gocodeme.soundstudiopro.com`
   - Or use main domain: `gocodeme.com`

2. **Configure Nginx** (if using custom domain):
```nginx
server {
    listen 80;
    server_name gocodeme.com;
    
    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}
```

### Step 4: SSL Certificate

1. **Install Certbot**:
```bash
sudo apt-get install certbot python3-certbot-nginx
```

2. **Get SSL Certificate**:
```bash
sudo certbot --nginx -d gocodeme.com
```

### Step 5: Production Deployment

1. **Build the Application**:
```bash
npm run build
```

2. **Start with PM2**:
```bash
pm2 start src/server/index.js --name "gocodeme"
pm2 save
pm2 startup
```

3. **Configure code-server**:
```bash
# Create code-server config
mkdir -p ~/.config/code-server
cat > ~/.config/code-server/config.yaml << EOF
bind-addr: 127.0.0.1:8080
auth: password
password: your_secure_password
cert: false
EOF

# Start code-server
pm2 start code-server --name "code-server"
pm2 save
```

### Step 6: Security Configuration

1. **Firewall Setup**:
```bash
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

2. **Environment Security**:
```bash
# Generate secure secrets
openssl rand -hex 32  # For SESSION_SECRET
openssl rand -hex 32  # For JWT_SECRET
```

### Step 7: Monitoring

1. **PM2 Monitoring**:
```bash
pm2 monit
pm2 logs gocodeme
```

2. **System Monitoring**:
```bash
htop
df -h
free -h
```

### Step 8: Backup Strategy

1. **Database Backup** (if using):
```bash
# Add to crontab
0 2 * * * /usr/bin/sqlite3 /path/to/gocodeme.db .dump > /backup/gocodeme_$(date +\%Y\%m\%d).sql
```

2. **Code Backup**:
```bash
# Git repository backup
git add .
git commit -m "Production deployment"
git push origin main
```

### Troubleshooting

#### Common Issues:

1. **Port 3000 already in use**:
```bash
sudo lsof -i :3000
sudo kill -9 <PID>
```

2. **code-server not starting**:
```bash
pm2 logs code-server
code-server --help
```

3. **AI not working**:
```bash
# Check API key
curl -H "Authorization: Bearer YOUR_API_KEY" https://openrouter.ai/api/v1/models
```

4. **SSL issues**:
```bash
sudo certbot renew --dry-run
sudo nginx -t
```

### Performance Optimization

1. **Enable Gzip**:
```nginx
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
```

2. **Caching**:
```nginx
location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

3. **PM2 Cluster Mode**:
```bash
pm2 start src/server/index.js --name "gocodeme" -i max
```

### Maintenance

1. **Update Dependencies**:
```bash
npm update
pm2 restart gocodeme
```

2. **Log Rotation**:
```bash
# Add to /etc/logrotate.d/gocodeme
/home/gositeme/domains/soundstudiopro.com/public_html/gocodeme.com/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 gositeme gositeme
}
```

### Support

For issues or questions:
- Check logs: `pm2 logs`
- Monitor resources: `htop`
- Test AI connection: `/health` endpoint
- Verify code-server: `http://localhost:8080` 