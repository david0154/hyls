# 👥 Multiple Social Accounts Feature

## Overview

The social accounts feature allows users to add **unlimited accounts per platform** with custom labels. This is perfect for users who manage multiple profiles (personal, business, brand accounts) on the same social media platform.

---

## ✨ Features

### 1. **Multiple Accounts per Platform**
- Add unlimited Instagram accounts (Personal, Business, Shop)
- Add multiple Twitter/X accounts
- Same functionality for all 29+ platforms
- Each account has its own label and URL

### 2. **Account Management**
- Custom labels (e.g., "Personal", "Business", "Brand")
- Toggle visibility with eye icon (enable/disable)
- Delete individual accounts
- Reorder accounts (coming soon)
- Click tracking per account

### 3. **Visual Feedback**
- Green eye icon = Account visible
- Gray eye icon = Account hidden
- Click counter shows engagement
- Grouped display by platform

---

## 🗄️ Database Schema

### Table: `bio_social_accounts`

```sql
CREATE TABLE bio_social_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    platform VARCHAR(50) NOT NULL,
    account_label VARCHAR(100) DEFAULT NULL,
    username VARCHAR(255) DEFAULT NULL,
    url VARCHAR(500) NOT NULL,
    clicks INT DEFAULT 0,
    account_order INT DEFAULT 0,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_platform (platform),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Fields Explained:

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT | Unique account identifier |
| `user_id` | INT | Links to users table |
| `platform` | VARCHAR(50) | Platform name (instagram, twitter, etc.) |
| `account_label` | VARCHAR(100) | Custom label (Personal, Business) |
| `username` | VARCHAR(255) | Platform username/handle |
| `url` | VARCHAR(500) | Full profile URL |
| `clicks` | INT | Number of clicks tracked |
| `account_order` | INT | Display order within platform |
| `is_active` | TINYINT | 1 = visible, 0 = hidden |
| `created_at` | TIMESTAMP | Creation timestamp |

---

## 🚀 Usage Guide

### For Users:

#### **Adding a Social Account**

1. Navigate to Bio Link Editor
2. Scroll to "Social Media Accounts" section
3. Find the platform card (e.g., Instagram)
4. Click "+ Add Instagram Account"
5. Fill in modal form:
   - **Label:** Personal, Business, Shop, etc.
   - **Username:** Your @handle
   - **URL:** Full profile URL
6. Click "Add Account"
7. Account appears in platform card

**Example:**
```
Platform: Instagram
Label: Personal
Username: @john_doe
URL: https://instagram.com/john_doe
```

#### **Managing Accounts**

**Toggle Visibility:**
- Click the eye icon (👁️) to hide/show
- Green = visible, Gray = hidden

**Delete Account:**
- Click trash icon (🗑️)
- Confirm deletion
- Account removed permanently

**View Analytics:**
- See click count next to each account
- Track which accounts get most engagement

---

## 💻 Developer Guide

### Add Social Account Handler

**File:** `biolink_handler.php`

```php
if (isset($_POST['add_social_account'])) {
    $platform = $_POST['platform'];
    $label = trim($_POST['account_label']);
    $username = trim($_POST['account_username']);
    $url = trim($_POST['account_url']);
    
    // Get max order for this platform
    $stmt = $db->prepare(
        "SELECT COALESCE(MAX(account_order), 0) as max_order 
         FROM bio_social_accounts 
         WHERE user_id = ? AND platform = ?"
    );
    $stmt->execute([$user_id, $platform]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_order = $result['max_order'] + 1;
    
    // Insert new account
    $stmt = $db->prepare(
        "INSERT INTO bio_social_accounts 
         (user_id, platform, account_label, username, url, account_order, is_active, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, 1, NOW())"
    );
    $stmt->execute([$user_id, $platform, $label, $username, $url, $new_order]);
    
    $_SESSION['success'] = 'Social account added!';
    header('Location: biolink_enhanced.php');
    exit;
}
```

### Toggle Account Visibility

```php
if (isset($_GET['toggle_social'])) {
    $account_id = (int)$_GET['toggle_social'];
    
    // Toggle is_active
    $stmt = $db->prepare(
        "UPDATE bio_social_accounts 
         SET is_active = NOT is_active 
         WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$account_id, $user_id]);
    
    $_SESSION['success'] = 'Account visibility toggled!';
    header('Location: biolink_enhanced.php');
    exit;
}
```

### Delete Account

```php
if (isset($_GET['delete_social'])) {
    $account_id = (int)$_GET['delete_social'];
    
    $stmt = $db->prepare(
        "DELETE FROM bio_social_accounts 
         WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$account_id, $user_id]);
    
    $_SESSION['success'] = 'Account deleted!';
    header('Location: biolink_enhanced.php');
    exit;
}
```

### Display Accounts (Grouped by Platform)

```php
<?php
// Get all accounts
$stmt = $db->prepare(
    "SELECT * FROM bio_social_accounts 
     WHERE user_id = ? 
     ORDER BY platform, account_order"
);
$stmt->execute([$user_id]);
$social_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by platform
$accounts_by_platform = [];
foreach ($social_accounts as $account) {
    $accounts_by_platform[$account['platform']][] = $account;
}
?>

