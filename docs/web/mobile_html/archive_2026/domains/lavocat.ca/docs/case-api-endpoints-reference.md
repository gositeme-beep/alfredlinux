# Case API Endpoints Reference

## Overview
This document provides a comprehensive reference for all case-related API endpoints in the platform. All endpoints use the `LegalCase` model as the single source of truth.

## Core Case Endpoints

### 1. Case Management (`/api/cases/`)

#### `GET /api/cases` - List Cases
- **Purpose**: Get cases based on user role and permissions
- **Query Parameters**: 
  - `role`: Filter by role (admin, lawyer, client)
  - `userId`: Specific user's cases
- **Response**: Array of cases with related data
- **Usage**: Main case listing for authenticated users

#### `POST /api/cases` - Create Case
- **Purpose**: Create a new legal case
- **Body**: 
  ```json
  {
    "title": "string",
    "description": "string", 
    "caseType": "string",
    "priority": "MEDIUM|HIGH|LOW",
    "budget": "number",
    "assignedTo": "userId",
    "isPublic": "boolean",
    "logoUrl": "string",
    "status": "PENDING|ACTIVE|CLOSED"
  }
  ```
- **Response**: Created case object
- **Usage**: Case creation form

#### `GET /api/cases/[id]` - Get Case Details
- **Purpose**: Get detailed case information with permissions
- **Response**: Case with related data and user permissions
- **Usage**: Case detail pages

#### `PUT /api/cases/[id]` - Update Case
- **Purpose**: Update case information (requires permissions)
- **Body**: Same as create, but all fields optional
- **Response**: Updated case object
- **Usage**: Case editing

#### `DELETE /api/cases/[id]` - Delete Case
- **Purpose**: Delete case (requires permissions)
- **Response**: Success message
- **Usage**: Case management

### 2. Public Case Feed (`/api/live-cases/`)

#### `GET /api/live-cases` - Public Case Feed
- **Purpose**: Get public cases for discovery
- **Query Parameters**:
  - `filter`: Category filter (urgent, criminal, civil, etc.)
  - `sortBy`: Sort order (newest, urgent, popular, deadline)
  - `search`: Search term
  - `page`: Page number
  - `limit`: Items per page
- **Response**: Paginated cases with metadata
- **Usage**: Public case discovery, homepage feed

#### `GET /api/live-cases/stats` - Case Statistics
- **Purpose**: Get platform-wide case statistics
- **Response**: Various case metrics and analytics
- **Usage**: Dashboard statistics

### 3. Case Interactions (`/api/live-cases/[id]/`)

#### `GET /api/live-cases/[id]/comments` - Get Case Comments
- **Purpose**: Get comments for a specific case
- **Query Parameters**:
  - `page`: Page number
  - `limit`: Comments per page
- **Response**: Paginated comments with replies
- **Usage**: Case discussion threads

#### `POST /api/live-cases/[id]/comments` - Add Comment
- **Purpose**: Add a comment to a case
- **Body**:
  ```json
  {
    "content": "string",
    "parentId": "string (optional)"
  }
  ```
- **Response**: Created comment
- **Usage**: Case discussions

#### `GET /api/live-cases/[id]/requests` - Get Lawyer Requests
- **Purpose**: Get lawyer requests for a case
- **Response**: Array of lawyer requests
- **Usage**: Case hiring process

#### `POST /api/live-cases/[id]/requests` - Submit Lawyer Request
- **Purpose**: Submit a lawyer request for a case
- **Body**:
  ```json
  {
    "message": "string",
    "proposedRate": "number",
    "estimatedHours": "number",
    "reasoning": "string"
  }
  ```
- **Response**: Created request
- **Usage**: Lawyer application process

### 4. Public Case Endpoints (`/api/public/cases/`)

#### `GET /api/public/cases` - Public Case Discovery
- **Purpose**: Public case listing with advanced filtering
- **Query Parameters**: Similar to live-cases but with additional filters
- **Response**: Paginated public cases
- **Usage**: Public case discovery

#### `GET /api/public/cases/[id]` - Public Case Details
- **Purpose**: Get public case information
- **Response**: Public case data
- **Usage**: Public case pages

#### `POST /api/public/cases/[id]/support` - Support Case
- **Purpose**: Support a public case
- **Response**: Support confirmation
- **Usage**: Case support system

#### `POST /api/public/cases/[id]/view` - Record Case View
- **Purpose**: Record a case view (analytics)
- **Response**: Success confirmation
- **Usage**: Analytics tracking

### 5. Case Competition (`/api/public/cases/[id]/competition/`)

#### `POST /api/public/cases/[id]/competition/join` - Join Competition
- **Purpose**: Join a case competition
- **Response**: Participation confirmation
- **Usage**: Competition system

#### `POST /api/public/cases/[id]/competition/bid` - Submit Bid
- **Purpose**: Submit a bid for a case
- **Body**:
  ```json
  {
    "amount": "number",
    "message": "string"
  }
  ```
- **Response**: Created bid
- **Usage**: Bidding system

#### `GET /api/public/cases/[id]/competition/bids` - Get Bids
- **Purpose**: Get all bids for a case
- **Response**: Array of bids
- **Usage**: Competition management

