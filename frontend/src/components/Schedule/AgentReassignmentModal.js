import React, { useState, useEffect } from 'react';
import scheduleService from '../../services/scheduleService';
import { formatDateTimeUTC } from '../../utils/dateUtils';

const AgentReassignmentModal = ({ schedule, agent, onReassign, onClose }) => {
  const [newAvailability, setNewAvailability] = useState({
    startTime: '',
    endTime: ''
  });
  const [preview, setPreview] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [showPreview, setShowPreview] = useState(false);

  useEffect(() => {
    if (agent && agent.currentAvailability) {
      setNewAvailability({
        startTime: agent.currentAvailability.startTime || '',
        endTime: agent.currentAvailability.endTime || ''
      });
    }
  }, [agent]);

  const handlePreview = async () => {
    if (!newAvailability.startTime || !newAvailability.endTime) {
      setError('Proszę wypełnić wszystkie pola dostępności');
      return;
    }

    try {
      setLoading(true);
      setError('');
      
      const previewData = await scheduleService.getReassignmentPreview(
        schedule.id,
        agent.id,
        newAvailability
      );
      
      setPreview(previewData);
      setShowPreview(true);
    } catch (err) {
      setError(err.message || 'Wystąpił błąd podczas generowania preview');
    } finally {
      setLoading(false);
    }
  };

  const handleReassign = async () => {
    try {
      setLoading(true);
      setError('');
      
      const result = await scheduleService.reassignAgent(
        schedule.id,
        agent.id,
        newAvailability
      );
      
      onReassign(result);
      onClose();
    } catch (err) {
      setError(err.message || 'Wystąpił błąd podczas reassignment');
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString) => {
    return formatDateTimeUTC(dateString);
  };

  const formatDuration = (hours) => {
    const wholeHours = Math.floor(hours);
    const minutes = Math.round((hours - wholeHours) * 60);
    return `${wholeHours}h ${minutes > 0 ? `${minutes}min` : ''}`.trim();
  };

  return (
    <div className="modal-overlay">
      <div className="reassignment-modal">
        <div className="modal-header">
          <h3>Zmiana dostępności agenta {agent?.name}</h3>
          <button onClick={onClose} className="btn-close">×</button>
        </div>

        {error && (
          <div className="alert alert-error">
            {error}
          </div>
        )}

        <div className="modal-content">
          {/* Formularz nowej dostępności */}
          <div className="availability-form">
            <h4>Nowa dostępność</h4>
            <div className="form-group">
              <label>Data i godzina rozpoczęcia:</label>
              <input
                type="datetime-local"
                value={newAvailability.startTime}
                onChange={(e) => setNewAvailability(prev => ({
                  ...prev,
                  startTime: e.target.value
                }))}
                className="form-control"
              />
            </div>
            <div className="form-group">
              <label>Data i godzina zakończenia:</label>
              <input
                type="datetime-local"
                value={newAvailability.endTime}
                onChange={(e) => setNewAvailability(prev => ({
                  ...prev,
                  endTime: e.target.value
                }))}
                className="form-control"
              />
            </div>
          </div>

          {/* Preview zmian */}
          {showPreview && preview && (
            <div className="preview-section">
              <h4>Podgląd zmian</h4>
              {preview.length === 0 ? (
                <div className="no-conflicts">
                  <p>✓ Brak konfliktów z nową dostępnością</p>
                </div>
              ) : (
                <div className="conflicts-list">
                  <p>Znaleziono {preview.length} konfliktujących przypisań:</p>
                  {preview.map((conflict) => (
                    <div key={conflict.assignmentId} className="conflict-item">
                      <div className="conflict-header">
                        <span className="conflict-date">{conflict.date} {conflict.time}</span>
                        <span className="conflict-duration">{formatDuration(conflict.duration)}</span>
                      </div>
                      <div className="conflict-details">
                        <div className="current-agent">
                          <strong>Aktualny agent:</strong> {conflict.currentAgent.name}
                        </div>
                        {conflict.suggestedReplacement ? (
                          <div className="suggested-replacement">
                            <strong>Sugerowany zastępca:</strong> {conflict.suggestedReplacement.name}
                            <small>(Efektywność: {conflict.suggestedReplacement.efficiencyScore})</small>
                          </div>
                        ) : (
                          <div className="no-replacement">
                            <strong>Brak dostępnego zastępcy</strong>
                          </div>
                        )}
                      </div>
                      <div className={`replacement-status ${conflict.canBeReplaced ? 'can-replace' : 'cannot-replace'}`}>
                        {conflict.canBeReplaced ? '✓ Można zastąpić' : '✗ Nie można zastąpić'}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* Akcje */}
          <div className="modal-actions">
            {!showPreview ? (
              <button 
                onClick={handlePreview}
                disabled={loading || !newAvailability.startTime || !newAvailability.endTime}
                className="btn btn-primary"
              >
                {loading ? 'Generowanie preview...' : 'Podgląd zmian'}
              </button>
            ) : (
              <>
                <button 
                  onClick={handleReassign}
                  disabled={loading || preview.some(c => !c.canBeReplaced)}
                  className="btn btn-success"
                >
                  {loading ? 'Przeprowadzanie zmian...' : 'Zastąp automatycznie'}
                </button>
                <button 
                  onClick={() => setShowPreview(false)}
                  disabled={loading}
                  className="btn btn-secondary"
                >
                  Wróć do edycji
                </button>
              </>
            )}
            <button 
              onClick={onClose}
              disabled={loading}
              className="btn btn-secondary"
            >
              Anuluj
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AgentReassignmentModal;
