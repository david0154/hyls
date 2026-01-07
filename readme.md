# HYLS - HypeLink Shortener System
### Complete PHP Link Shortener & Bio Link Platform with HypeChats OAuth

---

## 🚀 Features

- ✅ **Link Shortening** - Create custom short links with random codes
- ✅ **Bio Link Pages** - Beautiful animated profile pages with social media links
- ✅ **HypeChats OAuth** - Secure login via HypeChats platform (OFFICIAL API)
- ✅ **Automatic Signup** - Auto-creates default bio link on first signup
- ✅ **Analytics Dashboard** - Track clicks, views, and engagement
- ✅ **Advertisement System** - 5-second ads before redirects
- ✅ **Google Analytics** - Deep link tracking integration
- ✅ **User Management** - Full admin panel
- ✅ **Custom Themes** - Personalized colors for bio pages
- ✅ **Social Media Integration** - SVG icons for 16+ platforms
- ✅ **Responsive Design** - Mobile-friendly UI with smooth animations
- ✅ **Enhanced Bio Page** - New design with sections, badges, and animations
- ✅ **One-Click Install** - Easy setup wizard

---

## 📁 File Structure

```
hyls/
├── install.php                 # One-click installer
├── config.php                  # Generated configuration file
├── index.php                   # Homepage/landing page
├── auth.php                    # HypeChats OAuth handler (OFFICIAL API)
├── login.php                   # Traditional login page
├── dashboard.php               # User dashboard
├── biolink.php                 # Bio link editor
├── bio.php                     # Bio link display (ENHANCED)
├── shorten.php                 # Link creation handler
├── r.php                       # Redirect handler with ads
├── delete_link.php             # Link deletion
├── logout.php                  # Logout handler
├── .htaccess                   # URL rewriting rules
│
├── includes/
│   ├── db.php                  # Database class
│   ├── functions.php           # Helper functions
│   └── ads.php                 # Advertisement system
│
├── admin/
│   ├── index.php               # Admin dashboard
│   ├── users.php               # User management
│   ├── links.php               # Link management
│   └── settings.php            # System settings
│
├── uploads/
│   └── profiles/               # Profile pictures
│
└── assets/
    ├── logo.png
    └── favicon.ico
        
```

---

## 🔧 Installation Steps

### Prerequisites
- PHP 7.4 or higher (recommended 8.0+)
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- HypeChats App ID and App Secret
- **allow_url_fopen enabled in php.ini** (for API calls)

### Step 1: Upload Files
Upload all files to your web server directory

### Step 2: Set Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/profiles/
```

### Step 3: Enable URL Fopen (REQUIRED for HypeChats API)
Edit your php.ini and ensure:
```ini
allow_url_fopen = On
```
If you can't edit php.ini, contact your hosting provider.

### Step 4: Run Installer
1. Visit: `https://yourdomain.com/install.php`
2. Fill in the installation form:
   - **Database Information**
     - Host: localhost (usually)
     - Database Name: hyls_db
     - Username: your_db_user
     - Password: your_db_password
   
   - **Admin Account**
     - Username: admin
     - Email: admin@yourdomain.com
     - Password: (strong password)
   
   - **Site Configuration**
     - Site URL: https://yourdomain.com
     - Site Name: HYLS
   
   - **HypeChats OAuth**
     - App ID: YOUR_APP_ID
     - App Secret: YOUR_APP_SECRET
   
   - **Google Analytics (Optional)**
     - Tracking ID: G-XXXXXXXXXX

3. Click "Install HYLS"

### Step 5: Configure .htaccess
Make sure your `.htaccess` file is properly configured:

```apache
RewriteEngine On
RewriteBase /

# Bio link routing
RewriteRule ^bio/([a-zA-Z0-9_-]+)$ bio.php?u=$1 [L,QSA]

# Short link routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([a-zA-Z0-9_-]+)$ r.php?c=$1 [L,QSA]
```

### Step 6: Verify Installation
1. Visit your dashboard: `https://yourdomain.com/dashboard.php`
2. Try creating a short link
3. Test the bio page: `https://yourdomain.com/bio/yourusername`
4. Check admin panel: `https://yourdomain.com/admin/`

---

## 🔐 HypeChats OAuth Setup (OFFICIAL API)

### Get Your Credentials
1. Visit: https://hypechats.com/developer
2. Create a new app
3. Copy your **App ID** and **App Secret**
4. No specific callback URL needed - system handles it automatically

### Official OAuth Flow

HYLS implements the official HypeChats OAuth API endpoints:

