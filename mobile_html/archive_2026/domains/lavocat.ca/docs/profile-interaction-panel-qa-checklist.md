# ProfileInteractionPanel QA Checklist

## Pre-Test Setup
- [ ] Clear browser cache and cookies
- [ ] Ensure test database is clean
- [ ] Have test users ready (lawyer, client, admin roles)
- [ ] Open browser DevTools (Network, Console tabs)

## 1. Own Profile View (Analytics Panel)

### Visual Verification
- [ ] Profile Analytics panel is displayed
- [ ] "Profile Views" stat is shown with correct number
- [ ] "Followers" stat is shown with correct number
- [ ] "View Detailed Analytics" button is present
- [ ] No "Connect with" interaction buttons are shown
- [ ] Panel has proper styling (white background, rounded corners, shadow)

### Functionality
- [ ] Click "View Detailed Analytics" button
- [ ] Verify it expands/collapses or navigates to analytics page
- [ ] Stats numbers are accurate and update in real-time

## 2. Other User Profile View (Interaction Panel)

### Visual Verification
- [ ] "Connect with [Name]" header is displayed
- [ ] All interaction buttons are present:
  - [ ] "Send Message" / "Message Now" (if online)
  - [ ] "Follow" / "Unfollow"
  - [ ] "Schedule Meeting"
  - [ ] "Endorse" / "Endorsed"
  - [ ] "Share"
- [ ] Stats section shows:
  - [ ] Followers count
  - [ ] Endorsements count
  - [ ] Mutual connections count
- [ ] Online status indicator (if user is online)

### Button States
- [ ] Follow button shows "Follow" when not following
- [ ] Follow button shows "Unfollow" when already following
- [ ] Endorse button shows "Endorse" when not endorsed
- [ ] Endorse button shows "Endorsed" when already endorsed
- [ ] Buttons have proper hover effects
- [ ] Buttons are disabled during API calls

## 3. Follow/Unfollow Functionality

### Follow Action
- [ ] Click "Follow" button
- [ ] Verify loading state (button disabled)
- [ ] Check Network tab for API call to `/api/profile/[id]/follow`
- [ ] Verify success toast appears: "Following [Name]"
- [ ] Button changes to "Unfollow"
- [ ] Followers count increases by 1
- [ ] No console errors

### Unfollow Action
- [ ] Click "Unfollow" button
- [ ] Verify loading state (button disabled)
- [ ] Check Network tab for API call to `/api/profile/[id]/follow` (DELETE)
- [ ] Verify success toast appears: "Unfollowed [Name]"
- [ ] Button changes back to "Follow"
- [ ] Followers count decreases by 1
- [ ] No console errors

### Error Handling
- [ ] Try to follow while logged out
- [ ] Verify error toast: "Please log in to follow users"
- [ ] Try to follow yourself
- [ ] Verify error handling (should be prevented)
- [ ] Simulate network error (disable network)
- [ ] Verify error toast: "Error updating follow status"

## 4. Endorse/Unendorse Functionality

### Endorse Action
- [ ] Click "Endorse" button
- [ ] Verify loading state (button disabled)
- [ ] Check Network tab for API call to `/api/profile/[id]/endorse`
- [ ] Verify success toast appears: "Endorsed [Name]"
- [ ] Button changes to "Endorsed"
- [ ] Endorsements count increases by 1
- [ ] No console errors

### Unendorse Action
- [ ] Click "Endorsed" button
- [ ] Verify loading state (button disabled)
- [ ] Check Network tab for API call to `/api/profile/[id]/endorse` (DELETE)
- [ ] Verify success toast appears: "Removed endorsement for [Name]"
- [ ] Button changes back to "Endorse"
- [ ] Endorsements count decreases by 1
- [ ] No console errors

### Error Handling
- [ ] Try to endorse while logged out
- [ ] Verify error toast: "Please log in to endorse users"
- [ ] Try to endorse yourself
- [ ] Verify error handling (should be prevented)
- [ ] Try to endorse non-legal professional
- [ ] Verify error handling (should be prevented)

## 5. Share Functionality

### Share Action
- [ ] Click "Share" button
- [ ] If navigator.share is available:
  - [ ] Native share dialog appears
  - [ ] Share data includes profile name and URL
- [ ] If navigator.share is not available:
  - [ ] URL is copied to clipboard
  - [ ] Success toast appears: "Profile link copied to clipboard!"
- [ ] No console errors

## 6. Message Functionality

