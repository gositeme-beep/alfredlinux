# 🔍 Impersonation Functionality Audit Report

## 📊 Current State Analysis

### ✅ What's Working:
1. **Database Schema**: ImpersonationSession table exists and is properly structured
2. **API Endpoints**: Both `/api/admin/impersonate` and `/api/admin/stop-impersonation` are implemented
3. **Session Creation**: Impersonation sessions are being created in the database
4. **Admin Users**: 3 admin users exist (2 SUPERADMIN, 1 ADMIN)
5. **Target Users**: 5+ regular users available for impersonation

### ⚠️ Issues Identified:

#### 1. **Active Impersonation Session Found**
- **User**: dannywperez@msn.com (SUPERADMIN) 
- **Impersonating**: justine.monty@adwavocats.com (LAWYER)
- **Status**: ACTIVE (expires Sun Jul 06 2025 03:19:02)
- **Issue**: This suggests impersonation is working but session state may not be properly reflected in the UI

#### 2. **Potential Session State Issues**
- The JWT callback in `auth.ts` should be checking for active impersonation sessions
- Session updates may not be triggering properly after impersonation
- Frontend may not be reflecting the impersonated state

#### 3. **Frontend Button Logic**
- The "Sign In As" button only shows for SUPERADMIN users
- Button may be disabled due to `isImpersonating` state
- Session update may not be triggering UI refresh

## 🔧 Root Cause Analysis

### Primary Issue: Session Update Flow
The impersonation process involves:
1. ✅ API creates impersonation session in database
2. ✅ JWT callback should detect active session
3. ❌ **Session update may not be triggering properly**
4. ❌ **Frontend may not be reflecting impersonated state**

### Secondary Issue: Button State
- The "Sign In As" button is disabled when `isImpersonating` is true
- If session update fails, button remains disabled
- User can't start new impersonation or stop current one

## 🛠️ Recommended Fixes

### 1. **Immediate Fix - Clear Active Session**
```javascript
// Run this to clear the stuck impersonation session
await prisma.impersonationSession.updateMany({
  where: {
    originalUserId: "cmcpzyavn0002vjz01slrcn51", // dannywperez@msn.com
    isActive: true
  },
  data: {
    isActive: false,
    endedAt: new Date()
  }
});
```

### 2. **Session Update Enhancement**
The JWT callback should be more robust in handling session updates:

```typescript
// In auth.ts JWT callback
if (trigger === 'update') {
  // Force re-check of impersonation state
  const impersonationSession = await prisma.impersonationSession.findFirst({
    where: {
      originalUserId: token.id as string,
      isActive: true,
      expiresAt: { gt: new Date() }
    }
  });
  // ... rest of logic
}
```

### 3. **Frontend Button State Fix**
```typescript
// In useImpersonation hook
const isCurrentlyImpersonating = session?.user?.isImpersonating || false;

// Only disable button if actually impersonating
const canImpersonate = !isCurrentlyImpersonating && !isImpersonating;
```

### 4. **Add Debug Logging**
```typescript
// Add to useImpersonation hook
console.log('Impersonation State:', {
  isImpersonating,
  isCurrentlyImpersonating: session?.user?.isImpersonating,
  originalUser: session?.user?.originalUser,
  currentUser: session?.user
});
```

## 🧪 Testing Steps

### 1. **Clear Current Session**
```bash
node -e "
const { PrismaClient } = require('@prisma/client');
const prisma = new PrismaClient();
(async () => {
  await prisma.impersonationSession.updateMany({
    where: { isActive: true },
    data: { isActive: false, endedAt: new Date() }
  });
  console.log('Cleared all active sessions');
  await prisma.$disconnect();
})();
"
```

### 2. **Test Impersonation Flow**
1. Login as SUPERADMIN (dannywperez@msn.com)
2. Go to `/admin/users`
3. Click "Sign In As" on a regular user
4. Check browser console for logs
5. Verify session state changes
6. Test stopping impersonation

### 3. **Monitor Database**
```bash
# Watch for new sessions
node -e "
const { PrismaClient } = require('@prisma/client');
const prisma = new PrismaClient();
setInterval(async () => {
  const sessions = await prisma.impersonationSession.findMany({
    where: { isActive: true },
    include: { originalUser: true, impersonatedUser: true }
  });
  console.log('Active sessions:', sessions.length);
  sessions.forEach(s => console.log(\`\${s.originalUser.email} -> \${s.impersonatedUser.email}\`));
}, 5000);
"
```

## 🎯 Expected Behavior After Fix

1. **Button State**: "Sign In As" button should be enabled for SUPERADMIN users
2. **Impersonation Start**: Clicking button should create session and redirect
3. **Session State**: User should see impersonated user's dashboard
4. **Stop Impersonation**: Should restore original user session
5. **UI Feedback**: Toast notifications should confirm actions

## 📋 Action Items

- [ ] Clear current active impersonation session
- [ ] Test impersonation flow with fresh session
- [ ] Add debug logging to frontend
- [ ] Verify session update triggers
- [ ] Test button state logic
- [ ] Monitor for any remaining issues

## 🔍 Next Steps

1. **Immediate**: Clear the stuck session and test
2. **Short-term**: Add comprehensive logging
3. **Long-term**: Implement session state monitoring
4. **Prevention**: Add session cleanup on app startup 