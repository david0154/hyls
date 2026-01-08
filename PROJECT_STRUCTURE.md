# 📁 HYLS Project Structure

## Overview
This document describes the complete file and directory structure of the HYLS (Hyperlink Shortener) project.

---

## 🌳 Directory Tree

```
hyls/
│
├── 📁 admin/                    # Admin Panel
│   ├── index.php               # Admin dashboard
│   ├── users.php               # User management
│   ├── links.php               # Link management
│   ├── settings.php            # System settings
│   ├── advertisements.php      # Ad management
│   └── analytics.php           # Statistics & analytics
│
├── 📁 assets/                   # Static Resources
│   ├── css/                    # Stylesheets
│   ├── js/                     # JavaScript files
│   ├── images/                 # Images & graphics
│   └── favicon.ico             # Site favicon
│
├── 📁 docs/                     # Documentation
│   ├── API.md                  # API documentation
│   ├── INSTALLATION.md         # Installation guide
│   └── FEATURES.md             # Feature list
│
├── 📁 includes/                 # Core PHP Classes
│   ├── db.php                  # Database connection class
│   ├── functions.php           # Helper functions
│   ├── image_processor.php     # Image handling
│   ├── mailer.php              # Email functionality
│   └── video_embed.php         # Video embed handler
│
├── 📁 install/                  # Installation Files
│   ├── install.php             # Installation wizard
│   ├── database.sql            # Main database schema
│   ├── create_gallery_table.sql    # Gallery table
│   ├── fix_cover_image.sql     # Cover image migration
│   └── social_videos_table.sql # Video embeds table
│
├── 📁 uploads/                  # User Uploads (gitignored)
│   ├── .gitkeep               # Preserve directory
│   ├── bio/                    # Bio profile images
│   │   ├── .gitkeep
│   │   └── gallery/            # Gallery images
│   └── qr/                     # QR code images
│       └── .gitkeep
│
├── 📄 .htaccess                 # Apache Configuration
├── 📄 .gitignore               # Git ignore rules
│
├── 📄 ad-page.php              # Advertisement Management
├── 📄 auth.php                 # Authentication Handler
├── 📄 bio.php                  # Public Bio Page Display
├── 📄 biolink.php              # Bio Link Management (User Panel)
├── 📄 config.sample.php        # Sample Configuration
├── 📄 dashboard.php            # User Dashboard
├── 📄 delete_link.php          # Link Deletion Handler
├── 📄 edit_bio.php             # Bio Editing Interface
├── 📄 google-auth.php          # Google OAuth Integration
├── 📄 index.php                # Landing Page
├── 📄 login.php                # Login Page
├── 📄 logout.php               # Logout Handler
├── 📄 r.php                    # URL Redirect Handler
├── 📄 shorten.php              # URL Shortening API
├── 📄 update.php               # Database Update Script
│
├── 📄 CLEANUP_SCRIPT.md        # Repository cleanup guide
├── 📄 LICENSE                  # MIT License
├── 📄 PROJECT_STRUCTURE.md     # This file
├── 📄 README.md                # Main documentation
└── 📄 UPGRADE_INSTRUCTIONS.md  # Version upgrade guide
```

---

## 📂 Directory Details

### `/admin` - Admin Panel
Complete administrative interface for managing the application.

**Key Files:**
- `index.php` - Main admin dashboard with statistics
- `users.php` - User account management
- `links.php` - Manage all shortened links
- `settings.php` - System configuration
- `advertisements.php` - Advertisement campaigns
- `analytics.php` - Detailed analytics and reports

**Access:** Restricted to admin users only

---

### `/assets` - Static Resources
All static files served to the browser.

**Structure:**
```
assets/
├── css/
│   ├── style.css           # Main stylesheet
│   └── admin.css           # Admin panel styles
├── js/
│   ├── main.js             # Core JavaScript
│   └── admin.js            # Admin functionality
├── images/
│   ├── logo.png            # Site logo
│   └── icons/              # Icon assets
└── favicon.ico             # Browser favicon
```

**Performance:** Consider CDN for production

---

### `/docs` - Documentation
Project documentation and guides.

**Contents:**
- API documentation
- Installation guides
- Feature descriptions
- Developer notes

**Format:** Markdown (.md) files

---

### `/includes` - Core PHP Classes
Reusable PHP classes and functions.

#### **db.php** - Database Class
```php
// Handles all database connections
// Uses PDO for security
// Singleton pattern
```

#### **functions.php** - Helper Functions
```php
// General utility functions
// URL validation
// String manipulation
// Date formatting
```

