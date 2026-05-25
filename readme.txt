=== FlyBackup - Auto backup manager ===
Contributors: jshossen
Tags: backup, restore, database backup, wordpress backup, automatic backup, scheduled backup, site backup
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fast, reliable WordPress backups that just work. One-click automated backups without technical complexity. Backup your database, files, and restore with ease.

== Description ==

**FlyBackup** is a modern, lightweight WordPress backup plugin designed for beginners and professionals alike. Create automated backups of your entire WordPress site with just one click. No complex configuration, no technical knowledge required – just reliable backups that protect your website.

Whether you're running a personal blog, business website, or e-commerce store, FlyBackup ensures your data is safe and can be restored instantly when needed. With its intuitive interface and powerful features, backing up your WordPress site has never been easier.

= Why Choose FlyBackup? =

* **Simple & Intuitive** - Clean, modern interface that anyone can use
* **Reliable** - Built with WordPress best practices and tested thoroughly
* **Fast** - Optimized for performance with chunked processing
* **Flexible** - Backup everything or just what you need
* **Safe** - Secure backup storage with proper file permissions
* **Free** - Core features available at no cost

= Perfect For =

* Bloggers who want peace of mind
* Business owners protecting their online presence
* Developers working on client sites
* E-commerce stores running WooCommerce
* Anyone who values their website data

= Key Features =

* **One-Click Backups** - Create full or partial backups instantly
* **Automated Scheduling** - Set hourly, daily, weekly, or monthly backups
* **Selective Backup** - Choose what to backup: database, uploads, plugins, themes, wp-config
* **One-Click Restore** - Restore your site from any backup with a single click
* **Backup Health Monitor** - Visual health score shows your backup reliability
* **Smart Retention** - Automatically keep only your most recent backups
* **Detailed Logs** - Track every backup with comprehensive logging
* **Modern Interface** - Beautiful, intuitive dashboard built with React

= What Can You Backup? =

* WordPress Database
* Uploads Folder
* Plugins Folder
* Themes Folder
* wp-config.php
* Custom Folders

= Backup Types =

* **Full Backup** - Everything in one archive
* **Partial Backup** - Select specific items
* **Database Only** - Quick database snapshots

= Scheduling Options =

* Manual (on-demand)
* Hourly
* Daily
* Weekly
* Monthly

= System Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher
* ZIP PHP extension (enabled)
* MySQLi PHP extension (enabled)
* Minimum 256MB PHP memory limit (recommended)
* Minimum 300 seconds PHP execution time (recommended)
* 1GB available disk space (recommended)
* Write permissions on wp-content directory

= Pro Features (Coming Soon) =

* Cloud Storage (Google Drive, Dropbox, Amazon S3, OneDrive)
* Incremental Backups
* Real-Time Backup
* Migration Tool
* Emergency Restore Mode
* Malware Scanning
* WooCommerce Priority Backup
* White Label Mode

== Installation ==

= Automatic Installation (Recommended) =

1. Log in to your WordPress admin dashboard
2. Navigate to Plugins → Add New
3. Search for "FlyBackup"
4. Click "Install Now" button
5. Click "Activate" after installation completes
6. Navigate to "FlyBackup" in your WordPress admin menu
7. Review the system requirements checker on the dashboard
8. Configure your backup settings
9. Create your first backup!

= Manual Installation =

1. Download the plugin ZIP file from WordPress.org
2. Log in to your WordPress admin dashboard
3. Navigate to Plugins → Add New → Upload Plugin
4. Choose the downloaded ZIP file and click "Install Now"
5. Click "Activate Plugin" after installation completes
6. Navigate to "FlyBackup" in your WordPress admin menu
7. Configure your settings and create your first backup

= FTP Installation =

1. Download and extract the plugin ZIP file
2. Upload the `fly-backup` folder to `/wp-content/plugins/` directory via FTP
3. Log in to your WordPress admin dashboard
4. Navigate to Plugins → Installed Plugins
5. Find "FlyBackup" and click "Activate"
6. Navigate to "FlyBackup" in your WordPress admin menu

= After Installation =

1. **Check System Requirements** - Visit the dashboard to see if your server meets all requirements
2. **Configure Settings** - Set your preferred backup location and retention policy
3. **Create Test Backup** - Create a manual backup to ensure everything works
4. **Set Up Schedule** - Configure automated backups for peace of mind
5. **Test Restore** - Try restoring to a test site to familiarize yourself with the process

== Frequently Asked Questions ==

= Where are backups stored? =

By default, backups are stored in `/wp-content/fly-backups/` directory on your server. Each backup is saved as a ZIP file with a timestamp. Pro version will add cloud storage options (Google Drive, Dropbox, Amazon S3, OneDrive).

= How large can my backups be? =

The plugin uses chunked processing to handle sites of any size without memory issues. It's been tested with sites over 1GB. There's no hard limit – it depends on your server's available disk space.

