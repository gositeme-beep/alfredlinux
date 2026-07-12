# ALFRED DEVOPS & INFRASTRUCTURE RESEARCH
### Comprehensive Tooling Evaluation for Platform Modernization
### March 2026

---

## CURRENT STATE ASSESSMENT

| Component | Technology | Notes |
|-----------|-----------|-------|
| Hosting | DirectAdmin shared/VPS | Single server, manual management |
| Process Manager | PM2 | `alfred-ws` on port 3010, MCP server on port 3005 |
| Web Stack | PHP 8.x + Apache/LiteSpeed | Served via DirectAdmin |
| Database | MySQL 8.0+ / MariaDB 10.5+ | ~40+ Alfred tables, full schema in `config/alfred_schema.sql` |
| Cache / Pub-Sub | Redis 6+ (localhost:6379) | WebSocket fan-out, presence, sessions |
| Node.js Services | MCP Server (807 tools), WebSocket Server | Both managed by PM2 `ecosystem.config.js` |
| Deployment | Manual SSH/FTP | No CI/CD, no staging environment |
| Containerization | None | No Docker, no Kubernetes |
| SSL | DirectAdmin / Let's Encrypt manual | Auto-renew via DirectAdmin panel |
| Backups | DirectAdmin built-in (presumed) | No off-site, no granular DB backup automation |
| Secrets | `.env` files / PHP config | No vault, no rotation |

### Service Map

```
┌─────────────────────────────────────────────────────────┐
│                   DirectAdmin Server                     │
│                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌───────────────┐  │
│  │  Apache /     │  │  Node.js     │  │  Node.js      │  │
│  │  LiteSpeed    │  │  MCP Server  │  │  WebSocket    │  │
│  │  PHP 8.x     │  │  :3005       │  │  :3010        │  │
│  │  (:443/:80)  │  │  (PM2)       │  │  (PM2)        │  │
│  └──────┬───────┘  └──────┬───────┘  └──────┬────────┘  │
│         │                 │                  │           │
│  ┌──────┴─────────────────┴──────────────────┴────────┐  │
│  │              Redis (:6379)                          │  │
│  │           pub/sub + cache + presence                │  │
│  └────────────────────────┬───────────────────────────┘  │
│  ┌────────────────────────┴───────────────────────────┐  │
│  │           MySQL / MariaDB (:3306)                   │  │
│  │         40+ tables, JSON columns, FTS               │  │
│  └────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

---

## 1. CONTAINERIZATION

### 1.1 Docker

| Attribute | Details |
|-----------|---------|
| **What it solves** | Packages each service (PHP, Node MCP, Node WS, Redis, MySQL) into isolated, reproducible containers. Eliminates "works on my machine," enables identical dev/staging/prod environments. |
| **Cost** | Free (Docker Engine is open-source). Docker Desktop is paid for companies >250 employees. |
| **Complexity** | ⭐⭐ Medium — requires writing Dockerfiles and understanding networking, but extremely well-documented. |
| **Website** | https://docker.com |

**Why Alfred needs this:**
- Currently, a DirectAdmin update or PHP version change can break the MCP server or vice versa. Containers isolate each service.
- Enables running Alfred on *any* Linux server, cloud VM, or even locally.
- Makes scaling individual services possible (e.g., 3 MCP instances behind a load balancer).

### 1.2 Podman

| Attribute | Details |
|-----------|---------|
| **What it solves** | Drop-in Docker replacement that runs containers without a daemon and without root. Better security posture. |
| **Cost** | Free (open-source, Red Hat-backed) |
| **Complexity** | ⭐⭐ Medium — nearly identical CLI to Docker (`alias docker=podman`). |
| **Website** | https://podman.io |

**Alfred recommendation:** Start with Docker (larger ecosystem, more tutorials). Consider Podman if moving to RHEL/CentOS or if rootless security is a priority.

### 1.3 containerd

| Attribute | Details |
|-----------|---------|
| **What it solves** | Low-level container runtime (used *inside* Docker and Kubernetes). Not user-facing. |
| **Cost** | Free |
| **Complexity** | ⭐⭐⭐⭐ High — no CLI tooling for humans; it's the engine under the hood. |

**Alfred recommendation:** Not directly relevant. You'll use it implicitly if you adopt Kubernetes.

### 1.4 Docker Compose

| Attribute | Details |
|-----------|---------|
| **What it solves** | Defines multi-container applications in a single YAML file. One command (`docker compose up`) starts your entire stack. |
| **Cost** | Free (ships with Docker) |
| **Complexity** | ⭐⭐ Medium |

**Alfred recommendation:** **This is your #1 priority.** Docker Compose replaces PM2 + manual service management with a single declarative file.

### 1.5 Sample `docker-compose.yml` for Alfred

```yaml
# docker-compose.yml — Alfred AI Platform
# Usage: docker compose up -d
# Rebuild: docker compose up -d --build

version: "3.9"

