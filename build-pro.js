const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Read version from auto-backup.php
function getPluginVersion() {
    const pluginFile = path.join(__dirname, 'auto-backup.php');
    const content = fs.readFileSync(pluginFile, 'utf8');
    const versionMatch = content.match(/\*\s*Version:\s*([0-9.]+)/);
    
    if (!versionMatch) {
        console.error('❌ Could not find version in auto-backup.php');
        process.exit(1);
    }
    
    return versionMatch[1];
}

// Create ZIP file for Pro version
function createProZip(version) {
    const pluginName = 'auto-backup-pro';
    const zipName = `${pluginName}-${version}.zip`;
    const currentDir = __dirname;
    const buildDir = path.join(currentDir, 'build');
    const zipPath = path.join(buildDir, zipName);
    
    // Create build directory if it doesn't exist
    if (!fs.existsSync(buildDir)) {
        fs.mkdirSync(buildDir, { recursive: true });
        console.log(`📁 Created build directory`);
    }
    
    // Remove old ZIP if exists
    if (fs.existsSync(zipPath)) {
        fs.unlinkSync(zipPath);
        console.log(`🗑️  Removed old ${zipName}`);
    }
    
    // Files and directories to include in the Pro ZIP
    const includeFiles = [
        'auto-backup.php',
        'uninstall.php',
        'readme.txt',
        'README.md',
        'composer.json',
        'includes/',
        'admin/',
        'pro/',              // Pro features included
        'assets/js/',
        'assets/css/',
        'assets/images/',
        'languages/'
    ];
    
    // Files and directories to exclude
    const excludePatterns = [
        'node_modules',
        'assets/src',
        '.git',
        '.gitignore',
        'package.json',
        'package-lock.json',
        'webpack.config.js',
        'build.js',
        'build-pro.js',
        '.DS_Store',
        '*.map'
    ];
    
    console.log(`📦 Building Pro Version: ${zipName}...`);
    console.log(`📌 Version: ${version}`);
    console.log(`⭐ Pro Features: Enabled`);
    
    try {
        // Build the zip command - zip from current directory to build folder
        let zipCommand = `cd "${currentDir}" && zip -r "build/${zipName}"`;
        
        // Add files to include
        includeFiles.forEach(file => {
            zipCommand += ` "${file}"`;
        });
        
        // Add exclusions - also exclude the build folder itself
        zipCommand += ` -x "build/*"`;
        excludePatterns.forEach(pattern => {
            zipCommand += ` -x "${pattern}" "*/${pattern}/*" "*.${pattern}"`;
        });
        
        // Execute zip command
        console.log('\n📝 Creating Pro archive...');
        execSync(zipCommand, { stdio: 'pipe' });
        
        // Verify file was created
        if (!fs.existsSync(zipPath)) {
            throw new Error('Pro ZIP file was not created');
        }
        
        // Get file size
        const stats = fs.statSync(zipPath);
        const fileSizeInMB = (stats.size / (1024 * 1024)).toFixed(2);
        
        console.log(`\n✅ Pro Build complete!`);
        console.log(`📦 File: ${zipPath}`);
        console.log(`📊 Size: ${fileSizeInMB} MB`);
        console.log(`\n🚀 Pro version ready for distribution!`);
        console.log(`\n💡 Pro Features Included:`);
        console.log(`   - Cloud Storage (Google Drive, Dropbox, S3)`);
        console.log(`   - Migration Tool`);
        console.log(`   - Real-time Backup`);
        console.log(`   - License Management`);
        
    } catch (error) {
        console.error('❌ Pro build failed:', error.message);
        process.exit(1);
    }
}

// Main execution
const version = getPluginVersion();
createProZip(version);