= Can I restore a backup? =

Yes! One-click restore is included. Simply select a backup from the list, choose what to restore (database, files, or both), and click restore. The plugin will handle everything automatically.

= Does this work with WooCommerce? =

Absolutely! FlyBackup works perfectly with WooCommerce and all other WordPress plugins. It backs up your entire database including all WooCommerce orders, products, and customer data.

= How many backups are kept? =

You can configure retention settings to keep 1-10 backups. When the limit is reached, the oldest backup is automatically deleted to save disk space. This ensures you always have recent backups without filling up your server.

= Does this slow down my site? =

No. Backups run in the background using WordPress cron and don't affect your site's performance or visitor experience. Scheduled backups run during low-traffic periods.

= Can I download backups to my computer? =

Yes! Each backup has a download button. You can download the ZIP file to your local computer for extra security or to transfer to another location.

= What happens if a backup fails? =

The plugin logs all backup activities. If a backup fails, you'll see the error in the logs section. Common issues include insufficient disk space or PHP timeout limits. The system requirements checker helps identify potential problems.

= Can I backup just the database? =

Yes! You can create database-only backups, which are much smaller and faster. This is perfect for frequent snapshots of your content and settings.

= Is my data secure? =

Yes. Backups are stored in a protected directory with proper file permissions. The plugin follows WordPress security best practices and doesn't transmit your data anywhere (unless you use cloud storage in the Pro version).

= Can I schedule automatic backups? =

Yes! You can set up automated schedules for hourly, daily, weekly, or monthly backups. Multiple schedules can run simultaneously with different backup types and retention settings.

= Does it work on shared hosting? =

Yes! FlyBackup is designed to work on shared hosting environments. It respects server limits and uses efficient processing methods. Check the system requirements to ensure your host meets the minimum specifications.

= Can I compare backups? =

Yes! The plugin includes a backup comparison feature that shows you the differences between backups or between a backup and your current site. This helps you see what changed over time.

= What if I need help? =

Check the plugin's documentation first. For additional support, visit the WordPress.org support forums or contact us through the plugin's support page.

== Screenshots ==

1. Dashboard - Overview of your backup status
2. Backups List - Manage all your backups
3. Create Backup - Choose what to backup
4. Restore Interface - One-click restore
5. Schedule Management - Automated backups
6. Settings - Configure your preferences
7. Health Monitor - Backup reliability score

== Changelog ==

= 1.0.0 - 2026-05-24 =

**Initial Release**

* ✅ Manual backup creation with one click
* ✅ Automated backup scheduling (hourly, daily, weekly, monthly)
* ✅ Full site backups (database + files)
* ✅ Partial backups (select specific items)
* ✅ Database-only backups
* ✅ One-click restore functionality
* ✅ Selective restore (choose database, files, or both)
* ✅ Backup health monitoring with visual score
* ✅ Smart retention management (1-10 backups)
* ✅ Comprehensive activity logging
* ✅ Backup comparison tool (current vs backup, backup vs backup)
* ✅ Database table diff viewer
* ✅ System requirements checker
* ✅ Download backups to local computer
* ✅ Modern React-based admin interface
* ✅ WordPress REST API integration
* ✅ Chunked processing for large sites
* ✅ Backup metadata tracking
* ✅ Multiple schedule support
* ✅ WooCommerce compatible
* ✅ Multisite compatible (single site mode)

== Upgrade Notice ==

= 1.0.0 =
Initial release of FlyBackup. Create your first automated WordPress backup today! Protect your website with reliable, easy-to-use backup solution.

== Privacy Policy ==

FlyBackup does not collect, store, or transmit any personal data or website information to external servers. All backups are stored locally on your server in the `/wp-content/fly-backups/` directory. The plugin operates entirely within your WordPress installation and respects your privacy.

When you use the Pro version's cloud storage features (coming soon), your backup files will be transmitted to your chosen cloud provider (Google Drive, Dropbox, Amazon S3, or OneDrive) using their official APIs. You control which cloud services to use and can disconnect them at any time.

== Support ==

For support, please visit:
* WordPress.org support forums: https://wordpress.org/support/plugin/fly-backup/
* GitHub repository: https://github.com/jshossen/fly-backup
* Documentation: Available in the plugin's Help section
* Bug reports: Use the WordPress.org support forums or GitHub Issues

== Contributing ==

FlyBackup is open source and welcomes contributions. If you'd like to contribute code, report bugs, or suggest features:

* GitHub repository: https://github.com/jshossen/fly-backup
* Submit pull requests on GitHub
* Report issues on GitHub Issues
* Join discussions in WordPress.org support forums

== Credits ==

* Built with React and WordPress REST API
* Uses WordPress coding standards
* Follows WordPress plugin development best practices
* Icons and UI elements follow WordPress design guidelines
