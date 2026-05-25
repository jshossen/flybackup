import React from 'react';

const Button = ({ 
    children, 
    onClick, 
    variant = 'primary', 
    size = 'medium',
    icon = null,
    disabled = false,
    type = 'button',
    className = ''
}) => {
    const baseClasses = 'fly-backup-button';
    const variantClasses = `fly-backup-button--${variant}`;
    const sizeClasses = `fly-backup-button--${size}`;
    const disabledClass = disabled ? 'fly-backup-button--disabled' : '';
    
    const allClasses = [baseClasses, variantClasses, sizeClasses, disabledClass, className]
        .filter(Boolean)
        .join(' ');
    
    return (
        <button
            type={type}
            className={allClasses}
            onClick={onClick}
            disabled={disabled}
        >
            {icon && <span className="fly-backup-button__icon">{icon}</span>}
            <span className="fly-backup-button__text">{children}</span>
        </button>
    );
};

export default Button;
