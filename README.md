# Referral Hub - Complete Referral Site

A modern, fully-featured referral system built with PHP, HTML, CSS, and MySQL. Users can create accounts, share referral codes, track referrals, and earn reward points.

## Features

✅ **User Management**
- User registration with email validation
- Secure login with password hashing
- User profiles and account management

✅ **Referral System**
- Unique referral code generation for each user
- Referral tracking dashboard
- Share referral codes or direct links
- Track referral status (pending, completed, cancelled)

✅ **Rewards Program**
- Earn points for successful referrals
- Reward points tracking
- Transaction history
- Admin bonus system

✅ **Dashboard**
- View total points and referral statistics
- See all referrals with detailed information
- Transaction history
- Copy-to-clipboard functionality

✅ **Responsive Design**
- Mobile-friendly interface
- Clean, modern UI with Tailwind-inspired styling
- Professional branding

## Project Structure

```
referral-site/
├── index.php              # Landing page
├── register.php           # User registration
├── login.php              # User login
├── dashboard.php          # User dashboard
├── logout.php             # Logout handler
├── config.php             # Database configuration & helper functions
├── style.css              # Global styles
├── database.sql           # Database schema
├── api/
│   ├── referrals.php      # Referral API endpoints
│   └── admin.php          # Admin API endpoints
└── README.md              # This file
```

## Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache with mod_rewrite recommended)
- USB webserver environment or local server

### Step 1: Set Up the Database

1. Open your MySQL client or phpMyAdmin
2. Create a new database:
   ```sql
   CREATE DATABASE referral_site;
   ```

3. Import the database schema:
   ```sql
   USE referral_site;
   ```
   Then copy and paste the contents of `database.sql` and execute it.

   Or use command line:
   ```bash
   mysql -u root -p referral_site < database.sql
   ```

### Step 2: Configure the Application

1. Open `config.php`
2. Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'referral_site');
   ```

3. Update the site URL:
   ```php
   define('SITE_URL', 'http://localhost/referral-site');
   ```

4. Adjust reward points as needed:
   ```php
   define('REFERRAL_REWARD_POINTS', 100);  // Points for completing a referral
   define('REFERRAL_BONUS_POINTS', 50);    // Bonus points
   ```

### Step 3: Place Files on Server

1. Copy all files to your web server's document root (e.g., `htdocs/referral-site/`)
2. Ensure the directory has proper permissions (755 for folders, 644 for files)

### Step 4: Access the Site

Open your browser and navigate to:
```
http://localhost/referral-site/
```

## Usage

### For Users

1. **Sign Up**: Click "Start Earning Now" and register with your email
2. **Get Referral Code**: After registration, go to your dashboard
3. **Share Your Code**: Copy your unique referral code or full referral link
4. **Earn Rewards**: When someone signs up using your code, you earn points
5. **Track Progress**: View all referrals and transaction history on your dashboard

### Referral Process

```
1. User A signs up and gets referral code: ABC123456
2. User A shares code with User B
3. User B signs up and enters code ABC123456 during registration
4. System marks referral as "completed"
5. User A receives 100 reward points
6. Points appear in User A's dashboard
```

## API Endpoints

### Referrals API (`api/referrals.php`)

**Verify Referral Code**
```
POST /api/referrals.php?action=verify_code
Parameters: code (string)
Response: { success: boolean, user_id: integer, username: string }
```

**Complete Referral**
```
POST /api/referrals.php?action=complete_referral
Parameters: referral_id (integer)
Response: { success: boolean, message: string }
```

**Get User Stats**
```
GET /api/referrals.php?action=get_stats
Response: { success: boolean, reward_points, total_referrals, completed_referrals }
```

**Top Referrers**
```
GET /api/referrals.php?action=top_referrers&limit=10
Response: { success: boolean, referrers: array }
```

### Admin API (`api/admin.php`)

**Get Platform Statistics**
```
GET /api/admin.php?action=stats
Response: { total_users, total_referrals, completed_referrals, total_points_distributed }
```

**Get All Users**
```
GET /api/admin.php?action=users
Response: { users: array of users with points and creation date }
```

**Get User Details**
```
GET /api/admin.php?action=user_detail&user_id=1
Response: { user: object with details }
```

**Add Bonus Points**
```
POST /api/admin.php?action=add_bonus
Parameters: user_id (integer), points (integer), reason (string)
Response: { success: boolean, message: string }
```

## Database Schema

### Users Table
- `id`: Primary key
- `username`: Unique username
- `email`: Unique email address
- `password`: Hashed password
- `referral_code`: Unique referral code
- `referred_by_id`: ID of referrer (if applicable)
- `reward_points`: Total reward points
- `created_at`: Account creation timestamp

### Referrals Table
- `id`: Primary key
- `referrer_id`: ID of user who referred
- `referee_id`: ID of referred user
- `status`: pending, completed, or cancelled
- `reward_given`: Points given for this referral
- `created_at`: Referral creation timestamp
- `completed_at`: When referral was completed

### Transactions Table
- `id`: Primary key
- `user_id`: User who received transaction
- `points_change`: Points added or removed
- `transaction_type`: Type of transaction
- `description`: Transaction description
- `created_at`: Transaction timestamp

### Rewards Table
- `id`: Primary key
- `user_id`: User who earned reward
- `points`: Points awarded
- `type`: Type of reward
- `description`: Reward description
- `created_at`: Reward timestamp

## Security Considerations

⚠️ **Important Security Notes:**

1. **Password Security**: Passwords are hashed using PHP's `password_hash()` function
2. **SQL Injection Prevention**: All database queries use prepared statements
3. **XSS Protection**: User input is sanitized using `htmlspecialchars()`
4. **Session Management**: Sessions are used for user authentication

### For Production Deployment:

- Add HTTPS/SSL certificates
- Implement rate limiting on login/registration
- Add CSRF token protection
- Add stronger admin authentication
- Implement email verification for signups
- Add password reset functionality
- Add two-factor authentication (2FA)
- Implement API rate limiting
- Add comprehensive logging and monitoring
- Perform security audit before launch

## Customization

### Changing Reward Points

Edit `config.php`:
```php
define('REFERRAL_REWARD_POINTS', 100);  // Change this value
```

### Changing Site Colors

Edit `style.css` - modify CSS custom properties:
```css
:root {
    --primary-color: #4f46e5;      /* Main brand color */
    --secondary-color: #10b981;    /* Success/positive color */
    --danger-color: #ef4444;       /* Error/danger color */
    /* ... more colors ... */
}
```

### Adding Custom Features

Create new PHP files following this pattern:
```php
<?php
include 'config.php';
require_login();  // If user auth is required

// Your code here
?>
```

## Troubleshooting

### "Connection failed" Error
- Check database credentials in `config.php`
- Ensure MySQL server is running
- Verify database exists

### "Invalid username or password" on Login
- Check if user exists in database
- Ensure password is typed correctly
- User must have registered first

### Referral Code Not Working
- Verify code is typed correctly (case-sensitive)
- Check if referrer account still exists
- Ensure code hasn't been banned

### Session Issues
- Check PHP session settings
- Clear browser cookies
- Verify temp directory has write permissions

## Support & Updates

For issues, questions, or feature requests, please refer to the code comments or modify as needed for your specific use case.

## License

This project is open source and available for personal and commercial use.

## Version History

- **v1.0** (2026-05-02): Initial release
  - User registration and login
  - Referral system
  - Dashboard
  - Reward tracking
  - Admin APIs

---

**Last Updated**: May 2, 2026