services:
  # ─── PHP Web Application ───────────────────────────────
  php-app:
    build:
      context: .
      dockerfile: Dockerfile.php
    container_name: alfred-php
    restart: unless-stopped
    volumes:
      - ./:/var/www/html:cached
      - ./logs/php:/var/log/php
    environment:
      - DB_HOST=mysql
      - DB_PORT=3306
      - DB_NAME=${DB_NAME}
      - DB_USER=${DB_USER}
      - DB_PASS=${DB_PASS}
      - REDIS_HOST=redis
      - REDIS_PORT=6379
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      - alfred-net

  # ─── Nginx Reverse Proxy ───────────────────────────────
  nginx:
    image: nginx:alpine
    container_name: alfred-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
      - ./docker/nginx/ssl:/etc/nginx/ssl:ro
      - ./:/var/www/html:ro
    depends_on:
      - php-app
      - mcp-server
      - websocket
    networks:
      - alfred-net

  # ─── MCP Tool Server (Node.js) ─────────────────────────
  mcp-server:
    build:
      context: ./gocodeme/mcp-server
      dockerfile: Dockerfile
    container_name: alfred-mcp
    restart: unless-stopped
    expose:
      - "3005"
    environment:
      - NODE_ENV=production
      - PORT=3005
      - REDIS_URL=redis://redis:6379
      - DB_HOST=mysql
      - DB_PORT=3306
      - DB_NAME=${DB_NAME}
      - DB_USER=${DB_USER}
      - DB_PASS=${DB_PASS}
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "node", "-e", "require('http').get('http://localhost:3005/health', r => r.statusCode === 200 ? process.exit(0) : process.exit(1))"]
      interval: 30s
      timeout: 5s
      retries: 3
    networks:
      - alfred-net

  # ─── WebSocket Server (Node.js) ────────────────────────
  websocket:
    build:
      context: ./websocket
      dockerfile: Dockerfile
    container_name: alfred-ws
    restart: unless-stopped
    expose:
      - "3010"
    environment:
      - NODE_ENV=production
      - PORT=3010
      - REDIS_URL=redis://redis:6379
    depends_on:
      redis:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "node", "-e", "const ws = new (require('ws'))('ws://localhost:3010'); ws.on('open', () => process.exit(0)); ws.on('error', () => process.exit(1)); setTimeout(() => process.exit(1), 3000);"]
      interval: 30s
      timeout: 5s
      retries: 3
    networks:
      - alfred-net

  # ─── MySQL / MariaDB ───────────────────────────────────
  mysql:
    image: mariadb:10.11
    container_name: alfred-mysql
    restart: unless-stopped
    volumes:
      - mysql_data:/var/lib/mysql
      - ./config/alfred_schema.sql:/docker-entrypoint-initdb.d/01-schema.sql:ro
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=${DB_NAME}
      - MYSQL_USER=${DB_USER}
      - MYSQL_PASSWORD=${DB_PASS}
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 15s
      timeout: 5s
      retries: 5
    networks:
      - alfred-net

  # ─── Redis ─────────────────────────────────────────────
  redis:
    image: redis:7-alpine
    container_name: alfred-redis
    restart: unless-stopped
    command: redis-server --appendonly yes --maxmemory 256mb --maxmemory-policy allkeys-lru
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 3s
      retries: 3
    networks:
      - alfred-net

volumes:
  mysql_data:
    driver: local
  redis_data:
    driver: local

networks:
  alfred-net:
    driver: bridge
```

### 1.6 Sample Dockerfiles

**`Dockerfile.php`** (PHP + Apache for the main app):
```dockerfile
FROM php:8.2-apache

# Install PHP extensions Alfred needs
RUN apt-get update && apt-get install -y \
    libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mysqli intl zip gd opcache \
    && pecl install redis && docker-php-ext-enable redis \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/alfred.ini
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
```

**`websocket/Dockerfile`** (WebSocket server):
```dockerfile
FROM node:20-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci --omit=dev
COPY . .
USER node
EXPOSE 3010
CMD ["node", "server.js"]
```

**`gocodeme/mcp-server/Dockerfile`** (MCP server):
```dockerfile
FROM node:20-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci --omit=dev
COPY . .
USER node
EXPOSE 3005
CMD ["node", "src/index.js"]
```

---

## 2. ORCHESTRATION

### 2.1 When Does Alfred Need Orchestration?

| Stage | Users | Infra | Recommendation |
|-------|-------|-------|----------------|
| **Now** (0–1,000 users) | Single server | 1 VPS | **Docker Compose only** |
| **Growth** (1,000–10,000) | Need HA, zero-downtime deploys | 2–3 nodes | **Docker Swarm** or **K3s** |
| **Enterprise** (10,000+) | Multi-region, auto-scaling | 5+ nodes | **Kubernetes (K8s)** |

### 2.2 Docker Swarm

| Attribute | Details |
|-----------|---------|
| **What it solves** | Turns multiple Docker hosts into a single virtual cluster. Uses the same `docker-compose.yml` (with `deploy:` keys). |
| **Cost** | Free (built into Docker Engine) |
| **Complexity** | ⭐⭐ Low — `docker swarm init`, then `docker stack deploy`. |
| **When for Alfred** | When you need 2+ servers for high availability. |

**Pros:** Dead simple, uses existing Compose files, built-in service discovery, rolling updates.
**Cons:** Less ecosystem than K8s, no autoscaling, Docker Inc. de-prioritized it.

### 2.3 K3s (Lightweight Kubernetes)

| Attribute | Details |
|-----------|---------|
| **What it solves** | Full Kubernetes API in a single ~70MB binary. Designed for edge, IoT, and small-scale deployments. |
| **Cost** | Free (open-source, Rancher/SUSE) |
| **Complexity** | ⭐⭐⭐ Medium — Kubernetes concepts (pods, services, ingress) still apply, but install is trivial. |
| **When for Alfred** | When Swarm isn't enough — need autoscaling, Helm charts, or Kubernetes-native ecosystem tools. |

**Install:** `curl -sfL https://get.k3s.io | sh -` (one command, includes Traefik ingress, CoreDNS, local storage).

### 2.4 Full Kubernetes (K8s)

| Attribute | Details |
|-----------|---------|
| **What it solves** | Industry-standard container orchestration. Auto-healing, auto-scaling, declarative configuration, huge ecosystem. |
| **Cost** | Free (self-hosted) or managed: GKE ($72/mo+), EKS ($73/mo+), AKS (free control plane). |
| **Complexity** | ⭐⭐⭐⭐⭐ High — significant learning curve, requires dedicated ops knowledge. |
| **When for Alfred** | Enterprise-scale with multi-region deployment, complex autoscaling needs. |

### 2.5 HashiCorp Nomad

| Attribute | Details |
|-----------|---------|
| **What it solves** | Simpler alternative to Kubernetes. Schedules containers, VMs, and raw binaries. Single binary, no etcd dependency. |
| **Cost** | Free (open-source community edition). Enterprise is paid. |
| **Complexity** | ⭐⭐⭐ Medium — simpler than K8s, but smaller ecosystem. |

### ORCHESTRATION RECOMMENDATION FOR ALFRED

```
NOW (2026):     Docker Compose on single VPS     ← START HERE
6–12 months:    Docker Swarm on 2–3 nodes         ← IF HA needed
12–24 months:   K3s with Helm charts              ← IF autoscaling needed
Enterprise:     Managed K8s (GKE/EKS)             ← IF 10K+ users, multi-region
```

