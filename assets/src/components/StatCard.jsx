import React from 'react';

const StatCard = ({ title, value, icon, status }) => (
    <div className={`stat-card ${status ? `status-${status}` : ''}`}>
        <div className="stat-icon">
            <span className={`dashicons dashicons-${icon}`}></span>
        </div>
        <div className="stat-content">
            <h3>{title}</h3>
            <p className="stat-value">{value}</p>
        </div>
    </div>
);

export default StatCard;
