# HYLS - HypeLink Shortener System
### Complete PHP Link Shortener & Bio Link Platform with HypeChats OAuth

---

## 🚀 Features

- ✅ **Link Shortening** - Create custom short links with random codes
- ✅ **Bio Link Pages** - Beautiful animated profile pages with social media links
- ✅ **HypeChats OAuth** - Secure login via HypeChats platform (FIXED)
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
├── auth.php                    # HypeChats OAuth handler (FIXED)
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
    ├── favicon.ico
        
```

---

## 🔧 Installation Steps

### Prerequisites
- PHP 7.4 or higher (recommended 8.0+)
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- HypeChats App ID and App Secret
- OpenSSL extension for CURL

### Step 1: Upload Files
Upload all files to your web server directory

### Step 2: Set Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/profiles/
```

### Step 3: Run Installer
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

### Step 4: Configure .htaccess
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

### Step 5: Verify Installation
1. Visit your dashboard: `https://yourdomain.com/dashboard.php`
2. Try creating a short link
3. Test the bio page: `https://yourdomain.com/bio/yourusername`
4. Check admin panel: `https://yourdomain.com/admin/`

---

## 🔐 HypeChats OAuth Setup (FIXED)

### Get Your Credentials
1. Visit: https://hypechats.com/
2. Create a new app
3. Set **Callback URL**: `https://yourdomain.com/auth.php` (MUST be HTTPS)
4. Copy your **App ID** and **App Secret**

### Integration Flow (Improved)
1. User clicks "Sign in with HypeChats"
2. Redirected to: `https://hypechats.com/oauth?app_id={YOUR_APP_ID}&redirect_uri={ENCODED_CALLBACK_URL}`
3. User authorizes the app
4. Redirected back with code: `https://yourdomain.com/auth.php?code=XXX`
5. System exchanges code for access token (with improved error handling)
6. User data retrieved and account created/updated
7. **Default bio link automatically created**

### OAuth Troubleshooting

**Issue: "Failed to get access token"**
- Verify App ID and App Secret are correct
- Check callback URL matches exactly in HypeChats settings
- Ensure your domain uses HTTPS
- Check PHP CURL is enabled
- Verify OpenSSL extension is installed

**Issue: "Invalid user data received"**
- HypeChats API might be down - try again later
- Check your access token is valid
- Verify user has complete profile on HypeChats

**Issue: "No authorization code received"**
- Callback URL doesn't match
- Check browser redirect chain in developer tools
- Verify cookies are enabled

---

## 📊 Database Schema

### users
- `id` - Primary key
- `hype_id` - HypeChats user ID
- `username` - Unique username
- `email` - User email
- `password` - Hashed password (for non-OAuth users)
- `first_name`, `last_name` - User details
- `profile_picture` - Avatar URL
- `access_token` - HypeChats access token (auto-refreshed)
- `is_admin` - Admin flag
- `earnings` - Total earnings from ads
- `created_at` - Registration timestamp
- `updated_at` - Last update timestamp

### short_links
- `id` - Primary key
- `user_id` - Foreign key to users
- `short_code` - Unique short code (e.g., "abc123")
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
- `facebook`, `instagram`, `twitter`, etc. - Social links (16+ platforms)
- `email`, `phone` - Contact info
- `*_enabled` - Toggle columns for each social/contact (1/0)
- `views` - View counter
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### advertisements
- `id` - Primary key
- `title` - Ad title
- `description` - Ad description
- `url` - Ad link URL
- `cta_text` - Call-to-action button text
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

## 🎨 Enhanced Features (New)

### Bio Page Design Improvements
✨ **New Features:**
- Smooth slide-up animations on page load
- Staggered fade-in animations for elements
- Badge system for displaying views
- Section headers with icons
- Dividers between sections
- Ripple effect on social buttons
- Sliding gradient effect on contact buttons
- Better mobile responsiveness
- Improved typography hierarchy
- Lazy loading for images
- Open Graph meta tags for social sharing

### HypeChats OAuth Fixes
🔧 **Fixed Issues:**
- Improved error handling with detailed messages
- Added CURL timeout configuration
- SSL certificate verification
- JSON parsing error handling
- Automatic default bio link creation on signup
- Updated user profile on re-login
- Better error logging for debugging
- Proper HTTP status code handling

