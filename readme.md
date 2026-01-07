# HYLS - HypeLink Shortener System
### Complete PHP Link Shortener & Bio Link Platform with HypeChats OAuth

---

## 🚀 Features

- ✅ **Link Shortening** - Create custom short links with random codes
- ✅ **Bio Link Pages** - Beautiful profile pages with social media links
- ✅ **HypeChats OAuth** - Secure login via HypeChats platform
- ✅ **Analytics Dashboard** - Track clicks, views, and engagement
- ✅ **Advertisement System** - 5-second ads before redirects
- ✅ **Google Analytics** - Deep link tracking integration
- ✅ **User Management** - Full admin panel
- ✅ **Custom Themes** - Personalized colors for bio pages
- ✅ **Social Media Icons** - SVG icons for all major platforms
- ✅ **Responsive Design** - Mobile-friendly UI
- ✅ **One-Click Install** - Easy setup wizard

---

## 📁 File Structure

```
hyls/
├── install.php                 # One-click installer
├── config.php                  # Generated configuration file
├── index.php                   # Homepage/landing page
├── auth.php                    # HypeChats OAuth handler
├── login.php                   # Traditional login page
├── dashboard.php               # User dashboard
├── biolink.php                 # Bio link editor
├── bio.php                     # Bio link display (via .htaccess)
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
    ├── css/
    │   └── style.css
    ├── js/
    │   └── script.js
    └── images/
        └── logo.png
```

---

## 🔧 Installation Steps

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- HypeChats App ID and App Secret

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

---

## 🔐 HypeChats OAuth Setup

### Get Your Credentials
1. Visit: https://hypechats.com/developer
2. Create a new app
3. Set callback URL: `https://yourdomain.com/auth.php`
4. Copy your App ID and App Secret

### Integration Flow
1. User clicks "Sign in with HypeChats"
2. Redirected to: `https://hypechats.com/oauth?app_id={YOUR_APP_ID}`
3. User authorizes the app
4. Redirected back with code: `https://yourdomain.com/auth.php?code=XXX`
5. System exchanges code for access token
6. User data retrieved and account created/updated

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
- `access_token` - HypeChats access token
- `is_admin` - Admin flag
- `created_at` - Registration timestamp

### short_links
- `id` - Primary key
- `user_id` - Foreign key to users
- `short_code` - Unique short code (e.g., "abc123")
- `original_url` - Target URL
- `title` - Optional link title
- `clicks` - Click counter
- `created_at` - Creation timestamp

### bio_links
- `id` - Primary key
- `user_id` - Foreign key to users
- `username` - URL-safe username
- `display_name` - Display name
- `bio` - Bio text
- `profile_image` - Profile picture path
- `theme_color` - Hex color code
- `facebook`, `instagram`, `twitter`, etc. - Social links
- `email`, `phone` - Contact info
- `views` - View counter
- `created_at` - Creation timestamp

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

## 🎨 Features Explanation

### Link Shortening
- Random 6-character codes (letters + numbers)
- Custom code support (optional)
- Format: `yourdomain.com/{code}`
- Example: `yourdomain.com/abc123`

### Bio Links
- URL: `yourdomain.com/bio/{username}`
- Custom profile picture upload
- Theme color customization
- Social media icon integration:
  - Facebook, Instagram, Twitter, LinkedIn
  - YouTube, TikTok, GitHub, Website
- Contact buttons (Email, Phone)
- View counter

### Advertisement System
- 5-second countdown before redirect
- HypeChats promotion
- Progress bar indicator
- Skip button (appears after 3 seconds)
- Can be disabled in admin settings

### Google Analytics
- Track all link clicks
- Monitor bio page views
- Custom event tracking
- Deep link attribution

### Admin Panel
- User management
- Link moderation
- System analytics
- Settings configuration
- Theme customization

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

---

## 🎯 Usage Guide

### For Users

#### Creating Short Links
1. Login to dashboard
2. Click "Create Short Link"
3. Enter original URL
4. (Optional) Add custom code
5. (Optional) Add title
6. Click "Create Link"
7. Copy and share!

