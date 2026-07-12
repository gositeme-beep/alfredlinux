# 🌐 DOMAIN FORWARDING SETUP FOR LAVOCAT.CA

## ✅ **FORWARDING FILES CREATED**

I've created three different forwarding methods for you:

### **1. HTML Redirect (index.html)**
- **File**: `index.html`
- **Method**: Meta refresh + JavaScript redirect
- **Works with**: All web servers
- **Features**: Beautiful loading page with spinner

### **2. PHP Redirect (redirect.php)**
- **File**: `redirect.php`
- **Method**: HTTP 301 redirect headers
- **Works with**: PHP-enabled servers
- **Features**: Immediate redirect, SEO-friendly

### **3. Apache Redirect (.htaccess)**
- **File**: `.htaccess`
- **Method**: Apache rewrite rules
- **Works with**: Apache servers
- **Features**: Server-level redirect, most efficient

## 🚀 **HOW TO ACTIVATE FORWARDING**

### **Option 1: HTML Redirect (Recommended)**
The `index.html` file is already in place and will work immediately.

### **Option 2: PHP Redirect**
If your hosting supports PHP, you can rename `redirect.php` to `index.php`:
```bash
mv redirect.php index.php
```

### **Option 3: Apache Redirect**
The `.htaccess` file should work automatically if your hosting uses Apache.

## 🔧 **TESTING YOUR FORWARDING**

### **Test the redirect locally:**
```bash
# Test HTML redirect
curl -I http://localhost

# Test PHP redirect (if enabled)
curl -I http://localhost/redirect.php

# Test application directly
curl -k -I https://15.235.50.60:3443
```

### **Expected Results:**
- **HTML**: Should show redirect page briefly, then redirect
- **PHP**: Should redirect immediately with 301 status
- **Apache**: Should redirect immediately with 301 status
- **Application**: Should return 200 OK

## 🌐 **DNS CONFIGURATION**

Make sure your domain `lavocat.ca` points to your server IP `15.235.50.60`:

### **DNS Records Needed:**
```
Type: A
Name: @ (or lavocat.ca)
Value: 15.235.50.60
TTL: 300 (or default)
```

### **Optional: WWW Subdomain**
```
Type: A
Name: www
Value: 15.235.50.60
TTL: 300 (or default)
```

## 📊 **ACCESS URLs**

Once DNS is configured, users can access:

- **Primary**: https://lavocat.ca (redirects to application)
- **Direct**: https://15.235.50.60:3443 (direct access)
- **HTTP**: http://lavocat.ca (redirects to HTTPS)

## 🔍 **TROUBLESHOOTING**

### **If redirect doesn't work:**
1. **Check DNS**: `nslookup lavocat.ca`
2. **Check server**: `curl -I https://15.235.50.60:3443`
3. **Check files**: `ls -la index.html redirect.php .htaccess`

### **Common Issues:**
- **DNS not propagated**: Wait 24-48 hours
- **Server down**: Restart application
- **Port blocked**: Check firewall settings

## 🛡️ **SECURITY CONSIDERATIONS**

### **SSL Certificates:**
- Current: Self-signed certificates
- Recommended: Let's Encrypt for production

### **Security Headers:**
- X-Frame-Options: DENY
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block

## 📈 **MONITORING**

### **Check if forwarding is working:**
```bash
# Test domain resolution
nslookup lavocat.ca

# Test redirect
curl -I http://lavocat.ca

# Test application
curl -k -I https://15.235.50.60:3443
```

### **Application Status:**
- **Server**: ✅ Running on ports 3000/3443
- **SSL**: ✅ Self-signed certificates working
- **Database**: ✅ SQLite connected
- **Authentication**: ✅ Working

## 🎯 **NEXT STEPS**

1. **DNS Configuration**: Point lavocat.ca to 15.235.50.60
2. **SSL Certificates**: Consider Let's Encrypt
3. **Process Management**: Setup PM2 for reliability
4. **Monitoring**: Add uptime monitoring

---
**Status**: ✅ FORWARDING FILES READY
**Application**: ✅ RUNNING ON 15.235.50.60:3443
**Domain**: lavocat.ca
**Last Updated**: $(date) 