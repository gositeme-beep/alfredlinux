# Lawyer Registration Structure

## Overview

The lawyer registration system for avocat.quebec has been modernized with bilingual support and multiple access points for better user experience.

## Page Structure

### Root Level Pages (Recommended for SEO)
- `/register-verified` - French version with Layout
- `/en/register-verified` - English version with Layout

### Legacy Pages (Still functional)
- `/lawyer/register-verified` - French version with Layout
- `/en/lawyer/register-verified` - English version with Layout

## Components

### 1. VerifiedLawyerRegistrationForm
**Location**: `src/components/lawyer/VerifiedLawyerRegistrationForm.tsx`

A reusable form component that handles:
- Bilingual form fields
- Barreau verification process
- Step-by-step registration flow
- Form validation and submission

**Props**:
- `language`: 'fr' | 'en'
- `onLanguageToggle`: Function to switch languages
- `content`: Bilingual content object

### 2. VerifiedLawyerCTA
**Location**: `src/components/VerifiedLawyerCTA.tsx`

A call-to-action component with three variants:
- `primary` (default): Full-featured CTA with benefits
- `secondary`: Compact version with side-by-side layout
- `banner`: Horizontal banner for headers

**Props**:
- `language`: 'fr' | 'en'
- `variant`: 'primary' | 'secondary' | 'banner'
- `className`: Additional CSS classes

## Content Management

### Centralized Content
**Location**: `src/content/lawyer-registration.ts`

All text content is centralized in the `lawyerRegistrationContent` object with:
- French and English translations
- Form labels and placeholders
- Verification messages
- Success/error states
- Navigation links

### Usage Example
```typescript
import { lawyerRegistrationContent } from '@/content/lawyer-registration';

const t = lawyerRegistrationContent[language];
// Use t.form.barNumber, t.verification.success.title, etc.
```

## API Integration

### Barreau Verification
**Endpoint**: `/api/lawyer/verify-barreau`
**Service**: `src/lib/barreau-verification.ts`

The verification system includes:
- Barreau membership verification
- Profile creation/update
- Specialization and region management
- Caching for performance

### Registration Flow
1. User fills out form with Barreau number and details
2. System verifies with Barreau du Québec
3. Creates/updates lawyer profile
4. Handles business profile association
5. Auto-login after successful registration

## Usage Examples

### Adding CTA to Homepage
```tsx
import VerifiedLawyerCTA from '@/components/VerifiedLawyerCTA';

// In your homepage component
<VerifiedLawyerCTA 
  language={language} 
  variant="primary" 
  className="my-8" 
/>
```

### Creating Custom Registration Page
```tsx
import Layout from '@/components/Layout';
import VerifiedLawyerRegistrationForm from '@/components/lawyer/VerifiedLawyerRegistrationForm';
import { lawyerRegistrationContent } from '@/content/lawyer-registration';

const CustomRegistrationPage = () => {
  const [language, setLanguage] = useState<'fr' | 'en'>('fr');
  const t = lawyerRegistrationContent[language];

  return (
    <Layout>
      <VerifiedLawyerRegistrationForm
        language={language}
        onLanguageToggle={() => setLanguage(lang => lang === 'fr' ? 'en' : 'fr')}
        content={t}
      />
    </Layout>
  );
};
```

## SEO and Meta Tags

All registration pages include:
- Proper title and description
- Open Graph tags
- Canonical URLs
- Language-specific meta tags

## Navigation

### Language Switching
The system automatically detects the current language from the URL and provides seamless switching between French and English versions.

### URL Structure
- French: `/register-verified` or `/lawyer/register-verified`
- English: `/en/register-verified` or `/en/lawyer/register-verified`

## Testing

### Valid Barreau Numbers (for testing)
- `12345`
- `2016-ADW-001` through `2016-ADW-007`

### Form Validation
- Required fields: Bar number, name, email
- Email format validation
- Bar number format validation
- Specialization and region selection

## Future Enhancements

1. **Real Barreau API Integration**: Replace simulation with actual Barreau du Québec API
2. **Enhanced Verification**: Add document upload for verification
3. **Multi-step Profile Setup**: Expand profile creation process
4. **Analytics Integration**: Track registration funnel
5. **Email Notifications**: Send welcome emails and verification status updates 