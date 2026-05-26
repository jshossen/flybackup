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
    const [currentPage, setCurrentPage] = useState('flybackup');

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
            case 'flybackup-backup-details':
                return backupId ? <BackupDetails /> : <Backups />;
            case 'flybackup-compare':
                return <Compare />;
            case 'flybackup-backups':
                return <Backups />;
            case 'flybackup-restore':
                return <Restore />;
            case 'flybackup-schedules':
                return <Schedules />;
            case 'flybackup-settings':
                return <Settings />;
            case 'flybackup-cloud':
                return <CloudSettings />;
            case 'flybackup-logs':
                return <Logs />;
            case 'flybackup':
            default:
                return <Dashboard />;
        }
    };

    return (
        <div className="flybackup-container">
            <nav className="flybackup-nav">
                <ul>
                    <li className={currentPage === 'flybackup' ? 'active' : ''}>
                        <a href="?page=flybackup">
                            <span className="dashicons dashicons-dashboard"></span>
                            Dashboard
                        </a>
                    </li>
                    <li className={currentPage === 'flybackup-backups' ? 'active' : ''}>
                        <a href="?page=flybackup-backups">
                            <span className="dashicons dashicons-backup"></span>
                            Backups
                        </a>
                    </li>
                    <li className={currentPage === 'flybackup-restore' ? 'active' : ''}>
                        <a href="?page=flybackup-restore">
                            <span className="dashicons dashicons-update"></span>
                            Restore
                        </a>
                    </li>
                    <li className={currentPage === 'flybackup-schedules' ? 'active' : ''}>
                        <a href="?page=flybackup-schedules">
                            <span className="dashicons dashicons-clock"></span>
                            Schedules
                        </a>
                    </li>
                    <li className={currentPage === 'flybackup-settings' ? 'active' : ''}>
                        <a href="?page=flybackup-settings">
                            <span className="dashicons dashicons-admin-settings"></span>
                            Settings
                        </a>
                    </li>
                    <li className={currentPage === 'flybackup-compare' ? 'active' : ''}>
                        <a href="?page=flybackup-compare">
                            <span className="dashicons dashicons-image-flip-horizontal"></span>
                            Compare
                        </a>
                    </li>
                    <li className={currentPage === 'flybackup-cloud' ? 'active' : ''}>
                        <a href="?page=flybackup-cloud">
                            <span className="dashicons dashicons-cloud"></span>
                            Cloud
                        </a>
                    </li>
                    <li className={currentPage === 'flybackup-logs' ? 'active' : ''}>
                        <a href="?page=flybackup-logs">
                            <span className="dashicons dashicons-list-view"></span>
                            Logs
                        </a>
                    </li>
                </ul>
            </nav>
            <div className="flybackup-content">
                {renderPage()}
            </div>
        </div>
    );
};

export default App;
