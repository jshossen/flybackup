import React, { useState, useEffect } from 'react';
import { getBackups, restoreBackup } from '../utils/api';
import LoadingSpinner from '../components/LoadingSpinner';

const Restore = () => {
    const [backups, setBackups] = useState([]);
    const [loading, setLoading] = useState(true);
    const [restoring, setRestoring] = useState(false);

    useEffect(() => {
        loadBackups();
    }, []);

    const loadBackups = async () => {
        try {
            const data = await getBackups({ status: 'completed' });
            setBackups(data.backups || []);
        } catch (error) {
            console.error('Error loading backups:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleRestore = async (id) => {
        if (!confirm('Are you sure you want to restore this backup? This will overwrite your current site.')) return;
        
        setRestoring(true);
        try {
            await restoreBackup(id);
            alert('Backup restored successfully!');
        } catch (error) {
            alert('Failed to restore backup: ' + error.message);
        } finally {
            setRestoring(false);
        }
    };

    if (loading) return <LoadingSpinner />;

    return (
        <div className="restore-page">
            <h1>Restore Backup</h1>
            <p>Select a backup to restore. This will overwrite your current site data.</p>

            {backups.length === 0 ? (
                <div className="empty-state">
                    <p>No backups available for restore.</p>
                </div>
            ) : (
                <div className="backup-list">
                    {backups.map(backup => (
                        <div key={backup.id} className="backup-item">
                            <div className="backup-info">
                                <h3>{backup.backup_name}</h3>
                                <p>Created: {backup.time_ago} | Size: {backup.size_formatted}</p>
                            </div>
                            <button 
                                className="button button-primary"
                                onClick={() => handleRestore(backup.id)}
                                disabled={restoring}
                            >
                                {restoring ? 'Restoring...' : 'Restore'}
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default Restore;
