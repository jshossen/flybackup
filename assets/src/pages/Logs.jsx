import React, { useState, useEffect } from 'react';
import { getLogs } from '../utils/api';
import LoadingSpinner from '../components/LoadingSpinner';
import ConfirmModal from '../components/ConfirmModal';
import NotificationToast from '../components/NotificationToast';

const Logs = () => {
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [clearing, setClearing] = useState(false);
    
    const [confirmModal, setConfirmModal] = useState({
        isOpen: false,
        title: '',
        message: '',
        onConfirm: null,
        danger: false
    });
    
    const [notification, setNotification] = useState({
        isOpen: false,
        type: 'success',
        message: ''
    });

    useEffect(() => {
        loadLogs();
    }, []);

    const loadLogs = async () => {
        try {
            const data = await getLogs({ limit: 100 });
            setLogs(data || []);
        } catch (error) {
            console.error('Error loading logs:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleClearLogs = () => {
        setConfirmModal({
            isOpen: true,
            title: 'Clear All Logs',
            message: 'Are you sure you want to clear all logs? This action cannot be undone.',
            onConfirm: executeClearLogs,
            danger: true
        });
    };
    
    const executeClearLogs = async () => {
        setClearing(true);
        try {
            // Use AJAX instead of REST API
            const formData = new FormData();
            formData.append('action', 'ab_clear_logs');
            formData.append('nonce', window.autoBackupData.nonce);
            
            const response = await fetch(window.autoBackupData.ajaxUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                setNotification({
                    isOpen: true,
                    type: 'success',
                    message: 'Logs cleared successfully!'
                });
                setLogs([]);
            } else {
                setNotification({
                    isOpen: true,
                    type: 'error',
                    message: 'Failed to clear logs: ' + (result.data?.message || 'Unknown error')
                });
            }
        } catch (error) {
            setNotification({
                isOpen: true,
                type: 'error',
                message: 'Failed to clear logs: ' + error.message
            });
        } finally {
            setClearing(false);
        }
    };

    if (loading) return <LoadingSpinner />;

    return (
        <div className="logs-page">
            <div className="page-header">
                <h1>Backup Logs</h1>
                {logs.length > 0 && (
                    <button 
                        className="button button-secondary" 
                        onClick={handleClearLogs}
                        disabled={clearing}
                    >
                        {clearing ? 'Clearing...' : 'Clear All Logs'}
                    </button>
                )}
            </div>

            {logs.length === 0 ? (
                <div className="empty-state">
                    <p>No logs available.</p>
                </div>
            ) : (
                <table className="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        {logs.map(log => (
                            <tr key={log.id}>
                                <td>
                                    <span className={`log-type log-${log.log_type}`}>
                                        {log.log_type}
                                    </span>
                                </td>
                                <td>{log.message}</td>
                                <td>{log.created_at}</td>
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
            
            <NotificationToast
                isOpen={notification.isOpen}
                type={notification.type}
                message={notification.message}
                onClose={() => setNotification(prev => ({ ...prev, isOpen: false }))}
            />
        </div>
    );
};

export default Logs;
