import React, { useState, useEffect } from 'react';
import { getLogs } from '../utils/api';
import LoadingSpinner from '../components/LoadingSpinner';

const Logs = () => {
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [clearing, setClearing] = useState(false);

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

    const handleClearLogs = async () => {
        if (!confirm('Are you sure you want to clear all logs? This cannot be undone.')) return;
        
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
                alert('Logs cleared successfully!');
                setLogs([]);
            } else {
                alert('Failed to clear logs: ' + (result.data?.message || 'Unknown error'));
            }
        } catch (error) {
            alert('Failed to clear logs: ' + error.message);
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
        </div>
    );
};

export default Logs;