### Dashboard Features
📊 **Fully Working Features:**
- Create short links with custom codes
- View all links with statistics
- Copy links to clipboard
- Delete links with confirmation
- Real-time click counter
- Earnings tracking
- Bio link status indicator
- Edit bio page from dashboard
- Profile management

---

## 🔗 URL Structure

### Short Links
```
https://yourdomain.com/abc123
→ Shows 5-second ad → Redirects to target URL
```

### Bio Pages
```
https://yourdomain.com/bio/username
→ Displays user's bio page with all links
```

### Admin Panel
```
https://yourdomain.com/admin/
→ Admin dashboard (requires admin privileges)
```

### Custom Paths
```
https://yourdomain.com/my-custom-link
→ Redirects using short code
```

---

## 🎯 Usage Guide

### For Users

#### Creating Short Links
1. Login to dashboard
2. Click "Create Short Link"
3. Enter original URL (required)
4. (Optional) Add custom code (2-20 characters)
5. (Optional) Add title for reference
6. Click "Create Link"
7. Copy and share!

#### Creating/Editing Bio Page
1. Go to Dashboard
2. Click "Edit Bio Link"
3. Upload profile picture (optional)
4. Fill in display name and bio
5. Add social media links (16+ platforms)
6. Choose theme color (hex color picker)
7. Add contact information (email/phone)
8. Enable/disable individual social links
9. Click "Save Bio Link"
10. Share: `yourdomain.com/bio/yourusername`

#### Sharing Bio Page
- Direct link: `yourdomain.com/bio/yourusername`
- QR code: Can be generated from admin panel
- Social media: Share the link on all platforms
- View counter: Tracks total views

### For Admins

#### Managing Users
- View all registered users
- Check user statistics and earnings
- Promote/demote admin status
- View user links and bio pages
- Monitor registration trends

#### Managing Links
- View all shortened links
- Monitor click statistics
- Check earnings by link
- Delete inappropriate links
- Export analytics data

#### System Settings
- Enable/disable ads
- Set ad duration (seconds)
- Configure Google Analytics
- Update HypeChats credentials
- Customize branding
- Manage advertisements

---

## 🔒 Security Features

- Password hashing (bcrypt)
- SQL injection protection (PDO prepared statements)
- XSS prevention (htmlspecialchars, escaping)
- CSRF protection (session tokens)
- Input validation and sanitization
- Secure OAuth implementation with token handling
- Admin-only areas with session verification
- HTTPS enforcement
- SSL certificate validation
- Rate limiting capabilities

---

## 🐛 Troubleshooting

### Installation Issues

**"Database connection failed"**
- Check database credentials in install form
- Ensure MySQL server is running
- Verify database user has proper permissions
- Check MySQL port (usually 3306)

**"Forbidden" error after installation**
- Check `.htaccess` file exists and is readable
- Ensure `mod_rewrite` is enabled: `a2enmod rewrite` (Ubuntu/Debian)
- Verify file permissions: `chmod 644 .htaccess`
- Check Apache configuration allows `.htaccess` overrides

### OAuth Issues

