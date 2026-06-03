import React, { useState, useEffect } from 'react';
import { getBackups, compareCurrentVsBackup, compareBackupVsBackup, getTableDiff } from '../utils/api';
import LoadingSpinner from '../components/LoadingSpinner';

const Compare = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const [backups, setBackups] = useState([]);
    const [loading, setLoading] = useState(true);
    const [comparing, setComparing] = useState(false);
    const [results, setResults] = useState(null);
    const [error, setError] = useState(null);
    
    const [mode, setMode] = useState(urlParams.get('mode') || 'current');
    const [sourceId, setSourceId] = useState(urlParams.get('source') || '');
    const [targetId, setTargetId] = useState(urlParams.get('target') || '');
    const [expandedTables, setExpandedTables] = useState({});
    const [tableDiffs, setTableDiffs] = useState({});
    const [loadingDiff, setLoadingDiff] = useState({});
    const [expandedFiles, setExpandedFiles] = useState({
        added: false,
        removed: false,
        changed: false,
        unchanged: false
    });

    useEffect(() => {
        loadBackups();
    }, []);

    const loadBackups = async () => {
        try {
            const data = await getBackups({ limit: 100 });
            setBackups(data.backups || []);
        } catch (error) {
            console.error('Error loading backups:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleCompare = async () => {
        if (!targetId) {
            setError('Please select a target backup');
            return;
        }
        
        setComparing(true);
        setError(null);
        setResults(null);
        
        try {
            let result;
            if (mode === 'current') {
                result = await compareCurrentVsBackup(targetId);
            } else {
                if (!sourceId) {
                    setError('Please select a source backup');
                    setComparing(false);
                    return;
                }
                result = await compareBackupVsBackup(sourceId, targetId);
            }
            setResults(result);
        } catch (err) {
            setError('Comparison failed: ' + err.message);
        } finally {
            setComparing(false);
        }
    };

    const toggleTable = (tableName) => {
        setExpandedTables(prev => ({
            ...prev,
            [tableName]: !prev[tableName]
        }));
    };

    const loadTableDiff = async (tableName) => {
        if (tableDiffs[tableName]) {
            return;
        }

        setLoadingDiff(prev => ({ ...prev, [tableName]: true }));
        
        try {
            let sourceBackupId, targetBackupId;
            if (mode === 'current') {
                sourceBackupId = 0;
                targetBackupId = parseInt(targetId);
            } else {
                sourceBackupId = parseInt(sourceId);
                targetBackupId = parseInt(targetId);
            }

            const diff = await getTableDiff(targetBackupId, tableName, sourceBackupId, targetBackupId);
            setTableDiffs(prev => ({ ...prev, [tableName]: diff }));
        } catch (err) {
            console.error('Error loading table diff:', err);
        } finally {
            setLoadingDiff(prev => ({ ...prev, [tableName]: false }));
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            added: { class: 'status-added', label: 'Added' },
            removed: { class: 'status-removed', label: 'Removed' },
            changed: { class: 'status-changed', label: 'Changed' },
            unchanged: { class: 'status-unchanged', label: 'Unchanged' }
        };
        const badge = badges[status] || badges.unchanged;
        return <span className={`status-badge ${badge.class}`}>{badge.label}</span>;
    };

    const filterByStatus = (items, status) => {
        return items.filter(item => item.status === status);
    };

    if (loading) return <LoadingSpinner />;

    return (
        <div className="compare-page">
            <div className="page-header">
                <h1>Compare Backups</h1>
            </div>

            <div className="compare-form">
                <div className="form-group">
                    <label>Comparison Mode</label>
                    <select 
                        className="regular-text"
                        value={mode}
                        onChange={(e) => {
                            setMode(e.target.value);
                            setResults(null);
                            setError(null);
                        }}
                    >
                        <option value="current">Current Site vs Backup</option>
                        <option value="backup">Backup vs Backup</option>
                    </select>
                </div>

                {mode === 'backup' && (
                    <div className="form-group">
                        <label>Source Backup</label>
                        <select 
                            className="regular-text"
                            value={sourceId}
                            onChange={(e) => setSourceId(e.target.value)}
                        >
                            <option value="">Select source backup...</option>
                            {backups.map(backup => (
                                <option key={backup.id} value={backup.id}>
                                    #{backup.id} - {backup.backup_name || backup.backup_type} ({backup.time_ago})
                                </option>
                            ))}
                        </select>
                    </div>
                )}

                <div className="form-group">
                    <label>{mode === 'current' ? 'Compare with Backup' : 'Target Backup'}</label>
                    <select 
                        className="regular-text"
                        value={targetId}
                        onChange={(e) => setTargetId(e.target.value)}
                    >
                        <option value="">Select backup...</option>
                        {backups.map(backup => (
                            <option key={backup.id} value={backup.id}>
                                #{backup.id} - {backup.backup_name || backup.backup_type} ({backup.time_ago})
                            </option>
                        ))}
                    </select>
                </div>

                <button 
                    className="button button-primary"
                    onClick={handleCompare}
                    disabled={comparing}
                >
                    {comparing ? 'Comparing...' : 'Start Comparison'}
                </button>
            </div>

            {error && (
                <div className="notice notice-error">
                    <p>{error}</p>
                </div>
            )}

            {results && (
                <div className="compare-results">
                    <h2>Comparison Results</h2>
                    
                    {/* Summary */}
                    <div className="summary-cards">
                        <div className="summary-card">
                            <h3>Database Tables</h3>
                            <div className="summary-stats">
                                <div className="stat">
                                    <span className="stat-value added">{results.summary.tables_added}</span>
                                    <span className="stat-label">Added</span>
                                </div>
                                <div className="stat">
                                    <span className="stat-value removed">{results.summary.tables_removed}</span>
                                    <span className="stat-label">Removed</span>
                                </div>
                                <div className="stat">
                                    <span className="stat-value changed">{results.summary.tables_changed}</span>
                                    <span className="stat-label">Changed</span>
                                </div>
                                <div className="stat">
                                    <span className="stat-value unchanged">{results.summary.tables_unchanged}</span>
                                    <span className="stat-label">Unchanged</span>
                                </div>
                            </div>
                        </div>

                        <div className="summary-card">
                            <h3>Files</h3>
                            <div className="summary-stats">
                                <div className="stat">
                                    <span className="stat-value added">{results.summary.files_added}</span>
                                    <span className="stat-label">Added</span>
                                </div>
                                <div className="stat">
                                    <span className="stat-value removed">{results.summary.files_removed}</span>
                                    <span className="stat-label">Removed</span>
                                </div>
                                <div className="stat">
                                    <span className="stat-value changed">{results.summary.files_changed}</span>
                                    <span className="stat-label">Changed</span>
                                </div>
                                <div className="stat">
                                    <span className="stat-value unchanged">{results.summary.files_unchanged}</span>
                                    <span className="stat-label">Unchanged</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Database Comparison */}
                    <div className="comparison-section">
                        <h3>Database Changes</h3>
                        {Object.keys(results.database).length > 0 ? (
                            <div className="database-diff">
                                {Object.entries(results.database).map(([tableName, data]) => (
                                    <div key={tableName} className={`diff-item diff-${data.status}`}>
                                        <div 
                                            className="diff-header"
                                            onClick={() => toggleTable(tableName)}
                                        >
                                            <code>{tableName}</code>
                                            {getStatusBadge(data.status)}
                                            {data.status === 'changed' && (
                                                <span className="diff-count">
                                                    {data.rows_diff > 0 ? '+' : ''}{data.rows_diff} rows
                                                </span>
                                            )}
                                            <span className="expand-icon">
                                                {expandedTables[tableName] ? '▼' : '▶'}
                                            </span>
                                        </div>
                                        
                                        {expandedTables[tableName] && (
                                            <div className="diff-details">
                                                {results.mode === 'current_vs_backup' ? (
                                                    <div className="row-comparison">
                                                        <div className="row-side">
                                                            <strong>Current:</strong> {data.rows_current} rows ({data.size_current})
                                                        </div>
                                                        <div className="row-side">
                                                            <strong>Backup:</strong> {data.rows_backup} rows ({data.size_backup})
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <div className="row-comparison">
                                                        <div className="row-side">
                                                            <strong>Source:</strong> {data.rows_source} rows ({data.size_source})
                                                        </div>
                                                        <div className="row-side">
                                                            <strong>Target:</strong> {data.rows_target} rows ({data.size_target})
                                                        </div>
                                                    </div>
                                                )}
                                                
                                                {data.status === 'changed' && (
                                                    <div className="row-diff-actions">
                                                        {!tableDiffs[tableName] ? (
                                                            <button 
                                                                className="button button-small"
                                                                onClick={() => loadTableDiff(tableName)}
                                                                disabled={loadingDiff[tableName]}
                                                            >
                                                                {loadingDiff[tableName] ? 'Loading...' : 'View Row Details'}
                                                            </button>
                                                        ) : (
                                                            <div className="table-diff-results">
                                                                <div className="diff-summary">
                                                                    <span className="diff-stat added">+{tableDiffs[tableName].total_added} added</span>
                                                                    <span className="diff-stat removed">-{tableDiffs[tableName].total_removed} removed</span>
                                                                    <span className="diff-stat changed">{tableDiffs[tableName].total_modified} modified</span>
                                                                </div>
                                                                
                                                                {tableDiffs[tableName].total_added > 0 && (
                                                                    <div className="diff-section">
                                                                        <h4>Added Rows ({tableDiffs[tableName].total_added}):</h4>
                                                                        {tableDiffs[tableName].added.slice(0, 5).map((row, idx) => (
                                                                            <pre key={idx} className="row-data">{JSON.stringify(row, null, 2)}</pre>
                                                                        ))}
                                                                        {tableDiffs[tableName].total_added > 5 && (
                                                                            <p className="more-rows">... and {tableDiffs[tableName].total_added - 5} more</p>
                                                                        )}
                                                                    </div>
                                                                )}
                                                                
                                                                {tableDiffs[tableName].total_removed > 0 && (
                                                                    <div className="diff-section">
                                                                        <h4>Removed Rows ({tableDiffs[tableName].total_removed}):</h4>
                                                                        {tableDiffs[tableName].removed.slice(0, 5).map((row, idx) => (
                                                                            <pre key={idx} className="row-data">{JSON.stringify(row, null, 2)}</pre>
                                                                        ))}
                                                                        {tableDiffs[tableName].total_removed > 5 && (
                                                                            <p className="more-rows">... and {tableDiffs[tableName].total_removed - 5} more</p>
                                                                        )}
                                                                    </div>
                                                                )}
                                                                
                                                                {tableDiffs[tableName].total_modified > 0 && (
                                                                    <div className="diff-section">
                                                                        <h4>Modified Rows ({tableDiffs[tableName].total_modified}):</h4>
                                                                        {tableDiffs[tableName].modified.slice(0, 3).map((change, idx) => (
                                                                            <div key={idx} className="row-change">
                                                                                <div className="before">Before: <pre>{JSON.stringify(change.before, null, 2)}</pre></div>
                                                                                <div className="after">After: <pre>{JSON.stringify(change.after, null, 2)}</pre></div>
                                                                            </div>
                                                                        ))}
                                                                        {tableDiffs[tableName].total_modified > 3 && (
                                                                            <p className="more-rows">... and {tableDiffs[tableName].total_modified - 3} more</p>
                                                                        )}
                                                                    </div>
                                                                )}
                                                                
                                                                {tableDiffs[tableName].has_more && (
                                                                    <p className="notice-info">Showing limited results to prevent memory issues. Full diff contains more rows.</p>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p>No database changes detected.</p>
                        )}
                    </div>

                    {/* Files Comparison */}
                    <div className="comparison-section">
                        <h3>
                            File Changes
                            <button 
                                className="button button-small"
                                onClick={() => {
                                    const anyExpanded = Object.values(expandedFiles).some(v => v);
                                    setExpandedFiles({
                                        added: !anyExpanded,
                                        removed: !anyExpanded,
                                        changed: !anyExpanded,
                                        unchanged: !anyExpanded
                                    });
                                }}
                            >
                                {Object.values(expandedFiles).some(v => v) ? 'Collapse All' : 'Expand All'}
                            </button>
                        </h3>
                        {results.files && results.files.length > 0 ? (
                            <div className="file-diff">
                                {['added', 'removed', 'changed', 'unchanged'].map(status => {
                                    const items = filterByStatus(results.files, status);
                                    if (items.length === 0) return null;
                                    
                                    if (expandedFiles[status]) {
                                        return (
                                            <div key={status}>
                                                <div className={`summary-row status-${status}`} style={{marginBottom: '5px'}}>
                                                    <span className="status-label">{status.charAt(0).toUpperCase() + status.slice(1)}:</span>
                                                    <span className="status-count">{items.length} files</span>
                                                    <button 
                                                        className="button-link"
                                                        onClick={() => setExpandedFiles(prev => ({...prev, [status]: false}))}
                                                    >
                                                        Hide details
                                                    </button>
                                                </div>
                                                {items.slice(0, 50).map((file, idx) => (
                                                    <div key={idx} className={`diff-item diff-${file.status}`}>
                                                        <div className="diff-header">
                                                            <span className="file-path">{file.path}</span>
                                                            {getStatusBadge(file.status)}
                                                            <span className="file-size">
                                                                {results.mode === 'current_vs_backup' 
                                                                    ? `${file.size_backup} → ${file.size_current}`
                                                                    : `${file.size_source} → ${file.size_target}`
                                                                }
                                                            </span>
                                                        </div>
                                                    </div>
                                                ))}
                                                {items.length > 50 && (
                                                    <p className="more-files">... and {items.length - 50} more files</p>
                                                )}
                                            </div>
                                        );
                                    }
                                    
                                    return (
                                        <div key={status} className={`summary-row status-${status}`}>
                                            <span className="status-label">{status.charAt(0).toUpperCase() + status.slice(1)}:</span>
                                            <span className="status-count">{items.length} files</span>
                                            <button 
                                                className="button-link"
                                                onClick={() => setExpandedFiles(prev => ({...prev, [status]: true}))}
                                            >
                                                View details
                                            </button>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <p>No file changes detected.</p>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};

export default Compare;
