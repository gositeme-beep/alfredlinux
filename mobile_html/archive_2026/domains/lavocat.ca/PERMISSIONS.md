# 🔐 Role & Permission System Documentation

## Overview
This document outlines the role-based access control (RBAC) system implemented in the legal platform.

## Roles Hierarchy

### 1. **SUPERADMIN** (Highest Level)
- **Access:** All system features and data
- **Can impersonate:** Anyone
- **Dashboard:** `/admin/dashboard`
- **Key permissions:**
  - Manage all users and roles
  - Access all cases and data
  - System administration
  - Override any permissions

### 2. **ADMIN** (Administrative Level)
- **Access:** Administrative features and oversight
- **Can impersonate:** Anyone except SUPERADMIN
- **Dashboard:** `/admin/dashboard`
- **Key permissions:**
  - Manage users (except SUPERADMIN)
  - Access all cases
  - System configuration
  - Analytics and reporting

### 3. **LAWYER** (Legal Professional)
- **Access:** Legal case management and client services
- **Can impersonate:** None
- **Dashboard:** `/lawyer/dashboard`
- **Key permissions:**
  - Manage assigned cases
  - Access client data for assigned cases
  - Team management
  - Document management

### 4. **CLIENT/USER** (End User)
- **Access:** Personal case management and services
- **Can impersonate:** None
- **Dashboard:** `/client/dashboard` or `/user/dashboard`
- **Key permissions:**
  - View own cases
  - Manage personal profile
  - Submit applications
  - Access public cases

### 5. **Support Roles**
- **SECRETARY, ASSISTANT, CLERK, PARALEGAL**
- **Dashboard:** `/support/dashboard`
- **Key permissions:**
  - Support assigned cases
  - Document processing
  - Administrative tasks

### 6. **Specialized Roles**
- **JUDGE, JURIST, MEDIATOR, INVESTIGATOR, EXPERT_WITNESS, NOTARY**
- **Dashboard:** Role-specific dashboards
- **Key permissions:**
  - Role-specific case access
  - Specialized tools and features

## Permission Matrix

| Feature | SUPERADMIN | ADMIN | LAWYER | CLIENT | Support | Specialized |
|---------|:----------:|:-----:|:------:|:------:|:-------:|:-----------:|
| **Dashboard Access** |
| Admin Dashboard | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Lawyer Dashboard | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Client Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Support Dashboard | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ |
| **Case Management** |
| View All Cases | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| View Assigned Cases | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| View Own Cases | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| View Public Cases | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Create Cases | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Edit Cases | ✅ | ✅ | ✅* | ✅* | ❌ | ❌ |
| Delete Cases | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **User Management** |
| Manage All Users | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Manage Team | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| View Own Profile | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Impersonation** |
| Impersonate Anyone | ✅ | ✅* | ❌ | ❌ | ❌ | ❌ |
| **Chat & Communication** |
| Admin Rooms | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Legal Support Rooms | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| Public Rooms | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

*Admin cannot impersonate SUPERADMIN
*Lawyers can edit assigned cases, clients can edit own cases

## Implementation Details

### Frontend Protection
```typescript
// Use useRequireRole hook for page protection
import { useRequireRole, USER_ROLES } from '@/lib/auth-utils';

const { isAuthorized } = useRequireRole([
  USER_ROLES.LAWYER,
  USER_ROLES.ADMIN,
  USER_ROLES.SUPERADMIN
], '/auth/login');
```

### Backend Protection
```typescript
// Use requireAuth and requireRole for API protection
import { requireAuth, requireRole } from '@/lib/auth-utils';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  const session = await requireAuth(req, res);
  await requireRole(req, res, [USER_ROLES.ADMIN, USER_ROLES.SUPERADMIN]);
  // ... API logic
}
```

### Case Access Control
```typescript
// Use canAccessCase for case-specific permissions
import { canAccessCase } from '@/lib/case-permissions';

if (!canAccessCase(session.user, caseData)) {
  return res.status(403).json({ error: 'Access denied' });
}
```

## Security Best Practices

### 1. **Always Use Permission Guards**
- Every page should use `useRequireRole` or `canAccessPage`
- Every API endpoint should use `requireAuth` and `requireRole`
- Never rely on UI hiding alone for security

### 2. **Role Validation**
- Always validate roles on both frontend and backend
- Use `USER_ROLES` constants instead of hardcoded strings
- Implement proper error handling for unauthorized access

### 3. **Impersonation Security**
- Log all impersonation actions
- Ensure impersonation is always reversible
- Limit impersonation to necessary roles only

### 4. **Data Access Control**
- Use `canAccessCase` for case-specific data
- Implement row-level security where appropriate
- Validate ownership before allowing modifications

## Common Patterns

### Page Protection Pattern
```typescript
const MyPage = () => {
  const { isAuthorized } = useRequireRole([USER_ROLES.LAWYER], '/auth/login');
  
  if (!isAuthorized) {
    return <div>Loading...</div>;
  }
  
  return <div>Protected content</div>;
};
```

### API Protection Pattern
```typescript
export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  try {
    const session = await requireAuth(req, res);
    await requireRole(req, res, [USER_ROLES.ADMIN]);
    
    // API logic here
    res.status(200).json({ data: 'success' });
  } catch (error) {
    res.status(403).json({ error: 'Access denied' });
  }
}
```

## Testing Permissions

### Test Page Access
```typescript
// Visit /test-access to see current user's permissions
const TestAccessPage = () => {
  const { data: session } = useSession();
  
  return (
    <div>
      <h2>Permission Test</h2>
      <p>Role: {session?.user?.role}</p>
      <p>Can access admin: {canAccessPage(session?.user?.role, '/admin/dashboard')}</p>
      <p>Can access lawyer: {canAccessPage(session?.user?.role, '/lawyer/dashboard')}</p>
    </div>
  );
};
```

## Troubleshooting

### Common Issues
1. **"Access denied" errors**: Check if user has required role
2. **Impersonation stuck**: Use `/api/admin/stop-impersonation` endpoint
3. **Missing permissions**: Verify role assignments in database

### Debug Endpoints
- `/api/debug-impersonation` - Check impersonation status
- `/api/test-session` - View current session data
- `/test-access` - Test page access permissions

## Maintenance

### Adding New Roles
1. Add role to `USER_ROLES` in `auth-utils.ts`
2. Update permission matrix in this document
3. Add role to appropriate permission checks
4. Test thoroughly

### Adding New Features
1. Determine required roles for the feature
2. Add permission guards to pages and APIs
3. Update this documentation
4. Test with different user roles

---

**Last Updated:** July 6, 2025
**Version:** 1.0
**Maintainer:** Development Team 