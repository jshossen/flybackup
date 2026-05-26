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
    const baseClasses = 'flybackup-button';
    const variantClasses = `flybackup-button--${variant}`;
    const sizeClasses = `flybackup-button--${size}`;
    const disabledClass = disabled ? 'flybackup-button--disabled' : '';
    
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
            {icon && <span className="flybackup-button__icon">{icon}</span>}
            <span className="flybackup-button__text">{children}</span>
        </button>
    );
};

export default Button;
