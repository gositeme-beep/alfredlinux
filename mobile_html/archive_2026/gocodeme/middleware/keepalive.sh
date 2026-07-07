#!/bin/bash
# GoCodeMe Middleware & Redis Keep-Alive Script
# Can be called periodically via WHMCS cron or manually

cd /home/gositeme/public_html/gocodeme/middleware

# Check if PM2 daemon is running
if ! npx pm2 ping > /dev/null 2>&1; then
  echo "PM2 not running, resurrecting..."
  npx pm2 resurrect
  exit 0
fi

# Check middleware
MW_STATUS=$(npx pm2 jlist 2>/dev/null | python3 -c "import sys,json; procs=json.load(sys.stdin); mw=[p for p in procs if p['name']=='gocodeme-middleware']; print(mw[0]['pm2_env']['status'] if mw else 'missing')" 2>/dev/null)
if [ "$MW_STATUS" != "online" ]; then
  echo "Middleware not online (status=$MW_STATUS), restarting..."
  npx pm2 restart gocodeme-middleware 2>/dev/null || npx pm2 start src/server.js --name gocodeme-middleware --cwd /home/gositeme/public_html/gocodeme/middleware
fi

# Check Redis  
REDIS_STATUS=$(npx pm2 jlist 2>/dev/null | python3 -c "import sys,json; procs=json.load(sys.stdin); r=[p for p in procs if p['name']=='redis']; print(r[0]['pm2_env']['status'] if r else 'missing')" 2>/dev/null)
if [ "$REDIS_STATUS" != "online" ]; then
  echo "Redis not online (status=$REDIS_STATUS), restarting..."
  npx pm2 restart redis 2>/dev/null || npx pm2 start /home/gositeme/redis-7.2.4/src/redis-server --name redis -- --port 6379
fi

echo "All services OK: middleware=$MW_STATUS, redis=$REDIS_STATUS"
