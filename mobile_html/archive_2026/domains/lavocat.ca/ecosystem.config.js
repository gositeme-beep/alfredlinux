module.exports = {
  apps: [{
    name: 'lavocat',
    script: 'node_modules/.bin/next',
    args: 'start -p 3000',
    cwd: '/home/gositeme/domains/lavocat.ca/public_html',
    instances: 1,
    exec_mode: 'fork',
    env: {
      NODE_ENV: 'production',
      PORT: 3000,
      HOSTNAME: '0.0.0.0'
    },
    max_memory_restart: '1500M',
    watch: false,
    autorestart: true,
    max_restarts: 10,
    restart_delay: 5000,
    error_file: './logs/pm2-error.log',
    out_file: './logs/pm2-out.log',
    merge_logs: true,
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    node_args: '--max-old-space-size=1500'
  }]
};
