import React from 'react';

const ProgressModal = ({ 
    isOpen, 
    title, 
    steps = [], 
    currentStep = 0, 
    progress = 0, 
    message = '' 
}) => {
    if (!isOpen) return null;

    return (
        <div className="modal-overlay progress-modal-overlay">
            <div className="modal-content progress-modal" onClick={(e) => e.stopPropagation()}>
                <div className="modal-header">
                    <h2>{title}</h2>
                </div>
                <div className="modal-body">
                    {/* Progress Bar */}
                    <div className="progress-bar-container">
                        <div 
                            className="progress-bar-fill" 
                            style={{ width: `${progress}%` }}
                        >
                            <span className="progress-percentage">{Math.round(progress)}%</span>
                        </div>
                    </div>

                    {/* Current Message */}
                    {message && (
                        <p className="progress-message">{message}</p>
                    )}

                    {/* Steps List */}
                    {steps.length > 0 && (
                        <ul className="progress-steps">
                            {steps.map((step, index) => (
                                <li 
                                    key={index}
                                    className={`progress-step ${
                                        index < currentStep ? 'completed' : 
                                        index === currentStep ? 'active' : 
                                        'pending'
                                    }`}
                                >
                                    <span className="step-icon">
                                        {index < currentStep ? '✓' : 
                                         index === currentStep ? '⟳' : 
                                         '⏳'}
                                    </span>
                                    <span className="step-text">{step}</span>
                                </li>
                            ))}
                        </ul>
                    )}

                    {/* Loading Spinner */}
                    <div className="progress-spinner">
                        <div className="spinner"></div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ProgressModal;