### Message Button
- [ ] Click "Send Message" button
- [ ] Verify navigation to messages page or chat modal opens
- [ ] If online: button shows "Message Now" with different styling
- [ ] If offline: button shows "Send Message" with standard styling
- [ ] No console errors

## 7. Schedule Meeting Functionality

### Meeting Button
- [ ] Click "Schedule Meeting" button
- [ ] Verify navigation to scheduling page or modal opens
- [ ] No console errors

## 8. Online Status Indicator

### Online User
- [ ] Set test user as online
- [ ] Verify green dot indicator appears
- [ ] Verify "Online now" text appears
- [ ] Verify pulse animation is working

### Offline User
- [ ] Set test user as offline
- [ ] Verify no online indicator is shown

## 9. Stats Accuracy

### Real-time Updates
- [ ] Follow/unfollow user
- [ ] Verify followers count updates immediately
- [ ] Endorse/unendorse user
- [ ] Verify endorsements count updates immediately
- [ ] Check mutual connections count is accurate

### Data Persistence
- [ ] Refresh page after follow/endorse actions
- [ ] Verify button states persist
- [ ] Verify counts persist
- [ ] Verify relationship status is maintained

## 10. Responsive Design

### Mobile View
- [ ] Test on mobile device or responsive mode
- [ ] Verify buttons are properly sized for touch
- [ ] Verify text is readable
- [ ] Verify layout doesn't break

### Tablet View
- [ ] Test on tablet-sized viewport
- [ ] Verify layout adapts appropriately
- [ ] Verify all functionality works

### Desktop View
- [ ] Test on desktop viewport
- [ ] Verify layout is optimal
- [ ] Verify hover effects work

## 11. Accessibility

### Keyboard Navigation
- [ ] Tab through all interactive elements
- [ ] Verify focus indicators are visible
- [ ] Verify Enter/Space keys work for buttons
- [ ] Verify Escape key works for modals

### Screen Reader
- [ ] Test with screen reader
- [ ] Verify button labels are descriptive
- [ ] Verify status changes are announced
- [ ] Verify error messages are announced

### Color Contrast
- [ ] Verify text has sufficient contrast
- [ ] Verify button states are distinguishable
- [ ] Verify error/success states are clear

## 12. Performance

### Loading Times
- [ ] Measure initial load time
- [ ] Measure API response times
- [ ] Verify no unnecessary re-renders
- [ ] Verify smooth animations

### Memory Usage
- [ ] Monitor memory usage during interactions
- [ ] Verify no memory leaks
- [ ] Verify cleanup on unmount

## 13. Cross-browser Testing

### Browser Compatibility
- [ ] Test in Chrome
- [ ] Test in Firefox
- [ ] Test in Safari
- [ ] Test in Edge
- [ ] Verify consistent behavior across browsers

## 14. Error Scenarios

### Network Issues
- [ ] Simulate slow network
- [ ] Simulate network disconnection
- [ ] Verify graceful error handling
- [ ] Verify retry mechanisms work

### Invalid Data
- [ ] Test with invalid profile IDs
- [ ] Test with deleted users
- [ ] Test with malformed API responses
- [ ] Verify error boundaries work

## 15. Security

### Authentication
- [ ] Verify unauthenticated users can't perform actions
- [ ] Verify proper session handling
- [ ] Verify CSRF protection
- [ ] Verify no sensitive data exposure

### Authorization
- [ ] Verify users can't perform unauthorized actions
- [ ] Verify role-based restrictions work
- [ ] Verify proper access controls

## Test Results Summary

| Test Category | Passed | Failed | Notes |
|---------------|--------|--------|-------|
| Own Profile View |        |        |       |
| Other Profile View |        |        |       |
| Follow/Unfollow |        |        |       |
| Endorse/Unendorse |        |        |       |
| Share |        |        |       |
| Message |        |        |       |
| Schedule Meeting |        |        |       |
| Online Status |        |        |       |
| Stats Accuracy |        |        |       |
| Responsive Design |        |        |       |
| Accessibility |        |        |       |
| Performance |        |        |       |
| Cross-browser |        |        |       |
| Error Handling |        |        |       |
| Security |        |        |       |

## Issues Found

### Critical Issues
- [ ] 

### High Priority Issues
- [ ] 

### Medium Priority Issues
- [ ] 

### Low Priority Issues
- [ ] 

## Recommendations

- [ ] 

## Sign-off

- [ ] All tests completed
- [ ] All issues documented
- [ ] QA Lead approval
- [ ] Ready for production

**QA Tester:** _________________  
**Date:** _________________  
**Version:** _________________ 