**Step 1: Redirect user to authorization**
```
URL: https://hypechats.com/oauth?app_id={YOUR_APP_ID}
```
The system provides this link in the login page.

**Step 2: User authorizes and returns with code**
```
Return URL: https://yourdomain.com/auth.php?code=XXX
```

**Step 3: Exchange code for access token (Official Endpoint)**
```
URL: https://hypechats.com/authorize?app_id={APP_ID}&app_secret={APP_SECRET}&code={CODE}

Response:
{
    "access_token": "your_access_token",
    "token_type": "Bearer"
}
```

**Step 4: Get user data (Official Endpoint)**
```
URL: https://hypechats.com/app_api?access_token={TOKEN}&type=get_user_data

Response:
{
    "api_status": "success",
    "api_version": "1.3",
    "user_data": {
        "id": "user_id",
        "username": "username",
        "first_name": "First",
        "last_name": "Last",
        "gender": "M/F",
        "birthday": "1990-01-01",
        "about": "Bio text",
        "website": "https://example.com",
        "profile_picture": "https://...",
        "verified": "1",
        "url": "https://hypechats.com/user/..."
    }
}
```

### Implementation in auth.php

Our `auth.php` automatically handles all steps:

```php
// Get authorization code
$code = $_GET['code'];

// Step 1: Get access token using official endpoint
$token_url = "https://hypechats.com/authorize?app_id={$app_id}&app_secret={$app_secret}&code={$code}";
$response = file_get_contents($token_url);
$token_result = json_decode($response, true);
$access_token = $token_result['access_token'];

// Step 2: Get user data using official endpoint
$user_url = "https://hypechats.com/app_api?access_token={$access_token}&type=get_user_data";
$user_response = file_get_contents($user_url);
$user_result = json_decode($user_response, true);
$user_data = $user_result['user_data'];

// Step 3: Create/update user in database
// Step 4: Create bio link automatically
// Step 5: Set session and redirect
```

### OAuth Troubleshooting

| Problem | Solution |
|---------|----------|
| "No authorization code received" | Verify user completed HypeChats authorization, clear browser cookies |
| "Failed to obtain access token" | Check App ID & Secret are correct, verify allow_url_fopen is enabled |
| "Failed to retrieve user data" | Ensure access token is valid, check HypeChats API status |
| "allow_url_fopen is disabled" | Contact hosting provider, enable in php.ini |
| "Invalid JSON response" | HypeChats API might be temporarily down, try again later |
| User not created | Check database permissions, review error logs |

---

## 📊 Database Schema

### users
- `id` - Primary key
- `hype_id` - HypeChats user ID (unique)
- `username` - Unique username
- `email` - User email
- `password` - Hashed password (for non-OAuth users)
- `first_name`, `last_name` - User details from HypeChats
- `profile_picture` - Avatar URL from HypeChats
- `access_token` - HypeChats access token
- `is_admin` - Admin flag
- `earnings` - Total earnings from ads
- `created_at` - Registration timestamp
- `updated_at` - Last update timestamp

### short_links
- `id` - Primary key
- `user_id` - Foreign key to users
- `short_code` - Unique short code
- `original_url` - Target URL
- `title` - Optional link title
- `clicks` - Click counter
- `earnings` - Revenue from clicks
- `created_at` - Creation timestamp

### bio_links
- `id` - Primary key
- `user_id` - Foreign key to users
- `username` - URL-safe username
- `display_name` - Display name
- `bio` - Bio text
- `profile_image` - Profile picture path
- `theme_color` - Hex color code
- `facebook`, `instagram`, `twitter`, `linkedin`, `youtube`, `tiktok`, `github`, `website` - Social links
- `email`, `phone` - Contact info
- `*_enabled` - Toggle for each social/contact (1/0)
- `views` - View counter
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### advertisements
- `id` - Primary key
- `title` - Ad title
- `description` - Ad description
- `url` - Ad link URL
- `cta_text` - Call-to-action text
- `position` - Display position
- `is_active` - Active status (1/0)

### analytics
- `id` - Primary key
- `link_id` - Foreign key to short_links
- `ip_address` - Visitor IP
- `user_agent` - Browser info
- `referrer` - Referring URL
- `country` - Geographic location
- `clicked_at` - Click timestamp

### settings
- `id` - Primary key
- `setting_key` - Setting name
- `setting_value` - Setting value

---

## 🎨 Enhanced Features

