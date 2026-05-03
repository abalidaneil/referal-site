# Testing Guide - Referral Hub

## How to Test Your Referral Site

Complete guide to test all features of your new referral site.

## Pre-Test Checklist

- ✅ Database created
- ✅ config.php configured
- ✅ Files uploaded to webserver
- ✅ Can access http://localhost/referral-site/

## Test 1: Landing Page

**URL**: `http://localhost/referral-site/`

**Expected Results:**
- [ ] Page loads with hero section
- [ ] Features section visible (6 cards)
- [ ] How it works section visible (4 steps)
- [ ] Navigation shows "Login" and "Sign Up" buttons
- [ ] Responsive on mobile (test with browser dev tools)

## Test 2: User Registration

**URL**: `http://localhost/referral-site/register.php`

**Test Case 1: Basic Registration**
- [ ] Fill in form with:
  - Username: `testuser1`
  - Email: `test1@example.com`
  - Password: `Test123456`
  - Confirm: `Test123456`
  - Referral Code: (leave empty)
- [ ] Click "Create Account"
- [ ] See success message
- [ ] Redirected to login page

**Test Case 2: Validation Errors**
- [ ] Try empty fields → See error
- [ ] Try mismatched passwords → See error
- [ ] Try short password (< 6 chars) → See error
- [ ] Try invalid email → See error
- [ ] Try duplicate username → See error
- [ ] Try duplicate email → See error

**Test Case 3: With Referral Code**
- [ ] Use valid referral code → Should work
- [ ] Use invalid code → See error "Invalid referral code"

**Expected Database Changes:**
- [ ] New user created in `users` table
- [ ] Unique referral code generated
- [ ] If referral used: `referred_by_id` set, referral record created

## Test 3: User Login

**URL**: `http://localhost/referral-site/login.php`

**Test Case 1: Successful Login**
- [ ] Enter username: `testuser1`
- [ ] Enter password: `Test123456`
- [ ] Click "Login"
- [ ] Redirected to dashboard
- [ ] Session created

**Test Case 2: Failed Login**
- [ ] Try wrong password → See "Invalid username or password"
- [ ] Try non-existent user → See error
- [ ] Try empty fields → See error

**Expected Session:**
- [ ] `$_SESSION['user_id']` set
- [ ] `$_SESSION['username']` set

## Test 4: Dashboard

**URL**: `http://localhost/referral-site/dashboard.php` (after login)

**Expected Elements:**
- [ ] Welcome message with username
- [ ] Stats cards showing:
  - [ ] Total Points: 0 (if no referrals)
  - [ ] Total Referrals: 0
  - [ ] Completed Referrals: 0
- [ ] Referral code displayed
- [ ] "Copy Code" button works
- [ ] Referral link displayed
- [ ] "Copy Link" button works
- [ ] "Your Referrals" section (empty initially)
- [ ] "Recent Transactions" section (empty initially)

**Test Navigation:**
- [ ] "Logout" button visible
- [ ] Can access leaderboard
- [ ] Can access admin panel

## Test 5: Referral System

This is the core feature - test carefully!

**Test Case 1: Referral via Code**

**Step 1: Get Referral Code**
1. [ ] Login as testuser1
2. [ ] Go to dashboard
3. [ ] Copy referral code (example: ABC123456)
4. [ ] Note the code

**Step 2: Register with Referral Code**
1. [ ] Open new incognito/private browser window
2. [ ] Go to register page
3. [ ] Fill registration form:
   - Username: `testuser2`
   - Email: `test2@example.com`
   - Password: `Test123456`
   - Referral Code: `ABC123456`
4. [ ] Click "Create Account"
5. [ ] Should succeed

**Step 3: Verify Referral**
1. [ ] Close incognito window
2. [ ] Go back to testuser1's dashboard
3. [ ] Check stats - should see:
   - [ ] Total Points: 100
   - [ ] Total Referrals: 1
   - [ ] Completed Referrals: 1
4. [ ] Check "Your Referrals" table:
   - [ ] testuser2 listed
   - [ ] Status: "Completed"
   - [ ] Points: 100
5. [ ] Check "Recent Transactions":
   - [ ] Entry for referral reward
   - [ ] +100 points
   - [ ] Description: "Referral from testuser2"

**Expected Database Changes:**
- [ ] New referral record in `referrals` table
- [ ] Status: "completed"
- [ ] testuser1's `reward_points` increased by 100
- [ ] Transaction record created

**Test Case 2: Referral via Link**

1. [ ] Get full referral link from dashboard
2. [ ] Open new browser and visit link directly
3. [ ] Should see registration page
4. [ ] Referral code should be auto-filled (optional: implement this)
5. [ ] Complete registration
6. [ ] Verify points awarded to referrer

## Test 6: Leaderboard

**URL**: `http://localhost/referral-site/leaderboard.php`

**Test Case 1: View Leaderboard**
- [ ] Page loads
- [ ] Lists referrers by points (highest first)
- [ ] Shows rank with medals (🥇🥈🥉)
- [ ] Displays:
  - [ ] Username
  - [ ] Total Referrals
  - [ ] Completed Referrals
  - [ ] Points
  - [ ] Join Date
- [ ] Can access without login
- [ ] Responsive design works

