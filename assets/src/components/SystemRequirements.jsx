import React from 'react';

const SystemRequirements = ({ requirements }) => {
    if (!requirements) return null;

    const getStatusIcon = (status) => {
        switch(status) {
            case 'pass': return '✓';
            case 'fail': return '✗';
            case 'warning': return '⚠';
            default: return '?';
        }
    };

    const getStatusClass = (status) => {
        switch(status) {
            case 'pass': return 'status-pass';
            case 'fail': return 'status-fail';
            case 'warning': return 'status-warning';
            default: return '';
        }
    };

    return (
        <div className="system-requirements">
            <table className="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Requirement</th>
                        <th>Required</th>
                        <th>Current</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    {Object.entries(requirements).map(([key, req]) => (
                        <tr key={key} className={getStatusClass(req.status)}>
                            <td>
                                <strong>{req.name}</strong>
                            </td>
                            <td>{req.required}</td>
                            <td>{req.current}</td>
                            <td className={`status-cell ${getStatusClass(req.status)}`}>
                                <span className="status-icon">{getStatusIcon(req.status)}</span>
                                {req.status === 'fail' && <span className="status-text">Required</span>}
                                {req.status === 'warning' && <span className="status-text">Recommended</span>}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
};

export default SystemRequirements;