#### Creating Bio Page
1. Go to Dashboard
2. Click "Edit Bio Link"
3. Upload profile picture
4. Fill in display name and bio
5. Add social media links
6. Choose theme color
7. Add contact information
8. Click "Save Bio Link"
9. Share: `yourdomain.com/bio/yourusername`

### For Admins

#### Managing Users
- View all registered users
- Check user statistics
- Promote/demote admin status
- View user links and bio pages

#### Managing Links
- View all shortened links
- Monitor click statistics
- Delete inappropriate links
- Export analytics data

#### System Settings
- Enable/disable ads
- Set ad duration (seconds)
- Configure Google Analytics
- Update HypeChats credentials
- Customize branding

---

## 🔒 Security Features

- Password hashing (bcrypt)
- SQL injection protection (PDO prepared statements)
- XSS prevention (htmlspecialchars)
- CSRF protection (session tokens)
- Input validation and sanitization
- Secure OAuth implementation
- Admin-only areas

---

## 🐛 Troubleshooting

### Installation Issues

**"Database connection failed"**
- Check database credentials in install form
- Ensure MySQL server is running
- Verify database user has proper permissions

**"Forbidden" error after installation**
- Check `.htaccess` file exists
- Ensure `mod_rewrite` is enabled in Apache
- Verify file permissions

### OAuth Issues

**"Failed to get access token"**
- Verify App ID and App Secret are correct
- Check callback URL matches in HypeChats app settings
- Ensure your domain is whitelisted

### Short Links Not Working

**404 error on short links**
- Check `.htaccess` is properly configured
- Verify `mod_rewrite` is enabled
- Clear browser cache

**Ads not showing**
- Check ads are enabled in admin settings
- Verify `r.php` file exists and is accessible

---

## 📝 Configuration Options

### config.php (Auto-generated)
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hyls_db');
define('DB_USER', 'username');
define('DB_PASS', 'password');
define('SITE_URL', 'https://yourdomain.com');
define('APP_ID', 'your_app_id');
define('APP_SECRET', 'your_app_secret');
define('GA_TRACKING_ID', 'G-XXXXXXXXXX');
```

### settings table
- `site_name` - Website title
- `theme_color` - Primary color (hex)
- `ads_enabled` - Enable/disable ads (1/0)
- `ads_duration` - Ad duration in seconds
- `app_id` - HypeChats App ID
- `app_secret` - HypeChats App Secret
- `ga_tracking_id` - Google Analytics tracking ID

---

## 🌟 Customization

### Changing Colors
Edit in bio link settings or modify CSS:
```css
/* Primary color */
.btn-primary { background: #6366f1; }

/* Gradient */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

### Adding Social Networks
1. Edit `biolink.php` - add input field
2. Edit `bio.php` - add icon/button
3. Update database - add column to `bio_links` table

### Custom Domain
1. Point domain to server IP
2. Update `SITE_URL` in config.php
3. Update HypeChats callback URL

---

## 📈 Performance Tips

- Enable PHP OpCache
- Use CDN for static assets
- Implement Redis caching
- Optimize database queries
- Enable Gzip compression
- Minify CSS/JS files

---

## 🔄 Updates & Maintenance

### Backup
```bash
# Database
mysqldump -u username -p hyls_db > backup.sql

# Files
tar -czf hyls_backup.tar.gz /path/to/hyls/
```

### Restore
```bash
# Database
mysql -u username -p hyls_db < backup.sql

# Files
tar -xzf hyls_backup.tar.gz -C /path/to/restore/
```

---

## 📞 Support

For issues or questions:
1. Check this README first
2. Review error logs: `/var/log/apache2/error.log`
3. Contact HypeChats support: https://hypechats.com/support

---

## 📜 License

This software is provided as-is for use with HypeChats integration.

---

## 🎉 Credits

- **Built for**: HypeChats Platform
- **Powered by**: HypeChats OAuth
- **Icons**: Inline SVG social media icons
- **Design**: Modern gradient UI with responsive layout

---

**Version**: 1.0.0  
**Last Updated**: January 2025  
**Powered by**: [HypeChats](https://hypechats.com)