---

## 3. CI/CD — CONTINUOUS INTEGRATION & DEPLOYMENT

### 3.1 GitHub Actions

| Attribute | Details |
|-----------|---------|
| **What it solves** | Automated testing, building Docker images, and deploying on every git push. Triggers on PR, merge, tag, cron. |
| **Cost** | Free for public repos. Private: 2,000 minutes/month free, then $0.008/min (Linux). |
| **Complexity** | ⭐⭐ Low — YAML workflows in `.github/workflows/`. |
| **Website** | https://github.com/features/actions |

**Alfred-specific pipeline:**
```yaml
# .github/workflows/deploy.yml
name: Deploy Alfred

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mariadb:10.11
        env:
          MYSQL_ROOT_PASSWORD: test
          MYSQL_DATABASE: alfred_test
        ports: ['3306:3306']
      redis:
        image: redis:7-alpine
        ports: ['6379:6379']
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: '20' }
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.2', extensions: 'pdo_mysql, redis' }

      - name: Install & Test MCP Server
        run: |
          cd gocodeme/mcp-server && npm ci && npm test

      - name: Install & Test WebSocket
        run: |
          cd websocket && npm ci && npm test

      - name: PHP Lint
        run: find . -name "*.php" -not -path "./vendor/*" | xargs -n1 php -l

  build:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: docker/setup-buildx-action@v3
      - uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}
      - uses: docker/build-push-action@v5
        with:
          context: .
          file: Dockerfile.php
          push: true
          tags: ghcr.io/${{ github.repository }}/alfred-php:latest
          cache-from: type=gha
          cache-to: type=gha,mode=max

  deploy:
    needs: build
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to production
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /opt/alfred
            docker compose pull
            docker compose up -d --remove-orphans
            docker compose exec mcp-server node -e "console.log('MCP healthy')"
```

### 3.2 GitLab CI

| Attribute | Details |
|-----------|---------|
| **What it solves** | Same as GitHub Actions but integrated with GitLab. Built-in container registry, environment management, review apps. |
| **Cost** | Free tier: 400 CI/CD minutes/month. Premium: $29/user/month. |
| **Complexity** | ⭐⭐ Low — `.gitlab-ci.yml` in repo root. |

**Consider if:** You prefer GitLab's integrated DevSecOps platform (security scanning built-in).

### 3.3 Drone CI / Woodpecker CI

| Attribute | Details |
|-----------|---------|
| **What it solves** | Self-hosted, container-native CI/CD. Every pipeline step runs in a Docker container. Woodpecker is the community fork after Drone went proprietary. |
| **Cost** | Woodpecker: 100% free, open-source. Drone: free for open-source, paid for enterprise. |
| **Complexity** | ⭐⭐ Low — YAML config, Docker-native. |
| **Self-hosted** | Runs as a single Docker container on your server. |

**Consider if:** You want CI/CD running on your own infrastructure (no cloud minutes to pay for).

### 3.4 Jenkins

| Attribute | Details |
|-----------|---------|
| **What it solves** | Legacy CI/CD server with massive plugin ecosystem. Can automate anything. |
| **Cost** | Free (open-source) |
| **Complexity** | ⭐⭐⭐⭐ High — requires Java, plugin management, Groovy scripting. |

**Alfred recommendation:** Skip Jenkins. GitHub Actions or Woodpecker CI are simpler and more modern.

### CI/CD RECOMMENDATION FOR ALFRED

```
PRIMARY:    GitHub Actions               ← If code is on GitHub (most likely)
ALTERNATE:  Woodpecker CI (self-hosted)  ← If you want zero cloud dependency
AVOID:      Jenkins                      ← Over-engineered for Alfred's scale
```

---

## 4. INFRASTRUCTURE AS CODE (IaC)

### 4.1 Ansible

| Attribute | Details |
|-----------|---------|
| **What it solves** | Automates server provisioning, configuration, and deployment. Agentless (uses SSH). Defines server state in YAML "playbooks." |
| **Cost** | Free (open-source). AWX (GUI) is also free. |
| **Complexity** | ⭐⭐ Low — YAML-based, no agents to install, uses existing SSH. |
| **Website** | https://ansible.com |

**Perfect for Alfred because:**
- You already deploy via SSH — Ansible automates exactly that.
- No agent installation needed on the DirectAdmin server.
- Can manage: package installation, Docker setup, config file templating, PM2 to Docker migration, SSL cert management, backup cron jobs.

**Example Ansible playbook for Alfred server setup:**
```yaml
# playbooks/setup-alfred-server.yml
---
- name: Provision Alfred Server
  hosts: alfred_production
  become: yes
  vars_files:
    - ../vars/secrets.yml  # ansible-vault encrypted

  tasks:
    - name: Install Docker & Docker Compose
      apt:
        name: [docker.io, docker-compose-plugin]
        state: present
        update_cache: yes

    - name: Add deploy user to docker group
      user:
        name: "{{ deploy_user }}"
        groups: docker
        append: yes

    - name: Create Alfred directory
      file:
        path: /opt/alfred
        state: directory
        owner: "{{ deploy_user }}"

    - name: Copy docker-compose.yml
      template:
        src: templates/docker-compose.yml.j2
        dest: /opt/alfred/docker-compose.yml

    - name: Copy .env file
      template:
        src: templates/.env.j2
        dest: /opt/alfred/.env
        mode: '0600'

    - name: Start Alfred stack
      community.docker.docker_compose_v2:
        project_src: /opt/alfred
        state: present

    - name: Configure daily MySQL backup
      cron:
        name: "Alfred DB backup"
        minute: "0"
        hour: "3"
        job: >
          docker exec alfred-mysql
          mariadb-dump -u root -p'{{ mysql_root_pass }}' --all-databases
          | gzip > /opt/alfred/backups/db-$(date +\%Y\%m\%d).sql.gz

    - name: Configure Redis backup
      cron:
        name: "Alfred Redis backup"
        minute: "0"
        hour: "4"
        job: "docker exec alfred-redis redis-cli BGSAVE"
```

