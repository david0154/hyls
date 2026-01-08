# 🔗 HYLS - Modern URL Shortener & Bio Link Platform

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)

**HYLS** is a powerful URL shortener with bio link pages, OAuth integration, and analytics tracking.

## ✨ Features

### 🔐 Authentication
- **HypeChats OAuth** - Sign in with HypeChats account
- **Google OAuth** - Sign in with Google account
- **Email/Password Login** - Traditional authentication
- **Profile Management** - Update profile pictures and settings
- **Last Login Tracking** - Monitor user activity

### 🔗 URL Shortening
- **Custom Short URLs** - Create memorable short links
- **Random Short Codes** - Auto-generate unique codes
- **Password Protection** - Secure links with passwords
- **Link Expiration** - Set expiry dates for temporary links
- **Link Banning** - Admin can ban/block malicious links
- **Click Tracking** - Monitor link performance

### 👤 Bio Link Pages
- **Custom Bio Pages** - Create personalized landing pages
- **Profile & Cover Images** - Upload custom images
- **29 Social Platforms** - Connect all your social media
- **Link Blocking** - Enable/disable individual social links
- **6-Image Gallery** - Showcase your work
- **Theme Customization** - Choose your brand colors
- **Contact Information** - Email and phone with toggle
- **View Counter** - Track page visits

### 📊 Analytics
- **Click Tracking** - Real-time click statistics
- **IP Tracking** - Monitor visitor IPs
- **Referrer Tracking** - See where traffic comes from
- **User Agent Logging** - Track browsers and devices

### 💰 Monetization
- **Advertisement System** - Display ads on bio pages
- **Ad Management** - Create and manage advertisements
- **Position Control** - Order ad placement

### ⚙️ Admin Panel
- **User Management** - View and manage users
- **Link Management** - Moderate shortened URLs
- **Ban System** - Block problematic links with reasons
- **System Settings** - Configure site-wide options
- **SMTP Configuration** - Email settings
- **OAuth Settings** - Configure Google Sign-In

### 🚀 Advanced Features
- **One-Click Updates** - Auto-update from GitHub (requires Git)
- **Database Migration** - Automatic schema updates
- **Installation Wizard** - Easy setup process
- **Mobile Responsive** - Works on all devices
- **Modern UI** - Clean, gradient-based design

## 📋 Requirements

- **PHP** 7.4 or higher
- **MySQL** 5.7 or higher (or MariaDB 10.2+)
- **Apache/Nginx** with mod_rewrite
- **Git** (optional, for auto-updates)

### PHP Extensions
- PDO
- PDO_MySQL
- cURL
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
- ✅ Check system requirements
- ✅ Create database configuration
- ✅ Set up database tables
- ✅ Create admin account
- ✅ Configure initial settings

4. **Configure OAuth (Optional)**

Edit `config.php` after installation:
```php
// Google OAuth
define('GOOGLE_CLIENT_ID', 'your_google_client_id');
define('GOOGLE_CLIENT_SECRET', 'your_google_client_secret');

// HypeChats OAuth
define('APP_ID', 'your_hypechats_app_id');
define('APP_SECRET', 'your_hypechats_app_secret');
```

5. **Secure your installation**

After installation, the install.php file should be deleted or moved for security.

## 🔧 Configuration

### Google OAuth Setup

