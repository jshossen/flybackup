import React from 'react';

const HealthMeter = ({ health }) => {
    const getColor = (score) => {
        if (score >= 80) return '#46b450';
        if (score >= 50) return '#ffb900';
        return '#dc3232';
    };

    return (
        <div className="health-meter">
            <div className="health-score">
                <svg width="120" height="120" viewBox="0 0 120 120">
                    <circle
                        cx="60"
                        cy="60"
                        r="50"
                        fill="none"
                        stroke="#e0e0e0"
                        strokeWidth="10"
                    />
                    <circle
                        cx="60"
                        cy="60"
                        r="50"
                        fill="none"
                        stroke={getColor(health.score)}
                        strokeWidth="10"
                        strokeDasharray={`${(health.score / 100) * 314} 314`}
                        strokeLinecap="round"
                        transform="rotate(-90 60 60)"
                    />
                    <text
                        x="60"
                        y="60"
                        textAnchor="middle"
                        dy=".3em"
                        fontSize="24"
                        fontWeight="bold"
                        fill={getColor(health.score)}
                    >
                        {health.score}%
                    </text>
                </svg>
            </div>
            <p className="health-status">{health.status}</p>
        </div>
    );
};

export default HealthMeter;