### 4.2 Terraform

| Attribute | Details |
|-----------|---------|
| **What it solves** | Provisions cloud *infrastructure* (VMs, networks, DNS, load balancers, managed databases). Declarative — describes desired state. |
| **Cost** | Free (open-source CLI). Terraform Cloud free for up to 500 resources. |
| **Complexity** | ⭐⭐⭐ Medium — HCL language, state management. |
| **When for Alfred** | When you need to spin up/down cloud VMs, manage DNS, configure firewalls across providers. |

**Example:** Terraform to provision a Hetzner VPS for Alfred:
```hcl
# main.tf
resource "hcloud_server" "alfred" {
  name        = "alfred-prod"
  server_type = "cpx31"        # 4 vCPU, 8GB RAM, €12/mo
  image       = "ubuntu-22.04"
  location    = "nbg1"

  ssh_keys = [hcloud_ssh_key.deploy.id]

  user_data = file("cloud-init.yml")
}

resource "hcloud_firewall" "alfred" {
  name = "alfred-fw"
  rule {
    direction = "in"
    protocol  = "tcp"
    port      = "443"
    source_ips = ["0.0.0.0/0"]
  }
  rule {
    direction = "in"
    protocol  = "tcp"
    port      = "80"
    source_ips = ["0.0.0.0/0"]
  }
  rule {
    direction = "in"
    protocol  = "tcp"
    port      = "22"
    source_ips = ["YOUR_IP/32"]
  }
}
```

### 4.3 Pulumi

| Attribute | Details |
|-----------|---------|
| **What it solves** | Same as Terraform but you write infrastructure in real programming languages (TypeScript, Python, Go). |
| **Cost** | Free (open-source). Cloud service has free tier. |
| **Complexity** | ⭐⭐⭐ Medium — easier if you prefer code over Terraform's HCL. |

### 4.4 Chef & Puppet

| Attribute | Details |
|-----------|---------|
| **What it solves** | Configuration management (like Ansible but with agents and more complexity). |
| **Cost** | Free (open-source) |
| **Complexity** | ⭐⭐⭐⭐ High — Ruby DSL, agent-based, older paradigm. |

**Alfred recommendation:** Skip. Ansible does everything you need without agents.

### IaC RECOMMENDATION FOR ALFRED

```
IMMEDIATE:   Ansible                      ← Server config, Docker setup, deployments
WHEN NEEDED: Terraform                    ← If provisioning cloud VMs / multi-cloud
SKIP:        Chef, Puppet                 ← Over-engineered, agent-based
OPTIONAL:    Pulumi                       ← If you prefer TypeScript over HCL
```

---

## 5. REVERSE PROXY & LOAD BALANCING

### 5.1 Nginx

| Attribute | Details |
|-----------|---------|
| **What it solves** | HTTP reverse proxy, load balancer, static file server, SSL termination. The most popular web server in the world. |
| **Cost** | Free (open-source). NGINX Plus is paid ($2,500/yr). |
| **Complexity** | ⭐⭐ Low — config files are straightforward. |
| **Alfred already uses** | GoCodeMe has an nginx config (`gocodeme/nginx/nginx.conf`) that proxies to MCP server. |