**"Failed to get access token"**
- Verify App ID and App Secret are correct
- Check callback URL matches exactly (including https://)
- Ensure your domain is whitelisted in HypeChats
- Check PHP CURL extension is installed
- Verify SSL certificates are valid
- Check HypeChats API status

**"Invalid user data received"**
- Check HypeChats user profile is complete
- Verify access token is still valid
- Check network connectivity
- Review error logs at `/var/log/apache2/error.log`

**"No authorization code received"**
- Verify callback URL matches exactly
- Check browser developer tools for redirect chain
- Clear browser cookies and try again
- Ensure cookies are enabled

### Features Not Working

**Short links not working (404)**
- Check `.htaccess` is properly configured
- Verify `mod_rewrite` is enabled
- Clear browser cache
- Check short code exists in database
- Verify target URL is valid

**Ads not showing**
- Check ads are enabled in admin settings
- Verify `r.php` file exists and is accessible
- Check ad records in database
- Verify ad URL is valid

**Profile images not uploading**
- Check `uploads/profiles/` directory permissions
- Verify file size limits in php.ini
- Check file type restrictions
- Ensure disk space is available

**Dashboard shows "No links yet" for existing user**
- Check database connection
- Verify user_id is correct
- Check short_links table has data
- Review database logs

---

## 📝 Configuration Options

### config.php (Auto-generated)
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hyls_db');
define('DB_USER', 'username');
define('DB_PASS', 'password');
define('SITE_URL', 'https://yourdomain.com');
define('SITE_NAME', 'HYLS');
define('SITE_KEYWORDS', 'link shortener, bio links, url shortener');
define('APP_ID', 'your_app_id');
define('APP_SECRET', 'your_app_secret');
define('GA_TRACKING_ID', 'G-XXXXXXXXXX');
```

### settings table Options
- `site_name` - Website title
- `theme_color` - Primary color (hex)
- `ads_enabled` - Enable/disable ads (1/0)
- `ads_duration` - Ad duration in seconds (default: 5)
- `app_id` - HypeChats App ID
- `app_secret` - HypeChats App Secret
- `ga_tracking_id` - Google Analytics tracking ID
- `site_keywords` - Default meta keywords

---

## 🌟 Customization

### Changing Bio Page Colors
Edit in bio link settings or modify CSS in `bio.php`:
```css
/* Primary color gradient */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

### Adding New Social Networks
1. Edit `biolink.php` - add input field in form
2. Edit `bio.php` - add icon button in social section
3. Update database - add column to `bio_links` table:
   ```sql
   ALTER TABLE bio_links ADD COLUMN platform_name VARCHAR(255);
   ALTER TABLE bio_links ADD COLUMN platform_name_enabled TINYINT DEFAULT 1;
   ```
4. Add Font Awesome icon mapping

### Custom Domain
1. Point domain to server IP via DNS
2. Update `SITE_URL` in `config.php`
3. Update HypeChats callback URL to new domain
4. Update any hardcoded links in database

---

## 📈 Performance Tips

- Enable PHP OpCache in php.ini
- Use CDN for static assets
- Implement Redis caching for database queries
- Enable Gzip compression in Apache
- Minify CSS/JS files
- Optimize database indexes
- Use persistent database connections
- Enable browser caching headers

---

## 🔄 Updates & Maintenance

### Backup
```bash
# Database
mysqldump -u username -p hyls_db > backup_$(date +%Y%m%d).sql

# Files
tar -czf hyls_backup_$(date +%Y%m%d).tar.gz /path/to/hyls/
```

### Restore
```bash
# Database
mysql -u username -p hyls_db < backup.sql

# Files
tar -xzf hyls_backup.tar.gz -C /path/to/restore/
```

### Update System
1. Backup current installation
2. Download latest version
3. Replace files (keep config.php)
4. Check for database changes
5. Test all features
6. Update admin settings if needed

---

## 📞 Support

For issues or questions:
1. Check this README first
2. Review error logs: `/var/log/apache2/error.log`
3. Check PHP error logs: `/var/log/php-errors.log`
4. Contact HypeChats support: https://hypechats.com/support
5. Review database for data inconsistencies

---

## 📜 License

This software is provided as-is for use with HypeChats integration.

---

## 🎉 Credits

- **Built for**: HypeChats Platform
- **Powered by**: HypeChats OAuth
- **Icons**: Font Awesome 6.4.0
- **Design**: Modern gradient UI with animations and responsive layout
- **Database**: PDO for secure database operations

---

## 📋 Changelog

### Version 1.1.0 (Current - January 2026)
**Enhancements:**
- Enhanced bio page design with smooth animations
- Improved HypeChats OAuth signup with better error handling
- Automatic bio link creation on first signup
- Added badges and section headers to bio page
- Improved mobile responsiveness
- Better error logging and debugging
- Open Graph meta tags for social sharing
- Lazy loading for profile images

**Fixes:**
- Fixed HypeChats signup not working
- Fixed OAuth token exchange issues
- Improved error messages
- Better CURL error handling
- Fixed user profile update on re-login

### Version 1.0.0 (Initial Release)
- Link shortening system
- Bio link pages
- HypeChats OAuth
- Admin panel
- Analytics dashboard

---

**Version**: 1.1.0  
**Last Updated**: January 2026  
**Powered by**: [HypeChats](https://hypechats.com)
