import React, { useState, useEffect } from 'react';
import { getStats, getHealth, getSystemRequirements } from '../utils/api';
import HealthMeter from '../components/HealthMeter';
import StatCard from '../components/StatCard';
import SystemRequirements from '../components/SystemRequirements';
import LoadingSpinner from '../components/LoadingSpinner';

const Dashboard = () => {
    const [stats, setStats] = useState(null);
    const [health, setHealth] = useState(null);
    const [requirements, setRequirements] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        try {
            const [statsData, healthData, reqData] = await Promise.all([
                getStats(),
                getHealth(),
                getSystemRequirements()
            ]);
            setStats(statsData);
            setHealth(healthData);
            setRequirements(reqData);
        } catch (error) {
            console.error('Error loading dashboard data:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <LoadingSpinner />;
    }

    return (
        <div className="dashboard">
            <h1>Auto Backup Dashboard</h1>
            
            <div className="dashboard-stats">
                <StatCard
                    title="Total Backups"
                    value={stats?.total_backups || 0}
                    icon="backup"
                />
                <StatCard
                    title="Total Size"
                    value={stats?.total_size || '0 B'}
                    icon="database"
                />
                <StatCard
                    title="Available Space"
                    value={stats?.available_space || 'Unknown'}
                    icon="storage"
                />
                <StatCard
                    title="Health Score"
                    value={`${health?.health?.score || 0}%`}
                    icon="heart"
                    status={health?.health?.status}
                />
            </div>

            <div className="dashboard-grid">
                <div className="dashboard-card">
                    <h2>Backup Health</h2>
                    {health && <HealthMeter health={health.health} />}
                    {health?.recommendations && health.recommendations.length > 0 && (
                        <div className="recommendations">
                            <h3>Recommendations</h3>
                            <ul>
                                {health.recommendations.map((rec, index) => (
                                    <li key={index} className={`severity-${rec.severity}`}>
                                        {rec.message}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>

                <div className="dashboard-card">
                    <h2>Last Backup</h2>
                    {stats?.last_backup ? (
                        <div className="backup-info">
                            <p><strong>Name:</strong> {stats.last_backup.name}</p>
                            <p><strong>Date:</strong> {stats.last_backup.time_ago}</p>
                            <p><strong>Size:</strong> {stats.last_backup.size}</p>
                        </div>
                    ) : (
                        <p>No backups yet</p>
                    )}
                </div>

                <div className="dashboard-card">
                    <h2>Next Scheduled Backup</h2>
                    {stats?.next_scheduled ? (
                        <div className="schedule-info">
                            <p><strong>Schedule:</strong> {stats.next_scheduled.name}</p>
                            <p><strong>Time:</strong> {stats.next_scheduled.time_until}</p>
                        </div>
                    ) : (
                        <p>No schedules configured</p>
                    )}
                </div>

                <div className="dashboard-card full-width">
                    <h2>System Requirements</h2>
                    <SystemRequirements requirements={requirements} />
                </div>
            </div>

            <div className="dashboard-actions">
                <a href="?page=auto-backup-backups" className="button button-primary button-large">
                    Create Backup
                </a>
                <a href="?page=auto-backup-schedules" className="button button-secondary button-large">
                    Manage Schedules
                </a>
            </div>
        </div>
    );
};

export default Dashboard;
