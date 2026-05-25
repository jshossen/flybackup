import React, { useState, useEffect } from 'react';
import { getCloudStatus, connectCloudProvider, disconnectCloudProvider } from '../utils/api';
import LoadingSpinner from '../components/LoadingSpinner';
import ConfirmModal from '../components/ConfirmModal';

const CloudSettings = () => {
    const [providers, setProviders] = useState({});
    const [loading, setLoading] = useState(true);
    const [connecting, setConnecting] = useState(null);
    const [activeProvider, setActiveProvider] = useState(null);
    const [credentials, setCredentials] = useState({});
    const [message, setMessage] = useState(null);
    
    const [confirmModal, setConfirmModal] = useState({
        isOpen: false,
        title: '',
        message: '',
        onConfirm: null,
        danger: false
    });

    useEffect(() => {
        loadCloudStatus();
    }, []);

    const loadCloudStatus = async () => {
        setLoading(true);
        try {
            const data = await getCloudStatus();
            setProviders(data);
        } catch (error) {
            console.error('Error loading cloud status:', error);
        }
        setLoading(false);
    };

    const handleConnect = async (provider) => {
        if (!credentials[provider]) {
            setMessage({ type: 'error', text: 'Please enter credentials' });
            return;
        }

        setConnecting(provider);
        setMessage(null);

        try {
            const result = await connectCloudProvider(provider, credentials[provider]);
            setMessage({ type: 'success', text: 'Connected successfully!' });
            setActiveProvider(null);
            setCredentials(prev => ({ ...prev, [provider]: {} }));
            loadCloudStatus();
        } catch (error) {
            setMessage({ type: 'error', text: error.message || 'Connection failed' });
        }

        setConnecting(null);
    };

    const handleDisconnect = (provider) => {
        setConfirmModal({
            isOpen: true,
            title: 'Disconnect Cloud Storage',
            message: 'Are you sure you want to disconnect from this cloud storage provider?',
            onConfirm: () => executeDisconnect(provider),
            danger: true
        });
    };
    
    const executeDisconnect = async (provider) => {
        try {
            await disconnectCloudProvider(provider);
            setMessage({ type: 'success', text: 'Disconnected successfully!' });
            loadCloudStatus();
        } catch (error) {
            setMessage({ type: 'error', text: error.message || 'Disconnect failed' });
        }
    };

    const renderCredentialForm = (provider) => {
        switch (provider) {
            case 'google_drive':
                return (
                    <div className="credential-form">
                        <h4>Google Drive OAuth</h4>
                        <p className="help-text">
                            Enter your OAuth 2.0 credentials from Google Cloud Console.
                        </p>
                        <input
                            type="text"
                            placeholder="Access Token"
                            value={credentials[provider]?.access_token || ''}
                            onChange={(e) => setCredentials(prev => ({
                                ...prev,
                                [provider]: { ...prev[provider], access_token: e.target.value }
                            }))}
                        />
                        <input
                            type="text"
                            placeholder="Refresh Token (optional)"
                            value={credentials[provider]?.refresh_token || ''}
                            onChange={(e) => setCredentials(prev => ({
                                ...prev,
                                [provider]: { ...prev[provider], refresh_token: e.target.value }
                            }))}
                        />
                        <input
                            type="text"
                            placeholder="Client ID (optional)"
                            value={credentials[provider]?.client_id || ''}
                            onChange={(e) => setCredentials(prev => ({
                                ...prev,
                                [provider]: { ...prev[provider], client_id: e.target.value }
                            }))}
                        />
                        <input
                            type="text"
                            placeholder="Client Secret (optional)"
                            value={credentials[provider]?.client_secret || ''}
                            onChange={(e) => setCredentials(prev => ({
                                ...prev,
                                [provider]: { ...prev[provider], client_secret: e.target.value }
                            }))}
                        />
                    </div>
                );
            case 'dropbox':
                return (
                    <div className="credential-form">
                        <h4>Dropbox OAuth</h4>
                        <p className="help-text">
                            Enter your OAuth 2.0 access token from Dropbox App Console.
                        </p>
                        <input
                            type="text"
                            placeholder="Access Token"
                            value={credentials[provider]?.access_token || ''}
                            onChange={(e) => setCredentials(prev => ({
                                ...prev,
                                [provider]: { ...prev[provider], access_token: e.target.value }
                            }))}
                        />
                        <input
                            type="text"
                            placeholder="App Key (optional)"
                            value={credentials[provider]?.app_key || ''}
                            onChange={(e) => setCredentials(prev => ({
                                ...prev,
                                [provider]: { ...prev[provider], app_key: e.target.value }
                            }))}
                        />
                        <input
                            type="text"
                            placeholder="App Secret (optional)"
                            value={credentials[provider]?.app_secret || ''}
                            onChange={(e) => setCredentials(prev => ({
                                ...prev,
                                [provider]: { ...prev[provider], app_secret: e.target.value }
                            }))}
                        />
                    </div>
                );
            case 'amazon_s3':
                return (
                    <div className="credential-form">
                        <h4>Amazon S3 Credentials</h4>
                        <p className="help-text">
                            Enter your AWS IAM user credentials with S3 access.
                        </p>
                        <input
                            type="text"
                            placeholder="Access Key ID"
                            value={credentials[provider]?.access_key || ''}
                            onChange={(e) => setCredentials(prev => ({
                                ...prev,
                                [provider]: { ...prev[provider], access_key: e.target.value }
                            }))}
                        />
                        <input
                            type="password"
                            placeholder="Secret Access Key"
                            value={credentials[provider]?.secret_key || ''}
                            onChange={(e) => setCredentials(prev => ({
                                ...prev,
                                [provider]: { ...prev[provider], secret_key: e.target.value }
                            }))}
                        />
                        <input
                            type="text"
                            placeholder="Region (e.g., us-east-1)"
                            value={credentials[provider]?.region || ''}
                            onChange={(e) => setCredentials(prev => ({
                                ...prev,
                                [provider]: { ...prev[provider], region: e.target.value }
                            }))}
                        />
                        <input
                            type="text"
                            placeholder="Bucket Name"
                            value={credentials[provider]?.bucket || ''}
                            onChange={(e) => setCredentials(prev => ({
                                ...prev,
                                [provider]: { ...prev[provider], bucket: e.target.value }
                            }))}
                        />
                    </div>
                );
            default:
                return null;
        }
    };

    if (loading) return <LoadingSpinner />;

    const renderProviderHelp = (provider) => {
        const helpData = {
            google_drive: (
                <div className="provider-help-content">
                    <h4>How to Connect Google Drive</h4>
                    <ol>
                        <li>Go to <a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Google Cloud Console</a></li>
                        <li>Create a project and enable the Google Drive API</li>
                        <li>Go to Credentials → Create Credentials → OAuth 2.0 Client ID</li>
                        <li>Configure the consent screen with scope: <code>https://www.googleapis.com/auth/drive.file</code></li>
                        <li>Add redirect URI: <code>{window.location.origin}/wp-admin/admin.php?page=fly-backup-cloud</code></li>
                        <li>Copy Client ID and Client Secret</li>
                        <li>Use <a href="https://developers.google.com/oauthplayground" target="_blank" rel="noopener noreferrer">OAuth Playground</a> to exchange for an Access Token</li>
                        <li>Paste the Access Token here and click Connect</li>
                    </ol>
                    <p className="help-note"><strong>Tip:</strong> Save the Refresh Token to avoid re-authorizing every hour.</p>
                </div>
            ),
            dropbox: (
                <div className="provider-help-content">
                    <h4>How to Connect Dropbox</h4>
                    <ol>
                        <li>Go to <a href="https://www.dropbox.com/developers/apps" target="_blank" rel="noopener noreferrer">Dropbox App Console</a></li>
                        <li>Click <strong>Create app</strong> → Choose <strong>Scoped access</strong></li>
                        <li>Select <strong>App folder</strong> or <strong>Full Dropbox</strong></li>
                        <li>Name your app and click Create</li>
                        <li>Under <strong>Permissions</strong> tab, enable <code>files.content.write</code> and <code>files.content.read</code></li>
                        <li>Under <strong>Settings</strong> tab, click <strong>Generate access token</strong></li>
                        <li>Copy the Access Token and paste it here</li>
                        <li>Click Connect</li>
                    </ol>
                    <p className="help-note"><strong>Tip:</strong> The access token does not expire unless you manually revoke it.</p>
                </div>
            ),
            amazon_s3: (
                <div className="provider-help-content">
                    <h4>How to Connect Amazon S3</h4>
                    <ol>
                        <li>Go to <a href="https://console.aws.amazon.com/iam/" target="_blank" rel="noopener noreferrer">AWS IAM Console</a></li>
                        <li>Click <strong>Users</strong> → <strong>Create user</strong></li>
                        <li>Enter a username (e.g., <code>fly-backup-s3</code>)</li>
                        <li>Select <strong>Attach policies directly</strong></li>
                        <li>Attach <code>AmazonS3FullAccess</code> (or a custom policy with PutObject/GetObject)</li>
                        <li>Review and create user</li>
                        <li>On the success screen, copy <strong>Access Key ID</strong> and <strong>Secret Access Key</strong></li>
                        <li>Go to <a href="https://s3.console.aws.amazon.com/s3/" target="_blank" rel="noopener noreferrer">S3 Console</a> and create a bucket (or use existing)</li>
                        <li>Note the bucket's AWS Region (e.g., <code>us-east-1</code>)</li>
                        <li>Enter Access Key, Secret Key, Region, and Bucket Name here</li>
                        <li>Click Connect</li>
                    </ol>
                    <p className="help-note"><strong>Security Note:</strong> Never share your Secret Access Key. It grants full access to your AWS account resources.</p>
                </div>
            )
        };
        return helpData[provider] || null;
    };

    return (
        <div className="cloud-settings-page">
            <div className="page-header">
                <h1>Cloud Storage</h1>
                <p>Connect to cloud storage providers for offsite backups</p>
            </div>

            {message && (
                <div className={`notice notice-${message.type}`}>
                    <p>{message.text}</p>
                </div>
            )}

            <div className="providers-grid">
                {Object.entries(providers).map(([key, provider]) => (
                    <div key={key} className={`provider-card ${provider.connected ? 'connected' : ''}`}>
                        <div className="provider-header">
                            <h3>{provider.name}</h3>
                            <div className="provider-header-right">
                                <span className={`status-badge ${provider.connected ? 'connected' : 'disconnected'}`}>
                                    {provider.connected ? 'Connected' : 'Disconnected'}
                                </span>
                                <span className="help-icon-wrapper">
                                    <span className="dashicons dashicons-editor-help help-icon"></span>
                                    {renderProviderHelp(key)}
                                </span>
                            </div>
                        </div>

                        <div className="provider-body">
                            {activeProvider === key ? (
                                <>
                                    {renderCredentialForm(key)}
                                    <div className="form-actions">
                                        <button
                                            className="button button-primary"
                                            onClick={() => handleConnect(key)}
                                            disabled={connecting === key}
                                        >
                                            {connecting === key ? 'Connecting...' : 'Connect'}
                                        </button>
                                        <button
                                            className="button"
                                            onClick={() => setActiveProvider(null)}
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </>
                            ) : (
                                <div className="provider-actions">
                                    {provider.connected ? (
                                        <button
                                            className="button"
                                            onClick={() => handleDisconnect(key)}
                                        >
                                            Disconnect
                                        </button>
                                    ) : (
                                        <button
                                            className="button button-primary"
                                            onClick={() => setActiveProvider(key)}
                                        >
                                            Connect
                                        </button>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                ))}
            </div>
            
            <ConfirmModal
                isOpen={confirmModal.isOpen}
                title={confirmModal.title}
                message={confirmModal.message}
                onConfirm={confirmModal.onConfirm}
                onCancel={() => setConfirmModal(prev => ({ ...prev, isOpen: false }))}
                danger={confirmModal.danger}
            />
        </div>
    );
};

export default CloudSettings;
