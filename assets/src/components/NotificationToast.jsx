import React, { useEffect } from 'react';

const NotificationToast = ({ 
    isOpen, 
    type = 'success', 
    message, 
    onClose,
    duration = 4000 
}) => {
    useEffect(() => {
        if (isOpen) {
            const timer = setTimeout(() => {
                onClose();
            }, duration);

            return () => clearTimeout(timer);
        }
    }, [isOpen, duration, onClose]);

    if (!isOpen) return null;

    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };

    return (
        <div className={`notification-toast notification-${type}`}>
            <span className="toast-icon">{icons[type]}</span>
            <span className="toast-message">{message}</span>
            <button 
                className="toast-close" 
                onClick={onClose}
                aria-label="Close notification"
            >
                ×
            </button>
        </div>
    );
};

export default NotificationToast;
