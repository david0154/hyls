# 🔗 HYLS - URL Shortener & Bio Link Platform

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)

**HYLS** is a professional URL shortener and bio link platform with user authentication, analytics tracking, and customizable bio pages.

## ✨ Core Features

### 🔐 Authentication
- Email/Password Login & Registration
- Google OAuth Integration
- HypeChats OAuth Integration
- Profile Management
- Last Login Tracking

### 🔗 URL Shortening
- Custom Short URLs
- Random Short Code Generation
- Click Tracking
- Basic Analytics (clicks, timestamps)
- Link Management Dashboard
- Password Protection for Links
- Link Expiration Dates
- Link Banning System

### 👤 Bio Link Pages
- Custom Bio Pages (biolink feature)
- 29 Social Platform Integrations:
  - Facebook, Instagram, Twitter/X, TikTok, YouTube, LinkedIn
  - GitHub, Discord, Twitch, Telegram, WhatsApp, Spotify
  - Reddit, Snapchat, Pinterest, Medium, Patreon
  - OnlyFans, Threads, Bluesky, Mastodon, LINE
  - Cash App, Venmo, PayPal, Substack
  - Website, Email, Phone
- **Link Blocking System** - Enable/disable individual social links
- Profile & Cover Image Upload
- 6-Image Gallery Support
- Theme Color Customization
- View Counter
- Custom Bio Text

### 📊 Analytics
- Click Tracking
- IP Address Logging
- User Agent Detection
- Referrer Tracking
- Country Detection (basic)
- Timestamp Records

### 📢 Advertisement System
- Advertisement Management
- Display Ads on Bio Pages
- Position Control
- Active/Inactive Toggle
- Click-through URLs

### ⚙️ Admin Panel
- User Management
- Link Management
- System Settings
- SMTP Configuration
- Google OAuth Settings
- Advertisement Management

### 🔧 Technical Features
- One-Click Installation Wizard
- Database Migration System
- Auto-Update Database Schema
- SMTP Email Support (PHPMailer ready)
- Responsive Mobile Design
- Modern UI with Animations
- File Upload System
- Session Management

## 📋 Requirements

- **PHP** 8.0 or higher
- **MySQL** 5.7 or higher (or MariaDB 10.2+)
- **Apache/Nginx** with mod_rewrite
- **Git** (for updates)

### Required PHP Extensions
- PDO
- PDO_MySQL
- cURL
- GD (for image processing)
- JSON
- OpenSSL

## 🚀 Installation

### Quick Install

1. **Clone the repository**
```bash
git clone https://github.com/david0154/hyls.git
cd hyls
```

2. **Configure your web server**

For Apache, ensure `.htaccess` is enabled.

For Nginx, add this to your server block:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

3. **Run the installer**

Visit: `https://yourdomain.com/install.php`

The installer will:
- Check system requirements
- Create database configuration
- Set up database tables
- Create admin account
- Configure initial settings

4. **Configure OAuth (Optional)**

Edit `config.php` or use the admin panel:
```php
// Google OAuth
define('GOOGLE_CLIENT_ID', 'your_google_client_id');
define('GOOGLE_CLIENT_SECRET', 'your_google_client_secret');

// HypeChats OAuth
define('APP_ID', 'your_hypechats_app_id');
define('APP_SECRET', 'your_hypechats_app_secret');
```

5. **Security: Delete installer** (after successful installation)
```bash
rm install.php
```

## 🔧 Configuration

### Google OAuth Setup