**Test Case 2: Multiple Users**
1. [ ] Create 3-5 test users
2. [ ] Create referrals for each
3. [ ] Check leaderboard
4. [ ] Ranking is correct (highest points first)

## Test 7: Admin Panel

**URL**: `http://localhost/referral-site/admin.php` (after login)

**Expected Elements:**
- [ ] Platform statistics:
  - [ ] Total Users
  - [ ] Total Referrals
  - [ ] Completed Referrals
  - [ ] Total Points Distributed
- [ ] Conversion Rate calculation
- [ ] Average Points per User
- [ ] Recent Signups table
- [ ] Recent Referrals table

**Test Calculations:**
- [ ] Conversion rate: (completed / total) * 100 = correct %
- [ ] Avg points: total_points / user_count = correct number

## Test 8: Logout

**URL**: `http://localhost/referral-site/logout.php`

- [ ] Click logout button on any page
- [ ] Session destroyed
- [ ] Redirected to home page
- [ ] Cannot access dashboard without login
- [ ] Can access login/register pages

## Test 9: Session Management

**Test Case 1: Login Persistence**
1. [ ] Login to account
2. [ ] Refresh page
3. [ ] Still logged in
4. [ ] Dashboard loads correctly

**Test Case 2: Unauthorized Access**
1. [ ] Don't login
2. [ ] Try to access dashboard.php directly
3. [ ] Should redirect to login.php

## Test 10: Responsive Design

Test on different screen sizes:

**Mobile (375px width)**
- [ ] Navigation adapts
- [ ] Forms stack properly
- [ ] Tables scroll horizontally
- [ ] Buttons are touchable

**Tablet (768px width)**
- [ ] 2-column layout works
- [ ] Tables readable
- [ ] All content accessible

**Desktop (1200px width)**
- [ ] Full layout displays
- [ ] Grid layouts expand
- [ ] All features visible

## Test 11: Error Scenarios

**Database Issues**
- [ ] If DB disconnects → Appropriate error shown
- [ ] No SQL errors displayed to user

**Data Validation**
- [ ] Can't use same referral code twice with same user
- [ ] Can't create duplicate usernames
- [ ] Can't use duplicate emails

**Edge Cases**
- [ ] Very long usernames
- [ ] Special characters in username
- [ ] Large number of referrals
- [ ] Large point numbers

## API Testing

### Test Referral APIs

**Verify Code**
```bash
curl -X POST http://localhost/referral-site/api/referrals.php?action=verify_code \
  -d "code=ABC123456"
```
- [ ] Returns correct user_id
- [ ] Returns username
- [ ] Invalid code returns error

**Get Stats**
```bash
curl http://localhost/referral-site/api/referrals.php?action=get_stats
```
- [ ] Returns user's stats
- [ ] Requires login (test with cookie)

**Top Referrers**
```bash
curl "http://localhost/referral-site/api/referrals.php?action=top_referrers&limit=5"
```
- [ ] Returns sorted list
- [ ] Respects limit parameter
- [ ] Returns valid JSON

### Test Admin APIs

**Get Stats**
```bash
curl http://localhost/referral-site/api/admin.php?action=stats
```
- [ ] Returns platform statistics
- [ ] Numbers match dashboard

**Get Users**
```bash
curl http://localhost/referral-site/api/admin.php?action=users
```
- [ ] Returns user list
- [ ] Includes points and creation date

## Performance Testing

- [ ] Home page loads < 1 second
- [ ] Dashboard loads < 2 seconds
- [ ] Leaderboard loads < 2 seconds
- [ ] Can handle 100 referrals in table
- [ ] API responses < 500ms

## Security Testing

- [ ] Can't access config.php directly (should be denied by .htaccess)
- [ ] Can't access database.sql directly
- [ ] Password is hashed in database
- [ ] Emails are validated
- [ ] Input is sanitized
- [ ] SQL injection attempts don't work

## Final Checklist

- [ ] All pages load
- [ ] Registration works
- [ ] Login/logout works
- [ ] Referral system works
- [ ] Points awarded correctly
- [ ] Dashboard shows correct data
- [ ] Leaderboard displays correctly
- [ ] Admin panel works
- [ ] APIs return correct data
- [ ] Responsive design works
- [ ] Error handling works
- [ ] Database integrity maintained
- [ ] No console errors
- [ ] Performance is acceptable

## Known Test Scenarios

### Scenario 1: New User Flow
1. Register → 2. Get code → 3. Share → 4. Friend registers → 5. Points awarded

### Scenario 2: Multiple Referrals
1. User refers 5 people → 2. All complete → 3. Total points = 500 → 4. User ranks high

### Scenario 3: Admin Operations
1. View stats → 2. See totals → 3. Check referrer list → 4. View transactions

## Debugging

If something doesn't work:

1. Check browser console (F12) for errors
2. Check PHP error logs
3. Verify database connection:
   ```php
   echo $conn->connect_error; // In config.php
   ```
4. Test database queries in phpMyAdmin
5. Check file permissions
6. Verify .htaccess is working

## Next Steps After Testing

✅ If all tests pass:
1. Deploy to production server
2. Set up SSL/HTTPS
3. Configure admin authentication
4. Set up email notifications
5. Monitor logs
6. Get user feedback

---

**Ready to test?** Start with Test 1 and work through systematically!
