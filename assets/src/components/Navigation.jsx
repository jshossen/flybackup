import React from 'react';

const Navigation = ({ currentPage }) => {
    const menuItems = [
        { id: 'fly-backup', label: 'Dashboard', icon: 'dashicons-dashboard' },
        { id: 'fly-backup-backups', label: 'Backups', icon: 'dashicons-backup' },
        { id: 'fly-backup-restore', label: 'Restore', icon: 'dashicons-update' },
        { id: 'fly-backup-schedules', label: 'Schedules', icon: 'dashicons-clock' },
        { id: 'fly-backup-settings', label: 'Settings', icon: 'dashicons-admin-settings' },
        { id: 'fly-backup-logs', label: 'Logs', icon: 'dashicons-list-view' }
    ];

    return (
        <nav className="fly-backup-nav">
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
