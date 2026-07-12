# Open Graph Meta Tags Implementation

## Overview

This document outlines the implementation of Open Graph meta tags across the Liberté Même en Prison platform to enhance social media sharing and SEO.

## Components

### OpenGraphMeta Component

**Location**: `src/components/OpenGraphMeta.tsx`

A reusable React component that generates comprehensive meta tags for social media sharing and SEO.

#### Props Interface

```typescript
interface OpenGraphMetaProps {
  title: string;                    // Page title
  description: string;              // Page description
  url: string;                      // Page URL (relative or absolute)
  image?: string;                   // Featured image URL
  type?: 'website' | 'article' | 'profile';  // Content type
  siteName?: string;                // Site name (default: 'Liberté Même en Prison')
  author?: string;                  // Content author
  publishedTime?: string;           // Publication date (ISO format)
  modifiedTime?: string;            // Last modified date (ISO format)
  section?: string;                 // Content section/category
  tags?: string[];                  // Content tags
  twitterCard?: 'summary' | 'summary_large_image' | 'app' | 'player';
  twitterCreator?: string;          // Twitter handle of content creator
  twitterSite?: string;             // Twitter handle of site
}
```

#### Features

- **Automatic URL Processing**: Handles both relative and absolute URLs
- **Image Optimization**: Supports Open Graph image dimensions (1200x630)
- **Multi-Platform Support**: Facebook Open Graph, Twitter Cards, LinkedIn
- **Dynamic Content**: Supports article-specific meta tags
- **SEO Enhancement**: Includes canonical URLs and basic SEO meta tags

## Implementation Locations

### 1. Public Case Detail Page

**File**: `src/pages/public/cases/[id].tsx`

```typescript
<OpenGraphMeta
  title={`Case: ${caseData.title || 'Legal Case'} - Liberté Même en Prison`}
  description={caseData.description || caseData.summary || `Legal case details for ${caseData.title || 'this case'}. View case information, updates, and legal proceedings.`}
  url={`/public/cases/${id}`}
  type="article"
  author={caseData.client?.name || caseData.lawyer?.name}
  publishedTime={caseData.createdAt}
  modifiedTime={caseData.updatedAt}
  section="Legal Cases"
  tags={[
    'legal case',
    'justice',
    'law',
    caseData.category || 'legal',
    caseData.status || 'active'
  ]}
  image={caseData.client?.profileImage || '/images/logo.png'}
/>
```

### 2. User Profile Page

**File**: `src/pages/profile/[username].tsx`

```typescript
<OpenGraphMeta
  title={`${profile.name} - ${profile.title || getRoleName(profile.role)} - Liberté Même en Prison`}
  description={profile.bio || `Professional profile of ${profile.name}, ${profile.title || getRoleName(profile.role)}. ${profile.specialization ? `Specializing in ${profile.specialization}.` : ''} ${profile.yearsOfExperience ? `${profile.yearsOfExperience} years of experience.` : ''}`}
  url={`/profile/${profile.username}`}
  type="profile"
  author={profile.name}
  image={profile.profilePicture}
  tags={[
    profile.role.toLowerCase(),
    'legal professional',
    profile.specialization || 'law',
    profile.officeLocation || 'legal services'
  ]}
  twitterCreator={profile.linkedinUrl ? `@${profile.linkedinUrl.split('/').pop()}` : undefined}
/>
```

### 3. Live Cases Feed Page

**File**: `src/pages/live-cases.tsx`

```typescript
<OpenGraphMeta
  title="Live Cases - Browse Active Legal Cases - Liberté Même en Prison"
  description="Explore active legal cases, find legal professionals, and discover opportunities in the legal marketplace. Real-time updates on case status, lawyer availability, and legal proceedings."
  url="/live-cases"
  type="website"
  tags={[
    'legal cases',
    'lawyers',
    'legal marketplace',
    'justice',
    'legal services',
    'case management'
  ]}
  image="/images/logo.png"
/>
```

## Meta Tags Generated

### Basic SEO Meta Tags
- `<title>` - Page title
- `<meta name="description">` - Page description
- `<meta name="keywords">` - Content keywords
- `<meta name="robots">` - Search engine directives
- `<meta name="author">` - Content author
- `<link rel="canonical">` - Canonical URL

