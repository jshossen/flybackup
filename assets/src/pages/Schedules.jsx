import React, { useState, useEffect } from 'react';
import { getSchedules, deleteSchedule, createSchedule } from '../utils/api';
import LoadingSpinner from '../components/LoadingSpinner';
import ConfirmModal from '../components/ConfirmModal';
import NotificationToast from '../components/NotificationToast';

const Schedules = () => {
    const [schedules, setSchedules] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [creating, setCreating] = useState(false);
    const [formData, setFormData] = useState({
        schedule_name: '',
        frequency: 'daily',
        backup_type: 'full',
        time: '00:00'
    });
    
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

    const handleCreateSchedule = async (e) => {
        e.preventDefault();
        setCreating(true);
        try {
            await createSchedule(formData);
            setNotification({
                isOpen: true,
                type: 'success',
                message: 'Schedule created successfully!'
            });
            setShowModal(false);
            setFormData({
                schedule_name: '',
                frequency: 'daily',
                backup_type: 'full',
                time: '00:00'
            });
            loadSchedules();
        } catch (error) {
            setNotification({
                isOpen: true,
                type: 'error',
                message: 'Failed to create schedule: ' + error.message
            });
        } finally {
            setCreating(false);
        }
    };

    const handleDelete = (id) => {
        setConfirmModal({
            isOpen: true,
            title: 'Delete Schedule',
            message: 'Are you sure you want to delete this schedule? This action cannot be undone.',
            onConfirm: () => executeDelete(id),
            danger: true
        });
    };
    
    const executeDelete = async (id) => {
        try {
            await deleteSchedule(id);
            setNotification({
                isOpen: true,
                type: 'success',
                message: 'Schedule deleted successfully!'
            });
            loadSchedules();
        } catch (error) {
            setNotification({
                isOpen: true,
                type: 'error',
                message: 'Failed to delete schedule: ' + error.message
            });
        }
    };

    if (loading) return <LoadingSpinner />;

    return (
        <div className="schedules-page">
            <div className="page-header">
                <h1>Backup Schedules</h1>
                <button 
                    className="button button-primary"
                    onClick={() => setShowModal(true)}
                >
                    Create Schedule
                </button>
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

            {showModal && (
                <div className="modal-overlay" onClick={() => setShowModal(false)}>
                    <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                        <div className="modal-header">
                            <h2>Create Backup Schedule</h2>
                            <button 
                                className="modal-close"
                                onClick={() => setShowModal(false)}
                            >
                                ×
                            </button>
                        </div>
                        <form onSubmit={handleCreateSchedule}>
                            <div className="form-group">
                                <label>Schedule Name</label>
                                <input
                                    type="text"
                                    className="regular-text"
                                    value={formData.schedule_name}
                                    onChange={(e) => setFormData({...formData, schedule_name: e.target.value})}
                                    placeholder="e.g., Daily Backup"
                                    required
                                />
                            </div>
                            <div className="form-group">
                                <label>Frequency</label>
                                <select
                                    className="regular-text"
                                    value={formData.frequency}
                                    onChange={(e) => setFormData({...formData, frequency: e.target.value})}
                                >
                                    <option value="hourly">Hourly</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Backup Type</label>
                                <select
                                    className="regular-text"
                                    value={formData.backup_type}
                                    onChange={(e) => setFormData({...formData, backup_type: e.target.value})}
                                >
                                    <option value="full">Full Backup</option>
                                    <option value="database">Database Only</option>
                                    <option value="partial">Partial Backup</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Time (24-hour format)</label>
                                <input
                                    type="time"
                                    className="regular-text"
                                    value={formData.time}
                                    onChange={(e) => setFormData({...formData, time: e.target.value})}
                                    required
                                />
                                <p className="description">When should this backup run?</p>
                            </div>
                            <div className="modal-footer">
                                <button
                                    type="button"
                                    className="button"
                                    onClick={() => setShowModal(false)}
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="button button-primary"
                                    disabled={creating}
                                >
                                    {creating ? 'Creating...' : 'Create Schedule'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
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

export default Schedules;
