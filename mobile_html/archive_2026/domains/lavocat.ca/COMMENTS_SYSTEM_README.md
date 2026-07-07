# New Comments & Discussion System

## Overview

This is a completely new, robust comments and discussion system built from scratch to replace the previous broken implementation. The system is designed with modern React patterns, proper TypeScript types, and excellent user experience.

## Architecture

### Backend API (`/api/comments`)
- **Clean REST API** with proper validation using Zod
- **Comprehensive error handling** with meaningful error messages
- **Authentication required** for all operations
- **Cascading soft deletes** for comments and replies
- **File attachment support** with size and type validation
- **Notification system** for replies

### Frontend Components
- **`SimpleComments.tsx`** - Main comments component
- **`useComments.ts`** - Custom hook for state management
- **Modern UI** with Framer Motion animations
- **Accessibility features** with ARIA labels
- **Responsive design** for all screen sizes

### Database Schema
- **`CaseComment`** - Main comment model with threading support
- **`CommentAttachment`** - File attachments for comments
- **`CommentLike`** - Like tracking
- **`CommentReaction`** - Reaction system (like, love, etc.)

## Features

### ✅ Core Functionality
- **Real-time comment posting** with optimistic UI updates
- **Threaded replies** with unlimited nesting depth
- **Edit and delete comments** with proper permissions
- **File attachments** (images, PDFs, documents)
- **Pagination** with "Load More" functionality
- **Search and filtering** (planned)

### ✅ User Experience
- **Modern, clean UI** with smooth animations
- **Loading states** and error handling
- **Toast notifications** for all actions
- **Keyboard accessibility** and screen reader support
- **Mobile-responsive** design

### ✅ Technical Features
- **TypeScript** with strict typing
- **Custom hooks** for clean separation of concerns
- **Optimistic updates** with background sync
- **Proper error boundaries** and fallbacks
- **Performance optimized** with React.memo and useCallback

## Usage

### Basic Implementation

```tsx
import SimpleComments from '@/components/SimpleComments';

function MyPage() {
  return (
    <div>
      <h1>Case Discussion</h1>
      <SimpleComments caseId="your-case-id" />
    </div>
  );
}
```

### Advanced Usage with Custom Styling

```tsx
import SimpleComments from '@/components/SimpleComments';

function MyPage() {
  return (
    <div className="max-w-4xl mx-auto">
      <SimpleComments 
        caseId="your-case-id"
        className="bg-white rounded-lg shadow p-6"
      />
    </div>
  );
}
```

### Using the Hook Directly

```tsx
import { useComments } from '@/hooks/useComments';

function CustomComments() {
  const {
    comments,
    loading,
    posting,
    postComment,
    editComment,
    deleteComment
  } = useComments('your-case-id');

  const handlePost = async () => {
    await postComment('My comment content', 'your-case-id');
  };

  return (
    <div>
      {/* Your custom UI */}
    </div>
  );
}
```

## API Endpoints

### GET `/api/comments`
Fetch comments for a case with pagination.

**Query Parameters:**
- `caseId` (required) - The case ID
- `page` (optional) - Page number (default: 1)
- `limit` (optional) - Comments per page (default: 20)
- `parentId` (optional) - Get replies for a specific comment

**Response:**
```json
{
  "comments": [...],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 100,
    "pages": 5
  }
}
```

### POST `/api/comments`
Create a new comment or reply.

**Body:**
```json
{
  "content": "Comment text",
  "caseId": "case-id",
  "parentId": "parent-comment-id" // optional for replies
}
```

### PUT `/api/comments?commentId=id`
Update an existing comment.

**Body:**
```json
{
  "content": "Updated comment text"
}
```

### DELETE `/api/comments?commentId=id`
Delete a comment (soft delete with cascading).

## Database Schema

### CaseComment
```prisma
model CaseComment {
  id        String    @id @default(cuid())
  caseId    String
  userId    String
  parentId  String?   // For threaded replies
  content   String
  likes     Int       @default(0)
  isEdited  Boolean   @default(false)
  isDeleted Boolean   @default(false)
  createdAt DateTime  @default(now())
  updatedAt DateTime  @updatedAt
  
  // Relations
  case      LegalCase @relation(fields: [caseId], references: [id])
  user      User      @relation(fields: [userId], references: [id])
  parent    CaseComment? @relation("CommentReplies", fields: [parentId], references: [id])
  replies   CaseComment[] @relation("CommentReplies")
  attachments CommentAttachment[]
  likedBy   CommentLike[]
  reactions CommentReaction[]
}
```

### CommentAttachment
```prisma
model CommentAttachment {
  id        String      @id @default(cuid())
  commentId String
  name      String
  url       String
  type      String
  size      Int
  createdAt DateTime    @default(now())
  
  comment   CaseComment @relation(fields: [commentId], references: [id], onDelete: Cascade)
}
```

## Security Features

- **Authentication required** for all operations
- **Authorization checks** - users can only edit/delete their own comments
- **Admin override** - admins can delete any comment
- **Input validation** with Zod schemas
- **SQL injection protection** via Prisma ORM
- **XSS protection** with proper content sanitization

## Performance Optimizations

- **Pagination** to limit data transfer
- **Optimistic updates** for instant UI feedback
- **Background sync** to ensure data consistency
- **React.memo** for component memoization
- **useCallback** for stable function references
- **Lazy loading** for large comment threads

## Testing

Visit `/test-comments` to test the system with a sample case.

## Migration from Old System

The old `EnhancedComments.tsx` component has been replaced. To migrate:

1. Replace `<EnhancedComments />` with `<SimpleComments />`
2. Update props to match new interface
3. Remove old API endpoints (`/api/live-cases/[id]/comments`)
4. Update any custom styling

## Future Enhancements

- [ ] Real-time updates with WebSocket
- [ ] Advanced search and filtering
- [ ] Comment moderation tools
- [ ] Rich text editor
- [ ] Comment reactions (like, love, etc.)
- [ ] Comment pinning
- [ ] Email notifications
- [ ] Comment analytics

## Troubleshooting

### Common Issues

1. **Comments not loading**
   - Check if case exists and is public
   - Verify user authentication
   - Check browser console for errors

2. **Can't post comments**
   - Ensure user is authenticated
   - Check content length (max 5000 characters)
   - Verify case permissions

3. **Attachments not working**
   - Check file size (max 10MB)
   - Verify file type is allowed
   - Ensure proper file upload endpoint

### Debug Mode

Enable debug logging by setting `NODE_ENV=development` in your environment variables.

## Support

For issues or questions about the comments system, check the console logs and API responses for detailed error messages. 