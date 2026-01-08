# 🔗 HYLS - Modern URL Shortener & Bio Link Platform

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)
![Status](https://img.shields.io/badge/status-active-success.svg)

**HYLS** is a powerful, modern URL shortener with bio link pages, video embeds, OAuth integration, QR codes, and comprehensive analytics tracking.

---

## ✨ Complete Features

### 🔐 Authentication & Security
- **HypeChats OAuth** - Sign in with HypeChats account
- **Google OAuth 2.0** - Sign in with Google account
- **Email/Password Login** - Traditional authentication with bcrypt hashing
- **Profile Management** - Update profile pictures, names, and settings
- **Session Security** - Secure session handling and timeout
- **SQL Injection Protection** - All queries use prepared statements
- **XSS Prevention** - Input sanitization on all user data

### 🔗 URL Shortening
- **Custom Short URLs** - Create memorable branded short links
- **Random Short Codes** - Auto-generate unique 6-character codes
- **Link Editing** - Update destination URLs without changing short code
- **Link Deletion** - Remove unwanted links instantly
- **Link Banning** - Admin can ban/block malicious links with reasons
- **Bulk Operations** - Manage multiple links at once
- **Link Categories** - Organize links (coming soon)


### 👤 Bio Link Pages (Linktree Alternative)
- **Custom Bio Pages** - Create personalized landing pages at `/bio.php?u=username`
- **Profile & Cover Images** - Upload and crop custom images (12MB max)
- **Video Background** - Set video as cover background
- **29+ Social Platform Links** - Connect all your social media:
  - Facebook, Instagram, Twitter/X, LinkedIn, YouTube
  - TikTok, Snapchat, Pinterest, Reddit, Tumblr
  - WhatsApp, Telegram, Discord, Skype, Slack
  - GitHub, GitLab, Dribbble, Behance, Medium
  - Spotify, SoundCloud, Apple Music, Twitch
  - Steam, Xbox, PlayStation, Patreon, Ko-fi
  - And 4 more custom platforms!
- **Video Embeds** - Embed videos from 6 platforms:
  - 👍 **YouTube** - Full support with thumbnails
  - 👍 **Facebook Videos** - Native embed support
  - 👍 **Instagram Reels/Videos** - Direct integration
  - 👍 **TikTok** - Embed TikTok videos
  - 👍 **Vimeo** - Professional video hosting
  - 👍 **Dailymotion** - Alternative video platform
- **6-Image Gallery** - Showcase your portfolio or products
- **Theme Customization** - Choose custom brand colors (hex/rgb)
- **Contact Information** - Email and phone with show/hide toggle
- **Bio Description** - Rich text bio with line breaks
- **View Counter** - Track total page visits
- **Link Toggle** - Enable/disable individual social links
- **Display Order** - Reorder social links (drag & drop coming)
- **Mobile Optimized** - Perfect responsive design
- **SEO Friendly** - Meta tags and Open Graph support


### 📊 Analytics & Tracking
- **Real-time Click Tracking** - Monitor link performance live
- **Click Statistics** - Total clicks per link
- **IP Address Tracking** - Log visitor IPs
- **Referrer Tracking** - See where traffic comes from
- **User Agent Logging** - Track browsers and devices
- **Geolocation** - Country/city tracking (with IP database)
- **Time Series Data** - View clicks over time
- **Export Reports** - Download analytics as CSV
- **Link Performance** - Top performing links dashboard
- **Unique vs Total Clicks** - Track unique visitors


### 📹 Video Embed System
- **Multiple Platforms** - Support for 6 major video platforms
- **Auto Thumbnail** - Automatic thumbnail extraction
- **Responsive Embeds** - Adapts to all screen sizes
- **View Counter** - Track video views on bio page
- **Platform Icons** - Display source platform badges
- **Title & Description** - Add context to embedded videos
- **Display Order** - Control video sequence
- **Enable/Disable** - Show/hide individual videos

### 💰 Monetization System
- **Advertisement System** - Display ads on bio pages and redirects
- **Ad Management Panel** - Create and manage advertisements
- **Ad Analytics** - Track ad clicks and impressions
- **Position Control** - Set ad display order
- **Image Ads** - Upload custom ad banners
- **CTA Buttons** - Call-to-action button customization
- **Enable/Disable Ads** - Toggle ads on/off
- **Ad Scheduling** - Set start/end dates (coming soon)
- **Revenue Tracking** - Monitor monetization (coming soon)

### ⚙️ Admin Panel
- **Comprehensive Dashboard** - Statistics overview
- **User Management** - View, edit, delete users
- **Link Management** - Moderate all shortened URLs
- **Ban System** - Block problematic links with reasons
- **User Roles** - Admin and regular user roles
- **System Settings** - Configure site-wide options
- **SMTP Configuration** - Email server settings
- **OAuth Settings** - Configure Google & HypeChats OAuth
- **Advertisement Manager** - Create and manage ads
- **Analytics Dashboard** - View detailed statistics
- **Database Backup** - Manual backup functionality


### 🚀 Advanced Features
- **One-Click Updates** - Auto-update from GitHub (requires Git)
- **Database Migration System** - Automatic schema updates
- **Installation Wizard** - Easy setup with step-by-step guide
- **Mobile Responsive** - Optimized for all devices
- **Modern UI** - Clean, gradient-based design with animations
- **Dark Mode Ready** - Prepare for dark theme (coming soon)
- **REST API** - API endpoints for link creation (coming soon)
- **Webhooks** - Event notifications (coming soon)
- **Multi-language** - i18n support (coming soon)
- **Custom Domains** - Use your own domains (coming soon)
- **Team Collaboration** - Share links in workspace (coming soon)
- **Link Folders** - Organize links in folders (coming soon)

### 📧 Email System
- **SMTP Integration** - Full email sending capability
- **Welcome Emails** - Auto-send to new users
- **Password Reset Emails** - Secure reset links
- **Notification Emails** - Link expiration alerts
- **HTML Templates** - Beautiful email designs
- **Test Email Function** - Verify SMTP settings
- **Popular SMTP Support** - Gmail, SendGrid, Mailgun, etc.

### 📝 Content Management
- **Bio Content Editor** - Rich text editing
- **Image Upload** - Drag & drop image uploads
- **Image Cropping** - Built-in crop tool for profile pictures
- **Gallery Management** - Upload/delete gallery images
- **Link Organization** - Sort and manage social links
- **Draft Mode** - Save bio as draft before publishing
- **Preview Mode** - Preview bio page before going live

---

## 📊 Statistics

- ✅ **29+ Social Platforms** supported
- ✅ **6 Video Embed Platforms** integrated
- ✅ **2 OAuth Providers** (Google + HypeChats)
- ✅ **Unlimited Links** - No restrictions
- ✅ **Unlimited Bio Pages** - One per user
- ✅ **12MB Image Upload** - High quality support
- ✅ **6 Gallery Images** - Perfect portfolio showcase
- ✅ **100% Responsive** - Mobile-first design

---

## 💻 Requirements

### Server Requirements
- **PHP** 7.4 or higher (PHP 8.x recommended)
- **MySQL** 5.7 or higher (or MariaDB 10.2+)
- **Apache/Nginx** with mod_rewrite or equivalent
- **Git** (optional, for auto-updates)
- **SSL Certificate** (recommended for OAuth)

### PHP Extensions Required
- PDO
- PDO_MySQL
- cURL
- JSON
- OpenSSL
- GD (for image processing)
- mbstring
- fileinfo

### Recommended Server Specs
- **RAM:** 512MB minimum, 1GB+ recommended
- **Storage:** 1GB minimum (grows with uploads)
- **Bandwidth:** Unlimited or generous limit

---

## 🚀 Installation

### Quick Install (5 Minutes)

#### Step 1: Clone Repository
```bash
git clone https://github.com/david0154/hyls.git
cd hyls
```

#### Step 2: Set Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/bio/
chmod 755 uploads/bio/gallery/
chmod 755 uploads/qr/
```

#### Step 3: Configure Web Server

**For Apache:** Ensure `.htaccess` is enabled in your virtual host:
```apache
<Directory "/path/to/hyls">
    AllowOverride All
    Require all granted
</Directory>
```

**For Nginx:** Add to your server block:
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/hyls;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

#### Step 4: Run Installer

Visit: `https://yourdomain.com/install.php`

The installer will:
- ✅ Check system requirements
- ✅ Test PHP extensions
- ✅ Create database configuration
- ✅ Set up all database tables
- ✅ Create admin account
- ✅ Configure initial settings
- ✅ Generate security keys

#### Step 5: Configure OAuth (Optional)

Edit `config.php` after installation:

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hyls_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// Site Configuration
define('SITE_URL', 'https://yourdomain.com');
define('SITE_NAME', 'HYLS');

// Google OAuth
define('GOOGLE_CLIENT_ID', 'your_google_client_id.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'your_google_client_secret');
define('GOOGLE_REDIRECT_URI', 'https://yourdomain.com/google-auth.php');

// HypeChats OAuth
define('APP_ID', 'your_hypechats_app_id');
define('APP_SECRET', 'your_hypechats_app_secret');
define('REDIRECT_URI', 'https://yourdomain.com/auth.php');

// Email Configuration (Optional)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your@email.com');
define('SMTP_PASSWORD', 'your_app_password');
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', 'HYLS');
?>
```

#### Step 6: Secure Installation

After successful installation:
```bash
# Delete or move the installer
rm install.php
# Or move to a secure location
mv install.php install.php.bak
```

---

## 🔧 Configuration

### Google OAuth Setup

1. **Visit** [Google Cloud Console](https://console.cloud.google.com/)
2. **Create Project** or select existing one
3. **Enable APIs:**
   - Google+ API
   - Google People API
4. **Create Credentials:**
   - OAuth 2.0 Client ID
   - Application Type: Web Application
5. **Add Authorized Redirect URIs:**
   ```
   https://yourdomain.com/google-auth.php
   ```
6. **Copy Credentials** to `config.php`:
   - Client ID
   - Client Secret

### HypeChats OAuth Setup

1. **Visit** [HypeChats Developer Portal](https://hypechats.com/developers)
2. **Login/Sign up** at [HypeChats](https://hypechats.com/)
3. **Create New Application**
4. **Set Redirect URI:**
   ```
   https://yourdomain.com/auth.php
   ```
5. **Copy to** `config.php`:
   - App ID
   - App Secret

### SMTP Configuration

#### Option 1: Admin Panel (Recommended)
1. Login as admin
2. Go to **Admin Panel** → **Settings**
3. Enter SMTP details
4. Click **"Send Test Email"** to verify
5. Save settings

#### Option 2: Direct Configuration
Edit `config.php` with your SMTP details.

**Popular SMTP Providers:**

**Gmail:**
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your@gmail.com');
define('SMTP_PASSWORD', 'your_app_password'); // Use App Password, not regular password
```

**SendGrid:**
```php
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'apikey');
define('SMTP_PASSWORD', 'your_sendgrid_api_key');
```

**Mailgun:**
```php
define('SMTP_HOST', 'smtp.mailgun.org');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'postmaster@yourdomain.com');
define('SMTP_PASSWORD', 'your_mailgun_password');
```

**Microsoft 365:**
```php
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your@company.com');
define('SMTP_PASSWORD', 'your_password');
```

---

## 🔄 Updates

### Method 1: One-Click Update (Git Required)

1. Visit: `https://yourdomain.com/update.php`
2. Click **"Update Now"**
3. System will:
   - ✅ Fetch latest changes from GitHub
   - ✅ Run database migrations
   - ✅ Update all files
   - ✅ Clear cache

### Method 2: Manual Update

```bash
cd /path/to/hyls
git pull origin main
```

Then visit: `https://yourdomain.com/install.php?mode=migrate`

### Method 3: FTP/cPanel Update

1. Download latest release from GitHub
2. Extract files
3. Upload to server (overwrite existing)
4. Visit: `https://yourdomain.com/install.php?mode=migrate`

---

## 📚 Usage Guide

### Creating Short Links

1. Login to your dashboard
2. Enter your **long URL**
3. **(Optional)** Customize short code
4. **(Optional)** Add password protection
5. **(Optional)** Set expiration date
6. Click **"Shorten"**
7. Copy and share your short link!

**Short link format:** `yourdomain.com/abc123`

### Creating Bio Link Page

1. Go to **Dashboard** → **Bio Link**
2. **Basic Information:**
   - Display name
   - Bio description (supports line breaks)
   - Contact email
   - Phone number
   - Toggle contact visibility
3. **Images:**
   - Upload profile picture (crop to circle)
   - Upload cover image (16:9 recommended)
   - Or set video as background
4. **Social Links:**
   - Add links for 29+ platforms
   - Enable/disable individual links
   - Links appear with platform icons
5. **Gallery:**
   - Upload up to 6 images
   - Perfect for portfolio showcase
   - Images display in grid layout
6. **Videos:**
   - Add YouTube, Facebook, Instagram, TikTok, Vimeo, Dailymotion
   - Videos embed with play buttons
   - Thumbnails auto-generated
7. **Theme:**
   - Choose brand color (hex or RGB)
   - Applies to buttons and links
8. Click **"Save Changes"**
9. View your bio: `yourdomain.com/bio.php?u=your_username`

### Managing Advertisements

1. **Admin Panel** → **Advertisements**
2. Click **"Create New Ad"**
3. Fill in details:
   - Ad title
   - Description
   - Target URL
   - Upload ad image
   - CTA button text
4. Set display order
5. Enable ad
6. Ads appear on:
   - Bio pages
   - Redirect pages (optional)

### Viewing Analytics

1. **Dashboard** → **Your Links**
2. Click **"View Stats"** on any link
3. See:
   - Total clicks
   - Click timeline
   - Referrer sources
   - Browser/device breakdown
   - Geographic data

### Generating QR Codes

1. Go to any shortened link
2. Click **"Generate QR Code"**
3. QR code displays instantly
4. Right-click to save
5. Use for:
   - Print materials
   - Business cards
   - Posters and flyers
   - Product packaging

---

## 🗂️ Project Structure

```
hyls/
├── 📁 admin/                    # Admin Panel
│   ├── index.php               # Dashboard
│   ├── users.php               # User management
│   ├── links.php               # Link management
│   ├── settings.php            # Settings
│   ├── advertisements.php      # Ad management
│   └── analytics.php           # Analytics
│
├── 📁 assets/                   # Static files
│   ├── css/style.css           # Stylesheets
│   ├── js/main.js              # JavaScript
│   ├── images/                 # Images
│   └── favicon.ico             # Favicon
│
├── 📁 docs/                     # Documentation
│   ├── API.md
│   ├── INSTALLATION.md
│   └── FEATURES.md
│
├── 📁 includes/                 # Core classes
│   ├── db.php                  # Database
│   ├── functions.php           # Helpers
│   ├── image_processor.php     # Images
│   ├── mailer.php              # Email
│   └── video_embed.php         # Videos
│
├── 📁 install/                  # Installation
│   ├── install.php
│   └── database.sql
│
├── 📁 uploads/                  # User uploads
│   ├── bio/
│   │   └── gallery/
│   └── qr/
│
├── 📄 .htaccess                 # Apache config
├── 📄 .gitignore               # Git ignore
├── 📄 ad-page.php              # Ad page
├── 📄 auth.php                 # HypeChats OAuth
├── 📄 bio.php                  # Public bio page
├── 📄 biolink.php              # Bio management
├── 📄 config.sample.php        # Config template
├── 📄 dashboard.php            # User dashboard
├── 📄 delete_link.php          # Delete handler
├── 📄 edit_bio.php             # Bio editor
├── 📄 google-auth.php          # Google OAuth
├── 📄 index.php                # Landing page
├── 📄 login.php                # Login page
├── 📄 logout.php               # Logout
├── 📄 r.php                    # Redirect
├── 📄 shorten.php              # Shorten API
├── 📄 update.php               # Auto-update
├── 📄 LICENSE                  # MIT License
├── 📄 README.md                # This file
└── 📄 UPGRADE_INSTRUCTIONS.md  # Upgrade guide
```

---

## 🔒 Security Features

- ✅ **Password Hashing** - bcrypt with salt for user passwords
- ✅ **SQL Injection Protection** - PDO prepared statements everywhere
- ✅ **XSS Prevention** - htmlspecialchars() on all outputs
- ✅ **CSRF Protection** - Token-based form validation
- ✅ **Session Security** - Secure session handling with timeout
- ✅ **OAuth 2.0** - Industry-standard authentication
- ✅ **Link Password Protection** - Encrypted passwords for sensitive links
- ✅ **File Upload Validation** - Strict image type checking
- ✅ **SQL Timeout Protection** - Query timeout limits
- ✅ **Brute Force Protection** - Login attempt limiting
- ✅ **HTTPS Enforcement** - Redirect to secure connection
- ✅ **Input Sanitization** - Filter all user inputs
- ✅ **Directory Protection** - .htaccess security rules

---

## 🐛 Troubleshooting

### Installation Issues

**Error:** `Database connection failed`

**Solution:**
- Verify database credentials in `config.php`
- Ensure MySQL service is running
- Check if user has proper permissions
- Test connection:
```bash
mysql -u username -p database_name
```

### Google OAuth Issues

**Error:** `Redirect URI mismatch`

**Solution:**
Ensure redirect URI in Google Console matches EXACTLY:
```
https://yourdomain.com/google-auth.php
```
Note: No trailing slash, must be HTTPS in production

**Error:** `Invalid client`

**Solution:**
- Check Client ID is correct
- Verify Client Secret has no extra spaces
- Ensure OAuth consent screen is published

### Upload Issues

**Error:** `Failed to upload image`

**Solution:**
Check permissions:
```bash
chmod 755 uploads/
chmod 755 uploads/bio/
chmod 755 uploads/bio/gallery/
chmod 755 uploads/qr/
```

Check ownership:
```bash
chown www-data:www-data uploads/ -R
# Or for Apache:
chown apache:apache uploads/ -R
```

**Error:** `File too large`

**Solution:**
Increase PHP limits in `php.ini`:
```ini
upload_max_filesize = 12M
post_max_size = 12M
memory_limit = 128M
```

### Update Issues

**Error:** `Git not found`

**Solution:**
Install Git:
```bash
# Ubuntu/Debian
sudo apt-get install git

# CentOS/RHEL
sudo yum install git

# Or use manual update method
```

**Error:** `Permission denied during update`

**Solution:**
```bash
chown www-data:www-data /path/to/hyls -R
chmod 755 /path/to/hyls -R
```

### Email Issues

**Error:** `SMTP connection failed`

**Solution:**
- Verify SMTP credentials
- Check firewall allows port 587 (or 465)
- For Gmail: Enable "Less secure app access" or use App Password
- Test with telnet:
```bash
telnet smtp.gmail.com 587
```

### Video Embed Issues

**Error:** `Video not displaying`

**Solution:**
- Ensure video URL is public
- Check video platform allows embedding
- Verify video ID is extracted correctly
- Test embed code in standalone HTML

---

## 🚀 Performance Optimization

### Database Optimization

```sql
-- Add indexes for better performance
CREATE INDEX idx_short_code ON links(short_code);
CREATE INDEX idx_user_id ON links(user_id);
CREATE INDEX idx_created_at ON links(created_at);
```

### Caching

```php
// Enable OPcache in php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

### CDN Integration

Use CDN for static assets:
```html
<!-- Replace local assets with CDN -->
<link rel="stylesheet" href="https://cdn.yourdomain.com/css/style.css">
```

### Image Optimization

- Compress images before upload
- Use WebP format where possible
- Implement lazy loading

---

## 🤝 Contributing

Contributions are welcome! Here's how:

### Reporting Bugs

1. Check if bug already exists in [Issues](https://github.com/david0154/hyls/issues)
2. Create new issue with:
   - Clear description
   - Steps to reproduce
   - Expected vs actual behavior
   - Screenshots if applicable
   - Your environment (PHP version, OS, etc.)

### Suggesting Features

1. Open a [Feature Request](https://github.com/david0154/hyls/issues/new)
2. Describe:
   - Use case
   - Expected behavior
   - Mockups if available

### Pull Requests

1. **Fork** the repository
2. **Create** feature branch:
   ```bash
   git checkout -b feature/AmazingFeature
   ```
3. **Commit** your changes:
   ```bash
   git commit -m 'Add AmazingFeature'
   ```
4. **Push** to branch:
   ```bash
   git push origin feature/AmazingFeature
   ```
5. **Open** Pull Request

### Coding Standards

- Follow PSR-12 coding standard
- Comment complex logic
- Write descriptive commit messages
- Test before submitting
- Update documentation if needed

---

## 📝 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

**What this means:**
- ✅ Commercial use allowed
- ✅ Modification allowed
- ✅ Distribution allowed
- ✅ Private use allowed
- ⚠️ License and copyright notice required
- ❌ No warranty provided
- ❌ No liability accepted

---

## 👨‍💻 Author

**David Studioz**

- 🐛 GitHub: [@david0154](https://github.com/david0154)
- 📧 Email: Contact via GitHub
- 🌎 Website: [David Studioz](https://github.com/david0154)
- 💻 Project: [HYLS](https://github.com/david0154/hyls)

---

## 🙏 Acknowledgments

### Technologies Used
- **PHP** - Server-side scripting
- **MySQL** - Database management
- **JavaScript** - Client-side interactivity
- **Font Awesome** - Icon library
- **Google APIs** - OAuth authentication
- **HypeChats** - Social OAuth platform

### Inspiration
- Linktree - Bio link concept
- Bitly - URL shortening
- TinyURL - Simple short links

### Special Thanks
- All contributors and testers
- Open source community
- Users providing feedback

---

## 📊 Repository Stats

![GitHub stars](https://img.shields.io/github/stars/david0154/hyls?style=social)
![GitHub forks](https://img.shields.io/github/forks/david0154/hyls?style=social)
![GitHub watchers](https://img.shields.io/github/watchers/david0154/hyls?style=social)
![GitHub issues](https://img.shields.io/github/issues/david0154/hyls)
![GitHub pull requests](https://img.shields.io/github/issues-pr/david0154/hyls)
![GitHub last commit](https://img.shields.io/github/last-commit/david0154/hyls)
![GitHub repo size](https://img.shields.io/github/repo-size/david0154/hyls)
![GitHub language count](https://img.shields.io/github/languages/count/david0154/hyls)
![GitHub top language](https://img.shields.io/github/languages/top/david0154/hyls)

---

## 🔗 Important Links

- 📚 **Documentation:** [GitHub Wiki](https://github.com/david0154/hyls/wiki)
- 🐛 **Report Bug:** [GitHub Issues](https://github.com/david0154/hyls/issues)
- ✨ **Request Feature:** [GitHub Issues](https://github.com/david0154/hyls/issues/new)
- 💬 **Discussions:** [GitHub Discussions](https://github.com/david0154/hyls/discussions)
- 🔄 **Changelog:** [Releases](https://github.com/david0154/hyls/releases)
- 🛡️ **Security:** Report via GitHub Security tab

---

## 🔮 Roadmap

### Version 2.0 (Coming Soon)
- [ ] REST API with authentication
- [ ] Webhooks for events
- [ ] Custom domains support
- [ ] Team collaboration features
- [ ] Link folders and categories
- [ ] Advanced analytics dashboard
- [ ] Dark mode
- [ ] Multi-language support
- [ ] Mobile apps (iOS & Android)
- [ ] Browser extensions

### Version 2.1
- [ ] A/B testing for links
- [ ] Link retargeting pixels
- [ ] Conversion tracking
- [ ] Branded short domains
- [ ] Link scheduling
- [ ] Bulk link import/export
- [ ] Two-factor authentication
- [ ] API rate limiting

---

## ❓ FAQ

### General Questions

**Q: Is HYLS free?**  
A: Yes! HYLS is 100% free and open source under MIT License.

**Q: Can I use HYLS commercially?**  
A: Absolutely! Use it for personal or commercial projects.

**Q: Do you offer hosting?**  
A: No, but you can deploy on any PHP hosting (shared/VPS/cloud).

### Technical Questions

**Q: What's the maximum file size for uploads?**  
A: Default is 12MB, configurable in PHP settings.

**Q: How many links can I create?**  
A: Unlimited! No restrictions on link creation.

**Q: Does it support custom domains?**  
A: Not yet, planned for version 2.0.

**Q: Can I self-host?**  
A: Yes! That's the whole point. Full control on your server.

### Feature Questions

**Q: How many social platforms are supported?**  
A: 29+ major social platforms plus custom links.

**Q: Can I embed videos from any platform?**  
A: Currently supports 6 platforms: YouTube, Facebook, Instagram, TikTok, Vimeo, Dailymotion.

**Q: Is there an API?**  
A: API is coming in version 2.0.

---

## 📢 Stay Updated

- ⭐ **Star this repo** to get notifications
- 👁️ **Watch** for new releases
- 👨‍💻 **Follow** [@david0154](https://github.com/david0154)

---

## 🎉 Support the Project

If you find HYLS useful, consider:

- ⭐ Starring the repository
- 🐛 Reporting bugs
- 💡 Suggesting features
- 👥 Sharing with others
- 📝 Writing tutorials
- 🚀 Contributing code

---

<p align="center">
  <b>Made with ❤️ by <a href="https://github.com/david0154">David</a></b>
</p>

<p align="center">
  <a href="#-hyls---modern-url-shortener--bio-link-platform">Back to Top ⬆️</a>
</p>

<p align="center">
  <sub>Last Updated: January 8, 2026 | Version 1.5</sub>
</p>
