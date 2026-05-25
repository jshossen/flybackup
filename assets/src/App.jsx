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
    const [currentPage, setCurrentPage] = useState('fly-backup');

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
            case 'fly-backup-backup-details':
                return backupId ? <BackupDetails /> : <Backups />;
            case 'fly-backup-compare':
                return <Compare />;
            case 'fly-backup-backups':
                return <Backups />;
            case 'fly-backup-restore':
                return <Restore />;
            case 'fly-backup-schedules':
                return <Schedules />;
            case 'fly-backup-settings':
                return <Settings />;
            case 'fly-backup-cloud':
                return <CloudSettings />;
            case 'fly-backup-logs':
                return <Logs />;
            case 'fly-backup':
            default:
                return <Dashboard />;
        }
    };

    return (
        <div className="fly-backup-container">
            <nav className="fly-backup-nav">
                <ul>
                    <li className={currentPage === 'fly-backup' ? 'active' : ''}>
                        <a href="?page=fly-backup">
                            <span className="dashicons dashicons-dashboard"></span>
                            Dashboard
                        </a>
                    </li>
                    <li className={currentPage === 'fly-backup-backups' ? 'active' : ''}>
                        <a href="?page=fly-backup-backups">
                            <span className="dashicons dashicons-backup"></span>
                            Backups
                        </a>
                    </li>
                    <li className={currentPage === 'fly-backup-restore' ? 'active' : ''}>
                        <a href="?page=fly-backup-restore">
                            <span className="dashicons dashicons-update"></span>
                            Restore
                        </a>
                    </li>
                    <li className={currentPage === 'fly-backup-schedules' ? 'active' : ''}>
                        <a href="?page=fly-backup-schedules">
                            <span className="dashicons dashicons-clock"></span>
                            Schedules
                        </a>
                    </li>
                    <li className={currentPage === 'fly-backup-settings' ? 'active' : ''}>
                        <a href="?page=fly-backup-settings">
                            <span className="dashicons dashicons-admin-settings"></span>
                            Settings
                        </a>
                    </li>
                    <li className={currentPage === 'fly-backup-compare' ? 'active' : ''}>
                        <a href="?page=fly-backup-compare">
                            <span className="dashicons dashicons-image-flip-horizontal"></span>
                            Compare
                        </a>
                    </li>
                    <li className={currentPage === 'fly-backup-cloud' ? 'active' : ''}>
                        <a href="?page=fly-backup-cloud">
                            <span className="dashicons dashicons-cloud"></span>
                            Cloud
                        </a>
                    </li>
                    <li className={currentPage === 'fly-backup-logs' ? 'active' : ''}>
                        <a href="?page=fly-backup-logs">
                            <span className="dashicons dashicons-list-view"></span>
                            Logs
                        </a>
                    </li>
                </ul>
            </nav>
            <div className="fly-backup-content">
                {renderPage()}
            </div>
        </div>
    );
};

export default App;