1. Visit [Google Cloud Console](https://console.cloud.google.com/)
2. Create new project
3. Enable Google+ API
4. Create OAuth 2.0 credentials
5. Add authorized redirect URI: `https://yourdomain.com/google-auth.php`
6. Add credentials to admin panel settings

### HypeChats OAuth Setup

1. Visit HypeChats Developer Portal
2. Create new application
3. Set redirect URI: `https://yourdomain.com/auth.php`
4. Add App ID and Secret to `config.php`

### SMTP Configuration

1. Go to Admin Panel → Settings → SMTP
2. Enter your SMTP details
3. Test with "Send Test Email" button

**Gmail SMTP:**
- Host: `smtp.gmail.com`
- Port: `587`
- Encryption: TLS
- Use App Password (not regular password)

## 🔄 Updates

### Database Migration

When updating, visit: `https://yourdomain.com/install.php?mode=migrate`

This will:
- Add missing database columns
- Create new tables
- Update schema without data loss
- Safe to run multiple times

### Manual Update

```bash
cd /path/to/hyls
git pull origin main
```

Then run migration as shown above.

## 📚 Usage

### Creating Short Links

1. Login to your dashboard
2. Enter long URL
3. (Optional) Customize short code
4. (Optional) Add password protection
5. (Optional) Set expiration date
6. Click "Shorten"

### Creating Bio Pages

1. Go to "Edit Bio" in dashboard
2. Set username (will be your bio URL)
3. Add display name and bio
4. Upload profile picture and cover image
5. Add social media links (up to 29 platforms)
6. Upload gallery images (up to 6)
7. Customize theme color
8. Share your bio: `https://yourdomain.com/bio.php?u=username`

### Managing Links

- **Enable/Disable Social Links**: Use toggle switches in bio editor
- **Ban Links**: Mark links as banned in admin panel
- **View Analytics**: Click "Stats" on any short link
- **Delete Links**: Use delete button in dashboard

## 🗂️ Project Structure

```
hyls/
├── admin/              # Admin panel files
│   ├── index.php       # Admin dashboard
│   ├── users.php       # User management
│   └── settings.php    # System settings
├── assets/             # CSS, JS, images
├── includes/           # Core PHP classes
│   ├── db.php          # Database class
│   └── functions.php   # Helper functions
├── uploads/            # User uploaded files
│   ├── profiles/       # Profile pictures
│   └── bio/            # Bio images & gallery
├── auth.php            # HypeChats OAuth
├── google-auth.php     # Google OAuth
├── login.php           # Login page
├── dashboard.php       # User dashboard
├── bio.php             # Bio link display
├── edit_bio.php        # Bio editor
├── r.php               # URL redirect handler
├── shorten.php         # URL shortening logic
├── install.php         # Installation wizard
└── config.php          # Configuration
```

## 🔒 Security Features

- Password Hashing (bcrypt)
- SQL Injection Protection (Prepared Statements)
- XSS Prevention (Input Sanitization)
- Session Security
- OAuth 2.0 Authentication
- Link Password Protection
- Admin Panel Access Control

## 🎨 Customization

### Changing Theme Colors

Each bio page has customizable theme colors set in the bio editor.

### Custom Branding

Update `config.php`:
```php
define('SITE_NAME', 'Your Brand');
define('SITE_DESCRIPTION', 'Your Description');
define('SITE_KEYWORDS', 'your, keywords');
```

## 🐛 Troubleshooting

### Issue: Bio Links Not Showing
**Solution:** Run database migration at `install.php?mode=migrate`

### Issue: Blocked Links Still Clickable
**Solution:** Ensure `{platform}_enabled` columns exist in `bio_links` table (run migration)

### Issue: Ads Not Displaying
**Solution:** 
1. Check advertisement is marked as "Active" in admin panel
2. Verify `advertisements` table exists
3. Clear browser cache

### Issue: Upload Errors
**Solution:**
- Check folder permissions: `chmod 755 uploads/`
- Verify PHP upload settings in `php.ini`

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**David Studioz**
- GitHub: [@david0154](https://github.com/david0154)
- Project: [HYLS](https://github.com/david0154/hyls)

## 🙏 Acknowledgments

- [PHPMailer](https://github.com/PHPMailer/PHPMailer) - Email sending
- [Font Awesome](https://fontawesome.com/) - Icons
- [Google OAuth](https://developers.google.com/identity/protocols/oauth2) - Authentication

## 📊 Stats

![GitHub stars](https://img.shields.io/github/stars/david0154/hyls?style=social)
![GitHub forks](https://img.shields.io/github/forks/david0154/hyls?style=social)
![GitHub issues](https://img.shields.io/github/issues/david0154/hyls)

## 🔗 Links

- **Report Bug:** [GitHub Issues](https://github.com/david0154/hyls/issues)
- **Request Feature:** [GitHub Issues](https://github.com/david0154/hyls/issues)

---

<p align="center">
  Made with ❤️ by <a href="https://github.com/david0154">David Studioz</a>
</p>