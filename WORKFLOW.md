# Development & Release Workflow

## 🎯 Quick Commands

```bash
# Development
npm start              # Watch mode for development

# Free Version Build
npm run build          # Create free version ZIP

# Pro Version Build
npm run build:pro      # Create pro version ZIP

# Clean
npm run clean          # Remove compiled assets
```

## 📋 Development Workflow

### 1. Daily Development
```bash
# Start watch mode
npm start

# Make changes to React files in assets/src/
# Webpack automatically rebuilds

# Test in WordPress admin
# Navigate to: wp-admin/admin.php?page=auto-backup
```

### 2. Testing Production Build
```bash
# Build assets only (no ZIP)
npm run build:assets

# Test minified version in WordPress
# Check console for errors
# Verify all features work
```

## 🚀 Release Workflow

### Free Version Release (WordPress.org)

#### Step 1: Update Version
```php
// In auto-backup.php
/**
 * Version: 1.1.0  ← Update this
 */
```

#### Step 2: Update Changelog
```txt
// In readme.txt
== Changelog ==

= 1.1.0 =
* Added: New feature description
* Fixed: Bug fix description
* Improved: Enhancement description
```

#### Step 3: Build Free Version
```bash
npm run build
# Creates: build/auto-backup-1.1.0.zip
```

#### Step 4: Test ZIP
```bash
# Extract and test on clean WordPress install
unzip -q build/auto-backup-1.1.0.zip -d /tmp/test-plugin
# Install and activate in test environment
```

#### Step 5: Upload to WordPress.org
```bash
# Use SVN to upload to WordPress.org repository
svn co https://plugins.svn.wordpress.org/auto-backup
cd auto-backup
# Copy files to trunk/
# Update assets/
svn ci -m "Release version 1.1.0"
```

---

### Pro Version Release (Commercial)

#### Step 1: Update Version
```php
// In auto-backup.php
/**
 * Version: 1.1.0  ← Update this
 */
```

#### Step 2: Implement Pro Features
```php
// Complete implementation in pro/ folder
// - pro/cloud/class-google-drive.php
// - pro/cloud/class-dropbox.php
// - pro/migration/class-migration-tool.php
// - etc.
```

#### Step 3: Build Pro Version
```bash
npm run build:pro
# Creates: build/auto-backup-pro-1.1.0.zip
```

#### Step 4: Test Pro Features
```bash
# Test on staging environment
# Verify all pro features work:
# - Cloud storage connections
# - Migration functionality
# - Real-time backup triggers
# - License validation
```

#### Step 5: Distribute
```bash
# Upload to your distribution platform
# - EDD (Easy Digital Downloads)
# - WooCommerce
# - Freemius
# - Custom licensing server
```

## 🔄 Dual Release Workflow

When releasing both free and pro versions:

```bash
# 1. Update version in auto-backup.php
# Version: 1.2.0

# 2. Build both versions
npm run build          # Free version
npm run build:pro      # Pro version

# 3. Verify both ZIPs
ls -lh build/
# auto-backup-1.2.0.zip      (Free)
# auto-backup-pro-1.2.0.zip  (Pro)

# 4. Test both versions
# - Install free version on test site
# - Install pro version on test site
# - Verify upgrade path from free to pro

# 5. Release
# - Upload free to WordPress.org
# - Upload pro to commercial platform
```

## 📦 Build Output

### Free Version
```
build/auto-backup-1.0.0.zip
├── Core features (backup, restore, schedule)
├── Basic UI
├── Local storage only
└── Pro features (skeleton only)
```

### Pro Version
```
build/auto-backup-pro-1.0.0.zip
├── Core features (backup, restore, schedule)
├── Advanced UI
├── Local + Cloud storage
└── Pro features (fully functional)
    ├── Google Drive integration
    ├── Dropbox integration
    ├── Amazon S3 integration
    ├── Migration tool
    ├── Real-time backup
    └── License management
```

## 🐛 Troubleshooting

### Build Issues

**Problem**: Build fails with "zip command not found"
```bash
# Solution: Install zip utility
brew install zip  # macOS
```

**Problem**: Version not detected
```bash
# Solution: Check auto-backup.php header format
# Must be: * Version: 1.0.0
```

**Problem**: Old files in ZIP
```bash
# Solution: Clean and rebuild
npm run clean
npm run build
```

### Development Issues

**Problem**: Changes not reflecting
```bash
# Solution: Hard refresh browser
# Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows)
```

**Problem**: Watch mode not working
```bash
# Solution: Restart watch mode
# Press Ctrl+C to stop
npm start  # Restart
```

## 📊 Version Numbering

Follow semantic versioning (SemVer):

- **Major** (1.0.0 → 2.0.0): Breaking changes
- **Minor** (1.0.0 → 1.1.0): New features, backward compatible
- **Patch** (1.0.0 → 1.0.1): Bug fixes, backward compatible

### Examples

```
1.0.0 → 1.0.1  # Bug fix
1.0.1 → 1.1.0  # New feature
1.1.0 → 2.0.0  # Breaking change
```

## 🎯 Best Practices

### Before Every Release

- [ ] Update version in `auto-backup.php`
- [ ] Update changelog in `readme.txt`
- [ ] Run `npm run clean`
- [ ] Run tests (if available)
- [ ] Build ZIP file
- [ ] Test ZIP on clean WordPress install
- [ ] Verify all features work
- [ ] Check for console errors
- [ ] Test upgrade from previous version

### Code Quality

- [ ] Follow WordPress coding standards
- [ ] Add inline documentation
- [ ] Use proper escaping and sanitization
- [ ] Verify nonce checks
- [ ] Test on different PHP versions
- [ ] Test on different WordPress versions

### Distribution

- [ ] Free version: WordPress.org repository
- [ ] Pro version: Commercial platform
- [ ] Keep both versions in sync (version numbers)
- [ ] Maintain upgrade path from free to pro
- [ ] Document pro features clearly

## 🔐 Security Checklist

Before release:

- [ ] All user inputs sanitized
- [ ] All outputs escaped
- [ ] Nonce verification on all forms
- [ ] Capability checks on all actions
- [ ] SQL queries use prepared statements
- [ ] File uploads validated
- [ ] No sensitive data in logs
- [ ] Secure file permissions

## 📝 Release Checklist

### Free Version
- [ ] Version updated
- [ ] Changelog updated
- [ ] Build created: `npm run build`
- [ ] ZIP tested
- [ ] WordPress.org assets updated
- [ ] SVN committed
- [ ] Release tagged

### Pro Version
- [ ] Version updated
- [ ] Pro features implemented
- [ ] Build created: `npm run build:pro`
- [ ] ZIP tested
- [ ] License system tested
- [ ] Distribution platform updated
- [ ] Documentation updated
- [ ] Support resources updated

---

**Need Help?** Check BUILD.md for detailed build instructions.
