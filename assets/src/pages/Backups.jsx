import React, { useState, useEffect } from 'react';
import { getBackups, createBackup, deleteBackup, getSettings } from '../utils/api';
import LoadingSpinner from '../components/LoadingSpinner';
import Button from '../components/Button';

const Backups = () => {
    const [backups, setBackups] = useState([]);
    const [loading, setLoading] = useState(true);
    const [creating, setCreating] = useState(false);

    useEffect(() => {
        loadBackups();
    }, []);

    const loadBackups = async () => {
        try {
            const data = await getBackups();
            setBackups(data.backups || []);
        } catch (error) {
            console.error('Error loading backups:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleCreateBackup = async () => {
        if (!confirm('Create a new backup?')) return;
        
        setCreating(true);
        try {
            // Get settings to determine what to backup
            const settingsData = await getSettings();
            const backupItems = settingsData?.settings?.backup_items || {};
            
            // Build items array based on settings
            const items = [];
            if (backupItems.database !== false) items.push('database');
            if (backupItems.uploads !== false) items.push('uploads');
            if (backupItems.plugins !== false) items.push('plugins');
            if (backupItems.themes !== false) items.push('themes');
            if (backupItems.wp_config !== false) items.push('wp-config');
            
            await createBackup({ type: 'full', items });
            alert('Backup created successfully!');
            loadBackups();
        } catch (error) {
            alert('Failed to create backup: ' + error.message);
        } finally {
            setCreating(false);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this backup?')) return;
        
        try {
            await deleteBackup(id);
            alert('Backup deleted successfully!');
            loadBackups();
        } catch (error) {
            alert('Failed to delete backup: ' + error.message);
        }
    };

    const handleDownload = (backup) => {
        // Use admin-ajax.php for authenticated downloads
        const downloadUrl = `${window.autoBackupData.ajaxUrl}?action=ab_download_backup&backup_id=${backup.id}&nonce=${window.autoBackupData.nonce}`;
        window.location.href = downloadUrl;
    };

    if (loading) return <LoadingSpinner />;

    return (
        <div className="backups-page">
            <div className="page-header">
                <h1>Backups</h1>
                <button 
                    className="button button-primary" 
                    onClick={handleCreateBackup}
                    disabled={creating}
                >
                    {creating ? 'Creating...' : 'Create Backup'}
                </button>
            </div>

            {backups.length === 0 ? (
                <div className="empty-state">
                    <p>No backups found. Create your first backup!</p>
                </div>
            ) : (
                <table className="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {backups.map(backup => (
                            <tr key={backup.id}>
                                <td>{backup.backup_name}</td>
                                <td>{backup.backup_type}</td>
                                <td>{backup.size_formatted}</td>
                                <td>{backup.time_ago}</td>
                                <td>
                                    <span className={`status-badge status-${backup.status}`}>
                                        {backup.status}
                                    </span>
                                </td>
                                <td>
                                    <div style={{display: 'flex', gap: '8px'}}>
                                        <Button
                                            variant="secondary"
                                            size="small"
                                            icon={<span className="dashicons dashicons-download"></span>}
                                            onClick={() => handleDownload(backup)}
                                        >
                                            Download
                                        </Button>
                                        <Button
                                            variant="danger"
                                            size="small"
                                            onClick={() => handleDelete(backup.id)}
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
};

export default Backups;