1. Visit [Google Cloud Console](https://console.cloud.google.com/)
2. Create new project or select existing
3. Enable Google+ API
4. Create OAuth 2.0 credentials
5. Add authorized redirect URI: `https://yourdomain.com/google-auth.php`
6. Copy Client ID and Secret to `config.php`

### HypeChats OAuth Setup

1. Visit HypeChats Developer Portal
2. Create new application
3. Set redirect URI: `https://yourdomain.com/auth.php`
4. Copy App ID and App Secret to `config.php`

### SMTP Configuration

1. Go to Admin Panel → Settings
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

### One-Click Update (Git Required)

Visit: `https://yourdomain.com/update.php`

This will:
- ✅ Fetch latest changes from GitHub
- ✅ Run database migrations
- ✅ Update all files

### Manual Update

```bash
cd /path/to/hyls
git pull origin main
```

Then visit: `https://yourdomain.com/install.php?mode=migrate`

## 📚 Usage

### Creating Short Links

1. **Dashboard** → Enter long URL
2. **(Optional)** Customize short code
3. **(Optional)** Add password protection
4. **(Optional)** Set expiration date
5. Click **"Shorten"**

### Creating Bio Link Page

1. Go to **Dashboard** → **Bio Link**
2. Fill in your information:
   - Display name and bio
   - Upload profile and cover images
   - Add social media links
   - Upload gallery images (up to 6)
   - Set theme color
3. Enable/disable individual links
4. Click **"Save"**
5. Your bio page: `yourdomain.com/bio.php?u=username`

### Managing Advertisements

1. **Admin Panel** → **Advertisements**
2. Create new ad:
   - Title and description
   - Target URL
   - Upload ad image
   - Set CTA button text
3. Enable/disable ads
4. Ads appear on bio pages

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
│   ├── bio/            # Bio images
│   └── bio/gallery/    # Gallery images
├── auth.php            # HypeChats OAuth handler
├── google-auth.php     # Google OAuth handler
├── login.php           # Login page
├── dashboard.php       # User dashboard
├── bio.php             # Bio link display
├── edit_bio.php        # Bio link editor
├── r.php               # Link redirect handler
├── install.php         # Installation wizard
├── update.php          # Auto-update script
└── config.php          # Configuration file
```

## 🔒 Security Features

- ✅ **Password Hashing** - bcrypt for user passwords
- ✅ **SQL Injection Protection** - Prepared statements
- ✅ **XSS Prevention** - Input sanitization
- ✅ **Session Security** - Secure session handling
- ✅ **OAuth 2.0** - Secure third-party authentication
- ✅ **Link Password Protection** - Encrypted passwords for links

## 🐛 Troubleshooting

### Google OAuth Not Working

**Error:** `Redirect URI mismatch`

**Solution:** Ensure redirect URI in Google Console matches exactly:
```
https://yourdomain.com/google-auth.php
```

### Database Migration Issues

**Error:** `Column already exists`

**Solution:** Safe to ignore - migration system checks for existing columns

### Update.php Not Working

**Ensure:**
- Git is installed on server
- Repository was cloned (not downloaded as ZIP)
- Web server has write permissions on files

### Upload Directory Permissions

```bash
chmod 755 uploads/
chmod 755 uploads/profiles/
chmod 755 uploads/bio/
chmod 755 uploads/bio/gallery/
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

- [Font Awesome](https://fontawesome.com/) - Icons
- [Google OAuth](https://developers.google.com/identity/protocols/oauth2) - Authentication
- [HypeChats](https://hypechats.com/) - Social OAuth

## 📊 Stats

![GitHub stars](https://img.shields.io/github/stars/david0154/hyls?style=social)
![GitHub forks](https://img.shields.io/github/forks/david0154/hyls?style=social)
![GitHub issues](https://img.shields.io/github/issues/david0154/hyls)
![GitHub pull requests](https://img.shields.io/github/issues-pr/david0154/hyls)

## 🔗 Links

- **Documentation:** [GitHub Wiki](https://github.com/david0154/hyls/wiki)
- **Report Bug:** [GitHub Issues](https://github.com/david0154/hyls/issues)
- **Request Feature:** [GitHub Issues](https://github.com/david0154/hyls/issues)

---

<p align="center">
  Made with ❤️ by <a href="https://github.com/david0154">David Studioz</a>
</p>

<p align="center">
  <a href="#-hyls---modern-url-shortener--bio-link-platform">Back to Top ⬆️</a>
</p>