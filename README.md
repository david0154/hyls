# 🔗 HYLS - Modern URL Shortener

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)

**HYLS** is a powerful, modern, and feature-rich URL shortener with OAuth integration, analytics, and QR code generation.

## ✨ Features

### 🔐 Authentication & OAuth
- **Google OAuth** - Sign in with Google account
- **HypeChats OAuth** - Sign in with HypeChats account
- **Email/Password Login** - Traditional authentication
- **Profile Management** - Update profile pictures, settings
- **Last Login Tracking** - Monitor user activity

### 🔗 URL Management
- **Custom Short URLs** - Create memorable short links
- **Random Short Codes** - Auto-generate unique codes
- **URL Analytics** - Track clicks, referrers, devices, locations
- **QR Code Generation** - Automatic QR codes for all links
- **Link Expiration** - Set expiry dates for temporary links
- **Password Protection** - Secure links with passwords
- **Link Categories** - Organize links with tags

### 📊 Analytics Dashboard
- **Click Tracking** - Real-time click statistics
- **Geographic Data** - Country-based analytics
- **Device Detection** - Desktop, mobile, tablet tracking
- **Browser Analytics** - Track browser usage
- **Referrer Tracking** - See where traffic comes from
- **Time-based Stats** - Hourly, daily, weekly reports

### ⚙️ Admin Panel
- **User Management** - Create, edit, delete users
- **Link Management** - Moderate all shortened URLs
- **System Settings** - Configure site-wide options
- **SMTP Configuration** - Email settings with test functionality
- **Google OAuth Settings** - Configure Google Sign-In
- **Analytics Overview** - System-wide statistics
- **Database Backup** - One-click backup and restore

### 🚀 Advanced Features
- **One-Click Updates** - Auto-update from GitHub
- **Database Migration** - Automatic schema updates
- **QR Code API** - Generate QR codes programmatically
- **Custom Domains** - Support for multiple domains
- **Link Redirection** - 301/302 redirects
- **API Access** - RESTful API for integrations
- **Dark Mode UI** - Modern, responsive design
- **Mobile Responsive** - Works on all devices

### 📧 Email Features
- **Welcome Emails** - Automated for new users
- **SMTP Support** - Full SMTP configuration
- **PHPMailer Integration** - Reliable email delivery
- **Email Verification** - Optional email confirmation
- **Fallback to mail()** - Works without SMTP

## 📋 Requirements

- **PHP** 8.0 or higher
- **MySQL** 5.7 or higher (or MariaDB 10.2+)
- **Apache/Nginx** with mod_rewrite
- **Git** (for updates)
- **Composer** (optional, for PHPMailer)

### PHP Extensions
- PDO
- PDO_MySQL
- cURL
- GD (for QR codes)
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

Visit: `https://yourdomain.com/install/`

The installer will:
- ✅ Check system requirements
- ✅ Create database configuration
- ✅ Set up database tables
- ✅ Create admin account
- ✅ Configure initial settings

4. **Configure OAuth (Optional)**

Edit `config.php`:
```php
// Google OAuth
define('GOOGLE_CLIENT_ID', 'your_google_client_id');
define('GOOGLE_CLIENT_SECRET', 'your_google_client_secret');

// HypeChats OAuth
define('APP_ID', 'your_hypechats_app_id');
define('APP_SECRET', 'your_hypechats_app_secret');
```

5. **Delete install folder** (Security)
```bash
rm -rf install/
```

## 🔧 Configuration

### Google OAuth Setup

