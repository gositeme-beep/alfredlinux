# Barreau du Québec Verification System

## Overview

The Barreau du Québec verification system is a robust, production-grade solution that automatically verifies lawyer credentials by scraping the official Barreau directory. The system supports both "First Last" and "Last, First" name formats and provides comprehensive error handling and fallback options.

## Features

### ✅ Core Functionality
- **Dual Name Format Support**: Automatically tries both "First Last" and "Last, First" formats
- **Real-time Scraping**: Dynamically scrapes the Barreau directory without hardcoded data
- **Comprehensive Data Extraction**: Extracts name, employer, address, phone, email, practice areas, languages, and more
- **Smart Matching**: Uses phone number and practice area comparison with fuzzy matching
- **Bilingual Support**: Full French/English interface and error messages
- **Manual Verification Fallback**: Allows users to upload documents when automatic verification fails

### ✅ Enhanced User Experience
- **Form Validation**: Real-time validation with clear error messages
- **Progress Tracking**: Visual progress steps for user guidance
- **Error Recovery**: Helpful suggestions when verification fails
- **Result Display**: Shows matched profiles with detailed information
- **Responsive Design**: Works on all devices with modern UI

### ✅ Robust Backend
- **Rate Limiting**: Respectful scraping with delays between requests
- **Error Handling**: Comprehensive error handling for network issues
- **Input Validation**: Server-side validation for all inputs
- **Logging**: Detailed logging for debugging and monitoring
- **Timeout Handling**: Graceful handling of slow responses

## Architecture

### Frontend Components
```
src/
├── pages/register-verified.tsx          # Main registration page
├── components/lawyer/
│   └── VerifiedLawyerRegistrationForm.tsx  # Enhanced form component
├── components/ManualVerificationUpload.tsx  # Manual verification fallback
└── content/lawyer-registration.ts       # Bilingual content
```

### Backend API
```
src/pages/api/lawyer/
├── barreau-verify.ts                    # Main verification endpoint
└── manual-verification.ts               # Manual verification upload
```

## API Endpoints

### POST `/api/lawyer/barreau-verify`

Verifies lawyer credentials against the Barreau directory.

#### Request Body
```json
{
  "name": "Justin Wee",
  "phone": "514-555-0101",
  "practiceArea": "Civil"
}
```

#### Response Format
```json
{
  "found": true,
  "matchedProfiles": [
    {
      "name": "Justin Wee",
      "employer": "ADW Avocats",
      "address": "123 Main St, Montréal, QC",
      "phone": "5145550101",
      "email": "justin.wee@adwavocats.ca",
      "practiceAreas": ["Civil", "Commercial"],
      "languages": ["Français", "English"],
      "profileUrl": "https://www.barreau.qc.ca/...",
      "matchScore": 3
    }
  ],
  "allProfiles": [...],
  "message": "Matching Barreau profile(s) found.",
  "suggestions": []
}
```

## Setup and Installation

### Prerequisites
- Node.js 16+ 
- Next.js project
- Axios and Cheerio packages

### Installation
```bash
npm install axios cheerio
```

### Environment Variables
```env
# Optional: Configure timeouts and delays
BARREAU_REQUEST_TIMEOUT=15000
BARREAU_DELAY_BETWEEN_REQUESTS=1000
```

## Testing

### Automated Test Suite

Run the comprehensive test suite:

```bash
# Run all tests
node scripts/test-barreau-verification.js --test-all

# Test specific case
node scripts/test-barreau-verification.js --test-case="Justin Wee"

# Interactive testing
node scripts/test-barreau-verification.js --interactive
```

### Test Cases Included
1. **Real Lawyer Test**: "Justin Wee" (should match)
2. **Name Format Test**: "Wee, Justin" (should match)
3. **Fictional Lawyer**: "John Doe" (should not match)
4. **Common Quebec Names**: Various common names
5. **Invalid Input**: Validation error testing

### Manual Testing

1. Start your development server:
   ```bash
   npm run dev
   ```

2. Navigate to `/register-verified`

3. Fill out the form with test data:
   - Name: "Justin Wee"
   - Phone: "514-555-0101"
   - Practice Area: "Civil"

4. Click "Vérifier avec le Barreau"

5. Observe the verification process and results

## Usage Guide

### For Users