#### **image_processor.php** - Image Handler
```php
// Image upload processing
// Thumbnail generation
// Format conversion
// Compression
```

#### **mailer.php** - Email System
```php
// Email sending functionality
// Password reset emails
// Welcome emails
// Notifications
```

#### **video_embed.php** - Video Embeds
```php
// YouTube, Facebook, Instagram
// TikTok, Vimeo, Dailymotion
// Auto-thumbnail generation
```

---

### `/install` - Installation Files
First-time setup and database migrations.

**Files:**
1. `install.php` - Interactive installation wizard
2. `database.sql` - Complete database schema
3. `create_gallery_table.sql` - Gallery feature
4. `fix_cover_image.sql` - Cover image support
5. `social_videos_table.sql` - Video embeds

**Usage:** Run once during initial setup

---

### `/uploads` - User Uploads
**⚠️ Important:** This directory is gitignored!

**Structure:**
```
uploads/
├── bio/
│   ├── profile_*.jpg       # Profile images
│   ├── cover_*.jpg         # Cover images
│   └── gallery/            # Gallery images (6 max)
│       └── gallery_*.jpg
└── qr/
    └── qr_*.png            # Generated QR codes
```

**Permissions:** 755 (rwxr-xr-x)
**Size Limits:** 12MB per image

---

## 🔧 Core Files

### Public Pages

| File | Purpose | Access |
|------|---------|--------|
| `index.php` | Landing page | Public |
| `login.php` | User login | Public |
| `bio.php` | Public bio display | Public |
| `r.php` | Link redirect | Public |

### User Dashboard

| File | Purpose | Auth Required |
|------|---------|---------------|
| `dashboard.php` | Main dashboard | ✅ Yes |
| `biolink.php` | Bio management | ✅ Yes |
| `edit_bio.php` | Bio editor | ✅ Yes |
| `shorten.php` | Create short links | ✅ Yes |

### Authentication

| File | Purpose |
|------|----------|
| `auth.php` | Login handler |
| `google-auth.php` | OAuth handler |
| `logout.php` | Session cleanup |

### Utilities

| File | Purpose |
|------|----------|
| `delete_link.php` | Delete links |
| `update.php` | Database updates |
| `ad-page.php` | Ad management |

---

## 🗄️ Database Tables

```sql
-- Core Tables
users                    # User accounts
links                    # Shortened URLs
bio_links                # Bio profiles
bio_gallery              # Gallery images
bio_social_videos        # Embedded videos

-- Admin Tables
settings                 # System settings
administrators           # Admin users
advertisements           # Ad campaigns

-- Analytics Tables
link_analytics           # Click tracking
user_analytics           # User statistics
```

---

## 🔐 Security Files

### `.htaccess`
```apache
# URL rewriting
# Security headers
# File upload limits
# Directory protection
```

### `.gitignore`
```
# Protects:
- config.php (credentials)
- uploads/ (user data)
- *.log (sensitive logs)
- IDE files
```

### `config.sample.php`
```php
// Template for config.php
// Never commit config.php!
```

---

## 📝 Configuration

### Setup Steps
1. Copy `config.sample.php` to `config.php`
2. Edit database credentials
3. Set site URL
4. Configure email settings
5. Run `install/install.php`

### Required Settings
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('SITE_URL', 'https://yoursite.com');
```

---

## 🚀 Deployment Checklist

- [ ] Copy all files to server
- [ ] Create `config.php` from sample
- [ ] Set upload directory permissions (755)
- [ ] Run installation wizard
- [ ] Test URL shortening
- [ ] Test bio pages
- [ ] Configure SSL certificate
- [ ] Enable caching
- [ ] Set up backups

---

## 📊 File Statistics

- **Total Files:** ~30 core files
- **PHP Files:** ~25
- **SQL Files:** ~4
- **Documentation:** ~5 MD files
- **Configuration:** 3 files

---

## 🔄 Version Control

### Branch Structure
```
main          # Production-ready code
├── develop   # Development branch
├── feature/* # New features
└── hotfix/*  # Emergency fixes
```

### Commit Guidelines
- Use clear commit messages
- Prefix with type: `fix:`, `feat:`, `docs:`
- Reference issues: `#123`

---

## 🆘 Support

For questions about project structure:
1. Check this documentation
2. Review README.md
3. Check CLEANUP_SCRIPT.md
4. Open an issue on GitHub

---

**Last Updated:** January 8, 2026  
**Maintained By:** David Studioz  
**License:** MIT
