import React, { useState, useEffect } from 'react';
import Dashboard from './pages/Dashboard';
import Backups from './pages/Backups';
import BackupDetails from './pages/BackupDetails';
import Compare from './pages/Compare';
import Restore from './pages/Restore';
import Schedules from './pages/Schedules';
import Settings from './pages/Settings';
import Logs from './pages/Logs';
import CloudSettings from './pages/CloudSettings';

const App = () => {
    const [currentPage, setCurrentPage] = useState('auto-backup');

    useEffect(() => {
        if (window.autoBackupData && window.autoBackupData.currentPage) {
            setCurrentPage(window.autoBackupData.currentPage);
        }
    }, []);

    const renderPage = () => {
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page');
        const backupId = urlParams.get('backup_id');
        
        switch (page || currentPage) {
            case 'auto-backup-backup-details':
                return backupId ? <BackupDetails /> : <Backups />;
            case 'auto-backup-compare':
                return <Compare />;
            case 'auto-backup-backups':
                return <Backups />;
            case 'auto-backup-restore':
                return <Restore />;
            case 'auto-backup-schedules':
                return <Schedules />;
            case 'auto-backup-settings':
                return <Settings />;
            case 'auto-backup-cloud':
                return <CloudSettings />;
            case 'auto-backup-logs':
                return <Logs />;
            case 'auto-backup':
            default:
                return <Dashboard />;
        }
    };

    return (
        <div className="auto-backup-container">
            <nav className="auto-backup-nav">
                <ul>
                    <li className={currentPage === 'auto-backup' ? 'active' : ''}>
                        <a href="?page=auto-backup">
                            <span className="dashicons dashicons-dashboard"></span>
                            Dashboard
                        </a>
                    </li>
                    <li className={currentPage === 'auto-backup-backups' ? 'active' : ''}>
                        <a href="?page=auto-backup-backups">
                            <span className="dashicons dashicons-backup"></span>
                            Backups
                        </a>
                    </li>
                    <li className={currentPage === 'auto-backup-restore' ? 'active' : ''}>
                        <a href="?page=auto-backup-restore">
                            <span className="dashicons dashicons-update"></span>
                            Restore
                        </a>
                    </li>
                    <li className={currentPage === 'auto-backup-schedules' ? 'active' : ''}>
                        <a href="?page=auto-backup-schedules">
                            <span className="dashicons dashicons-clock"></span>
                            Schedules
                        </a>
                    </li>
                    <li className={currentPage === 'auto-backup-settings' ? 'active' : ''}>
                        <a href="?page=auto-backup-settings">
                            <span className="dashicons dashicons-admin-settings"></span>
                            Settings
                        </a>
                    </li>
                    <li className={currentPage === 'auto-backup-compare' ? 'active' : ''}>
                        <a href="?page=auto-backup-compare">
                            <span className="dashicons dashicons-image-flip-horizontal"></span>
                            Compare
                        </a>
                    </li>
                    <li className={currentPage === 'auto-backup-cloud' ? 'active' : ''}>
                        <a href="?page=auto-backup-cloud">
                            <span className="dashicons dashicons-cloud"></span>
                            Cloud
                        </a>
                    </li>
                    <li className={currentPage === 'auto-backup-logs' ? 'active' : ''}>
                        <a href="?page=auto-backup-logs">
                            <span className="dashicons dashicons-list-view"></span>
                            Logs
                        </a>
                    </li>
                </ul>
            </nav>
            <div className="auto-backup-content">
                {renderPage()}
            </div>
        </div>
    );
};

export default App;
