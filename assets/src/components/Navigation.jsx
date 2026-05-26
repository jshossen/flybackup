import React from 'react';

const Navigation = ({ currentPage }) => {
    const menuItems = [
        { id: 'flybackup', label: 'Dashboard', icon: 'dashicons-dashboard' },
        { id: 'flybackup-backups', label: 'Backups', icon: 'dashicons-backup' },
        { id: 'flybackup-restore', label: 'Restore', icon: 'dashicons-update' },
        { id: 'flybackup-schedules', label: 'Schedules', icon: 'dashicons-clock' },
        { id: 'flybackup-settings', label: 'Settings', icon: 'dashicons-admin-settings' },
        { id: 'flybackup-logs', label: 'Logs', icon: 'dashicons-list-view' }
    ];

    return (
        <nav className="flybackup-nav">
            <ul>
                {menuItems.map(item => (
                    <li key={item.id} className={currentPage === item.id ? 'active' : ''}>
                        <a href={`?page=${item.id}`}>
                            <span className={`dashicons ${item.icon}`}></span>
                            {item.label}
                        </a>
                    </li>
                ))}
            </ul>
        </nav>
    );
};

export default Navigation;