1. Visit [Google Cloud Console](https://console.cloud.google.com/)
2. Create new project or select existing
3. Enable Google+ API
4. Create OAuth 2.0 credentials
5. Add authorized redirect URI: `https://yourdomain.com/google-auth.php`
6. Copy Client ID and Secret to admin panel

### HypeChats OAuth Setup

1. Visit [HypeChats Developer Portal](https://hypechats.com/developers)
2. Create new application
3. Set redirect URI: `https://yourdomain.com/auth.php`
4. Copy App ID and App Secret to `config.php`

### SMTP Configuration

1. Go to Admin Panel → Settings → SMTP
2. Enter your SMTP details
3. Test with "Send Test Email" button

**Popular SMTP Providers:**

**Gmail:**
- Host: `smtp.gmail.com`
- Port: `587`
- Encryption: TLS
- Use App Password (not regular password)

**SendGrid:**
- Host: `smtp.sendgrid.net`
- Port: `587`
- Username: `apikey`
- Password: Your API key

## 🔄 Updates

### One-Click Update

Visit: `https://yourdomain.com/update.php`

This will:
- ✅ Fetch latest changes from GitHub
- ✅ Run database migrations
- ✅ Update all files
- ✅ Show detailed change log

### Manual Update

```bash
cd /path/to/hyls
git pull origin main
```

Then visit: `https://yourdomain.com/install/migrate.php`

## 📚 Usage

### Creating Short Links

1. **Dashboard** → Enter long URL
2. **(Optional)** Customize short code
3. **(Optional)** Add password protection
4. **(Optional)** Set expiration date
5. Click **"Shorten"**

### Viewing Analytics

1. Go to **Dashboard**
2. Click **"Stats"** on any link
3. View detailed analytics:
   - Total clicks
   - Geographic distribution
   - Device breakdown
   - Browser statistics
   - Referrer sources

### QR Code Generation

QR codes are automatically generated for all links.

**Download:** Click QR icon on any link

**API Endpoint:**
```
GET /qr.php?url=YOUR_SHORT_CODE
```

## 🛠️ API Documentation

### Shorten URL

```bash
POST /api/shorten
Content-Type: application/json

{
  "url": "https://example.com/very/long/url",
  "custom": "mylink",
  "password": "secret123"
}
```

**Response:**
```json
{
  "status": "success",
  "short_url": "https://yourdomain.com/mylink",
  "qr_code": "https://yourdomain.com/qr.php?url=mylink"
}
```

### Get Analytics

```bash
GET /api/stats?url=mylink
```

**Response:**
```json
{
  "status": "success",
  "clicks": 142,
  "countries": {...},
  "devices": {...},
  "browsers": {...}
}
```

## 🗂️ Project Structure

```
hyls/
├── admin/              # Admin panel
│   ├── index.php       # Dashboard
│   ├── users.php       # User management
│   ├── links.php       # Link management
│   └── settings.php    # System settings
├── assets/             # CSS, JS, images
├── includes/           # Core PHP classes
│   ├── db.php          # Database class
│   ├── functions.php   # Helper functions
│   └── mailer.php      # Email handler
├── install/            # Installation wizard
│   ├── index.php       # Installer
│   └── migrate.php     # Database migrations
├── auth.php            # HypeChats OAuth
├── google-auth.php     # Google OAuth
├── login.php           # Login page
├── dashboard.php       # User dashboard
├── qr.php              # QR code generator
├── update.php          # Auto-update script
└── config.php          # Configuration
```

## 🔒 Security Features

- ✅ **Password Hashing** - bcrypt for user passwords
- ✅ **SQL Injection Protection** - Prepared statements
- ✅ **XSS Prevention** - Input sanitization
- ✅ **CSRF Protection** - Token validation
- ✅ **Session Security** - Secure session handling
- ✅ **OAuth 2.0** - Secure third-party authentication
- ✅ **Rate Limiting** - Prevent abuse
- ✅ **Link Password Protection** - Encrypted passwords

## 🎨 Customization

### Changing Colors

Edit CSS files in `assets/css/` to customize colors and themes.

### Custom Logo

Replace `assets/favicon.ico` and update `SITE_NAME` in `config.php`.

### Custom Domain

Update `SITE_URL` in `config.php`:
```php
define('SITE_URL', 'https://your-domain.com');
```

## 🐛 Troubleshooting

### Google OAuth Not Working

**Error:** `Column 'google_id' not found`

**Solution:** Run database migration:
```
https://yourdomain.com/install/migrate.php
```

### SMTP Emails Not Sending

1. Check SMTP credentials
2. Enable "Less secure apps" (Gmail)
3. Use App Password (Gmail)
4. Test with "Send Test Email" button

### Update.php Not Working

**Ensure:**
- Git is installed
- Repository is cloned (not downloaded ZIP)
- Write permissions on files

### QR Codes Not Generating

**Check:**
- GD extension is installed
- `php -m | grep gd`
- Write permissions on temp directory

## 📦 Database Schema

### Users Table
```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE,
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255) NULL,
  google_id VARCHAR(255) NULL,
  hypechats_id VARCHAR(255) NULL,
  profile_picture TEXT,
  email_verified TINYINT(1) DEFAULT 0,
  is_admin TINYINT(1) DEFAULT 0,
  created_at DATETIME,
  updated_at DATETIME,
  last_login DATETIME
);
```

### Links Table
```sql
CREATE TABLE links (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  original_url TEXT,
  short_code VARCHAR(20) UNIQUE,
  password VARCHAR(255),
  expires_at DATETIME,
  clicks INT DEFAULT 0,
  created_at DATETIME
);
```

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

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
- [QR Code Generator](https://github.com/chillerlan/php-qrcode) - QR codes
- [Google OAuth](https://developers.google.com/identity/protocols/oauth2) - Authentication
- [HypeChats](https://hypechats.com/) - Social OAuth

## 📊 Stats

![GitHub stars](https://img.shields.io/github/stars/david0154/hyls?style=social)
![GitHub forks](https://img.shields.io/github/forks/david0154/hyls?style=social)
![GitHub issues](https://img.shields.io/github/issues/david0154/hyls)
![GitHub pull requests](https://img.shields.io/github/issues-pr/david0154/hyls)

## 🔗 Links

- **Live Demo:** [Coming Soon]
- **Documentation:** [GitHub Wiki](https://github.com/david0154/hyls/wiki)
- **Report Bug:** [GitHub Issues](https://github.com/david0154/hyls/issues)
- **Request Feature:** [GitHub Issues](https://github.com/david0154/hyls/issues)

---

<p align="center">
  Made with ❤️ by <a href="https://github.com/david0154">David Studioz</a>
</p>

<p align="center">
  <a href="#-hyls---modern-url-shortener">Back to Top ⬆️</a>
</p>