<!-- Display grouped accounts -->
<?php foreach ($platforms as $key => $platform): ?>
<div class="social-section">
    <h3><?= $platform['label'] ?></h3>
    
    <?php if (isset($accounts_by_platform[$key])): ?>
        <?php foreach ($accounts_by_platform[$key] as $account): ?>
        <div class="account-item">
            <div>
                <strong><?= htmlspecialchars($account['account_label']) ?></strong>
                - @<?= htmlspecialchars($account['username']) ?>
                <br><small><?= number_format($account['clicks']) ?> clicks</small>
            </div>
            <div>
                <a href="?toggle_social=<?= $account['id'] ?>">
                    <?= $account['is_active'] ? '👁️' : '👁️‍🗨️' ?>
                </a>
                <a href="?delete_social=<?= $account['id'] ?>" 
                   onclick="return confirm('Delete?')">
                    🗑️
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <button onclick="openModal('<?= $key ?>', '<?= $platform['label'] ?>')">
        + Add Account
    </button>
</div>
<?php endforeach; ?>
```

---

## 🎨 Modal Dialog

### HTML Structure

```html
<div id="socialModal" style="display: none;">
    <div class="modal-content">
        <h3 id="modalTitle">Add Social Account</h3>
        <form action="biolink_handler.php" method="POST">
            <input type="hidden" name="add_social_account" value="1">
            <input type="hidden" name="platform" id="modalPlatform">
            
            <label>Label (e.g., Personal, Business)</label>
            <input type="text" name="account_label" placeholder="Personal" required>
            
            <label>Username</label>
            <input type="text" name="account_username" placeholder="@username" required>
            
            <label>Full URL</label>
            <input type="url" name="account_url" placeholder="https://" required>
            
            <button type="button" onclick="closeModal()">Cancel</button>
            <button type="submit">Add Account</button>
        </form>
    </div>