### Facebook Open Graph Meta Tags
- `<meta property="og:title">` - Content title
- `<meta property="og:description">` - Content description
- `<meta property="og:url">` - Content URL
- `<meta property="og:type">` - Content type
- `<meta property="og:site_name">` - Site name
- `<meta property="og:locale">` - Content locale
- `<meta property="og:image">` - Featured image
- `<meta property="og:image:width">` - Image width
- `<meta property="og:image:height">` - Image height
- `<meta property="og:image:alt">` - Image alt text

### Article-Specific Meta Tags (when type="article")
- `<meta property="article:author">` - Article author
- `<meta property="article:published_time">` - Publication date
- `<meta property="article:modified_time">` - Last modified date
- `<meta property="article:section">` - Article section
- `<meta property="article:tag">` - Article tags

### Twitter Card Meta Tags
- `<meta name="twitter:card">` - Card type
- `<meta name="twitter:title">` - Content title
- `<meta name="twitter:description">` - Content description
- `<meta name="twitter:image">` - Featured image
- `<meta name="twitter:creator">` - Content creator
- `<meta name="twitter:site">` - Site Twitter handle

## Testing

### Automated Testing

Run the test script to verify implementation:

```bash
npx ts-node scripts/test-open-graph.ts
```

### Manual Testing

#### Facebook Sharing Debugger
1. Visit: https://developers.facebook.com/tools/debug/
2. Enter your page URL
3. Click "Debug" to see how your content appears on Facebook

#### Twitter Card Validator
1. Visit: https://cards-dev.twitter.com/validator
2. Enter your page URL
3. Preview how your content appears on Twitter

#### LinkedIn Post Inspector
1. Visit: https://www.linkedin.com/post-inspector/
2. Enter your page URL
3. Preview how your content appears on LinkedIn

### Browser Testing

1. Open browser developer tools
2. Navigate to the Elements tab
3. Search for "og:" or "twitter:" to verify meta tags are present
4. Check that URLs are properly formatted (absolute URLs)

## Best Practices

### Content Guidelines

1. **Title**: Keep under 60 characters for optimal display
2. **Description**: Keep under 160 characters for optimal display
3. **Images**: Use 1200x630 pixels for optimal display on all platforms
4. **URLs**: Always use absolute URLs for external sharing

### Image Requirements

- **Facebook**: 1200x630 pixels (minimum 600x315)
- **Twitter**: 1200x600 pixels for large image cards
- **LinkedIn**: 1200x627 pixels
- **Format**: JPG, PNG, or GIF
- **File size**: Under 8MB

### Content Type Guidelines

- **website**: General pages, homepages, category pages
- **article**: Blog posts, case details, news articles
- **profile**: User profiles, lawyer profiles

## Environment Variables

Ensure the following environment variable is set:

```env
NEXT_PUBLIC_APP_URL=https://yourdomain.com
```

This is used to convert relative URLs to absolute URLs for social media platforms.

## Troubleshooting

### Common Issues

1. **Images not displaying**: Check image URL is absolute and accessible
2. **Meta tags not updating**: Clear social media cache using their debug tools
3. **URLs not working**: Ensure NEXT_PUBLIC_APP_URL is set correctly

### Debugging Steps

1. Run the test script to verify implementation
2. Check browser developer tools for meta tag presence
3. Use social media debug tools to test sharing
4. Verify environment variables are set correctly

## Future Enhancements

### Planned Features

1. **Structured Data (JSON-LD)**: Add schema.org markup for enhanced SEO
2. **Dynamic Image Generation**: Create custom social media images
3. **Analytics Integration**: Track social media sharing metrics
4. **A/B Testing**: Test different meta tag configurations

### Advanced Features

1. **Scheduled Sharing**: Pre-schedule social media posts
2. **Share Incentives**: Reward users for sharing content
3. **Social Proof**: Display share counts and engagement metrics
4. **Mobile Optimization**: Optimize for mobile social media apps

## Support

For issues or questions regarding the Open Graph implementation:

1. Check this documentation
2. Run the test script for verification
3. Review the component code in `src/components/OpenGraphMeta.tsx`
4. Test with social media debug tools 