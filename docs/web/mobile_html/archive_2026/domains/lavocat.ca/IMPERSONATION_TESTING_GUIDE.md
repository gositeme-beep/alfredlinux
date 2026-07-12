# 🔄 Impersonation System Testing Guide

## 🎯 **Quick Start Testing**

### 1. **Login as Super Admin**
- **URL**: `https://localhost:3443/auth/login`
- **Email**: `dannywperez@msn.com`
- **Password**: `your_password`

### 2. **Access User Management**
- **URL**: `https://localhost:3443/admin/users`
- Look for **"🔄 Sign In As"** buttons next to each user

### 3. **Test Different User Types**

## 👥 **Available Test Users**

| Role | Name | Email | Password | Expected Features |
|------|------|-------|----------|-------------------|
| 👤 **USER** | Danny PEREZ | support@gositeme.com | - | Basic dashboard, profile, applications |
| 📋 **SECRETARY** | Isabelle Gagnon | legal.secretary@lmep.ca | demo123 | Admin dashboard, case support, documents |
| 📚 **CLERK** | Sophie Tremblay | law.clerk@lmep.ca | demo123 | Research tools, document access, case viewing |
| ⚖️ **LAWYER** | Marie Dubois | lead.attorney@lmep.ca | demo123 | Full case access, assignments, analytics |
| ⚖️ **LAWYER** | Jean-Pierre Martin | assistant.lawyer@lmep.ca | demo123 | Case management, client communication |
| 👩‍⚖️ **ADMIN** | Admin User | admin@example.com | - | User management, newsletter, export functions |

## 🧪 **Step-by-Step Testing Process**

### **Step 1: Test USER Role**
1. Click **"🔄 Sign In As"** next to Danny PEREZ (USER)
2. **Expected Behavior**:
   - ✅ Redirected to `/user/dashboard`
   - ✅ Orange impersonation banner appears at top
   - ✅ Limited navigation (no admin options)
   - ✅ Can access profile, applications
   - ❌ Cannot access admin features

### **Step 2: Test SECRETARY Role**
1. Click **"Stop Impersonating"** to return to Super Admin
2. Click **"🔄 Sign In As"** next to Isabelle Gagnon (SECRETARY)
3. **Expected Behavior**:
   - ✅ Access to admin dashboard
   - ✅ Can view case management
   - ✅ Document handling capabilities
   - ❌ Cannot create new users
   - ❌ Limited admin features

### **Step 3: Test CLERK Role**
1. Stop impersonation and try Sophie Tremblay (CLERK)
2. **Expected Behavior**:
   - ✅ Research tools access
   - ✅ Document viewing
   - ✅ Case viewing (read-only)
   - ❌ Cannot assign cases
   - ❌ No user management

### **Step 4: Test LAWYER Role**
1. Stop impersonation and try Marie Dubois (LAWYER)
2. **Expected Behavior**:
   - ✅ Full case access
   - ✅ Case assignments dashboard
   - ✅ Analytics dashboard
   - ✅ Client communication
   - ✅ Can view and manage assigned cases
   - ❌ Cannot manage users (unless also ADMIN)

### **Step 5: Test ADMIN Role**
1. Stop impersonation and try Admin User (ADMIN)
2. **Expected Behavior**:
   - ✅ User management access
   - ✅ Newsletter system
   - ✅ Export functions (PDF/CSV)
   - ✅ All admin features
   - ❌ Cannot impersonate others (only SUPERADMIN can)

## 🔍 **What to Verify During Each Test**

### **Navigation Access**
- Check sidebar menu items
- Verify role-appropriate features are visible
- Confirm restricted features are hidden

### **Page Access**
- Try accessing admin URLs directly
- Verify proper redirects for unauthorized access
- Check error handling

### **Functional Testing**
- Test creating/editing within role permissions
- Verify data access is properly scoped
- Check API endpoint restrictions

## 🚨 **Security Validation**

### **Critical Security Tests**
1. **Cannot Impersonate SUPERADMIN**
   - Verify no "Sign In As" button for SUPERADMIN users
   - API should reject attempts to impersonate SUPERADMIN

2. **Session Logging**
   - Check database for impersonation session records
   - Verify IP address and user agent are logged

3. **Proper Session Cleanup**
   - Ensure stopping impersonation restores original user
   - Verify no session leakage between users

4. **Rate Limiting**
   - Try rapid impersonation attempts
   - Should be rate limited for security

## 🎯 **Expected User Experience Flow**

### **Successful Impersonation**
1. 🔄 Click "Sign In As" → Loading state
2. 🎯 Redirect to appropriate dashboard
3. 🟠 Orange banner shows impersonation status
4. 👤 Navigation reflects user's role
5. 🔙 "Stop Impersonating" button works
6. ✅ Return to Super Admin dashboard

### **Visual Indicators**
- **Impersonation Banner**: Orange gradient at top
- **User Info**: Shows current impersonated user
- **Original User**: Shows who is impersonating
- **Stop Button**: Clear exit option

## 🔗 **Quick Test URLs**

| Feature | URL | Purpose |
|---------|-----|---------|
| User Management | `/admin/users` | Main impersonation interface |
| Super Admin | `/admin/super` | System overview |
| Case Assignments | `/admin/case-assignments` | Test lawyer/admin access |
| Analytics | `/admin/analytics-dashboard` | Test advanced features |
| Newsletter | `/admin/newsletter` | Test admin-only features |
| User Dashboard | `/user/dashboard` | Test basic user experience |

## 🐛 **Troubleshooting**

### **Common Issues**
1. **Impersonation Not Working**
   - Check server logs for JWT callback errors
   - Verify database has ImpersonationSession table
   - Ensure user has SUPERADMIN role

2. **Permissions Not Applied**
   - Verify API endpoints include SUPERADMIN in role checks
   - Check if permissions were set up correctly
   - Review role hierarchy in auth-utils.ts

3. **Session Issues**
   - Clear browser cookies
   - Restart development server
   - Check NextAuth configuration

### **Debug Commands**
```bash
# Check user roles
npm run check-user dannywperez@msn.com

# View all users for testing
npm run test-impersonation

# Check permissions setup
npm run setup-permissions
```

## ✅ **Success Criteria**

**Impersonation system is working correctly when:**
- ✅ Can impersonate all roles except SUPERADMIN
- ✅ Each role sees appropriate features only
- ✅ Security restrictions are enforced
- ✅ Can easily stop impersonation and return
- ✅ Sessions are properly logged and managed
- ✅ No permission leakage between roles

---

**🎉 Happy Testing!** The impersonation system allows you to experience your application from each user's perspective, ensuring proper role-based access control. 