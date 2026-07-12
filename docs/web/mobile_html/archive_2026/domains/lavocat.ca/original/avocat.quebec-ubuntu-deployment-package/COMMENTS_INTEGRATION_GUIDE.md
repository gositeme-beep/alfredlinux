# Comments System Integration Guide

## Quick Start

### 1. Test the New System
Visit `http://localhost:3000/test-comments` to see the new comments system in action.

### 2. Replace Old Components

**Before (Old System):**
```tsx
import EnhancedComments from '@/components/EnhancedComments';

<EnhancedComments 
  caseId={caseId}
  initialComments={comments}
  onCommentAdded={handleCommentAdded}
  mode="public"
  allowAttachments={true}
  allowReactions={true}
  allowReplies={true}
  maxRepliesDepth={3}
/>
```

**After (New System):**
```tsx
import SimpleComments from '@/components/SimpleComments';

<SimpleComments 
  caseId={caseId}
  className="your-custom-styles"
/>
```

### 3. Update API Calls

**Remove old API endpoints:**
- `/api/live-cases/[id]/comments`
- `/api/live-cases/[id]/comments/[commentId]`
- `/api/live-cases/[id]/comments/[commentId]/like`
- `/api/live-cases/[id]/comments/[commentId]/reactions`

**Use new API endpoint:**
- `/api/comments` (handles all operations)

### 4. Database Migration

The database has been updated with:
- New `CommentAttachment` model
- Enhanced `CaseComment` model with attachment support

Run migrations if needed:
```bash
npx prisma migrate dev
```

## Step-by-Step Migration

### Step 1: Backup Current Data
```bash
# Backup your current database
cp prisma/dev.db prisma/dev.db.backup-$(date +%Y%m%d-%H%M%S)
```

### Step 2: Update Imports
Replace all imports of `EnhancedComments` with `SimpleComments`:

```tsx
// Old
import EnhancedComments from '@/components/EnhancedComments';

// New
import SimpleComments from '@/components/SimpleComments';
```

### Step 3: Update Component Usage
The new component has a simpler API:

```tsx
// Old - Complex props
<EnhancedComments 
  caseId={caseId}
  initialComments={comments}
  onCommentAdded={handleCommentAdded}
  mode="public"
  allowAttachments={true}
  allowReactions={true}
  allowReplies={true}
  maxRepliesDepth={3}
/>

// New - Simple props
<SimpleComments 
  caseId={caseId}
  className="your-custom-styles"
/>
```

### Step 4: Remove Old API Routes
Delete or rename these files:
- `src/pages/api/live-cases/[id]/comments.ts`
- `src/pages/api/live-cases/[id]/comments/[commentId].ts`
- `src/pages/api/live-cases/[id]/comments/[commentId]/like.ts`
- `src/pages/api/live-cases/[id]/comments/[commentId]/reactions.ts`

### Step 5: Update Any Custom Logic
If you had custom comment handling logic, update it to use the new hook:

```tsx
// Old - Manual state management
const [comments, setComments] = useState([]);
const [loading, setLoading] = useState(false);

// New - Use the hook
const {
  comments,
  loading,
  posting,
  postComment,
  editComment,
  deleteComment
} = useComments(caseId);
```

### Step 6: Test Thoroughly
1. Test comment posting
2. Test replies
3. Test editing
4. Test deletion
5. Test file attachments
6. Test pagination
7. Test error scenarios

## Common Migration Issues

### Issue 1: Comments not loading
**Solution:** Check if the case exists and is public in the database.

### Issue 2: Can't post comments
**Solution:** Ensure user is authenticated and has proper permissions.

### Issue 3: Styling looks different
**Solution:** The new component uses different CSS classes. Update your styles accordingly.

### Issue 4: Old comments not showing
**Solution:** The new system uses a different API. Old comments should still be in the database and will load normally.

## Performance Improvements

The new system includes several performance improvements:

1. **Optimistic Updates** - Comments appear instantly
2. **Pagination** - Only loads 20 comments at a time
3. **Background Sync** - Ensures data consistency
4. **Memoization** - Prevents unnecessary re-renders

## Rollback Plan

If you need to rollback:

1. **Restore database backup:**
```bash
cp prisma/dev.db.backup-YYYYMMDD-HHMMSS prisma/dev.db
```

2. **Restore old components:**
```bash
git checkout HEAD~1 -- src/components/EnhancedComments.tsx
```

3. **Restore old API routes:**
```bash
git checkout HEAD~1 -- src/pages/api/live-cases/
```

## Support

If you encounter issues during migration:

1. Check the browser console for errors
2. Check the server logs for API errors
3. Verify database connectivity
4. Test with the `/test-comments` page first

## Next Steps

After successful migration:

1. **Remove old files** - Clean up unused components and API routes
2. **Update documentation** - Update any internal docs
3. **Train team** - Ensure team knows how to use the new system
4. **Monitor performance** - Watch for any performance issues
5. **Gather feedback** - Collect user feedback on the new system 