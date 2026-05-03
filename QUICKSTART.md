# Quick Start Guide - Referral Hub

## 5-Minute Setup

### Step 1: Create Database (1 minute)

1. Open phpMyAdmin or MySQL CLI
2. Create database:
   ```sql
   CREATE DATABASE referral_site;
   ```
3. Import `database.sql` file:
   - In phpMyAdmin: Import tab → Select database.sql
   - Or in MySQL CLI: `mysql -u root -p referral_site < database.sql`

### Step 2: Configure Settings (1 minute)

Edit `config.php` and verify:
```php
define('DB_HOST', 'localhost');      // Usually localhost
define('DB_USER', 'root');           // Your MySQL user
define('DB_PASS', '');               // Your MySQL password
define('DB_NAME', 'referral_site');  // Database name
define('SITE_URL', 'http://localhost/referral-site'); // Your site URL
```

### Step 3: Upload Files (1 minute)

Place all files in your webserver's public directory:
```
htdocs/referral-site/  (or www/ or public_html/)
├── config.php
├── database.sql
├── index.php
├── register.php
├── login.php
├── dashboard.php
├── logout.php
├── leaderboard.php
├── admin.php
├── style.css
├── .htaccess
├── api/
│   ├── referrals.php
│   └── admin.php
└── README.md
```

### Step 4: Access Your Site (2 minutes)

Open browser and go to:
```
http://localhost/referral-site/
```

## First Test Run

### Create Test Account

1. Click "Start Earning Now"
2. Fill in registration form:
   - Username: testuser1
   - Email: test1@example.com
   - Password: Test123456
3. Click "Create Account"

### Get Your Referral Code

1. Login with your credentials
2. Go to Dashboard
3. Copy your referral code (example: ABC123456)

### Test Referral

1. Open another browser tab (private/incognito)
2. Go to registration page
3. Fill in new account details:
   - Username: testuser2
   - Email: test2@example.com
   - Referral Code: ABC123456 (from previous account)
4. Complete registration

### Verify It Worked

1. Go back to testuser1's dashboard
2. You should see:
   - 100 reward points
   - 1 completed referral
   - testuser2 listed in referrals table

## Important Files Explained

| File | Purpose |
|------|---------|
| `config.php` | Database connection & helper functions |
| `index.php` | Landing/home page |
| `register.php` | User registration |
| `login.php` | User login |
| `dashboard.php` | User dashboard (shows referrals & points) |
| `leaderboard.php` | Top referrers leaderboard |
| `admin.php` | Admin statistics panel |
| `logout.php` | Logout handler |
| `style.css` | All styling |
| `api/referrals.php` | Referral API endpoints |
| `api/admin.php` | Admin API endpoints |
| `database.sql` | Database schema |
| `.htaccess` | URL rewriting & security |

## Useful URLs

| URL | Purpose |
|-----|---------|
| `/index.php` | Home page |
| `/register.php` | Registration page |
| `/login.php` | Login page |
| `/dashboard.php` | User dashboard (logged in) |
| `/leaderboard.php` | View top referrers |
| `/admin.php` | Admin statistics (logged in) |
| `/logout.php` | Logout |
| `/api/referrals.php?action=top_referrers` | Get top referrers (JSON API) |
| `/api/admin.php?action=stats` | Get platform stats (JSON API) |

## Common Issues

### "Connection failed: Unknown database"
- Check database name in `config.php`
- Verify you ran `database.sql` to create tables
- Make sure database exists: `SHOW DATABASES;`

### "Referral code doesn't work"
- Referral code is case-sensitive
- Referrer account must exist
- New user must use code during registration

### "Can't login"
- Make sure you registered first
- Check username/password spelling
- Clear browser cache/cookies

### "Page is blank"
- Check PHP error logs
- Verify all files uploaded correctly
- Check file permissions (755 for folders, 644 for files)

## Next Steps

1. ✅ Test the basic functionality
2. ✅ Create sample accounts
3. ✅ Test referral process
4. ✅ Check admin panel
5. ✅ View leaderboard
6. ❌ For production:
   - Add SSL/HTTPS
   - Implement proper admin authentication
   - Set up email notifications
   - Add password reset
   - Add two-factor authentication
   - Set up backups
   - Configure proper error handling

## API Testing

### Test Get Stats API
```bash
curl "http://localhost/referral-site/api/admin.php?action=stats"
```

### Test Top Referrers API
```bash
curl "http://localhost/referral-site/api/referrals.php?action=top_referrers&limit=5"
```

## Customization Ideas

- Add email notifications for referrals
- Create a rewards/redemption system
- Add social sharing buttons
- Implement tiered rewards
- Add monthly bonuses for top referrers
- Create user profile pages
- Add referral analytics charts
- Integrate with payment systems for rewards

## Support

Refer to `README.md` for detailed documentation, API reference, and production recommendations.

---

**Ready to go!** Your referral site is now live and ready to use.