#### `GET /api/public/cases/[id]/competition/participants` - Get Participants
- **Purpose**: Get competition participants
- **Response**: Array of participants
- **Usage**: Competition overview

#### `POST /api/public/cases/[id]/competition/end` - End Competition
- **Purpose**: End a case competition
- **Response**: Competition results
- **Usage**: Competition management

### 6. Case Chat (`/api/public/cases/[id]/chat-messages`)

#### `GET /api/public/cases/[id]/chat-messages` - Get Chat Messages
- **Purpose**: Get case chat messages
- **Query Parameters**:
  - `page`: Page number
  - `limit`: Messages per page
- **Response**: Paginated chat messages
- **Usage**: Case communication

#### `POST /api/public/cases/[id]/chat-messages` - Send Message
- **Purpose**: Send a chat message
- **Body**:
  ```json
  {
    "content": "string",
    "isPublic": "boolean"
  }
  ```
- **Response**: Created message
- **Usage**: Case communication

## Role-Specific Endpoints

### Lawyer Endpoints (`/api/lawyer/`)

#### `GET /api/lawyer/cases` - Lawyer's Cases
- **Purpose**: Get cases assigned to a lawyer
- **Response**: Lawyer's case portfolio
- **Usage**: Lawyer dashboard

#### `GET /api/lawyer/clients` - Lawyer's Clients
- **Purpose**: Get clients for a lawyer
- **Response**: Client relationships
- **Usage**: Client management

### Client Endpoints (`/api/client/`)

#### `GET /api/client/cases` - Client's Cases
- **Purpose**: Get cases for a client
- **Response**: Client's cases
- **Usage**: Client dashboard

### Admin Endpoints (`/api/admin/`)

#### `GET /api/admin/cases` - Admin Case Management
- **Purpose**: Admin case overview
- **Response**: All cases with admin controls
- **Usage**: Admin dashboard

## Data Models

### LegalCase Model (Primary)
```prisma
model LegalCase {
  id                    String   @id @default(cuid())
  title                 String
  description           String
  caseNumber            String?  @unique
  caseType              String
  jurisdiction          String
  court                 String?
  status                String   @default("PENDING")
  priority              String   @default("MEDIUM")
  budget                Float?
  isPublic              Boolean  @default(false)
  isAcceptingApplications Boolean @default(true)
  applicationDeadline   DateTime?
  competitionDeadline   DateTime?
  requiredDocuments     String?
  eligibilityCriteria   String?
  publicSummary         String?
  legalArea             String?
  urgencyLevel          String?
  supporterCount        Int      @default(0)
  viewCount             Int      @default(0)
  tags                  String?
  logoUrl               String?
  createdBy             String
  leadLawyerId          String?
  primaryLawyerId       String?
  assistantLawyerId     String?
  secretaryId           String?
  clientId              String?
  createdAt             DateTime @default(now())
  updatedAt             DateTime @updatedAt
  
  // Relations
  creator               User     @relation("CaseCreator", fields: [createdBy], references: [id])
  leadLawyer            User?    @relation("LeadLawyer", fields: [leadLawyerId], references: [id])
  primaryLawyer         User?    @relation("PrimaryLawyer", fields: [primaryLawyerId], references: [id])
  assistantLawyer       User?    @relation("AssistantLawyer", fields: [assistantLawyerId], references: [id])
  secretary             User?    @relation("Secretary", fields: [secretaryId], references: [id])
  client                User?    @relation("Client", fields: [clientId], references: [id])
  
  // Related data
  registrations         Registration[]
  caseUpdates           CaseUpdate[]
  caseAssignments       CaseAssignment[]
  offers                CaseOffer[]
  supporters            CaseSupport[]
  comments              CaseComment[]
  chatMessages          CaseChatMessage[]
  lawyerRequests        LawyerRequest[] @relation("CaseLawyerRequests")
  competitionParticipants CaseCompetitionParticipant[]
  bids                  CaseBid[]
  documents             Document[]
  
  @@map("legal_cases")
}
```

## Best Practices

1. **Always use `LegalCase` model** - Never use the simple `Case` model
2. **Check permissions** - Verify user can access/modify cases
3. **Use role-based filtering** - Filter cases based on user role
4. **Include related data** - Use Prisma includes for efficient queries
5. **Handle errors gracefully** - Return appropriate HTTP status codes
6. **Validate input** - Check required fields and data types
7. **Use pagination** - For large datasets, implement pagination
8. **Log operations** - Track important case operations for audit

## Migration Notes

- ✅ Removed duplicate `Case` model from schema
- ✅ Deleted duplicate API endpoints
- ✅ All endpoints now use `LegalCase` model
- ✅ Database migration completed
- ✅ Prisma client regenerated

## Frontend Integration

When integrating with the frontend:

1. **Use existing endpoints** - Don't create new ones unless necessary
2. **Follow the API contracts** - Use the exact request/response formats
3. **Handle authentication** - Include session tokens in requests
4. **Implement error handling** - Handle API errors gracefully
5. **Use TypeScript types** - Define interfaces for API responses

This reference ensures consistency across the entire platform and prevents future duplication issues. 