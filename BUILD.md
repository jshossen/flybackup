# Build Instructions

## Quick Reference

### Development (Watch Mode)
```bash
npm start
```
- Watches for file changes in `assets/src/`
- Auto-rebuilds on save
- Creates development builds (unminified, with source maps)
- Perfect for active development

### Production Build (Create ZIP)
```bash
npm run build
```
- Builds optimized production assets
- Reads version from `auto-backup.php` header
- Creates `build/` folder in plugin root
- Creates `build/auto-backup-{version}.zip` (Free version)
- ZIP file is ready to upload to WordPress
- Excludes development files (node_modules, src files, etc.)

### Pro Build (Create Pro ZIP)
```bash
npm run build:pro
```
- Builds optimized production assets
- Reads version from `auto-backup.php` header
- Creates `build/auto-backup-pro-{version}.zip` (Pro version)
- Includes all pro features (cloud storage, migration, real-time, licensing)
- Ready for commercial distribution

### Build Assets Only
```bash
npm run build:assets
```
- Only builds the React assets (JS/CSS)
- Does not create ZIP file
- Useful for testing production builds

### Clean Build Files
```bash
npm run clean
```
- Removes compiled JS and CSS files
- Useful before fresh builds

## Free vs Pro Builds

| Feature | Free Build | Pro Build |
|---------|-----------|-----------|
| **Command** | `npm run build` | `npm run build:pro` |
| **Output File** | `auto-backup-{version}.zip` | `auto-backup-pro-{version}.zip` |
| **Core Features** | ✅ All included | ✅ All included |
| **Pro Features** | ⚠️ Skeleton only | ✅ Fully functional |
| **Cloud Storage** | ❌ Skeleton | ✅ Google Drive, Dropbox, S3 |
| **Migration Tool** | ❌ Skeleton | ✅ Full migration |
| **Real-time Backup** | ❌ Skeleton | ✅ Active monitoring |
| **License System** | ❌ Skeleton | ✅ License validation |
| **Distribution** | WordPress.org | Commercial/Premium |

## What Gets Included in ZIP

### ✅ Included (Both Versions)
- `auto-backup.php` (main plugin file)
- `uninstall.php`
- `readme.txt` & `README.md`
- `composer.json`
- `includes/` (all PHP classes)
- `admin/` (admin integration)
- `pro/` (pro features - skeleton in free, full in pro)
- `assets/js/` (compiled JavaScript)
- `assets/css/` (compiled CSS)
- `assets/images/` (if exists)
- `languages/` (if exists)

### ❌ Excluded (Both Versions)
- `node_modules/`
- `assets/src/` (React source files)
- `.git/`
- `.gitignore`
- `package.json` & `package-lock.json`
- `webpack.config.js`
- `build.js` & `build-pro.js`
- `.DS_Store`
- `*.map` (source maps)

## Version Management

The version is automatically read from the plugin header in `auto-backup.php`:

```php
/**
 * Version: 1.0.0
 */
```

To release a new version:
1. Update version in `auto-backup.php` header
2. Update version in `package.json` (optional, for consistency)
3. Run `npm run build`
4. ZIP file will be named with the new version

## Build Output

After running `npm run build`, you'll see:

```
📦 Building auto-backup-1.0.0.zip...
📌 Version: 1.0.0
[... file list ...]
✅ Build complete!
📦 File: /path/to/build/auto-backup-1.0.0.zip
📊 Size: 0.09 MB
🚀 Ready to upload to WordPress!
```

The ZIP file will be located at:
```
/Users/jshossen/Local Sites/auto-backup/app/public/wp-content/plugins/auto-backup/build/auto-backup-1.0.0.zip
```

## Troubleshooting

### Build fails with "zip command not found"
- Install zip utility: `brew install zip` (macOS) or `apt-get install zip` (Linux)

### Version not detected
- Check that `auto-backup.php` has the version header:
  ```php
  * Version: 1.0.0
  ```

### Assets not updating
- Run `npm run clean` first
- Then run `npm run build`

### Watch mode not working
- Make sure you're in the plugin directory
- Check that webpack is installed: `npm install`

## Workflow Examples

### Daily Development
```bash
# Start watch mode
npm start

# Make changes to React files in assets/src/
# Webpack automatically rebuilds

# Test in WordPress admin
```

### Preparing for Release
```bash
# Update version in auto-backup.php
# Example: Version: 1.1.0

# Clean old builds
npm run clean

# Create production build
npm run build

# Upload the ZIP file to WordPress.org or your site
```

### Testing Production Build Locally
```bash
# Build assets only
npm run build:assets

# Test in WordPress
# Assets will be minified like production

# If satisfied, create full ZIP
npm run build
```
