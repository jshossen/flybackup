import React, { useState, useEffect } from 'react';
import { getBackupDetails, getBackup } from '../utils/api';
import LoadingSpinner from '../components/LoadingSpinner';

const BackupDetails = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('backup_id');
    
    const [backup, setBackup] = useState(null);
    const [details, setDetails] = useState(null);
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState('database');

    useEffect(() => {
        if (id) {
            loadData();
        }
    }, [id]);

    const loadData = async () => {
        setLoading(true);
        
        try {
            const backupData = await getBackup(id);
            setBackup(backupData);
        } catch (error) {
            console.error('Error fetching backup:', error);
        }
        
        try {
            const detailsData = await getBackupDetails(id);
            setDetails(detailsData);
        } catch (error) {
            console.error('Error fetching details:', error);
        }
        
        setLoading(false);
    };

    const handleCompareCurrent = () => {
        window.location.href = `?page=flybackup-compare&mode=current&target=${id}`;
    };

    const handleCompareBackup = () => {
        window.location.href = `?page=flybackup-compare&mode=backup&source=${id}`;
    };

    if (loading) return <LoadingSpinner />;
    if (!backup) return <div>Backup not found</div>;

    return (
        <div className="backup-details-page">
            {details?.warning && (
                <div className="notice notice-warning">
                    <p><strong>Warning:</strong> {details.warning}</p>
                </div>
            )}
            
            <div className="page-header">
                <div>
                    <h1>{backup.backup_name || `Backup #${backup.id}`}</h1>
                    <p className="backup-meta">
                        Type: {backup.backup_type} | 
                        Size: {backup.size_formatted} | 
                        Created: {backup.time_ago}
                    </p>
                </div>
                <div className="header-actions">
                    <button 
                        className="button"
                        onClick={handleCompareCurrent}
                    >
                        Compare with Current
                    </button>
                    <button 
                        className="button button-primary"
                        onClick={handleCompareBackup}
                    >
                        Compare with Another Backup
                    </button>
                </div>
            </div>

            <div className="tabs">
                <button 
                    className={`tab ${activeTab === 'database' ? 'active' : ''}`}
                    onClick={() => setActiveTab('database')}
                >
                    Database ({details?.database?.tables?.length || 0} tables)
                </button>
                <button 
                    className={`tab ${activeTab === 'files' ? 'active' : ''}`}
                    onClick={() => setActiveTab('files')}
                >
                    Files ({Object.values(details?.files || {}).reduce((acc, cat) => acc + (cat.count || 0), 0)} items)
                </button>
            </div>

            {activeTab === 'database' && (
                <div className="tab-content">
                    {details?.database?.tables?.length > 0 ? (
                        <table className="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Rows</th>
                                    <th>Size</th>
                                </tr>
                            </thead>
                            <tbody>
                                {details.database.tables.map((table, index) => (
                                    <tr key={index}>
                                        <td><code>{table.name}</code></td>
                                        <td>{table.rows}</td>
                                        <td>{table.size}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <div className="empty-state">
                            <p>No database tables found in this backup.</p>
                        </div>
                    )}
                </div>
            )}

            {activeTab === 'files' && (
                <div className="tab-content">
                    {details?.files && Object.keys(details.files).length > 0 ? (
                        <div className="file-categories">
                            {Object.entries(details.files).map(([category, data]) => (
                                data.count > 0 && (
                                    <div key={category} className="file-category">
                                        <h3>{category.charAt(0).toUpperCase() + category.slice(1)}</h3>
                                        <p className="category-meta">
                                            {data.count} files | {data.size_formatted || data.size}
                                        </p>
                                        <div className="file-list">
                                            {data.files?.slice(0, 10).map((file, idx) => (
                                                <div key={idx} className="file-item">
                                                    <span className="file-name">{file.name}</span>
                                                    <span className="file-size">{file.size}</span>
                                                </div>
                                            ))}
                                            {data.files?.length > 10 && (
                                                <p className="more-files">
                                                    ... and {data.files.length - 10} more files
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                )
                            ))}
                        </div>
                    ) : (
                        <div className="empty-state">
                            <p>No files found in this backup.</p>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

export default BackupDetails;