</div>
```

### JavaScript

```javascript
function openModal(platform, label) {
    document.getElementById('modalPlatform').value = platform;
    document.getElementById('modalTitle').textContent = 'Add ' + label + ' Account';
    document.getElementById('socialModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('socialModal').style.display = 'none';
}

// Auto-fill URL from username
document.querySelector('[name="account_username"]').addEventListener('input', function(e) {
    const username = e.target.value.replace('@', '');
    const platform = document.getElementById('modalPlatform').value;
    
    const urls = {
        'instagram': 'https://instagram.com/',
        'twitter': 'https://twitter.com/',
        'facebook': 'https://facebook.com/',
        'tiktok': 'https://tiktok.com/@',
    };
    
    if (urls[platform] && username) {
        document.querySelector('[name="account_url"]').value = urls[platform] + username;
    }
});
```

---

## 🎨 CSS Styling

```css
/* Account Item */
.account-item {
    background: #f1f5f9;
    padding: 12px 16px;
    border-radius: 8px;
    margin: 8px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s;
}

.account-item:hover {
    background: #e2e8f0;
    transform: translateX(4px);
}

/* Toggle Button */
.toggle-btn {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.toggle-btn.active {
    background: #22c55e;
    color: white;
}

.toggle-btn.inactive {
    background: #94a3b8;
    color: white;
}

/* Modal */
#socialModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 16px;
    max-width: 500px;
    width: 90%;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

---

## 📊 Supported Platforms

### Currently Available (29 Platforms)

```php
$platforms = [
    'instagram' => ['icon' => 'fab fa-instagram', 'label' => 'Instagram'],
    'twitter' => ['icon' => 'fab fa-x-twitter', 'label' => 'X (Twitter)'],
    'facebook' => ['icon' => 'fab fa-facebook-f', 'label' => 'Facebook'],
    'youtube' => ['icon' => 'fab fa-youtube', 'label' => 'YouTube'],
    'tiktok' => ['icon' => 'fab fa-tiktok', 'label' => 'TikTok'],
    'linkedin' => ['icon' => 'fab fa-linkedin-in', 'label' => 'LinkedIn'],
    'github' => ['icon' => 'fab fa-github', 'label' => 'GitHub'],
    'discord' => ['icon' => 'fab fa-discord', 'label' => 'Discord'],
    'twitch' => ['icon' => 'fab fa-twitch', 'label' => 'Twitch'],
    'reddit' => ['icon' => 'fab fa-reddit-alien', 'label' => 'Reddit'],
    'snapchat' => ['icon' => 'fab fa-snapchat', 'label' => 'Snapchat'],
    'pinterest' => ['icon' => 'fab fa-pinterest-p', 'label' => 'Pinterest'],
    'telegram' => ['icon' => 'fab fa-telegram-plane', 'label' => 'Telegram'],
    'whatsapp' => ['icon' => 'fab fa-whatsapp', 'label' => 'WhatsApp'],
    'spotify' => ['icon' => 'fab fa-spotify', 'label' => 'Spotify'],
    'threads' => ['icon' => 'fab fa-threads', 'label' => 'Threads'],
    'bluesky' => ['icon' => 'fas fa-cloud', 'label' => 'Bluesky'],
    'mastodon' => ['icon' => 'fab fa-mastodon', 'label' => 'Mastodon'],
    'medium' => ['icon' => 'fab fa-medium', 'label' => 'Medium'],
    'substack' => ['icon' => 'fas fa-newspaper', 'label' => 'Substack'],
    'patreon' => ['icon' => 'fab fa-patreon', 'label' => 'Patreon'],
    'onlyfans' => ['icon' => 'fas fa-user-lock', 'label' => 'OnlyFans'],
    'line' => ['icon' => 'fab fa-line', 'label' => 'LINE'],
    'cashapp' => ['icon' => 'fas fa-dollar-sign', 'label' => 'Cash App'],
    'venmo' => ['icon' => 'fas fa-money-bill-wave', 'label' => 'Venmo'],
    'paypal' => ['icon' => 'fab fa-paypal', 'label' => 'PayPal'],
    'website' => ['icon' => 'fas fa-globe', 'label' => 'Website'],
    'email' => ['icon' => 'fas fa-envelope', 'label' => 'Email'],
    'phone' => ['icon' => 'fas fa-phone', 'label' => 'Phone'],
];
```

### Adding New Platforms

```php
// 1. Add to platforms array
$platforms['custom'] = [
    'icon' => 'fas fa-custom-icon',
    'label' => 'Custom Platform',
    'placeholder' => 'https://custom.com/username'
];

// 2. No database changes needed!
// Platform name is stored as string in 'platform' column
```

---

## 📱 Click Tracking

### Track Clicks on Bio Page

```php
// bio.php (public page)
if (isset($_GET['track']) && isset($_GET['account'])) {
    $account_id = (int)$_GET['account'];
    
    // Increment click count
    $stmt = $db->prepare(
        "UPDATE bio_social_accounts 
         SET clicks = clicks + 1 
         WHERE id = ?"
    );
    $stmt->execute([$account_id]);
    
    // Get URL and redirect
    $stmt = $db->prepare(
        "SELECT url FROM bio_social_accounts WHERE id = ?"
    );
    $stmt->execute([$account_id]);
    $account = $stmt->fetch();
    
    if ($account) {
        header('Location: ' . $account['url']);
        exit;
    }
}

// Display with tracking link
<a href="bio.php?track=1&account=<?= $account['id'] ?>" 
   target="_blank" 
   rel="noopener noreferrer">
    <?= $account['account_label'] ?>
</a>
```

---

## 🐛 Troubleshooting

### Issue: "Account not saving"

**Solution:** Check form data
```php
error_log(print_r($_POST, true));
// Verify all fields are present
```

### Issue: "Modal not opening"

**Solution:** Check JavaScript console
```javascript
console.log('Modal clicked:', platform);
// Verify function is defined
```

### Issue: "Toggle not working"

**Solution:** Check user_id verification
```php
// ALWAYS include user_id in WHERE clause
WHERE id = ? AND user_id = ?
```

---

## 🔒 Security

### User Isolation
```php
// BAD - Missing user_id check
DELETE FROM bio_social_accounts WHERE id = ?;

// GOOD - User can only delete their own accounts
DELETE FROM bio_social_accounts WHERE id = ? AND user_id = ?;
```

### URL Validation
```php
$url = filter_var($_POST['account_url'], FILTER_VALIDATE_URL);
if (!$url) {
    throw new Exception('Invalid URL');
}
```

### XSS Prevention
```php
echo htmlspecialchars($account['account_label'], ENT_QUOTES, 'UTF-8');
```

---

## ✅ Testing Checklist

- [ ] Add single account to platform
- [ ] Add second account to same platform
- [ ] Toggle visibility (eye icon)
- [ ] Delete account with confirmation
- [ ] Verify click tracking works
- [ ] Test on mobile (modal, buttons)
- [ ] Try with all 29 platforms
- [ ] Test concurrent users
- [ ] Verify user isolation (can't edit others' accounts)
- [ ] Test with special characters in username

---

## 📈 Analytics Dashboard (Future)

```php
// Get top performing accounts
$stmt = $db->prepare(
    "SELECT platform, account_label, clicks 
     FROM bio_social_accounts 
     WHERE user_id = ? 
     ORDER BY clicks DESC 
     LIMIT 10"
);
$stmt->execute([$user_id]);
$top_accounts = $stmt->fetchAll();

// Display chart
foreach ($top_accounts as $account) {
    echo "{$account['platform']} - {$account['account_label']}: ";
    echo "{$account['clicks']} clicks<br>";
}
```

---

## 🚀 Roadmap

- [ ] Drag-and-drop reordering
- [ ] Bulk operations (delete multiple, toggle multiple)
- [ ] Account verification badges
- [ ] QR codes per account
- [ ] Deep linking (open in app)
- [ ] Custom icons per account
- [ ] Scheduled visibility (show/hide by date)
- [ ] A/B testing (test different accounts)

---

**Last Updated:** January 8, 2026  
**Version:** 1.0.0  
**Author:** David Studioz