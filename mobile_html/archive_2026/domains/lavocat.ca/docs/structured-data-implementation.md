# Structured Data (JSON-LD) Implementation

## Overview

This document outlines the implementation of structured data (JSON-LD) across the Liberté Même en Prison platform to enhance search engine understanding and enable rich snippets in search results.

## What is Structured Data?

Structured data is a standardized format for providing information about a page and classifying the page content. It helps search engines understand the content and context of your pages, potentially leading to rich snippets in search results.

## Components

### StructuredData Component

**Location**: `src/components/StructuredData.tsx`

A comprehensive React component that generates JSON-LD structured data for various content types.

#### Supported Schema Types

1. **LegalCase** - For legal case details
2. **Person/Lawyer** - For lawyer profiles
3. **Organization** - For company/business information
4. **Article** - For blog posts and articles
5. **WebSite** - For website information
6. **BreadcrumbList** - For navigation breadcrumbs
7. **FAQPage** - For FAQ content
8. **HowTo** - For instructional content

#### Props Interface

```typescript
interface StructuredDataProps {
  type: 'legalCase' | 'lawyer' | 'organization' | 'article' | 'website' | 'breadcrumb' | 'faq' | 'howTo';
  data: LegalCaseStructuredData | LawyerStructuredData | OrganizationStructuredData | ArticleStructuredData | WebSiteStructuredData | BreadcrumbStructuredData | FAQStructuredData | HowToStructuredData;
}
```

## Implementation Locations

### 1. Public Case Detail Pages

**File**: `src/pages/public/cases/[id].tsx`

```typescript
<StructuredData
  type="legalCase"
  data={createLegalCaseStructuredData(caseData)}
/>
```

**Schema**: LegalCase
- Includes case title, description, dates
- Links to client and lawyer information
- Contains legal area, jurisdiction, court details
- Includes engagement metrics (views, comments, supporters)

### 2. User Profile Pages

**File**: `src/pages/profile/[username].tsx`

```typescript
{profile.role === 'LAWYER' && (
  <StructuredData
    type="lawyer"
    data={createLawyerStructuredData(profile)}
  />
)}
```

**Schema**: Person (Lawyer)
- Professional information (name, title, specialization)
- Contact details and location
- Credentials and education
- Ratings and reviews
- Service areas and pricing

### 3. Live Cases Feed Page

**File**: `src/pages/live-cases.tsx`

```typescript
<StructuredData
  type="website"
  data={createWebSiteStructuredData()}
/>
```

**Schema**: WebSite
- Site name and description
- Search functionality
- Publisher information
- Contact details

### 4. Layout Component (Global)

**File**: `src/components/LayoutWithSidebar.tsx`

```typescript
<StructuredData
  type="organization"
  data={createOrganizationStructuredData()}
/>
```

**Schema**: Organization
- Company information
- Contact points
- Service areas
- Social media links

## Helper Functions

### createLegalCaseStructuredData(caseData)

Creates structured data for legal cases.

```typescript
export const createLegalCaseStructuredData = (caseData: any): LegalCaseStructuredData => {
  return {
    '@context': 'https://schema.org',
    '@type': 'LegalCase',
    name: caseData.title,
    description: caseData.description,
    url: `${baseUrl}/public/cases/${caseData.id}`,
    dateCreated: caseData.createdAt,
    dateModified: caseData.updatedAt,
    datePublished: caseData.createdAt,
    author: {
      '@type': 'Person',
      name: caseData.client?.name
    },
    publisher: {
      '@type': 'Organization',
      name: 'Liberté Même en Prison',
      url: baseUrl
    },
    category: caseData.category,
    legalArea: caseData.legalArea,
    jurisdiction: caseData.jurisdiction,
    status: caseData.status,
    // ... additional fields
  };
};
```

### createLawyerStructuredData(lawyerData)

Creates structured data for lawyer profiles.

```typescript
export const createLawyerStructuredData = (lawyerData: any): LawyerStructuredData => {
  return {
    '@context': 'https://schema.org',
    '@type': 'Person',
    name: lawyerData.name,
    jobTitle: lawyerData.title || 'Lawyer',
    worksFor: {
      '@type': 'Organization',
      name: 'Liberté Même en Prison'
    },
    knowsAbout: [lawyerData.specialization, 'Legal Services'],
    aggregateRating: {
      '@type': 'AggregateRating',
      ratingValue: lawyerData.averageRating,
      reviewCount: lawyerData.totalCases
    },
    // ... additional fields
  };
};
```

### createOrganizationStructuredData()

Creates structured data for the organization.

```typescript
export const createOrganizationStructuredData = (): OrganizationStructuredData => {
  return {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: 'Liberté Même en Prison',
    url: baseUrl,
    logo: {
      '@type': 'ImageObject',
      url: `${baseUrl}/images/logo.png`
    },
    description: 'A comprehensive legal marketplace...',
    contactPoint: [
      {
        '@type': 'ContactPoint',
        contactType: 'customer service',
        email: 'support@libertememeenprison.com'
      }
    ],
    // ... additional fields
  };
};
```

## Schema Types in Detail

### LegalCase Schema