1. **Fill Required Fields**:
   - Full Name (required)
   - Email Address (required)
   - Practice Address (required)
   - Phone Number (required)
   - Primary Practice Area (required)

2. **Optional Fields**:
   - Bar Number
   - Legal Specializations
   - Practice Regions

3. **Verification Process**:
   - Click "Vérifier avec le Barreau"
   - Wait for verification (usually 10-30 seconds)
   - Review results

4. **If Verification Fails**:
   - Check the suggestions provided
   - Try correcting your information
   - Use manual verification as fallback

### For Administrators

1. **Monitor Verification Results**:
   - Check server logs for verification attempts
   - Review matched profiles in admin dashboard
   - Handle manual verification uploads

2. **Troubleshooting**:
   - Monitor API response times
   - Check for Barreau site changes
   - Review error logs

## Error Handling

### Common Error Scenarios

1. **No Profiles Found**
   - Check name spelling
   - Verify phone number format
   - Try different name variations

2. **Profiles Found but No Match**
   - Verify practice area spelling
   - Check phone number accuracy
   - Ensure name matches Barreau registration

3. **Network Errors**
   - Barreau site may be slow
   - Try again in a few minutes
   - Use manual verification

4. **Validation Errors**
   - Fill all required fields
   - Use valid email format
   - Enter valid phone number

### Error Messages (Bilingual)

| Error | French | English |
|-------|--------|---------|
| Invalid name | Le nom doit contenir au moins 2 caractères | Name must be at least 2 characters |
| Invalid email | Adresse email invalide | Invalid email address |
| Invalid phone | Numéro de téléphone invalide | Invalid phone number |
| Missing practice area | Veuillez sélectionner un domaine de pratique | Please select a practice area |

## Monitoring and Maintenance

### Logging

The system logs detailed information for debugging:

```javascript
console.log(`Starting Barreau verification for: ${name}, ${phone}, ${practiceArea}`);
console.log(`Found ${profileLinks.length} profile links`);
console.log(`Successfully scraped ${scrapedProfiles.length} profiles`);
```

### Performance Metrics

- **Response Time**: Typically 10-30 seconds
- **Success Rate**: Depends on data accuracy
- **Error Rate**: Monitored for system health

### Maintenance Tasks

1. **Regular Testing**: Run test suite weekly
2. **Monitor Logs**: Check for scraping errors
3. **Update Selectors**: If Barreau site changes
4. **Rate Limiting**: Ensure respectful scraping

## Security Considerations

### Data Protection
- No personal data is stored permanently
- Scraped data is only used for verification
- Manual uploads are securely handled

### Rate Limiting
- 1-second delay between search requests
- 500ms delay between profile scraping
- 15-second timeout for requests

### Input Validation
- Server-side validation for all inputs
- Sanitization of user data
- Protection against injection attacks

## Troubleshooting

### Common Issues

1. **Verification Always Fails**
   - Check Barreau site accessibility
   - Verify API endpoint is working
   - Review server logs

2. **Slow Response Times**
   - Barreau site may be slow
   - Check network connectivity
   - Consider increasing timeouts

3. **No Data Extracted**
   - Barreau site structure may have changed
   - Update CSS selectors
   - Test with known working data

### Debug Mode

Enable detailed logging by setting:

```javascript
// In barreau-verify.ts
const DEBUG = process.env.NODE_ENV === 'development';
if (DEBUG) {
  console.log('Detailed debug information...');
}
```

## Future Enhancements

### Planned Features
- **Caching**: Cache verification results
- **Batch Processing**: Handle multiple verifications
- **Advanced Matching**: AI-powered matching algorithms
- **Webhook Support**: Real-time notifications
- **Analytics Dashboard**: Verification statistics

### Scalability Improvements
- **Queue System**: Handle high-volume verification
- **Distributed Scraping**: Multiple scraping instances
- **Database Storage**: Store verification history
- **API Rate Limiting**: Protect against abuse

## Support

### Getting Help
1. Check this documentation
2. Review server logs
3. Run the test suite
4. Contact development team

### Contributing
1. Follow existing code patterns
2. Add tests for new features
3. Update documentation
4. Test thoroughly before deployment

---

**Last Updated**: January 2025  
**Version**: 2.0.0  
**Maintainer**: Development Team 