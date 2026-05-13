=== Auto Backup ===
Contributors: yourname
Tags: backup, restore, database backup, wordpress backup, automatic backup
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatic WordPress backups that just work. One-click automated backups without technical complexity.

== Description ==

Auto Backup is a modern, lightweight WordPress backup plugin designed for beginners and professionals alike. Create automated backups of your entire WordPress site with just one click.

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

1. Upload the plugin files to `/wp-content/plugins/auto-backup` directory, or install through WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to Auto Backup in your WordPress admin menu
4. Configure your backup settings and create your first backup

== Frequently Asked Questions ==

= Where are backups stored? =

By default, backups are stored in `/wp-content/auto-backups/` directory on your server. Pro version adds cloud storage options.

= How large can my backups be? =

The plugin uses chunked processing to handle sites of any size without memory issues. It's been tested with sites over 1GB.

= Can I restore a backup? =

Yes! One-click restore is included. Simply select a backup and click restore.

= Does this work with WooCommerce? =

Yes, Auto Backup works with WooCommerce and all other WordPress plugins.

= How many backups are kept? =

You can configure retention settings to keep 1-10 backups. Older backups are automatically deleted.

= Does this slow down my site? =

No. Backups run in the background and don't affect your site's performance.

== Screenshots ==

1. Dashboard - Overview of your backup status
2. Backups List - Manage all your backups
3. Create Backup - Choose what to backup
4. Restore Interface - One-click restore
5. Schedule Management - Automated backups
6. Settings - Configure your preferences
7. Health Monitor - Backup reliability score

== Changelog ==

= 1.0.0 =
* Initial release
* Manual and scheduled backups
* Full, partial, and database-only backup types
* One-click restore
* Backup health monitoring
* Retention management
* Comprehensive logging
* Modern React-based UI

== Upgrade Notice ==

= 1.0.0 =
Initial release of Auto Backup. Create your first automated WordPress backup today!