```json
{
  "@context": "https://schema.org",
  "@type": "LegalCase",
  "name": "Case Title",
  "description": "Case description",
  "url": "https://example.com/cases/123",
  "dateCreated": "2024-01-01T00:00:00Z",
  "dateModified": "2024-01-02T00:00:00Z",
  "datePublished": "2024-01-01T00:00:00Z",
  "author": {
    "@type": "Person",
    "name": "Client Name"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Liberté Même en Prison"
  },
  "category": "Civil Law",
  "legalArea": "Contract Dispute",
  "jurisdiction": "France",
  "court": "Paris Court of Appeal",
  "status": "Active",
  "urgencyLevel": "HIGH",
  "riskLevel": "MEDIUM",
  "estimatedValue": 50000,
  "tags": ["contract", "dispute", "civil"],
  "commentCount": 15,
  "viewCount": 250,
  "supporterCount": 45
}
```

### Person (Lawyer) Schema

```json
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "John Doe",
  "url": "https://example.com/profile/johndoe",
  "jobTitle": "Lawyer",
  "worksFor": {
    "@type": "Organization",
    "name": "Liberté Même en Prison"
  },
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Paris"
  },
  "telephone": "+33123456789",
  "email": "john.doe@example.com",
  "knowsAbout": ["Contract Law", "Legal Services"],
  "hasCredential": [
    {
      "@type": "EducationalOccupationalCredential",
      "name": "Law Degree",
      "credentialCategory": "Educational Credential"
    }
  ],
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": 4.8,
    "reviewCount": 150,
    "bestRating": 5
  },
  "priceRange": "$200/hour",
  "areaServed": [
    {
      "@type": "Place",
      "name": "Paris"
    }
  ]
}
```

### Organization Schema

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Liberté Même en Prison",
  "url": "https://example.com",
  "logo": {
    "@type": "ImageObject",
    "url": "https://example.com/images/logo.png",
    "width": 180,
    "height": 180
  },
  "description": "A comprehensive legal marketplace...",
  "contactPoint": [
    {
      "@type": "ContactPoint",
      "contactType": "customer service",
      "email": "support@libertememeenprison.com"
    }
  ],
  "sameAs": [
    "https://twitter.com/LiberteMemeEnPrison",
    "https://linkedin.com/company/libertememeenprison"
  ],
  "serviceArea": [
    {
      "@type": "Place",
      "name": "Global"
    }
  ]
}
```

## Testing

### Automated Testing

Run the test script to verify implementation:

```bash
npx ts-node scripts/test-structured-data.ts
```

### Manual Testing Tools

#### Google Rich Results Test
1. Visit: https://search.google.com/test/rich-results
2. Enter your page URL or paste HTML code
3. Check for rich result eligibility

#### Google Structured Data Testing Tool
1. Visit: https://search.google.com/structured-data/testing-tool
2. Enter your page URL or paste HTML code
3. Validate JSON-LD structure

#### Schema.org Validator
1. Visit: https://validator.schema.org/
2. Enter your page URL
3. Check for schema validation

### Browser Testing

1. Open browser developer tools
2. Navigate to the Elements tab
3. Search for "application/ld+json"
4. Verify structured data is present and valid

## Best Practices

### Content Guidelines

1. **Accuracy**: Ensure all data is accurate and up-to-date
2. **Completeness**: Include all relevant fields for each schema type
3. **Consistency**: Use consistent formatting across similar content
4. **Relevance**: Only include data that's relevant to the content

### Technical Guidelines

1. **URLs**: Always use absolute URLs
2. **Dates**: Use ISO 8601 format (YYYY-MM-DDTHH:mm:ssZ)
3. **Images**: Include proper dimensions and alt text
4. **Validation**: Test with Google's tools before deployment

### SEO Guidelines

1. **Rich Snippets**: Focus on schemas that can generate rich snippets
2. **Local SEO**: Include location data for local businesses
3. **Reviews**: Include aggregate ratings when available
4. **Breadcrumbs**: Implement breadcrumb navigation with structured data

## Monitoring and Analytics

### Google Search Console

1. Monitor rich snippet performance
2. Check for structured data errors
3. Track click-through rates
4. Analyze search appearance

### Rich Snippet Metrics

- **Rich Result Impressions**: How often rich snippets appear
- **Rich Result Clicks**: How often rich snippets are clicked
- **Rich Result CTR**: Click-through rate for rich snippets
- **Rich Result Position**: Average position of rich snippets

## Troubleshooting

### Common Issues

1. **Validation Errors**: Check for missing required fields
2. **No Rich Snippets**: Ensure content meets Google's guidelines
3. **Incorrect Data**: Verify data accuracy and formatting
4. **Missing Schema**: Add appropriate schema types

### Debugging Steps

1. Run the test script to verify implementation
2. Use Google's testing tools to validate structure
3. Check browser developer tools for JSON-LD presence
4. Verify environment variables are set correctly

## Future Enhancements

### Planned Features

1. **FAQ Schema**: Add FAQ structured data for help pages
2. **HowTo Schema**: Add instructional content schemas
3. **Breadcrumb Navigation**: Implement breadcrumb structured data
4. **Local Business**: Add local business schema for law firms

### Advanced Features

1. **Dynamic Schemas**: Generate schemas based on content type
2. **Schema Analytics**: Track schema performance
3. **A/B Testing**: Test different schema configurations
4. **Automated Validation**: Continuous schema validation

## Support

For issues or questions regarding structured data implementation:

1. Check this documentation
2. Run the test script for verification
3. Use Google's testing tools for validation
4. Review the component code in `src/components/StructuredData.tsx`

## Resources

- [Schema.org Documentation](https://schema.org/)
- [Google Structured Data Guidelines](https://developers.google.com/search/docs/advanced/structured-data/intro-structured-data)
- [Google Rich Results Test](https://search.google.com/test/rich-results)
- [JSON-LD Specification](https://json-ld.org/) 