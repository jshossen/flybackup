import React, { useState, useEffect } from 'react';
import { getSchedules, deleteSchedule } from '../utils/api';
import LoadingSpinner from '../components/LoadingSpinner';

const Schedules = () => {
    const [schedules, setSchedules] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadSchedules();
    }, []);

    const loadSchedules = async () => {
        try {
            const data = await getSchedules();
            setSchedules(data || []);
        } catch (error) {
            console.error('Error loading schedules:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Delete this schedule?')) return;
        
        try {
            await deleteSchedule(id);
            alert('Schedule deleted!');
            loadSchedules();
        } catch (error) {
            alert('Failed to delete schedule: ' + error.message);
        }
    };

    if (loading) return <LoadingSpinner />;

    return (
        <div className="schedules-page">
            <div className="page-header">
                <h1>Backup Schedules</h1>
                <button className="button button-primary">Create Schedule</button>
            </div>

            {schedules.length === 0 ? (
                <div className="empty-state">
                    <p>No schedules configured.</p>
                </div>
            ) : (
                <table className="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Frequency</th>
                            <th>Type</th>
                            <th>Next Run</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {schedules.map(schedule => (
                            <tr key={schedule.id}>
                                <td>{schedule.schedule_name}</td>
                                <td>{schedule.frequency}</td>
                                <td>{schedule.backup_type}</td>
                                <td>{schedule.next_run_formatted}</td>
                                <td>
                                    <span className={`status-badge status-${schedule.status}`}>
                                        {schedule.status}
                                    </span>
                                </td>
                                <td>
                                    <button 
                                        className="button button-small"
                                        onClick={() => handleDelete(schedule.id)}
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
};

export default Schedules;
