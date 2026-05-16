import apiFetch from '@wordpress/api-fetch';

const API_NAMESPACE = 'auto-backup/v1';

export const getBackups = async (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return await apiFetch({ path: `/${API_NAMESPACE}/backups${query ? '?' + query : ''}` });
};

export const getBackup = async (id) => {
    return await apiFetch({ path: `/${API_NAMESPACE}/backups/${id}` });
};

export const createBackup = async (data) => {
    return await apiFetch({
        path: `/${API_NAMESPACE}/backups`,
        method: 'POST',
        data
    });
};

export const deleteBackup = async (id) => {
    return await apiFetch({
        path: `/${API_NAMESPACE}/backups/${id}`,
        method: 'DELETE'
    });
};

export const restoreBackup = async (id, items = []) => {
    return await apiFetch({
        path: `/${API_NAMESPACE}/backups/${id}/restore`,
        method: 'POST',
        data: { items }
    });
};

export const getSchedules = async () => {
    return await apiFetch({ path: `/${API_NAMESPACE}/schedules` });
};

export const createSchedule = async (data) => {
    return await apiFetch({
        path: `/${API_NAMESPACE}/schedules`,
        method: 'POST',
        data
    });
};

export const updateSchedule = async (id, data) => {
    return await apiFetch({
        path: `/${API_NAMESPACE}/schedules/${id}`,
        method: 'PUT',
        data
    });
};

export const deleteSchedule = async (id) => {
    return await apiFetch({
        path: `/${API_NAMESPACE}/schedules/${id}`,
        method: 'DELETE'
    });
};

export const getHealth = async () => {
    return await apiFetch({ path: `/${API_NAMESPACE}/health` });
};

export const getLogs = async (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return await apiFetch({ path: `/${API_NAMESPACE}/logs${query ? '?' + query : ''}` });
};

export const getSettings = async () => {
    return await apiFetch({ path: `/${API_NAMESPACE}/settings` });
};

export const updateSettings = async (data) => {
    return await apiFetch({
        path: `/${API_NAMESPACE}/settings`,
        method: 'POST',
        data
    });
};

export const getStats = async () => {
    return await apiFetch({ path: `/${API_NAMESPACE}/stats` });
};

export const getBackupDetails = async (id) => {
    return await apiFetch({ path: `/${API_NAMESPACE}/backups/${id}/details` });
};

export const compareCurrentVsBackup = async (id) => {
    return await apiFetch({ path: `/${API_NAMESPACE}/backups/${id}/compare/current` });
};

export const compareBackupVsBackup = async (sourceId, targetId) => {
    return await apiFetch({
        path: `/${API_NAMESPACE}/backups/compare`,
        method: 'POST',
        data: { source_id: sourceId, target_id: targetId }
    });
};

export const getTableDiff = async (backupId, table, sourceBackupId = 0, targetBackupId = null) => {
    const params = new URLSearchParams();
    if (sourceBackupId) params.append('source_backup_id', sourceBackupId);
    if (targetBackupId) params.append('target_backup_id', targetBackupId);
    
    return await apiFetch({ 
        path: `/${API_NAMESPACE}/backups/${backupId}/tables/${table}/diff?${params.toString()}` 
    });
};

// Cloud storage API
export const getCloudStatus = async () => {
    return await apiFetch({ path: `/${API_NAMESPACE}/cloud/status` });
};

export const connectCloudProvider = async (provider, credentials, settings = {}) => {
    return await apiFetch({
        path: `/${API_NAMESPACE}/cloud/connect`,
        method: 'POST',
        data: { provider, credentials, settings }
    });
};

export const disconnectCloudProvider = async (provider) => {
    return await apiFetch({
        path: `/${API_NAMESPACE}/cloud/disconnect/${provider}`,
        method: 'DELETE'
    });
};

export const uploadBackupToCloud = async (backupId, provider) => {
    return await apiFetch({
        path: `/${API_NAMESPACE}/cloud/upload/${backupId}`,
        method: 'POST',
        data: { provider }
    });
};
