import React from 'react';

const ConfirmModal = ({ 
    isOpen, 
    title, 
    message, 
    onConfirm, 
    onCancel, 
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    danger = false 
}) => {
    if (!isOpen) return null;

    const handleConfirm = () => {
        onConfirm();
        onCancel(); // Close modal after confirm
    };

    return (
        <div className="modal-overlay" onClick={onCancel}>
            <div className="modal-content confirm-modal" onClick={(e) => e.stopPropagation()}>
                <div className="modal-header">
                    <h2>{title}</h2>
                    <button 
                        className="modal-close"
                        onClick={onCancel}
                        aria-label="Close"
                    >
                        ×
                    </button>
                </div>
                <div className="modal-body">
                    <p>{message}</p>
                </div>
                <div className="modal-footer">
                    <button
                        type="button"
                        className="button button-secondary"
                        onClick={onCancel}
                    >
                        {cancelText}
                    </button>
                    <button
                        type="button"
                        className={`button ${danger ? 'button-danger' : 'button-primary'}`}
                        onClick={handleConfirm}
                        autoFocus
                    >
                        {confirmText}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ConfirmModal;