**Alfred Nginx config (containerized):**
```nginx
# docker/nginx/default.conf
upstream php_app {
    server php-app:80;
}

upstream mcp_server {
    server mcp-server:3005;
}

upstream websocket_server {
    server websocket:3010;
}

server {
    listen 80;
    server_name gositeme.com www.gositeme.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name gositeme.com www.gositeme.com;

    ssl_certificate     /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;

    # PHP application
    location / {
        proxy_pass http://php_app;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # MCP Server (SSE — needs long timeout)
    location /mcp/ {
        proxy_pass http://mcp_server/;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_read_timeout 86400s;
        proxy_buffering off;
    }

    # WebSocket
    location /ws {
        proxy_pass http://websocket_server;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 86400s;
    }

    # Static files
    location ~* \.(js|css|png|jpg|jpeg|gif|svg|ico|woff2?)$ {
        proxy_pass http://php_app;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 5.2 Caddy

| Attribute | Details |
|-----------|---------|
| **What it solves** | Modern reverse proxy with **automatic HTTPS** (built-in Let's Encrypt / ZeroSSL). Zero-config SSL. |
| **Cost** | Free (open-source) |
| **Complexity** | ⭐ Very Low — simplest config of any reverse proxy. |

**Caddy equivalent of the above Nginx config:**
```caddyfile
gositeme.com {
    # Automatic HTTPS — no SSL config needed!

    handle /mcp/* {
        reverse_proxy mcp-server:3005
    }

    handle /ws {
        reverse_proxy websocket:3010
    }

    handle {
        reverse_proxy php-app:80
    }

    header {
        Strict-Transport-Security "max-age=31536000; includeSubDomains"
        X-Content-Type-Options "nosniff"
        X-Frame-Options "DENY"
    }
}
```

### 5.3 Traefik

| Attribute | Details |
|-----------|---------|
| **What it solves** | Cloud-native reverse proxy designed for Docker & Kubernetes. **Auto-discovers services** from container labels. |
| **Cost** | Free (open-source). Traefik Enterprise is paid. |
| **Complexity** | ⭐⭐⭐ Medium — Docker label-based config is powerful but has a learning curve. |

**How it works with Docker Compose:**
```yaml
# Add labels to services — Traefik discovers routes automatically
services:
  traefik:
    image: traefik:v3.0
    command:
      - "--providers.docker=true"
      - "--entrypoints.web.address=:80"
      - "--entrypoints.websecure.address=:443"
      - "--certificatesresolvers.letsencrypt.acme.email=admin@gositeme.com"
      - "--certificatesresolvers.letsencrypt.acme.storage=/letsencrypt/acme.json"
      - "--certificatesresolvers.letsencrypt.acme.httpchallenge.entrypoint=web"
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - traefik_certs:/letsencrypt

  mcp-server:
    labels:
      - "traefik.http.routers.mcp.rule=Host(`gositeme.com`) && PathPrefix(`/mcp`)"
      - "traefik.http.routers.mcp.tls.certresolver=letsencrypt"
      - "traefik.http.services.mcp.loadbalancer.server.port=3005"
```

### 5.4 HAProxy

| Attribute | Details |
|-----------|---------|
| **What it solves** | High-performance TCP/HTTP load balancer. Used by GitHub, Stack Overflow, Reddit. |
| **Cost** | Free (open-source). HAProxy Enterprise is paid. |
| **Complexity** | ⭐⭐⭐ Medium |

**Alfred recommendation:** Better for TCP-level load balancing (database connections, Redis). Overkill for Alfred at current scale.

### 5.5 Envoy

| Attribute | Details |
|-----------|---------|
| **What it solves** | Service mesh proxy from Lyft. L7 proxy with observability, gRPC support, circuit breaking. |
| **Cost** | Free (open-source, CNCF) |
| **Complexity** | ⭐⭐⭐⭐ High — designed for microservices at massive scale. |

**Alfred recommendation:** Skip. Only relevant if Alfred becomes a large-scale microservices architecture.

### REVERSE PROXY RECOMMENDATION FOR ALFRED

```
BEST FIT:    Caddy                       ← Auto-HTTPS, simplest config, great for small teams
ALTERNATIVE: Nginx                       ← If you want battle-tested + maximum performance
FUTURE:      Traefik                     ← If you move to Kubernetes/Docker Swarm
SKIP:        HAProxy, Envoy             ← Overkill for current scale
```

---

## 6. SSL / TLS

### 6.1 Let's Encrypt + Certbot

| Attribute | Details |
|-----------|---------|
| **What it solves** | Free, automated SSL certificates. 90-day validity, auto-renewal. |
| **Cost** | Free |
| **Complexity** | ⭐⭐ Low — `certbot certonly --nginx` or Docker-native solutions. |

**Docker approach (with Nginx):**
```yaml
services:
  certbot:
    image: certbot/certbot
    volumes:
      - certbot_certs:/etc/letsencrypt
      - certbot_www:/var/www/certbot
    entrypoint: "/bin/sh -c 'trap exit TERM; while :; do certbot renew; sleep 12h; done'"
```

### 6.2 Caddy Auto-SSL

| Attribute | Details |
|-----------|---------|
| **What it solves** | Automatic HTTPS with zero configuration. Provisions and renews Let's Encrypt certs automatically. Also supports ZeroSSL. |
| **Cost** | Free |
| **Complexity** | ⭐ None — literally just specify your domain name. |

**This is the strongest argument for choosing Caddy as Alfred's reverse proxy.**

### 6.3 cert-manager (Kubernetes)

| Attribute | Details |
|-----------|---------|
| **What it solves** | Automated certificate management for Kubernetes. Works with Let's Encrypt, Vault, Venafi. |
| **Cost** | Free (open-source) |
| **Complexity** | ⭐⭐⭐ Medium — Kubernetes CRDs. |
| **When for Alfred** | Only if/when you move to Kubernetes. |

### SSL RECOMMENDATION FOR ALFRED

```
WITH CADDY:   Zero config                ← Just use Caddy, SSL is automatic
WITH NGINX:   Certbot + cron renewal     ← Works, but requires setup
WITH K8s:     cert-manager               ← Future, if K8s adopted
```

---

## 7. BACKUP & DISASTER RECOVERY

### 7.1 MySQL/MariaDB Backups

**Automated mysqldump (Dockerized):**
```bash
#!/bin/bash
# scripts/backup-db.sh
BACKUP_DIR="/opt/alfred/backups/db"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

mkdir -p "$BACKUP_DIR"

# Dump all databases
docker exec alfred-mysql mariadb-dump \
  -u root -p"${MYSQL_ROOT_PASSWORD}" \
  --all-databases \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  | gzip > "${BACKUP_DIR}/alfred-${TIMESTAMP}.sql.gz"

# Remove backups older than retention period
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +${RETENTION_DAYS} -delete

# Upload to S3-compatible storage
rclone copy "${BACKUP_DIR}/alfred-${TIMESTAMP}.sql.gz" \
  b2:alfred-backups/db/ \
  --progress
```

### 7.2 Redis Persistence

| Strategy | Description | Alfred Config |
|----------|-------------|---------------|
| **RDB** | Point-in-time snapshots | Default, good for cache data |
| **AOF** | Append-only file (every write logged) | `--appendonly yes` — already in our Compose |
| **RDB + AOF** | Both (recommended) | `--appendonly yes --save 900 1 --save 300 10` |

Redis data volume (`redis_data`) in Docker Compose already persists data across restarts.

### 7.3 Restic

| Attribute | Details |
|-----------|---------|
| **What it solves** | Fast, encrypted, deduplicated backups. Supports local, S3, B2, SFTP, Azure, GCS backends. |
| **Cost** | Free (open-source) |
| **Complexity** | ⭐⭐ Low — single binary, simple CLI. |

```bash
# Initialize backup repository
restic init --repo b2:alfred-backups-restic

# Backup Alfred's data
restic backup \
  /opt/alfred/backups/db \
  /opt/alfred/docker-compose.yml \
  /opt/alfred/.env \
  /opt/alfred/docker \
  --repo b2:alfred-backups-restic \
  --tag alfred-daily

# Prune old backups (keep 7 daily, 4 weekly, 12 monthly)
restic forget \
  --keep-daily 7 --keep-weekly 4 --keep-monthly 12 \
  --prune \
  --repo b2:alfred-backups-restic
```

### 7.4 BorgBackup

| Attribute | Details |
|-----------|---------|
| **What it solves** | Same as Restic — deduplication, compression, encryption. Slightly faster for large datasets. |
| **Cost** | Free (open-source) |
| **Complexity** | ⭐⭐ Low |

**Compare:** Restic has more backend support (S3, B2 native). Borg requires SSH target or `borgmatic` wrapper.

### 7.5 Velero

| Attribute | Details |
|-----------|---------|
| **What it solves** | Kubernetes-native backup and disaster recovery. Backs up K8s resources + persistent volumes. |
| **Cost** | Free (open-source, VMware) |
| **Complexity** | ⭐⭐⭐ Medium |
| **When for Alfred** | Only if/when running on Kubernetes. |

### 7.6 S3-Compatible Storage Backends

| Service | Cost | Notes |
|---------|------|-------|
| **Backblaze B2** | $0.005/GB/month storage, $0.01/GB egress | Cheapest major provider. Restic/rclone native support. |
| **MinIO** | Free (self-hosted) | S3-compatible object storage you run yourself. |
| **Cloudflare R2** | $0.015/GB/month, **$0 egress** | Zero egress fees game-changer. |
| **Wasabi** | $0.0059/GB/month, no egress fees | Minimum 1TB billing ($5.99/mo min). |
| **AWS S3** | $0.023/GB/month + $0.09/GB egress | Most expensive, most battle-tested. |

### BACKUP RECOMMENDATION FOR ALFRED

```
DATABASE:    mysqldump daily → gzip → Backblaze B2           ← Immediate
REDIS:       AOF + RDB persistence (Docker volume)           ← Already configured in Compose
FILES:       Restic incremental → Backblaze B2               ← Weekly
STORAGE:     Backblaze B2 ($5/TB/month)                      ← Best price/reliability ratio
ALTERNATIVE: Cloudflare R2                                   ← If egress costs become an issue
FUTURE:      Velero                                          ← Only with Kubernetes
```

---

## 8. SECRETS MANAGEMENT

### 8.1 Current State (INSECURE)

Alfred likely stores secrets in:
- `.env` files on disk (readable by any process)
- PHP config files (`config/shield_config.php`)
- Plain-text environment variables in `ecosystem.config.js`

### 8.2 Docker Secrets (Compose)

| Attribute | Details |
|-----------|---------|
| **What it solves** | In Swarm mode, stores secrets encrypted at rest and mounts them as files in containers (`/run/secrets/`). |
| **Cost** | Free (built into Docker) |
| **Complexity** | ⭐ Very Low |

```yaml
# docker-compose.yml (Swarm mode)
secrets:
  db_password:
    external: true
  redis_password:
    external: true

services:
  mcp-server:
    secrets:
      - db_password
    # Access via: cat /run/secrets/db_password
```

**Limitation:** Only works in Docker Swarm mode, not standalone Compose.

### 8.3 HashiCorp Vault

| Attribute | Details |
|-----------|---------|
| **What it solves** | Enterprise-grade secrets management. Dynamic secrets, auto-rotation, audit logging, encryption as a service. |
| **Cost** | Free (open-source). HCP Vault has free tier (25 secrets). Paid tiers for HA. |
| **Complexity** | ⭐⭐⭐⭐ High — requires running and managing the Vault server itself. |
| **When for Alfred** | When handling sensitive customer data at enterprise scale, or for crypto wallet key management. |

### 8.4 Infisical

| Attribute | Details |
|-----------|---------|
| **What it solves** | Modern, developer-friendly secrets manager. Dashboard, CLI, SDKs for Node.js/Python/PHP. Auto-sync to .env files. |
| **Cost** | Free (open-source, self-hostable). Cloud free for up to 5 team members. |
| **Complexity** | ⭐⭐ Low — designed for developer experience. |

```bash
# Install CLI
npm install -g @infisical/cli

# Pull secrets for deployment
infisical export --env=prod --format=dotenv > .env

# In Docker Compose:
# infisical run -- docker compose up -d
```

**Best fit for Alfred:** Modern, easy to adopt, self-hostable, SDKs for Node.js.

### 8.5 SOPS (Secrets OPerationS)

| Attribute | Details |
|-----------|---------|
| **What it solves** | Encrypts values in YAML/JSON/ENV files while keeping keys readable. Committed to git safely. Uses AWS KMS, GCP KMS, Azure Key Vault, or PGP for encryption. |
| **Cost** | Free (open-source, Mozilla) |
| **Complexity** | ⭐⭐ Low |

```bash
# Encrypt .env file
sops --encrypt --pgp FINGERPRINT .env > .env.encrypted

# Decrypt at deploy time
sops --decrypt .env.encrypted > .env
```

### 8.6 Doppler

| Attribute | Details |
|-----------|---------|
| **What it solves** | Cloud-based secrets manager with CLI, dashboard, and integrations for Docker, CI/CD, Kubernetes. |
| **Cost** | Free for up to 5 users, 3 projects. Teams: $6/user/month. |
| **Complexity** | ⭐ Very Low — easiest DX of all options. |

```bash
# Deploy with secrets injected
doppler run -- docker compose up -d
```

### SECRETS RECOMMENDATION FOR ALFRED

```
IMMEDIATE:   SOPS + age/PGP              ← Encrypt .env files in git, zero infrastructure
SHORT-TERM:  Infisical (self-hosted)      ← Dashboard, rotation, SDKs, team-friendly
ENTERPRISE:  HashiCorp Vault              ← If managing crypto wallets, HSM integration needed
QUICK WIN:   Doppler (cloud)              ← Fastest setup, SaaS dependency
```

---

## 9. EDGE DEPLOYMENT

### 9.1 Cloudflare Workers

| Attribute | Details |
|-----------|---------|
| **What it solves** | Run JavaScript/TypeScript at 300+ edge locations worldwide. Sub-millisecond cold starts. |
| **Cost** | Free: 100K requests/day. Paid: $5/month + $0.50/million requests. |
| **Complexity** | ⭐⭐ Low |
| **Alfred use case** | API rate limiting, geo-routing, A/B testing, bot protection, caching Alfred's static assets. |

```javascript
// worker.js — Edge rate limiter for Alfred API
export default {
  async fetch(request, env) {
    const ip = request.headers.get('CF-Connecting-IP');
    const key = `rate:${ip}`;
    const count = await env.RATE_LIMIT.get(key);

    if (count && parseInt(count) > 100) {
      return new Response('Rate limited', { status: 429 });
    }

    await env.RATE_LIMIT.put(key, (parseInt(count || '0') + 1).toString(), { expirationTtl: 60 });
    return fetch(request);
  }
};
```

### 9.2 Fly.io

| Attribute | Details |
|-----------|---------|
| **What it solves** | Run Docker containers at edge locations worldwide. Full VM micro-VMs (Firecracker), not just serverless. |
| **Cost** | Free: 3 shared VMs, 160GB bandwidth. Paid: from $1.94/mo per VM. |
| **Complexity** | ⭐⭐ Low — `fly launch` deploys a Dockerfile. |
| **Alfred use case** | Deploy WebSocket server or MCP server to multiple regions for low-latency access. |

```bash
# Deploy Alfred WebSocket to Fly.io
cd websocket
fly launch --name alfred-ws-edge
fly scale count 3  # 3 instances across regions
fly regions add ord ams sin  # Chicago, Amsterdam, Singapore
```

### 9.3 Railway

| Attribute | Details |
|-----------|---------|
| **What it solves** | Deploy from GitHub in seconds. Managed infrastructure — databases, Redis, cron jobs built-in. |
| **Cost** | Trial: $5 credit. Pro: $5/month + usage ($0.000231/min vCPU, $0.000231/MB RAM/min). |
| **Complexity** | ⭐ Very Low — point to repo and deploy. |
| **Alfred use case** | Quick staging environments. Deploy entire Alfred stack from one dashboard. |

### 9.4 Deno Deploy

| Attribute | Details |
|-----------|---------|
| **What it solves** | Edge runtime for Deno/TypeScript. Similar to Cloudflare Workers but with more Node.js compatibility. |
| **Cost** | Free: 100K requests/day. Pro: $10/month. |
| **Complexity** | ⭐⭐ Low |
| **Alfred use case** | Edge API proxy, but requires rewriting from Node.js to Deno. Lower priority. |

### 9.5 Vercel Edge Functions

| Attribute | Details |
|-----------|---------|
| **What it solves** | Edge compute for Next.js and other frameworks. Great for frontend, limited for backend services. |
| **Cost** | Hobby free. Pro: $20/month. |
| **Complexity** | ⭐ Very Low |
| **Alfred use case** | Limited — designed for Next.js frontends, not Alfred's PHP + Node stack. |

### EDGE RECOMMENDATION FOR ALFRED

```
IMMEDIATE:   Cloudflare Workers           ← Edge caching, rate limiting, bot protection ($5/mo)
CONSIDER:    Fly.io                       ← If multi-region WebSocket needed
STAGING:     Railway                      ← Quick staging/preview environments
SKIP:        Deno Deploy, Vercel Edge     ← Wrong fit for Alfred's stack
```

---

## 10. SELF-HOSTING PLATFORMS (PaaS)

### 10.1 Coolify

| Attribute | Details |
|-----------|---------|
| **What it solves** | Self-hosted Heroku/Vercel/Netlify alternative. One-click deploys, automatic SSL, database provisioning. Supports Docker Compose natively. |
| **Cost** | Free (100% open-source). Cloud hosted from $5/month. |
| **Complexity** | ⭐ Very Low — GUI dashboard, git push deploys. |
| **Website** | https://coolify.io |

**Why Coolify is potentially the best fit for Alfred:**
- Deploy Alfred's entire `docker-compose.yml` with one click.
- Built-in Let's Encrypt SSL.
- Database management (MySQL, Redis, Postgres).
- GitHub/GitLab webhook deployments.
- Server monitoring and log viewing.
- Runs on any VPS ($5 DigitalOcean, Hetzner).
- Supports multi-server deployments.

### 10.2 CapRover

| Attribute | Details |
|-----------|---------|
| **What it solves** | Self-hosted PaaS built on Docker Swarm. One-click apps (MySQL, Redis, PostgreSQL), automatic SSL. |
| **Cost** | Free (open-source) |
| **Complexity** | ⭐⭐ Low — web dashboard, CLI, Dockerfile/Compose deploys. |

**Comparison with Coolify:** CapRover uses Docker Swarm (built-in clustering), Coolify uses plain Docker. Both equally capable for Alfred.

### 10.3 Dokku

| Attribute | Details |
|-----------|---------|
| **What it solves** | "The smallest PaaS." Heroku-compatible — `git push dokku main` to deploy. Buildpacks or Dockerfile. |
| **Cost** | Free (open-source) |
| **Complexity** | ⭐⭐ Low — CLI-driven, no web UI (but plugins exist). |
| **Limitation** | Single-server only. No built-in multi-server support. |

```bash
# Deploy Alfred on Dokku
dokku apps:create alfred
dokku mysql:create alfred-db
dokku redis:create alfred-cache
dokku mysql:link alfred-db alfred
dokku redis:link alfred-cache alfred
git push dokku main
```

### 10.4 Portainer

| Attribute | Details |
|-----------|---------|
| **What it solves** | GUI for managing Docker containers, images, volumes, networks. Not a PaaS — it's a Docker management dashboard. |
| **Cost** | Free (Community Edition, up to 3 nodes). Business: $0.60/node/month. |
| **Complexity** | ⭐ Very Low — web UI wraps Docker CLI. |

**Alfred use case:** Run alongside Docker Compose for visual container management, log viewing, and restart capabilities without SSH.

```yaml
# Add Portainer to Alfred's docker-compose.yml
  portainer:
    image: portainer/portainer-ce
    container_name: alfred-portainer
    restart: unless-stopped
    ports:
      - "9443:9443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
      - portainer_data:/data
```

### SELF-HOSTING RECOMMENDATION FOR ALFRED

```
BEST FIT:    Coolify                     ← Full PaaS, Docker Compose native, GUI, free
COMPLEMENT:  Portainer                   ← Add to any Docker setup for visual management
ALTERNATIVE: CapRover                    ← If you prefer Docker Swarm built-in
MINIMALIST:  Dokku                       ← If you prefer CLI-only, Heroku-style
```

---

## MASTER IMPLEMENTATION ROADMAP

### Phase 1: Foundation (Week 1–2)
| Task | Tool | Effort |
|------|------|--------|
| Write Dockerfiles for PHP, MCP, WebSocket | Docker | 2 hours |
| Create `docker-compose.yml` | Docker Compose | 2 hours |
| Test full stack locally with `docker compose up` | Docker Compose | 4 hours |
| Install Portainer for GUI management | Portainer | 30 min |
| Set up `.env` file with secrets | SOPS | 1 hour |

### Phase 2: CI/CD (Week 2–3)
| Task | Tool | Effort |
|------|------|--------|
| Create GitHub Actions workflow (test → build → deploy) | GitHub Actions | 3 hours |
| Add PHP linting, Node.js tests to pipeline | GitHub Actions | 2 hours |
| Set up GitHub Container Registry (GHCR) | GitHub Actions | 1 hour |
| Add deploy step (SSH → docker compose pull → up) | GitHub Actions | 1 hour |

### Phase 3: Infrastructure (Week 3–4)
| Task | Tool | Effort |
|------|------|--------|
| Write Ansible playbook for server provisioning | Ansible | 4 hours |
| Set up Caddy or Nginx as reverse proxy | Caddy | 2 hours |
| Configure automatic SSL | Caddy (auto) | 0 min |
| Set up automated database backups | mysqldump + Restic | 2 hours |
| Configure Backblaze B2 for off-site backups | rclone | 1 hour |

### Phase 4: Hardening (Week 4–5)
| Task | Tool | Effort |
|------|------|--------|
| Migrate secrets to Infisical or SOPS | Infisical/SOPS | 3 hours |
| Set up Cloudflare Workers for edge rate limiting | Cloudflare | 2 hours |
| Add health checks and monitoring | Docker healthchecks | 1 hour |
| Document runbooks for disaster recovery | Markdown | 2 hours |

### Phase 5: Scale (When Needed)
| Task | Tool | Trigger |
|------|------|---------|
| Switch to Docker Swarm | Docker | >1,000 concurrent users |
| Evaluate K3s | K3s | Need autoscaling or complex scheduling |
| Multi-region WebSocket | Fly.io | Global user base |
| Evaluate Coolify as management layer | Coolify | Team grows beyond 1 ops person |

---

## COST COMPARISON

### Alfred's Current Cost
| Item | Cost |
|------|------|
| DirectAdmin VPS (estimated) | $20–50/month |
| **Total** | **~$20–50/month** |

### Alfred Modernized (Phase 1–4 Complete)
| Item | Cost |
|------|------|
| VPS (Hetzner CPX31: 4 vCPU, 8GB) | €12/month (~$13) |
| Backblaze B2 (50GB backups) | $0.25/month |
| Cloudflare (free tier + Workers) | $5/month |
| GitHub Actions (free tier) | $0/month |
| SSL (Let's Encrypt / Caddy) | $0/month |
| Docker, Compose, Portainer | $0/month |
| Ansible, SOPS | $0/month |
| **Total** | **~$18/month** |

### Enterprise Scale (Phase 5)
| Item | Cost |
|------|------|
| 3× Hetzner nodes (K3s cluster) | €36/month (~$39) |
| Managed DB (if desired) | $15–50/month |
| Fly.io (3 edge nodes) | $6/month |
| Backblaze B2 (500GB) | $2.50/month |
| **Total** | **~$60–100/month** |

---

## TOOL DECISION MATRIX

| Category | Recommended | Complexity | Cost | Priority |
|----------|------------|------------|------|----------|
| **Containerization** | Docker + Docker Compose | ⭐⭐ | Free | 🔴 P0 |
| **Orchestration** | Docker Compose → Swarm → K3s | ⭐⭐ to ⭐⭐⭐ | Free | 🟡 P2 |
| **CI/CD** | GitHub Actions | ⭐⭐ | Free | 🔴 P0 |
| **IaC** | Ansible | ⭐⭐ | Free | 🟠 P1 |
| **Reverse Proxy** | Caddy | ⭐ | Free | 🔴 P0 |
| **SSL** | Caddy auto-SSL | ⭐ | Free | 🔴 P0 (bundled) |
| **Backups** | mysqldump + Restic → B2 | ⭐⭐ | ~$0.25/mo | 🔴 P0 |
| **Secrets** | SOPS (now) → Infisical (later) | ⭐⭐ | Free | 🟠 P1 |
| **Edge** | Cloudflare Workers | ⭐⭐ | $0–5/mo | 🟡 P2 |
| **Self-hosted PaaS** | Coolify + Portainer | ⭐ | Free | 🟡 P2 |

---

## SUMMARY

Alfred's infrastructure modernization follows a clear progression:

1. **Containerize first** — Docker Compose replaces PM2 and manual process management. Every service (PHP, MCP, WebSocket, MySQL, Redis) gets its own container. This is the single highest-impact change.

2. **Automate deployments** — GitHub Actions replaces SSH/FTP. `git push main` → tests → builds → deploys. Zero-touch production updates.

3. **Harden the perimeter** — Caddy handles SSL automatically. SOPS encrypts secrets in git. Restic sends encrypted backups to Backblaze B2.

4. **Scale when needed** — Docker Swarm or K3s when concurrent users exceed single-server capacity. Fly.io for multi-region WebSocket. Cloudflare Workers for edge intelligence.

Every tool recommended above is **free and open-source**. The entire modernized stack costs less than Alfred's current DirectAdmin hosting.

---

## CROSS-REFERENCES

| Document | Relationship |
|----------|-------------|
| [ALFRED_FAILSAFE_OPERATIONS.md](ALFRED_FAILSAFE_OPERATIONS.md) | Disaster recovery, failover chains, incident response, operational runbooks, deployment strategy (reconciles PM2 vs Docker: PM2 now, Docker Month 4) |
| [ALFRED_AUTONOMY_METAVERSE_MASTERPLAN.md](ALFRED_AUTONOMY_METAVERSE_MASTERPLAN.md) | The Kingdom metaverse runs on this infrastructure stack — Socket.IO + Redis + MySQL |
| [ALFRED_ANALYTICS_MONITORING_RESEARCH.md](ALFRED_ANALYTICS_MONITORING_RESEARCH.md) | Monitoring and observability tools (Prometheus, Grafana, Sentry, Uptime Kuma) that run on this infrastructure |
| [ALFRED_SECURITY_CRYPTO_RESEARCH.md](ALFRED_SECURITY_CRYPTO_RESEARCH.md) | Security scanning pipeline and authentication systems deployed on this stack |
