# Fly Backup Plugin - Developer Documentation

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Directory Structure](#directory-structure)
3. [Core Components](#core-components)
4. [Database Schema](#database-schema)
5. [REST API Endpoints](#rest-api-endpoints)
6. [Frontend Architecture](#frontend-architecture)
7. [Adding New Features](#adding-new-features)
8. [Code Standards](#code-standards)
9. [Common Tasks](#common-tasks)
10. [Troubleshooting](#troubleshooting)

---

## Architecture Overview

### Technology Stack
- **Backend**: WordPress PHP (OOP)
- **Frontend**: React 18 + WordPress API Fetch
- **Build Tool**: Webpack 5
- **Styling**: SCSS
- **Database**: WordPress wpdb (MySQL)

### Design Pattern
The plugin follows a **modular, class-based architecture** with clear separation of concerns:

```
WordPress Plugin
├── Core Plugin Class (flybackup.php)
├── Database Layer (class-database.php)
├── Business Logic Layer
│   ├── Backup Engine (class-backup-engine.php)
│   ├── Restore Engine (class-restore-engine.php)
│   ├── Scheduler (class-scheduler.php)
│   └── Logger (class-logger.php)
├── API Layer (class-rest-api.php)
└── Frontend (React SPA)
```

### Data Flow

```
User Action (React)
    ↓
REST API Endpoint (class-rest-api.php)
    ↓
Business Logic (Engine Classes)
    ↓
Database Layer (class-database.php)
    ↓
WordPress wpdb (MySQL)
```

---

## Directory Structure

```
flybackup/
├── flybackup.php              # Main plugin file, bootstrap
├── README.md                    # User documentation
├── DEVELOPER_DOCUMENTATION.md   # This file
│
├── includes/                    # PHP Backend
│   ├── class-database.php       # Database operations (CRUD)
│   ├── class-backup-engine.php  # Backup creation logic
│   ├── class-restore-engine.php # Restore logic
│   ├── class-scheduler.php      # Scheduled backups (WP Cron)
│   ├── class-logger.php         # Logging system
│   ├── class-rest-api.php       # REST API endpoints
│   ├── class-zip-manager.php    # ZIP file operations
│   └── helpers.php              # Utility functions
│
├── assets/                      # Frontend
│   ├── src/                     # React source files
│   │   ├── index.js             # Entry point
│   │   ├── App.jsx              # Main React component
│   │   ├── components/          # Reusable components
│   │   │   ├── Button.jsx
│   │   │   ├── LoadingSpinner.jsx
│   │   │   └── ...
│   │   ├── pages/               # Page components
│   │   │   ├── Dashboard.jsx
│   │   │   ├── Backups.jsx
│   │   │   ├── Schedules.jsx
│   │   │   ├── Settings.jsx
│   │   │   └── Logs.jsx
│   │   ├── utils/               # Utilities
│   │   │   └── api.js           # API wrapper functions
│   │   └── styles/              # SCSS styles
│   │       └── main.scss
│   │
│   ├── js/                      # Compiled JavaScript
│   │   └── admin-script.js
│   └── css/                     # Compiled CSS
│       └── admin-style.css
│
├── webpack.config.js            # Webpack build configuration
├── package.json                 # NPM dependencies
└── .gitignore
```

---

## Core Components

### 1. Main Plugin Class (`flybackup.php`)

**Purpose**: Bootstrap the plugin, initialize all components.

**Key Methods**:
- `init()` - Initialize plugin hooks and components
- `load_dependencies()` - Require all PHP files
- `init_components()` - Instantiate all classes

**When to modify**:
- Adding new PHP classes
- Registering new WordPress hooks
- Adding plugin activation/deactivation hooks

**Example - Adding a new component**:
```php
// In flybackup.php
private function load_dependencies() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-new-component.php';
}

private function init_components() {
    $this->new_component = new Fly_Backup_New_Component($this->database, $this->logger);
}
```

---

### 2. Database Class (`includes/class-database.php`)

**Purpose**: All database operations (CRUD) for backups, schedules, and logs.

**Tables**:
- `wp_fly_backup_backups` - Backup records
- `wp_fly_backup_schedules` - Scheduled backup configurations
- `wp_fly_backup_logs` - Activity logs

**Key Methods**:
```php
// Backups
create_backup($data)
get_backup($id)
get_backups($args)
update_backup($id, $data)
delete_backup($id)

// Schedules
create_schedule($data)
get_schedule($id)
get_schedules()
update_schedule($id, $data)
delete_schedule($id)

// Logs
create_log($data)
get_logs($args)
clear_logs()
```

**When to modify**:
- Adding new database tables
- Adding new CRUD operations
- Modifying table schemas

**Example - Adding a new table**:
```php
public function create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ab_new_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        field_name varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

public function create_new_record($data) {
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'ab_new_table',
        $data,
        array('%s', '%s')
    );
    return $wpdb->insert_id;
}
```

---

### 3. Backup Engine (`includes/class-backup-engine.php`)

**Purpose**: Create backup ZIP files containing database and files.

**Key Methods**:
```php
create_backup($type, $items)      // Main backup creation
backup_database($backup_id)        // Export database to SQL
backup_files($backup_id, $path)    // Add files to ZIP
get_backup_items($type, $items)    // Determine what to backup
download_backup($id)               // Send ZIP to browser
```

**Backup Process Flow**:
```
1. create_backup() called
2. Create ZIP file
3. backup_database() - Export SQL with DROP/CREATE/INSERT
4. backup_files() - Add selected files/folders to ZIP
5. Close ZIP
6. Update database record with size/status
7. Log completion
```

**SQL Export Format**:
```sql
-- WordPress Database Backup
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;

-- Table: wp_posts
DROP TABLE IF EXISTS `wp_posts`;
CREATE TABLE `wp_posts` (...);
INSERT INTO `wp_posts` (`ID`, `post_title`, ...) VALUES
(1, 'Hello World', ...),
(2, 'Sample Page', ...);

SET FOREIGN_KEY_CHECKS = 1;
```

**When to modify**:
- Adding new backup types
- Changing SQL export format
- Adding file compression options
- Implementing incremental backups

**Example - Adding custom backup type**:
```php
private function get_backup_items($type, $items) {
    $backup_items = array();
    
    if ($type === 'custom_type') {
        $backup_items['custom_folder'] = WP_CONTENT_DIR . '/custom';
    }
    
    return apply_filters('fly_backup_backup_items', $backup_items, $type, $items);
}
```

---

### 4. Restore Engine (`includes/class-restore-engine.php`)

**Purpose**: Restore backups by extracting ZIP and importing data.

**Key Methods**:
```php
restore_backup($id, $items)        // Main restore function
restore_database($sql_file, $id)   // Import SQL file
restore_files($source, $dest)      // Extract files
```

**Restore Process Flow**:
```
1. restore_backup() called
2. Extract ZIP to temp directory
3. restore_database() - Parse and execute SQL line by line
   - Skip plugin tables (wp_fly_backup_backups, wp_fly_backup_logs, wp_fly_backup_schedules)
   - Execute DROP TABLE IF EXISTS
   - Execute CREATE TABLE
   - Execute INSERT statements
4. restore_files() - Copy files to original locations
5. Clean up temp directory
6. Log completion
```

**Important**: Plugin tables are SKIPPED during restore to preserve current backups!

**When to modify**:
- Adding selective restore options
- Implementing restore verification
- Adding rollback functionality

**Example - Adding restore validation**:
```php
private function validate_restore($sql_file) {
    if (!file_exists($sql_file)) {
        throw new Exception('SQL file not found');
    }
    
    $content = file_get_contents($sql_file);
    if (strpos($content, 'DROP TABLE') === false) {
        throw new Exception('Invalid SQL format');
    }
    
    return true;
}
```

---

### 5. Scheduler (`includes/class-scheduler.php`)

**Purpose**: Manage scheduled backups using WordPress Cron.

**Key Methods**:
```php
create_schedule($name, $freq, $type, $items)
schedule_cron($id, $frequency, $next_run)
run_scheduled_backup($schedule_id)
calculate_next_run($frequency)
```

**Cron Integration**:
```php
// Register cron hook
add_action('fly_backup_run_schedule', array($this, 'run_scheduled_backup'));

// Schedule event
wp_schedule_event($timestamp, $recurrence, 'fly_backup_run_schedule', array($schedule_id));
```

**Supported Frequencies**:
- `hourly` - Every hour
- `daily` - Once per day
- `weekly` - Once per week
- `monthly` - Once per month

**When to modify**:
- Adding new schedule frequencies
- Implementing schedule conditions (e.g., only if changes detected)
- Adding schedule notifications

---

### 6. REST API (`includes/class-rest-api.php`)

**Purpose**: Expose backend functionality to React frontend.

**Namespace**: `flybackup/v1`

**Endpoint Structure**:
```php
register_rest_route('flybackup/v1', '/endpoint', array(
    'methods' => 'GET|POST|PUT|DELETE',
    'callback' => array($this, 'method_name'),
    'permission_callback' => array($this, 'check_permission')
));
```

**All Endpoints**: See [REST API Endpoints](#rest-api-endpoints) section.

**When to modify**:
- Adding new API endpoints
- Changing request/response formats
- Adding API authentication

**Example - Adding new endpoint**:
```php
public function register_routes() {
    register_rest_route('flybackup/v1', '/custom-action', array(
        'methods' => 'POST',
        'callback' => array($this, 'custom_action'),
        'permission_callback' => array($this, 'check_permission')
    ));
}

public function custom_action($request) {
    $params = $request->get_json_params();
    
    // Your logic here
    $result = $this->some_component->do_something($params);
    
    if ($result['success']) {
        return new WP_REST_Response($result, 200);
    } else {
        return new WP_Error('error_code', $result['message'], array('status' => 500));
    }
}
```

---

### 7. Logger (`includes/class-logger.php`)

**Purpose**: Log all plugin activities for debugging and audit.

**Log Levels**:
- `info` - General information
- `warning` - Non-critical issues
- `error` - Critical errors

**Key Methods**:
```php
info($message, $backup_id = null)
warning($message, $backup_id = null)
error($message, $backup_id = null)
```

**Usage Example**:
```php
$this->logger->info('Backup started', $backup_id);
$this->logger->warning('Large file detected: ' . $file, $backup_id);
$this->logger->error('Backup failed: ' . $error, $backup_id);
```

---

## Database Schema

### Table: `wp_fly_backup_backups`

```sql
CREATE TABLE wp_fly_backup_backups (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    backup_name varchar(255) NOT NULL,
    backup_type varchar(50) NOT NULL,           -- 'full', 'database', 'partial'
    file_path varchar(500) NOT NULL,
    file_size bigint(20) DEFAULT 0,
    included_items text,                        -- JSON array of items
    status varchar(50) DEFAULT 'pending',       -- 'pending', 'in_progress', 'completed', 'failed'
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    completed_at datetime DEFAULT NULL,
    PRIMARY KEY (id),
    KEY status (status),
    KEY created_at (created_at)
);
```

### Table: `wp_fly_backup_schedules`

```sql
CREATE TABLE wp_fly_backup_schedules (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    schedule_name varchar(255) NOT NULL,
    frequency varchar(50) NOT NULL,             -- 'hourly', 'daily', 'weekly', 'monthly'
    backup_type varchar(50) NOT NULL,
    included_items text,                        -- JSON array
    next_run datetime NOT NULL,
    last_run datetime DEFAULT NULL,
    status varchar(50) DEFAULT 'active',        -- 'active', 'paused', 'disabled'
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY status (status),
    KEY next_run (next_run)
);
```

### Table: `wp_fly_backup_logs`

```sql
CREATE TABLE wp_fly_backup_logs (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    backup_id bigint(20) DEFAULT NULL,
    level varchar(20) NOT NULL,                 -- 'info', 'warning', 'error'
    message text NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY backup_id (backup_id),
    KEY level (level),
    KEY created_at (created_at)
);
```

---

## REST API Endpoints

### Backups

| Method | Endpoint | Description | Parameters |
|--------|----------|-------------|------------|
| GET | `/backups` | List all backups | `limit`, `offset`, `status` |
| GET | `/backups/{id}` | Get single backup | - |
| POST | `/backups` | Create new backup | `type`, `items` |
| DELETE | `/backups/{id}` | Delete backup | - |
| POST | `/backups/{id}/restore` | Restore backup | `items` |
| GET | `/backups/{id}/download` | Download backup ZIP | - |

### Schedules

| Method | Endpoint | Description | Parameters |
|--------|----------|-------------|------------|
| GET | `/schedules` | List all schedules | - |
| GET | `/schedules/{id}` | Get single schedule | - |
| POST | `/schedules` | Create schedule | `schedule_name`, `frequency`, `backup_type`, `items` |
| PUT | `/schedules/{id}` | Update schedule | Same as POST |
| DELETE | `/schedules/{id}` | Delete schedule | - |

### Logs

| Method | Endpoint | Description | Parameters |
|--------|----------|-------------|------------|
| GET | `/logs` | List logs | `limit`, `offset`, `level`, `backup_id` |

### Settings

| Method | Endpoint | Description | Parameters |
|--------|----------|-------------|------------|
| GET | `/settings` | Get all settings | - |
| POST | `/settings` | Update settings | `max_backups`, `backup_items`, etc. |

### Health & Stats

| Method | Endpoint | Description | Parameters |
|--------|----------|-------------|------------|
| GET | `/health` | System health check | - |
| GET | `/stats` | Dashboard statistics | - |

---

## Frontend Architecture

### Technology
- **React 18** with Hooks
- **React Router** for navigation
- **WordPress API Fetch** for API calls
- **SCSS** for styling

### Component Structure

```
App.jsx (Root)
├── Dashboard.jsx
├── Backups.jsx
│   └── Button.jsx (reusable)
├── Schedules.jsx
│   ├── Button.jsx
│   └── Modal (inline)
├── Settings.jsx
└── Logs.jsx
    └── LoadingSpinner.jsx (reusable)
```

### State Management

**Local State** (useState):
- Component-specific data
- Form inputs
- UI state (loading, modals)

**No Global State**: Each page fetches its own data from API.

### API Layer (`assets/src/utils/api.js`)

All API calls are wrapped in utility functions:

```javascript
import apiFetch from '@wordpress/api-fetch';

export const getBackups = async (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return await apiFetch({ 
        path: `/flybackup/v1/backups${query ? '?' + query : ''}` 
    });
};

export const createBackup = async (data) => {
    return await apiFetch({
        path: '/flybackup/v1/backups',
        method: 'POST',
        data
    });
};
```

### Styling Architecture

**File**: `assets/src/styles/main.scss`

**Structure**:
```scss
// Variables
$primary-color: #2271b1;
$danger-color: #d63638;

// Base styles
.flybackup-admin { ... }

// Components
.button { ... }
.modal-overlay { ... }

// Pages
.dashboard-page { ... }
.backups-page { ... }
```

**BEM-like naming**:
```scss
.button {
    &--primary { ... }
    &--danger { ... }
    &--small { ... }
}
```

---

## Adding New Features

### Example: Adding a "Backup Notes" Feature

#### Step 1: Update Database Schema

**File**: `includes/class-database.php`

```php
public function create_tables() {
    global $wpdb;
    
    // Add column to existing table
    $wpdb->query("ALTER TABLE {$wpdb->prefix}fly_backup_backups 
                  ADD COLUMN notes text DEFAULT NULL");
}

public function update_backup($id, $data) {
    global $wpdb;
    
    $allowed_fields = array('backup_name', 'status', 'notes'); // Add 'notes'
    // ... rest of method
}
```

#### Step 2: Update Backup Engine

**File**: `includes/class-backup-engine.php`

```php
public function create_backup($type, $items = array(), $notes = '') {
    // ... existing code
    
    $backup_data = array(
        'backup_name' => $backup_name,
        'backup_type' => $type,
        'file_path' => $file_path,
        'included_items' => json_encode($items),
        'notes' => $notes,  // Add notes
        'status' => 'in_progress'
    );
    
    // ... rest of method
}
```

#### Step 3: Update REST API

**File**: `includes/class-rest-api.php`

```php
public function create_backup($request) {
    $params = $request->get_json_params();
    
    $type = isset($params['type']) ? sanitize_text_field($params['type']) : 'full';
    $items = isset($params['items']) ? array_map('sanitize_text_field', $params['items']) : array();
    $notes = isset($params['notes']) ? sanitize_textarea_field($params['notes']) : ''; // Add notes
    
    $result = $this->backup_engine->create_backup($type, $items, $notes);
    
    // ... rest of method
}
```

#### Step 4: Update Frontend API

**File**: `assets/src/utils/api.js`

```javascript
export const createBackup = async (data) => {
    return await apiFetch({
        path: '/flybackup/v1/backups',
        method: 'POST',
        data  // data now includes { type, items, notes }
    });
};
```

#### Step 5: Update React Component

**File**: `assets/src/pages/Backups.jsx`

```javascript
const [notes, setNotes] = useState('');

const handleCreateBackup = async () => {
    setCreating(true);
    try {
        await createBackup({
            type: backupType,
            items: selectedItems,
            notes: notes  // Add notes
        });
        alert('Backup created!');
        setNotes('');  // Reset
        loadBackups();
    } catch (error) {
        alert('Failed: ' + error.message);
    } finally {
        setCreating(false);
    }
};

// In JSX
<textarea
    value={notes}
    onChange={(e) => setNotes(e.target.value)}
    placeholder="Add notes about this backup..."
/>
```

#### Step 6: Display Notes in List

```javascript
{backups.map(backup => (
    <tr key={backup.id}>
        <td>{backup.backup_name}</td>
        <td>{backup.notes || '-'}</td>
        {/* ... other columns */}
    </tr>
))}
```

---

## Code Standards

### PHP Standards

**Naming Conventions**:
```php
// Classes: PascalCase with prefix
class Fly_Backup_Component_Name { }

// Methods: snake_case
public function create_backup() { }

// Variables: snake_case
$backup_id = 123;

// Constants: UPPER_SNAKE_CASE
define('FLY_BACKUP_VERSION', '1.0.0');
```

**Documentation**:
```php
/**
 * Create a new backup
 *
 * @param string $type Backup type (full, database, partial)
 * @param array $items Items to include in backup
 * @return array Result with success status and message
 */
public function create_backup($type, $items = array()) {
    // Implementation
}
```

**Error Handling**:
```php
try {
    $result = $this->backup_engine->create_backup($type, $items);
} catch (Exception $e) {
    $this->logger->error('Backup failed: ' . $e->getMessage());
    return array('success' => false, 'message' => $e->getMessage());
}
```

### JavaScript Standards

**Naming Conventions**:
```javascript
// Components: PascalCase
const BackupList = () => { };

// Functions: camelCase
const handleCreateBackup = () => { };

// Variables: camelCase
const backupId = 123;

// Constants: UPPER_SNAKE_CASE
const API_NAMESPACE = 'flybackup/v1';
```

**Component Structure**:
```javascript
import React, { useState, useEffect } from 'react';

const ComponentName = () => {
    // State
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    
    // Effects
    useEffect(() => {
        loadData();
    }, []);
    
    // Handlers
    const handleAction = async () => {
        // Implementation
    };
    
    // Render
    return (
        <div className="component-name">
            {/* JSX */}
        </div>
    );
};

export default ComponentName;
```

---

## Common Tasks

### Adding a New Page

1. **Create page component**: `assets/src/pages/NewPage.jsx`
2. **Add route in App.jsx**:
```javascript
<Route path="/new-page" element={<NewPage />} />
```
3. **Add navigation link**:
```javascript
<a href="#/new-page">New Page</a>
```
4. **Rebuild assets**: `npm run build:assets`

### Adding a New Database Table

1. **Update `class-database.php`**:
```php
public function create_tables() {
    $sql = "CREATE TABLE {$wpdb->prefix}ab_new_table (...)";
    dbDelta($sql);
}
```
2. **Add CRUD methods**:
```php
public function create_record($data) { }
public function get_record($id) { }
public function update_record($id, $data) { }
public function delete_record($id) { }
```
3. **Deactivate and reactivate plugin** to run table creation

### Adding a New REST Endpoint

1. **Register route in `class-rest-api.php`**:
```php
register_rest_route('flybackup/v1', '/endpoint', array(
    'methods' => 'POST',
    'callback' => array($this, 'method_name'),
    'permission_callback' => array($this, 'check_permission')
));
```
2. **Add method**:
```php
public function method_name($request) {
    $params = $request->get_json_params();
    // Logic
    return new WP_REST_Response($result, 200);
}
```
3. **Add API wrapper in `api.js`**:
```javascript
export const newAction = async (data) => {
    return await apiFetch({
        path: '/flybackup/v1/endpoint',
        method: 'POST',
        data
    });
};
```

### Adding a New Backup Type

1. **Update `get_backup_items()` in `class-backup-engine.php`**
2. **Add UI option in Settings.jsx**
3. **Update backup creation form in Backups.jsx**

### Modifying SQL Export Format

**File**: `includes/class-backup-engine.php`

**Method**: `backup_database()`

Modify the SQL generation logic to change format.

---

## Troubleshooting

### Common Issues

**Issue**: Backup fails silently
- **Check**: Logs table for error messages
- **Check**: PHP error log
- **Check**: Disk space and permissions

**Issue**: Restore shows duplicate entry errors
- **Solution**: Ensure `DROP TABLE IF EXISTS` is in SQL
- **Check**: `restore_database()` method properly parses SQL

**Issue**: Scheduled backups not running
- **Check**: WordPress Cron is working (`wp cron event list`)
- **Check**: Schedule status is 'active'
- **Check**: `next_run` time is in the future

**Issue**: Frontend not updating after code changes
- **Solution**: Run `npm run build:assets`
- **Solution**: Clear browser cache
- **Solution**: Check browser console for errors

### Debug Mode

Enable WordPress debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at: `wp-content/debug.log`

### Database Queries

View all plugin tables:
```sql
SHOW TABLES LIKE 'wp_ab_%';
```

Check backup records:
```sql
SELECT * FROM wp_fly_backup_backups ORDER BY created_at DESC LIMIT 10;
```

Check logs:
```sql
SELECT * FROM wp_fly_backup_logs WHERE level = 'error' ORDER BY created_at DESC LIMIT 20;
```

---

## Build Commands

### Development
```bash
npm run watch        # Watch for changes and rebuild
npm run dev          # Development build (not minified)
```

### Production
```bash
npm run build:assets # Production build (minified)
```

### Linting
```bash
npm run lint         # Check code style
```

---

## Plugin Hooks & Filters

### Actions

```php
// Before backup starts
do_action('fly_backup_before_backup', $backup_id, $type);

// After backup completes
do_action('fly_backup_after_backup', $backup_id, $result);

// Before restore starts
do_action('fly_backup_before_restore', $backup_id);

// After restore completes
do_action('fly_backup_after_restore', $backup_id, $result);
```

### Filters

```php
// Modify backup items
apply_filters('fly_backup_backup_items', $items, $type, $selected_items);

// Modify backup filename
apply_filters('fly_backup_filename', $filename, $type);

// Modify max backups to keep
apply_filters('fly_backup_max_backups', $max_backups);
```

### Usage Example

```php
// In your theme or another plugin
add_filter('fly_backup_backup_items', function($items, $type) {
    if ($type === 'full') {
        $items['custom_dir'] = WP_CONTENT_DIR . '/custom';
    }
    return $items;
}, 10, 2);
```

---

## Security Considerations

### Permissions
- All REST endpoints check `manage_options` capability
- File operations validate paths to prevent directory traversal
- SQL queries use prepared statements

### Nonces
- AJAX requests use WordPress nonces
- REST API uses WordPress authentication

### File Access
- Backup directory is outside web root when possible
- `.htaccess` file prevents direct access to backups
- Download endpoint validates user permissions

### SQL Injection Prevention
```php
// Always use wpdb methods
$wpdb->prepare("SELECT * FROM table WHERE id = %d", $id);

// Never concatenate user input
// BAD: $wpdb->query("SELECT * FROM table WHERE id = " . $_GET['id']);
```

---

## Performance Optimization

### Large Backups
- Use batch processing for large databases
- Stream ZIP file creation instead of loading in memory
- Set PHP memory limit: `ini_set('memory_limit', '512M');`
- Increase max execution time: `set_time_limit(300);`

### Database Queries
- Use indexes on frequently queried columns
- Limit result sets with `LIMIT` clause
- Use `get_results()` instead of multiple `get_row()` calls

### Frontend
- Lazy load components
- Debounce search inputs
- Paginate large lists
- Cache API responses when appropriate

---

## Testing Checklist

### Before Release
- [ ] Create backup (all types: full, database, partial)
- [ ] Restore backup and verify data integrity
- [ ] Create schedule and verify it runs
- [ ] Delete backup and verify file is removed
- [ ] Download backup and verify ZIP is valid
- [ ] Check logs for errors
- [ ] Test with low disk space
- [ ] Test with large database (>100MB)
- [ ] Test on different PHP versions (7.4, 8.0, 8.1)
- [ ] Test on different WordPress versions
- [ ] Verify plugin tables are skipped during restore
- [ ] Test all settings save correctly

---

## Version History & Migration

### Database Migrations

When updating table schema:

```php
// In class-database.php
public function migrate_to_version_2() {
    global $wpdb;
    
    $current_version = get_option('fly_backup_db_version', '1.0');
    
    if (version_compare($current_version, '2.0', '<')) {
        // Add new column
        $wpdb->query("ALTER TABLE {$wpdb->prefix}fly_backup_backups 
                      ADD COLUMN new_field varchar(255) DEFAULT NULL");
        
        update_option('fly_backup_db_version', '2.0');
    }
}
```

Call in plugin activation hook.

---

## Support & Resources

### WordPress Codex
- [Plugin API](https://codex.wordpress.org/Plugin_API)
- [wpdb Class](https://developer.wordpress.org/reference/classes/wpdb/)
- [REST API](https://developer.wordpress.org/rest-api/)

### React Documentation
- [React Hooks](https://react.dev/reference/react)
- [WordPress API Fetch](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/)

### Tools
- [WP-CLI](https://wp-cli.org/) - Command line interface
- [Query Monitor](https://wordpress.org/plugins/query-monitor/) - Debug plugin
- [React DevTools](https://react.dev/learn/react-developer-tools) - Browser extension

---

## Contributing Guidelines

### Code Review Checklist
- [ ] Follows naming conventions
- [ ] Includes PHPDoc/JSDoc comments
- [ ] No hardcoded values (use constants/settings)
- [ ] Error handling implemented
- [ ] Logging added for important actions
- [ ] Security checks in place
- [ ] Tested on local environment
- [ ] No console.log() in production code
- [ ] SCSS follows BEM-like structure

### Git Commit Messages
```
feat: Add backup notes feature
fix: Resolve duplicate entry error on restore
refactor: Improve SQL export performance
docs: Update developer documentation
style: Fix button component styling
```

---

## Contact & Maintenance

For questions or issues:
1. Check this documentation
2. Review code comments
3. Check WordPress debug log
4. Review browser console
5. Contact plugin maintainer

**Last Updated**: May 14, 2026
**Plugin Version**: 1.0.0
**WordPress Compatibility**: 5.8+
**PHP Compatibility**: 7.4+
