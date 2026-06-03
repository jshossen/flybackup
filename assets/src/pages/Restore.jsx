import React, { useState, useEffect } from 'react';
import { getBackups, restoreBackup } from '../utils/api';
import LoadingSpinner from '../components/LoadingSpinner';
import ConfirmModal from '../components/ConfirmModal';
import ProgressModal from '../components/ProgressModal';
import NotificationToast from '../components/NotificationToast';

const Restore = () => {
    const [backups, setBackups] = useState([]);
    const [loading, setLoading] = useState(true);
    const [restoring, setRestoring] = useState(false);
    
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
            const data = await getBackups({ status: 'completed' });
            setBackups(data.backups || []);
        } catch (error) {
            console.error('Error loading backups:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleRestore = (id) => {
        setConfirmModal({
            isOpen: true,
            title: 'Restore Backup',
            message: 'Are you sure you want to restore this backup? This will overwrite your current site data. This action cannot be undone.',
            onConfirm: () => executeRestore(id),
            danger: true
        });
    };
    
    const executeRestore = async (id) => {
        setRestoring(true);
        
        const steps = [
            'Validating backup...',
            'Creating safety snapshot...',
            'Restoring database...',
            'Restoring files...',
            'Finalizing...'
        ];
        
        setProgressModal({
            isOpen: true,
            title: 'Restoring Backup',
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
                
                // Actual restore happens on step 2
                if (i === 2) {
                    await restoreBackup(id);
                }
                
                // Small delay between steps for UX
                await new Promise(resolve => setTimeout(resolve, 800));
            }
            
            // Complete
            setProgressModal(prev => ({
                ...prev,
                currentStep: steps.length,
                progress: 100,
                message: 'Restore completed successfully!'
            }));
            
            await new Promise(resolve => setTimeout(resolve, 1000));
            setProgressModal(prev => ({ ...prev, isOpen: false }));
            
            setNotification({
                isOpen: true,
                type: 'success',
                message: 'Backup restored successfully! Your site has been restored.'
            });
        } catch (error) {
            setProgressModal(prev => ({ ...prev, isOpen: false }));
            setNotification({
                isOpen: true,
                type: 'error',
                message: 'Failed to restore backup: ' + error.message
            });
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

export default Restore;
