# 🧪 Live Chat System Verification Checklist

## 📋 Pre-Test Setup

- [ ] Development server is running (`npm run dev`)
- [ ] Database is up to date (`npx prisma migrate dev`)
- [ ] You have a test user account with SUPERADMIN role
- [ ] You have at least one public case in the database
- [ ] WebSocket server is accessible (check console for WebSocket messages)

## 🔌 WebSocket Connection Tests

### Test 1: Basic WebSocket Connection
- [ ] Open browser developer tools (F12)
- [ ] Go to Network tab
- [ ] Navigate to a public case page (e.g., `/public/cases/[case-id]`)
- [ ] Look for WebSocket connection in Network tab
- [ ] Verify connection status shows "Connected" or "Open"

### Test 2: WebSocket Authentication
- [ ] Check browser console for WebSocket authentication messages
- [ ] Verify no authentication errors in console
- [ ] Confirm user session is properly attached to WebSocket

## 🎨 UI Component Tests

### Test 3: Chat Button Visibility
- [ ] Navigate to a public case page
- [ ] Look for floating chat button (bottom-right corner)
- [ ] Verify button has gradient background (blue to purple)
- [ ] Check button has MessageSquare icon
- [ ] Confirm button is visible and clickable

### Test 4: Chat Window Opening
- [ ] Click the floating chat button
- [ ] Verify chat window opens with smooth animation
- [ ] Check window has proper header with case title
- [ ] Confirm window shows "Live Chat" title
- [ ] Verify online user count is displayed

### Test 5: Chat Window Features
- [ ] Check for minimize/maximize functionality
- [ ] Verify mute/unmute button works
- [ ] Test settings button (if implemented)
- [ ] Confirm close button closes the window

## 💬 Message Functionality Tests

### Test 6: Message Input
- [ ] Type a message in the textarea
- [ ] Verify character counter updates (X/500)
- [ ] Test Enter key sends message
- [ ] Test Send button sends message
- [ ] Confirm message appears in chat history

### Test 7: Message Display
- [ ] Send a test message
- [ ] Verify message appears with correct formatting
- [ ] Check sender name and avatar display
- [ ] Confirm timestamp is shown
- [ ] Verify message alignment (right for own messages, left for others)

### Test 8: Quick Actions
- [ ] Test "interested" quick action button
- [ ] Test "question" quick action button
- [ ] Test "support" quick action button
- [ ] Test "apply" quick action button
- [ ] Verify quick actions populate the message input

### Test 9: Chat Modes
- [ ] Test switching between "Public" and "Private" modes
- [ ] Verify mode buttons change color when active
- [ ] Confirm mode selection is maintained
- [ ] Test sending messages in both modes

## 🔄 Real-Time Features Tests

### Test 10: Typing Indicators
- [ ] Open chat in two different browser windows/tabs
- [ ] Start typing in one window
- [ ] Verify typing indicator appears in other window
- [ ] Stop typing and confirm indicator disappears
- [ ] Test typing indicator timeout (should auto-stop after 3 seconds)

### Test 11: User Presence
- [ ] Open case page in multiple browser windows
- [ ] Verify online user count increases
- [ ] Close one window and check count decreases
- [ ] Test page visibility changes (tab switching)

### Test 12: Message Broadcasting
- [ ] Send message from one browser window
- [ ] Verify message appears in other windows
- [ ] Test message order and timestamps
- [ ] Confirm system messages (join/leave) work

## 🗄️ Database Integration Tests

### Test 13: Message Persistence
- [ ] Send a public message
- [ ] Refresh the page
- [ ] Verify message history is loaded
- [ ] Check message appears in database (if you have access)
- [ ] Test private messages are not persisted

### Test 14: API Endpoint
- [ ] Test `/api/public/cases/[id]/chat-messages` endpoint
- [ ] Verify pagination works (page, limit parameters)
- [ ] Check public/private message filtering
- [ ] Confirm proper error handling for invalid requests

## 📱 Responsive Design Tests

### Test 15: Mobile Responsiveness
- [ ] Test on mobile device or browser mobile view
- [ ] Verify chat button is accessible on mobile
- [ ] Check chat window fits mobile screen
- [ ] Test touch interactions work properly
- [ ] Confirm text input works on mobile keyboard

### Test 16: Different Screen Sizes
- [ ] Test on desktop (1920x1080)
- [ ] Test on tablet (768x1024)
- [ ] Test on mobile (375x667)
- [ ] Verify chat window positioning and sizing
- [ ] Check button accessibility on all sizes

## 🔒 Security Tests

### Test 17: Authentication
- [ ] Test without being logged in (should not show chat)
- [ ] Verify only authenticated users can access chat
- [ ] Test with different user roles
- [ ] Confirm proper session handling

### Test 18: Authorization
- [ ] Test access to private case chats
- [ ] Verify users can only access public cases
- [ ] Test message permissions
- [ ] Confirm proper error messages for unauthorized access

## 🎵 Audio Tests

### Test 19: Notification Sounds
- [ ] Send message from another window
- [ ] Verify notification sound plays
- [ ] Test mute button functionality
- [ ] Confirm sound stops when muted
- [ ] Test sound volume and quality

## 🐛 Error Handling Tests

### Test 20: Network Issues
- [ ] Disconnect internet temporarily
- [ ] Try to send a message
- [ ] Verify proper error handling
- [ ] Reconnect and test reconnection
- [ ] Confirm message queue works

### Test 21: Invalid Input
- [ ] Try to send empty message
- [ ] Test very long messages (>500 characters)
- [ ] Test special characters and emojis
- [ ] Verify proper validation and error messages

## 📊 Performance Tests

### Test 22: Message Load Performance
- [ ] Send 50+ messages
- [ ] Verify chat scrolls smoothly
- [ ] Check memory usage in browser
- [ ] Test with many concurrent users
- [ ] Confirm no memory leaks

### Test 23: WebSocket Performance
- [ ] Monitor WebSocket connection stability
- [ ] Test with poor network conditions
- [ ] Verify automatic reconnection
- [ ] Check latency and responsiveness

## 🎯 Integration Tests

### Test 24: Case Page Integration
- [ ] Verify chat appears on all public case pages
- [ ] Test case-specific chat rooms
- [ ] Confirm chat doesn't interfere with other page elements
- [ ] Test navigation between different cases

### Test 25: User Experience Flow
- [ ] Complete full user journey: visit case → open chat → send message → receive response
- [ ] Test with multiple users simultaneously
- [ ] Verify smooth user experience
- [ ] Confirm no broken functionality

## 📝 Test Results Documentation

### Pass/Fail Summary
- [ ] Total tests attempted: ____
- [ ] Tests passed: ____
- [ ] Tests failed: ____
- [ ] Success rate: ____%

### Issues Found
- [ ] List any bugs or issues discovered
- [ ] Document error messages
- [ ] Note browser-specific problems
- [ ] Record performance issues

### Recommendations
- [ ] Suggest improvements
- [ ] Note missing features
- [ ] Document user feedback
- [ ] Plan next iteration

## 🚀 Post-Test Actions

- [ ] Fix any critical issues found
- [ ] Document test results
- [ ] Update test cases if needed
- [ ] Plan performance optimizations
- [ ] Consider user feedback for improvements

---

**Test Date:** _______________  
**Tester:** _______________  
**Browser:** _______________  
**Device:** _______________  
**Overall Result:** ⭐⭐⭐⭐⭐ (1-5 stars) 