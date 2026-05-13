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
