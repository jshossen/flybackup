import React, { useState, useEffect } from 'react';
import { getSettings, updateSettings } from '../utils/api';
import LoadingSpinner from '../components/LoadingSpinner';
import NotificationToast from '../components/NotificationToast';

const Settings = () => {
    const [settings, setSettings] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    
    const [notification, setNotification] = useState({
        isOpen: false,
        type: 'success',
        message: ''
    });

    useEffect(() => {
        loadSettings();
    }, []);

    const loadSettings = async () => {
        try {
            const data = await getSettings();
            setSettings(data);
        } catch (error) {
            console.error('Error loading settings:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSave = async (e) => {
        e.preventDefault();
        setSaving(true);
        try {
            await updateSettings(settings);
            setNotification({
                isOpen: true,
                type: 'success',
                message: 'Settings saved successfully!'
            });
        } catch (error) {
            setNotification({
                isOpen: true,
                type: 'error',
                message: 'Failed to save settings: ' + error.message
            });
        } finally {
            setSaving(false);
        }
    };

    if (loading) return <LoadingSpinner />;

    return (
        <div className="settings-page">
            <h1>Settings</h1>
            <form onSubmit={handleSave}>
                <table className="form-table">
                    <tbody>
                        <tr>
                            <th>Email Notifications</th>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        checked={settings?.settings?.email_notifications || false}
                                        onChange={(e) => setSettings({
                                            ...settings,
                                            settings: {
                                                ...settings.settings,
                                                email_notifications: e.target.checked
                                            }
                                        })}
                                    />
                                    Enable email notifications
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th>Notification Email</th>
                            <td>
                                <input
                                    type="email"
                                    className="regular-text"
                                    value={settings?.settings?.notification_email || ''}
                                    onChange={(e) => setSettings({
                                        ...settings,
                                        settings: {
                                            ...settings.settings,
                                            notification_email: e.target.value
                                        }
                                    })}
                                />
                            </td>
                        </tr>
                        <tr>
                            <th>Retention Count</th>
                            <td>
                                <input
                                    type="number"
                                    min="1"
                                    max="50"
                                    value={settings?.retention_count || 5}
                                    onChange={(e) => setSettings({
                                        ...settings,
                                        retention_count: parseInt(e.target.value)
                                    })}
                                />
                                <p className="description">Number of backups to keep</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2>Default Backup Items</h2>
                <p>Select what data should be included in backups by default:</p>
                <table className="form-table">
                    <tbody>
                        <tr>
                            <th>Database</th>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        checked={settings?.settings?.backup_items?.database !== false}
                                        onChange={(e) => setSettings({
                                            ...settings,
                                            settings: {
                                                ...settings.settings,
                                                backup_items: {
                                                    ...settings.settings?.backup_items,
                                                    database: e.target.checked
                                                }
                                            }
                                        })}
                                    />
                                    Include all database tables
                                </label>
                                <p className="description">All WordPress database tables (posts, pages, users, settings, etc.)</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Uploads</th>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        checked={settings?.settings?.backup_items?.uploads !== false}
                                        onChange={(e) => setSettings({
                                            ...settings,
                                            settings: {
                                                ...settings.settings,
                                                backup_items: {
                                                    ...settings.settings?.backup_items,
                                                    uploads: e.target.checked
                                                }
                                            }
                                        })}
                                    />
                                    Include media uploads
                                </label>
                                <p className="description">All files in wp-content/uploads (images, videos, documents)</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Plugins</th>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        checked={settings?.settings?.backup_items?.plugins !== false}
                                        onChange={(e) => setSettings({
                                            ...settings,
                                            settings: {
                                                ...settings.settings,
                                                backup_items: {
                                                    ...settings.settings?.backup_items,
                                                    plugins: e.target.checked
                                                }
                                            }
                                        })}
                                    />
                                    Include all plugins
                                </label>
                                <p className="description">All installed plugins in wp-content/plugins</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Themes</th>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        checked={settings?.settings?.backup_items?.themes !== false}
                                        onChange={(e) => setSettings({
                                            ...settings,
                                            settings: {
                                                ...settings.settings,
                                                backup_items: {
                                                    ...settings.settings?.backup_items,
                                                    themes: e.target.checked
                                                }
                                            }
                                        })}
                                    />
                                    Include all themes
                                </label>
                                <p className="description">All installed themes in wp-content/themes</p>
                            </td>
                        </tr>
                        <tr>
                            <th>WP-Config</th>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        checked={settings?.settings?.backup_items?.wp_config !== false}
                                        onChange={(e) => setSettings({
                                            ...settings,
                                            settings: {
                                                ...settings.settings,
                                                backup_items: {
                                                    ...settings.settings?.backup_items,
                                                    wp_config: e.target.checked
                                                }
                                            }
                                        })}
                                    />
                                    Include wp-config.php
                                </label>
                                <p className="description">WordPress configuration file (database credentials, security keys)</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p className="submit">
                    <button type="submit" className="button button-primary" disabled={saving}>
                        {saving ? 'Saving...' : 'Save Settings'}
                    </button>
                </p>
            </form>
            
            <NotificationToast
                isOpen={notification.isOpen}
                type={notification.type}
                message={notification.message}
                onClose={() => setNotification(prev => ({ ...prev, isOpen: false }))}
            />
        </div>
    );
};

export default Settings;
