import React, { useState, useEffect } from 'react';
import { getBackups, createBackup, deleteBackup, getSettings } from '../utils/api';
import LoadingSpinner from '../components/LoadingSpinner';
import Button from '../components/Button';
import ConfirmModal from '../components/ConfirmModal';
import ProgressModal from '../components/ProgressModal';
import NotificationToast from '../components/NotificationToast';

const Backups = () => {
    const [backups, setBackups] = useState([]);
    const [loading, setLoading] = useState(true);
    const [creating, setCreating] = useState(false);
    
    const [confirmModal, setConfirmModal] = useState({
        isOpen: false,
        title: '',
        message: '',
        onConfirm: null,
        danger: false
    });
    
    const [progressModal, setProgressModal] = useState({
        isOpen: false,
        title: '',
        steps: [],
        currentStep: 0,
        progress: 0,
        message: ''
    });
    
    const [notification, setNotification] = useState({
        isOpen: false,
        type: 'success',
        message: ''
    });

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

    const handleCreateBackup = () => {
        setConfirmModal({
            isOpen: true,
            title: 'Create Backup',
            message: 'Are you sure you want to create a new backup?',
            onConfirm: executeBackupCreation,
            danger: false
        });
    };
    
    const executeBackupCreation = async () => {
        setCreating(true);
        
        const steps = [
            'Preparing backup...',
            'Backing up database...',
            'Backing up files...',
            'Uploading to cloud...',
            'Finalizing...'
        ];
        
        setProgressModal({
            isOpen: true,
            title: 'Creating Backup',
            steps,
            currentStep: 0,
            progress: 0,
            message: steps[0]
        });
        
        try {
            // Simulate progress steps
            for (let i = 0; i < steps.length; i++) {
                setProgressModal(prev => ({
                    ...prev,
                    currentStep: i,
                    progress: (i / steps.length) * 100,
                    message: steps[i]
                }));
                
                // Actual backup happens on step 1
                if (i === 1) {
                    const settingsData = await getSettings();
                    const backupItems = settingsData?.settings?.backup_items || {};
                    
                    const items = [];
                    if (backupItems.database !== false) items.push('database');
                    if (backupItems.uploads !== false) items.push('uploads');
                    if (backupItems.plugins !== false) items.push('plugins');
                    if (backupItems.themes !== false) items.push('themes');
                    if (backupItems.wp_config !== false) items.push('wp-config');
                    
                    await createBackup({ type: 'full', items });
                }
                
                // Small delay between steps for UX
                await new Promise(resolve => setTimeout(resolve, 500));
            }
            
            // Complete
            setProgressModal(prev => ({
                ...prev,
                currentStep: steps.length,
                progress: 100,
                message: 'Backup created successfully!'
            }));
            
            await new Promise(resolve => setTimeout(resolve, 1000));
            setProgressModal(prev => ({ ...prev, isOpen: false }));
            
            setNotification({
                isOpen: true,
                type: 'success',
                message: 'Backup created successfully!'
            });
            
            loadBackups();
        } catch (error) {
            setProgressModal(prev => ({ ...prev, isOpen: false }));
            setNotification({
                isOpen: true,
                type: 'error',
                message: 'Failed to create backup: ' + error.message
            });
        } finally {
            setCreating(false);
        }
    };

    const handleDelete = (id) => {
        setConfirmModal({
            isOpen: true,
            title: 'Delete Backup',
            message: 'Are you sure you want to delete this backup? This action cannot be undone.',
            onConfirm: () => executeDelete(id),
            danger: true
        });
    };
    
    const executeDelete = async (id) => {
        try {
            await deleteBackup(id);
            setNotification({
                isOpen: true,
                type: 'success',
                message: 'Backup deleted successfully!'
            });
            loadBackups();
        } catch (error) {
            setNotification({
                isOpen: true,
                type: 'error',
                message: 'Failed to delete backup: ' + error.message
            });
        }
    };

    const handleDownload = (backup) => {
        // Use admin-ajax.php for authenticated downloads
        const downloadUrl = `${window.flybackupData.ajaxUrl}?action=flybackup_download_backup&backup_id=${backup.id}&nonce=${window.flybackupData.nonce}`;
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
                            <th>Cloud</th>
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
                                    {backup.cloud_storage && (
                                        <span 
                                            className="dashicons dashicons-cloud" 
                                            title={`Stored in ${backup.cloud_storage.provider}`}
                                            style={{ color: '#46b450' }}
                                        ></span>
                                    )}
                                </td>
                                <td>
                                    <div className="backup-actions">
                                        <a 
                                            href={`?page=flybackup-backup-details&backup_id=${backup.id}`}
                                            className="button button-small action-details"
                                            title="View Details"
                                        >
                                            Details
                                        </a>
                                        <button
                                            className="button button-small action-download"
                                            title="Download"
                                            onClick={() => handleDownload(backup)}
                                        >
                                            <span className="dashicons dashicons-download"></span>
                                        </button>
                                        <button
                                            className="button button-small action-delete"
                                            title="Delete"
                                            onClick={() => handleDelete(backup.id)}
                                        >
                                            <span className="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
            
            <ConfirmModal
                isOpen={confirmModal.isOpen}
                title={confirmModal.title}
                message={confirmModal.message}
                onConfirm={confirmModal.onConfirm}
                onCancel={() => setConfirmModal(prev => ({ ...prev, isOpen: false }))}
                danger={confirmModal.danger}
            />
            
            <ProgressModal
                isOpen={progressModal.isOpen}
                title={progressModal.title}
                steps={progressModal.steps}
                currentStep={progressModal.currentStep}
                progress={progressModal.progress}
                message={progressModal.message}
            />
            
            <NotificationToast
                isOpen={notification.isOpen}
                type={notification.type}
                message={notification.message}
                onClose={() => setNotification(prev => ({ ...prev, isOpen: false }))}
            />
        </div>
    );
};

export default Backups;
