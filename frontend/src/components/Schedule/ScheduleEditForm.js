import React, { useState, useEffect } from 'react';
import scheduleService from '../../services/scheduleService';
import AgentReassignmentModal from './AgentReassignmentModal';

const ScheduleEditForm = ({ schedule, onClose, onUpdate }) => {
  const [scheduleDetails, setScheduleDetails] = useState(null);
  const [metrics, setMetrics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState('assignments'); // assignments, metrics, validation
  const [showReassignmentModal, setShowReassignmentModal] = useState(false);
  const [selectedAgent, setSelectedAgent] = useState(null);

  useEffect(() => {
    loadScheduleDetails();
  }, [schedule.id]);

  const loadScheduleDetails = async () => {
    try {
      setLoading(true);
      setError('');
      
      // Pobierz szczegóły harmonogramu
      const details = await scheduleService.getById(schedule.id);
      setScheduleDetails(details);
      
      // Pobierz metryki
      const metricsData = await scheduleService.getMetrics(schedule.id);
      setMetrics(metricsData);
      
    } catch (err) {
      setError(err.message || 'Wystąpił błąd podczas ładowania szczegółów harmonogramu');
    } finally {
      setLoading(false);
    }
  };

  const handleChangeAvailability = (agent) => {
    setSelectedAgent(agent);
    setShowReassignmentModal(true);
  };

  const handleReassignmentComplete = async (result) => {
    // Odśwież dane po reassignment
    await loadScheduleDetails();
    
    // Pokaż komunikat sukcesu
    if (result.changes && result.changes.length > 0) {
      alert(`Pomyślnie zastąpiono ${result.changes.length} przypisań. ${result.unresolvedConflicts.length} konfliktów nierozwiązanych.`);
    }
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('pl-PL', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const formatDuration = (hours) => {
    const wholeHours = Math.floor(hours);
    const minutes = Math.round((hours - wholeHours) * 60);
    return `${wholeHours}h ${minutes > 0 ? `${minutes}min` : ''}`.trim();
  };

  const getStatusText = (status) => {
    const statusMap = {
      'draft': 'Szkic',
      'generated': 'Wygenerowany',
      'published': 'Opublikowany',
      'finalized': 'Sfinalizowany'
    };
    return statusMap[status] || status;
  };

  const getStatusClass = (status) => {
    const classMap = {
      'draft': 'status-draft',
      'generated': 'status-generated',
      'published': 'status-published',
      'finalized': 'status-finalized'
    };
    return classMap[status] || '';
  };

  if (loading) {
    return (
      <div className="schedule-edit-form">
        <div className="loading">Ładowanie szczegółów harmonogramu...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="schedule-edit-form">
        <div className="alert alert-error">{error}</div>
        <button onClick={onClose} className="btn btn-secondary">Zamknij</button>
      </div>
    );
  }

  return (
    <div className="schedule-edit-form">
      <div className="edit-header">
        <h3>Edycja harmonogramu #{scheduleDetails.id}</h3>
        <button onClick={onClose} className="btn btn-secondary">Zamknij</button>
      </div>

      {/* Informacje podstawowe */}
      <div className="schedule-info">
        <div className="info-grid">
          <div className="info-item">
            <label>Typ kolejki:</label>
            <span>{scheduleDetails.queueType.name}</span>
          </div>
          <div className="info-item">
            <label>Tydzień:</label>
            <span>{formatDate(scheduleDetails.weekStartDate)} - {formatDate(scheduleDetails.weekEndDate)}</span>
          </div>
          <div className="info-item">
            <label>Status:</label>
            <span className={`status ${getStatusClass(scheduleDetails.status)}`}>
              {getStatusText(scheduleDetails.status)}
            </span>
          </div>
          <div className="info-item">
            <label>Łączne godziny:</label>
            <span>{scheduleDetails.totalAssignedHours} h</span>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="tabs">
        <button 
          className={`tab-button ${activeTab === 'assignments' ? 'active' : ''}`}
          onClick={() => setActiveTab('assignments')}
        >
          Przypisania ({scheduleDetails.assignments.length})
        </button>
        <button 
          className={`tab-button ${activeTab === 'metrics' ? 'active' : ''}`}
          onClick={() => setActiveTab('metrics')}
        >
          Metryki
        </button>
        <button 
          className={`tab-button ${activeTab === 'validation' ? 'active' : ''}`}
          onClick={() => setActiveTab('validation')}
        >
          Walidacja
        </button>
      </div>

      {/* Tab content */}
      <div className="tab-content">
        {activeTab === 'assignments' && (
          <div className="assignments-tab">
            <h4>Przypisania agentów</h4>
            {scheduleDetails.assignments.length === 0 ? (
              <div className="empty-state">
                <p>Brak przypisań dla tego harmonogramu.</p>
              </div>
            ) : (
              <div className="table-responsive">
                <table className="table">
                  <thead>
                    <tr>
                      <th>Agent</th>
                      <th>Data rozpoczęcia</th>
                      <th>Data zakończenia</th>
                      <th>Czas trwania</th>
                      <th>Akcje</th>
                    </tr>
                  </thead>
                  <tbody>
                    {scheduleDetails.assignments.map((assignment) => (
                      <tr key={assignment.id}>
                        <td>
                          <div className="agent-info">
                            <span className="agent-name">{assignment.agentName}</span>
                            <small className="agent-id">ID: {assignment.agentId}</small>
                          </div>
                        </td>
                        <td>{formatDate(assignment.startTime)}</td>
                        <td>{formatDate(assignment.endTime)}</td>
                        <td>{formatDuration(assignment.duration)}</td>
                        <td>
                          <button
                            onClick={() => handleChangeAvailability({
                              id: assignment.agentId,
                              name: assignment.agentName
                            })}
                            className="btn btn-sm btn-warning"
                            title="Zmień dostępność agenta"
                          >
                            Zmień dostępność
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        )}

        {activeTab === 'metrics' && metrics && (
          <div className="metrics-tab">
            <h4>Metryki harmonogramu</h4>
            <div className="metrics-grid">
              <div className="metric-card">
                <h5>Podstawowe metryki</h5>
                <div className="metric-item">
                  <label>Łączne godziny:</label>
                  <span>{metrics.metrics.totalHours} h</span>
                </div>
                <div className="metric-item">
                  <label>Liczba agentów:</label>
                  <span>{metrics.metrics.agentCount}</span>
                </div>
                <div className="metric-item">
                  <label>Średnie godziny na agenta:</label>
                  <span>{metrics.metrics.averageHoursPerAgent.toFixed(2)} h</span>
                </div>
                <div className="metric-item">
                  <label>Maksymalne godziny na agenta:</label>
                  <span>{metrics.metrics.maxHoursPerAgent} h</span>
                </div>
                <div className="metric-item">
                  <label>Minimalne godziny na agenta:</label>
                  <span>{metrics.metrics.minHoursPerAgent} h</span>
                </div>
              </div>

              <div className="metric-card">
                <h5>Pokrycie godzinowe</h5>
                <div className="hourly-coverage">
                  {Object.entries(metrics.metrics.hourlyCoverage).slice(0, 10).map(([hour, coverage]) => (
                    <div key={hour} className="coverage-item">
                      <span className="hour">{formatDate(hour)}</span>
                      <span className="coverage">{coverage} h</span>
                    </div>
                  ))}
                  {Object.keys(metrics.metrics.hourlyCoverage).length > 10 && (
                    <div className="coverage-more">
                      <small>... i {Object.keys(metrics.metrics.hourlyCoverage).length - 10} więcej</small>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'validation' && metrics && (
          <div className="validation-tab">
            <h4>Walidacja harmonogramu</h4>
            <div className="validation-status">
              <div className={`validation-indicator ${metrics.validation.isValid ? 'valid' : 'invalid'}`}>
                {metrics.validation.isValid ? '✓ Harmonogram jest poprawny' : '✗ Harmonogram zawiera błędy'}
              </div>
              
              {metrics.validation.totalViolations > 0 && (
                <div className="violations-list">
                  <h5>Wykryte problemy ({metrics.validation.totalViolations}):</h5>
                  <ul>
                    {metrics.validation.violations.map((violation, index) => (
                      <li key={index} className="violation-item">
                        {violation}
                      </li>
                    ))}
                  </ul>
                </div>
              )}
              
              {metrics.validation.isValid && (
                <div className="validation-success">
                  <p>✓ Wszystkie ograniczenia są spełnione</p>
                  <p>✓ Brak nakładających się przypisań</p>
                  <p>✓ Limity godzin pracy są respektowane</p>
                </div>
              )}
            </div>
          </div>
        )}
      </div>

      {/* Modal reassignment */}
      {showReassignmentModal && selectedAgent && (
        <AgentReassignmentModal
          schedule={schedule}
          agent={selectedAgent}
          onReassign={handleReassignmentComplete}
          onClose={() => {
            setShowReassignmentModal(false);
            setSelectedAgent(null);
          }}
        />
      )}
    </div>
  );
};

export default ScheduleEditForm;