### Bio Page Design
✨ **Animations & Effects:**
- Smooth slide-up entrance animation
- Staggered fade-in for sections
- Badge system for view counter
- Section headers with icons
- Ripple effect on social buttons
- Gradient slide on contact buttons
- Improved mobile responsiveness
- Open Graph meta tags for sharing
- Lazy loading for images

### HypeChats OAuth (Official API)
✅ **Features:**
- Uses official HypeChats endpoints
- Automatic bio link creation
- User profile updates
- Better error handling
- Comprehensive logging
- Secure token management

### Dashboard
📊 **Full Functionality:**
- Create/manage short links
- Copy links to clipboard
- Delete links
- View statistics
- Edit bio page
- Profile management
- Earnings tracking

---

## 🔗 URL Structure

```
Home: https://yourdomain.com/
Bio Page: https://yourdomain.com/bio/{username}
Short Link: https://yourdomain.com/{code}
Admin: https://yourdomain.com/admin/
```

---

## 🎯 Usage Guide

### For Users

**Creating Short Links:**
1. Login to dashboard
2. Click "Create Short Link"
3. Enter original URL
4. (Optional) Add custom code
5. (Optional) Add title
6. Click Create

**Creating Bio Page:**
1. Go to Dashboard
2. Click "Edit Bio Link"
3. Upload profile picture
4. Fill in display name and bio
5. Add social media links
6. Choose theme color
7. Add email/phone (optional)
8. Save

**Share Your Bio:**
- URL: `yourdomain.com/bio/yourusername`
- Share on social media
- Use in email signatures

### For Admins

- Manage users and permissions
- Monitor link analytics
- Configure ads
- Update settings
- Manage system

---

## 🔒 Security

- SQL injection protection (PDO prepared statements)
- XSS prevention (htmlspecialchars)
- CSRF protection (session tokens)
- Input validation
- Secure OAuth (official API)
- HTTPS enforcement
- Admin area protection
- Error logging

---

## 🐛 Troubleshooting

### Installation

**"Database connection failed"**
- Check database credentials
- Ensure MySQL is running
- Verify user permissions

**"Forbidden" error**
- Enable mod_rewrite
- Check .htaccess permissions
- Verify Apache configuration

### OAuth

**"No authorization code"**
- Check HypeChats app is set up
- Verify user authorized
- Clear browser cookies

**"Failed to obtain token"**
- Check App ID/Secret
- Verify allow_url_fopen is ON
- Check internet connection

**"Failed to get user data"**
- Check HypeChats API status
- Verify access token
- Check error logs

### Features

**Short links not working:**
- Check .htaccess configuration
- Verify mod_rewrite is enabled
- Clear browser cache

**Images not uploading:**
- Check folder permissions
- Verify disk space
- Check file size limits in php.ini

---

## 📝 Configuration

### config.php
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hyls_db');
define('DB_USER', 'username');
define('DB_PASS', 'password');
define('SITE_URL', 'https://yourdomain.com');
define('SITE_NAME', 'HYLS');
define('APP_ID', 'your_app_id');
define('APP_SECRET', 'your_app_secret');
define('GA_TRACKING_ID', 'G-XXXXXXXXXX');
```

### php.ini (Required)
```ini
allow_url_fopen = On
```

---

## 📈 Performance

- Enable OpCache
- Use CDN for assets
- Enable compression
- Optimize database
- Cache pages

---

## 🔄 Backup & Restore

### Backup
```bash
mysqldump -u user -p database > backup.sql
tar -czf hyls_backup.tar.gz /path/to/hyls/
```

### Restore
```bash
mysql -u user -p database < backup.sql
tar -xzf hyls_backup.tar.gz
```

---

## 📞 Support

- Check README
- Review error logs
- HypeChats: https://hypechats.com/support
- Check PHP logs

---

## 📜 License

Provided as-is for HypeChats integration.

---

## 🎉 Credits

- **Built for**: HypeChats
- **Uses**: Official HypeChats API
- **Icons**: Font Awesome
- **Design**: Modern gradient UI

---

## 📋 Changelog

### Version 1.1.1 (Current)
✅ **Official API Implementation:**
- Uses https://hypechats.com/authorize for tokens
- Uses https://hypechats.com/app_api for user data
- Uses file_get_contents() per official docs
- Automatic bio link on signup
- Better error handling
- Enhanced documentation

### Version 1.1.0
- Bio page animations
- Enhanced error handling
- Documentation improvements

### Version 1.0.0
- Initial release

---

**Version**: 1.1.1  
**Updated**: January 8, 2026  
**Status**: ✅ Production Ready  
**Powered by**: [HypeChats](https://hypechats.com)
