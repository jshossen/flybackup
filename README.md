# Auto Backup - WordPress Plugin

**Version:** 1.0.0  
**Author:** Your Name  
**License:** GPL v2 or later

## Description

Auto Backup is a modern, lightweight WordPress backup plugin designed for beginners and professionals alike. Create automated backups of your entire WordPress site with just one click.

## Features

### Free Version
- ✅ **One-Click Backups** - Create full or partial backups instantly
- ✅ **Automated Scheduling** - Set hourly, daily, weekly, or monthly backups
- ✅ **Selective Backup** - Choose what to backup (database, uploads, plugins, themes, wp-config)
- ✅ **One-Click Restore** - Restore your site from any backup with a single click
- ✅ **Backup Health Monitor** - Visual health score shows your backup reliability
- ✅ **Smart Retention** - Automatically keep only your most recent backups
- ✅ **Detailed Logs** - Track every backup with comprehensive logging
- ✅ **Modern React UI** - Beautiful, intuitive dashboard

### Pro Features (Coming Soon)
- ☁️ Cloud Storage (Google Drive, Dropbox, Amazon S3, OneDrive)
- ⚡ Incremental Backups
- 🔄 Real-Time Backup
- 🚀 Migration Tool
- 🛡️ Emergency Restore Mode
- 🔍 Malware Scanning
- 🎨 White Label Mode

## Installation

1. Upload the `auto-backup` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Auto Backup** in your WordPress admin menu
4. Configure your backup settings and create your first backup

## Development Setup

### Prerequisites
- PHP 7.4 or higher
- WordPress 5.8 or higher
- Node.js 14+ and npm
- Composer (optional)

### Build Instructions

1. **Install Dependencies**
   ```bash
   npm install
   ```

2. **Development Mode (with watch)**
   ```bash
   npm start
   # This will watch for changes and rebuild automatically
   ```

3. **Production Build (creates ZIP file)**
   ```bash
   npm run build
   # This will:
   # - Build optimized React assets
   # - Read version from auto-backup.php
   # - Create build/auto-backup-{version}.zip
   # - Ready to upload to WordPress!
   ```

4. **Build Assets Only**
   ```bash
   npm run build:assets
   # Just builds the React assets without creating ZIP
   ```

5. **Clean Build Artifacts**
   ```bash
   npm run clean
   # Removes compiled JS/CSS files
   ```

6. **Activate Plugin**
   - Go to WordPress admin → Plugins
   - Activate "Auto Backup"

## Architecture

### Backend (PHP)
- **OOP Architecture** - Clean, modular class structure
- **WordPress Standards** - Follows WordPress coding standards
- **Performance-First** - Chunked processing for large sites
- **Secure** - Nonce verification, capability checks, sanitization

### Frontend (React)
- **Modern React 18** - Hooks-based components
- **WordPress API** - Uses wp.apiFetch for communication
- **Responsive Design** - Works on all devices
- **Real-time Updates** - Dynamic UI updates

### Database Schema
- `wp_ab_backups` - Stores backup metadata
- `wp_ab_logs` - Comprehensive logging
- `wp_ab_schedules` - Scheduled backup configuration

### REST API Endpoints
- `GET /wp-json/auto-backup/v1/backups` - List backups
- `POST /wp-json/auto-backup/v1/backups` - Create backup
- `DELETE /wp-json/auto-backup/v1/backups/{id}` - Delete backup
- `POST /wp-json/auto-backup/v1/backups/{id}/restore` - Restore backup
- `GET /wp-json/auto-backup/v1/schedules` - List schedules
- `GET /wp-json/auto-backup/v1/health` - Health status
- `GET /wp-json/auto-backup/v1/logs` - View logs
- `GET /wp-json/auto-backup/v1/settings` - Get settings

## File Structure

```
auto-backup/
├── auto-backup.php          # Main plugin file
├── uninstall.php            # Uninstall cleanup
├── readme.txt               # WordPress.org readme
├── package.json             # NPM dependencies
├── webpack.config.js        # Build configuration
├── assets/
│   ├── src/                 # React source files
│   ├── js/                  # Compiled JavaScript
│   └── css/                 # Compiled CSS
├── includes/                # PHP classes
│   ├── class-auto-backup.php
│   ├── class-backup-engine.php
│   ├── class-restore-engine.php
│   ├── class-scheduler.php
│   ├── class-database.php
│   ├── class-rest-api.php
│   └── helpers.php
├── admin/                   # Admin integration
│   ├── class-admin-menu.php
│   └── class-dashboard-widget.php
└── pro/                     # Pro features (skeleton)
    ├── cloud/
    ├── migration/
    ├── realtime/
    └── licensing/
```

## Usage

### Creating a Backup
1. Go to **Auto Backup → Backups**
2. Click "Create Backup"
3. Select backup type (Full, Partial, or Database)
4. Wait for completion

### Restoring a Backup
1. Go to **Auto Backup → Restore**
2. Select a backup from the list
3. Click "Restore"
4. Confirm the action

### Scheduling Backups
1. Go to **Auto Backup → Schedules**
2. Click "Create Schedule"
3. Configure frequency and backup type
4. Save schedule

### Configuring Settings
1. Go to **Auto Backup → Settings**
2. Enable email notifications (optional)
3. Set retention count
4. Save settings

## Hooks & Filters

### Actions
- `auto_backup_before_backup` - Fires before backup starts
- `auto_backup_after_backup` - Fires after backup completes
- `auto_backup_before_restore` - Fires before restore starts
- `auto_backup_after_restore` - Fires after restore completes

### Filters
- `auto_backup_storage_locations` - Modify storage locations
- `auto_backup_backup_items` - Modify backup items
- `auto_backup_retention_policy` - Modify retention count
- `auto_backup_health_checks` - Add custom health checks
- `auto_backup_excluded_paths` - Modify excluded file paths

## Troubleshooting

### Backup Fails
- Check disk space
- Verify write permissions on `/wp-content/auto-backups/`
- Increase PHP memory limit
- Check error logs

### Restore Issues
- Ensure backup file exists
- Verify ZIP file integrity
- Check database connection
- Review logs for details

### Performance Issues
- Use partial backups for large sites
- Exclude unnecessary folders
- Increase PHP max_execution_time
- Use WP-CLI for large operations

## Support

- **Documentation:** [Plugin Documentation](#)
- **Support Forum:** [WordPress.org Support](#)
- **Bug Reports:** [GitHub Issues](#)

## Changelog

### 1.0.0 (2024-01-01)
- Initial release
- Manual and scheduled backups
- Full, partial, and database-only backup types
- One-click restore
- Backup health monitoring
- Retention management
- Comprehensive logging
- Modern React-based UI

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Built with ❤️ using:
- React 18
- WordPress REST API
- Chart.js
- Modern WordPress development practices

---

**Note:** This is a production-ready plugin with extensible architecture for future pro features.
