import React, { useState, useEffect } from 'react';
import scheduleService from '../../services/scheduleService';
import AgentReassignmentModal from './AgentReassignmentModal';
import { formatDateTimeUTC } from '../../utils/dateUtils';

const ScheduleEditForm = ({ schedule, onClose, onUpdate }) => {
  const [scheduleDetails, setScheduleDetails] = useState(null);
  const [metrics, setMetrics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState('assignments'); // assignments, calendar, metrics, validation
  const [viewMode, setViewMode] = useState('calendar'); // calendar, table, cards
  const [showReassignmentModal, setShowReassignmentModal] = useState(false);
  const [selectedAgent, setSelectedAgent] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedDay, setSelectedDay] = useState('all');

  useEffect(() => {
    loadScheduleDetails();
  }, [schedule.id]);

  const loadScheduleDetails = async () => {
    try {
      setLoading(true);
      setError('');
      
      // Resetuj filtry przy ładowaniu nowych danych
      setSearchTerm('');
      setSelectedDay('all');
      
      // Pobierz szczegóły harmonogramu
      const details = await scheduleService.getById(schedule.id);
      setScheduleDetails(details);
      
      // Pobierz metryki
      const metricsData = await scheduleService.getMetrics(schedule.id);
      setMetrics(metricsData);
      
    } catch (err) {
      console.error('Error loading schedule details:', err);
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

  // Funkcja do resetowania filtrów
  const resetFilters = () => {
    setSearchTerm('');
    setSelectedDay('all');
  };

  // Funkcja do czyszczenia wszystkich filtrów
  const clearAllFilters = () => {
    resetFilters();
  };

  const formatDate = (dateString) => {
    return formatDateTimeUTC(dateString);
  };

  const formatDuration = (hours) => {
    const wholeHours = Math.floor(hours);
    const minutes = Math.round((hours - wholeHours) * 60);
    return `${wholeHours}h ${minutes > 0 ? `${minutes}min` : ''}`.trim();
  };

  // Funkcja pomocnicza do bezpiecznego dostępu do metryk
  const getMetricsValue = (path, defaultValue = 0) => {
    if (!metrics || !metrics.metrics) return defaultValue;
    
    const keys = path.split('.');
    let value = metrics.metrics;
    
    for (const key of keys) {
      if (value && typeof value === 'object' && key in value) {
        value = value[key];
      } else {
        return defaultValue;
      }
    }
    
    return value !== undefined && value !== null ? value : defaultValue;
  };

  // Funkcja pomocnicza do bezpiecznego dostępu do walidacji
  const getValidationValue = (path, defaultValue = null) => {
    if (!metrics || !metrics.validation) return defaultValue;
    
    const keys = path.split('.');
    let value = metrics.validation;
    
    for (const key of keys) {
      if (value && typeof value === 'object' && key in value) {
        value = value[key];
      } else {
        return defaultValue;
      }
    }
    
    return value !== undefined && value !== null ? value : defaultValue;
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

  // Funkcje do obsługi widoku kalendarza
  const getWeekDays = () => {
    if (!scheduleDetails) return [];
    
    const startDate = new Date(scheduleDetails.weekStartDate);
    const days = [];
    
    for (let i = 0; i < 7; i++) {
      const date = new Date(startDate);
      date.setDate(startDate.getDate() + i);
      days.push({
        date: date,
        dayName: date.toLocaleDateString('pl-PL', { weekday: 'long' }),
        dayShort: date.toLocaleDateString('pl-PL', { weekday: 'short' }),
        dateFormatted: date.toLocaleDateString('pl-PL', { day: 'numeric', month: 'short' })
      });
    }
    
    return days;
  };

  const getAssignmentsForDay = (dayDate) => {
    if (!scheduleDetails?.assignments) return [];
    
    const dayAssignments = scheduleDetails.assignments.filter(assignment => {
      // Sprawdź czy data jest w formacie UTC
      let assignmentDate;
      if (assignment.startTime.includes('Z') || assignment.startTime.includes('+')) {
        // Data jest w UTC, przekonwertuj na lokalny czas
        assignmentDate = new Date(assignment.startTime);
      } else {
        // Data jest już w lokalnym czasie
        assignmentDate = new Date(assignment.startTime);
      }
      
      const isSameDay = assignmentDate.toDateString() === dayDate.toDateString();
      
      return isSameDay;
    });
    
    return dayAssignments;
  };

  const getHourLabel = (hour) => {
    return `${hour.toString().padStart(2, '0')}:00`;
  };

  const getAssignmentPosition = (assignment) => {
    const startTime = new Date(assignment.startTime);
    const hour = startTime.getHours();
    const minutes = startTime.getMinutes();
    
    // Pozycja od góry (każda godzina = 60px, każda minuta = 1px)
    const top = hour * 60 + minutes;
    
    // Wysokość na podstawie czasu trwania
    const height = assignment.duration * 60;
    
    return { top, height };
  };

  // Funkcje do obsługi widoku kart agentów
  const getAgentsWithAssignments = () => {
    if (!scheduleDetails?.assignments) return [];
    
    const agentMap = new Map();
    
    scheduleDetails.assignments.forEach(assignment => {
      if (!agentMap.has(assignment.agentId)) {
        agentMap.set(assignment.agentId, {
          id: assignment.agentId,
          name: assignment.agentName,
          assignments: [],
          totalHours: 0
        });
      }
      
      const agent = agentMap.get(assignment.agentId);
      agent.assignments.push(assignment);
      agent.totalHours += assignment.duration;
    });
    
    return Array.from(agentMap.values()).sort((a, b) => a.name.localeCompare(b.name));
  };

  const getAssignmentsForAgent = (agentId) => {
    if (!scheduleDetails?.assignments) return [];
    
    return scheduleDetails.assignments
      .filter(assignment => assignment.agentId === agentId)
      .sort((a, b) => new Date(a.startTime) - new Date(b.startTime));
  };

  // Funkcje do filtrowania
  const getFilteredAssignments = () => {
    if (!scheduleDetails?.assignments) return [];
    
    let filtered = scheduleDetails.assignments;
    
    // Filtrowanie według wyszukiwania - tylko jeśli jest coś wpisane
    if (searchTerm && searchTerm.trim() !== '') {
      filtered = filtered.filter(assignment => 
        assignment.agentName.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }
    
    // Filtrowanie według dnia
    if (selectedDay !== 'all') {
      const dayIndex = parseInt(selectedDay);
      const weekDays = getWeekDays();
      if (weekDays[dayIndex]) {
        const targetDate = weekDays[dayIndex].date;
        filtered = filtered.filter(assignment => {
          const assignmentDate = new Date(assignment.startTime);
          return assignmentDate.toDateString() === targetDate.toDateString();
        });
      }
    }
    
    return filtered;
  };

  const getFilteredAgents = () => {
    const filteredAssignments = getFilteredAssignments();
    const agentMap = new Map();
    
    filteredAssignments.forEach(assignment => {
      if (!agentMap.has(assignment.agentId)) {
        agentMap.set(assignment.agentId, {
          id: assignment.agentId,
          name: assignment.agentName,
          assignments: [],
          totalHours: 0
        });
      }
      
      const agent = agentMap.get(assignment.agentId);
      agent.assignments.push(assignment);
      agent.totalHours += assignment.duration;
    });
    
    return Array.from(agentMap.values()).sort((a, b) => a.name.localeCompare(b.name));
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
            <div className="assignments-header">
              <h4>Przypisania agentów</h4>
              <div className="assignments-controls">
                <div className="assignments-info">
                  <span className="assignments-count">
                    {scheduleDetails?.assignments?.length || 0} przypisań
                    {(searchTerm || selectedDay !== 'all') && (
                      <span className="filtered-count">
                        {' '}(wyświetlone: {getFilteredAssignments().length})
                      </span>
                    )}
                  </span>
                </div>
                <div className="filters">
                  <input
                    type="text"
                    placeholder="Szukaj agenta..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="search-input"
                  />
                  <select
                    value={selectedDay}
                    onChange={(e) => setSelectedDay(e.target.value)}
                    className="day-filter"
                  >
                    <option value="all">Wszystkie dni</option>
                    {getWeekDays().map((day, index) => (
                      <option key={index} value={index}>
                        {day.dayName} ({day.dateFormatted})
                      </option>
                    ))}
                  </select>
                  {(searchTerm || selectedDay !== 'all') && (
                    <button
                      onClick={clearAllFilters}
                      className="btn btn-sm btn-secondary"
                      title="Pokaż wszystkie przypisania"
                    >
                      🗑️ Wyczyść
                    </button>
                  )}
                </div>
                <div className="view-mode-toggle">
                  <button
                    className={`view-mode-btn ${viewMode === 'calendar' ? 'active' : ''}`}
                    onClick={() => setViewMode('calendar')}
                  >
                    📅 Kalendarz
                  </button>
                  <button
                    className={`view-mode-btn ${viewMode === 'cards' ? 'active' : ''}`}
                    onClick={() => setViewMode('cards')}
                  >
                    👥 Karty agentów
                  </button>
                  <button
                    className={`view-mode-btn ${viewMode === 'table' ? 'active' : ''}`}
                    onClick={() => setViewMode('table')}
                  >
                    📋 Tabela
                  </button>
                </div>
              </div>
            </div>

            {scheduleDetails.assignments.length === 0 ? (
              <div className="empty-state">
                <p>Brak przypisań dla tego harmonogramu.</p>
              </div>
            ) : (
              <>
                {/* Informacje o filtrach */}
                {(searchTerm || selectedDay !== 'all') && (
                  <div className="filter-info">
                    <span className="filter-label">Filtry aktywne:</span>
                    {searchTerm && (
                      <span className="filter-tag">
                        Szukaj: "{searchTerm}"
                        <button 
                          onClick={() => setSearchTerm('')}
                          className="filter-remove"
                        >
                          ×
                        </button>
                      </span>
                    )}
                    {selectedDay !== 'all' && (
                      <span className="filter-tag">
                        Dzień: {getWeekDays()[parseInt(selectedDay)]?.dayName}
                        <button 
                          onClick={() => setSelectedDay('all')}
                          className="filter-remove"
                        >
                          ×
                        </button>
                      </span>
                    )}
                    <button 
                      onClick={clearAllFilters}
                      className="clear-filters"
                    >
                      Wyczyść wszystkie filtry
                    </button>
                  </div>
                )}

                {viewMode === 'calendar' ? (
                  <div className="calendar-view">
                    <div className="calendar-header">
                      <div className="time-column-header">Godzina</div>
                      {getWeekDays().map((day, index) => (
                        <div key={index} className="day-column-header">
                          <div className="day-name">{day.dayShort}</div>
                          <div className="day-date">{day.dateFormatted}</div>
                        </div>
                      ))}
                    </div>
                    
                    <div className="calendar-body">
                      <div className="time-column">
                        {Array.from({ length: 24 }, (_, hour) => (
                          <div key={hour} className="time-slot">
                            <div className="time-label">{getHourLabel(hour)}</div>
                          </div>
                        ))}
                      </div>
                      
                      {getWeekDays().map((day, dayIndex) => (
                        <div key={dayIndex} className="day-column">
                          {Array.from({ length: 24 }, (_, hour) => (
                            <div key={hour} className="time-slot">
                              {(() => {
                                const dayAssignments = getAssignmentsForDay(day.date);
                                const hourAssignments = dayAssignments.filter(assignment => {
                                  const startHour = new Date(assignment.startTime).getHours();
                                  return startHour === hour;
                                });
                                
                                // Pokaż liczbę przypisań jeśli jest więcej niż 1
                                if (hourAssignments.length > 1) {
                                  return (
                                    <div className="assignments-count-badge">
                                      {hourAssignments.length}
                                    </div>
                                  );
                                }
                                return null;
                              })()}
                              
                              {(() => {
                                const dayAssignments = getAssignmentsForDay(day.date);
                                const hourAssignments = dayAssignments.filter(assignment => {
                                  const startHour = new Date(assignment.startTime).getHours();
                                  return startHour === hour;
                                });
                                
                                return hourAssignments
                                  .filter(assignment => {
                                    // Filtruj według wyszukiwania tylko jeśli jest aktywny
                                    if (searchTerm && searchTerm.trim() !== '') {
                                      return assignment.agentName.toLowerCase().includes(searchTerm.toLowerCase());
                                    }
                                    return true;
                                  })
                                  .map((assignment, assignmentIndex) => {
                                    const position = getAssignmentPosition(assignment);
                                    
                                    // Oblicz szerokość i pozycję dla bloków obok siebie
                                    const totalAssignments = hourAssignments.length;
                                    const blockWidth = `calc((100% - ${(totalAssignments - 1) * 4}px) / ${totalAssignments})`;
                                    const leftPosition = `${assignmentIndex * (100 / totalAssignments)}%`;
                                    
                                    return (
                                      <div
                                        key={`${assignment.id}-${assignmentIndex}`}
                                        className="assignment-block"
                                        style={{
                                          top: `${position.top % 60}px`,
                                          height: `${position.height}px`,
                                          left: leftPosition,
                                          width: blockWidth,
                                          zIndex: assignmentIndex + 1
                                        }}
                                        title={`${assignment.agentName} - ${formatDuration(assignment.duration)}`}
                                        onClick={() => handleChangeAvailability({
                                          id: assignment.agentId,
                                          name: assignment.agentName
                                        })}
                                      >
                                        <div className="assignment-content">
                                          <div className="agent-name">{assignment.agentName}</div>
                                          <div className="assignment-time">
                                            {new Date(assignment.startTime).toLocaleTimeString('pl-PL', { 
                                              hour: '2-digit', 
                                              minute: '2-digit' 
                                            })} - {new Date(assignment.endTime).toLocaleTimeString('pl-PL', { 
                                              hour: '2-digit', 
                                              minute: '2-digit' 
                                            })}
                                          </div>
                                        </div>
                                      </div>
                                    );
                                  });
                              })()}
                            </div>
                          ))}
                        </div>
                      ))}
                    </div>
                  </div>
                ) : viewMode === 'cards' ? (
                  <div className="agent-cards-view">
                    <div className="cards-grid">
                      {getFilteredAgents().map((agent) => (
                        <div key={agent.id} className="agent-card">
                          <div className="agent-card-header">
                            <h5 className="agent-name">{agent.name}</h5>
                            <div className="agent-stats">
                              <span className="total-hours">{agent.totalHours.toFixed(1)}h</span>
                              <span className="assignments-count">{agent.assignments.length} przypisań</span>
                            </div>
                          </div>
                          
                          <div className="agent-schedule">
                            <h6>Harmonogram tygodnia:</h6>
                            <div className="weekly-timeline">
                              {getWeekDays().map((day, dayIndex) => {
                                const dayAssignments = getAssignmentsForAgent(agent.id)
                                  .filter(assignment => {
                                    const assignmentDate = new Date(assignment.startTime);
                                    return assignmentDate.toDateString() === day.date.toDateString();
                                  });
                                
                                return (
                                  <div key={dayIndex} className="day-schedule">
                                    <div className="day-label">
                                      <div className="day-name">{day.dayShort}</div>
                                      <div className="day-date">{day.dateFormatted}</div>
                                    </div>
                                    <div className="day-assignments">
                                      {dayAssignments.length === 0 ? (
                                        <div className="no-assignment">Wolne</div>
                                      ) : (
                                        dayAssignments.map((assignment, assignmentIndex) => (
                                          <div key={assignmentIndex} className="day-assignment">
                                            <div className="assignment-time">
                                              {new Date(assignment.startTime).toLocaleTimeString('pl-PL', { 
                                                hour: '2-digit', 
                                                minute: '2-digit' 
                                              })} - {new Date(assignment.endTime).toLocaleTimeString('pl-PL', { 
                                                hour: '2-digit', 
                                                minute: '2-digit' 
                                              })}
                                            </div>
                                            <div className="assignment-duration">
                                              {formatDuration(assignment.duration)}
                                            </div>
                                          </div>
                                        ))
                                      )}
                                    </div>
                                  </div>
                                );
                              })}
                            </div>
                          </div>
                          
                          <div className="agent-actions">
                            <button
                              onClick={() => handleChangeAvailability({
                                id: agent.id,
                                name: agent.name
                              })}
                              className="btn btn-sm btn-warning"
                              title="Zmień dostępność agenta"
                            >
                              Zmień dostępność
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
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
                        {getFilteredAssignments().map((assignment) => (
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
              </>
            )}
          </div>
        )}

        {activeTab === 'metrics' && metrics && (
          <div className="metrics-tab">
            <h4>Metryki harmonogramu</h4>
            
            {/* Podsumowanie pokrycia rozmów */}
            {getMetricsValue('callCoverage') && (
              <div className="coverage-summary">
                <h5>Podsumowanie pokrycia rozmów</h5>
                <div className="summary-stats">
                  {(() => {
                    const coverageData = getMetricsValue('callCoverage', {});
                    const days = Object.values(coverageData);
                    const totalDays = days.length;
                    // Nowa logika: >100% = nadmiar agentów (dobrze), <100% = za mało agentów (problem)
                    const goodDays = days.filter(d => d.coverage >= 100).length;
                    const warningDays = days.filter(d => d.coverage >= 80 && d.coverage < 100).length;
                    const poorDays = days.filter(d => d.coverage >= 60 && d.coverage < 80).length;
                    const criticalDays = days.filter(d => d.coverage < 60).length;
                    const avgCoverage = days.reduce((sum, d) => sum + d.coverage, 0) / totalDays;
                    
                    return (
                      <>
                        <div className="summary-item">
                          <span className="summary-label">Średnie pokrycie:</span>
                          <span className={`summary-value ${avgCoverage >= 100 ? 'good' : avgCoverage >= 80 ? 'warning' : avgCoverage >= 60 ? 'poor' : 'critical'}`}>
                            {avgCoverage.toFixed(1)}%
                          </span>
                        </div>
                        <div className="summary-breakdown">
                          <div className="breakdown-item good">
                            <span className="breakdown-count">{goodDays}</span>
                            <span className="breakdown-label">Wystarczające</span>
                          </div>
                          <div className="breakdown-item warning">
                            <span className="breakdown-count">{warningDays}</span>
                            <span className="breakdown-label">Lekki niedobór</span>
                          </div>
                          <div className="breakdown-item poor">
                            <span className="breakdown-count">{poorDays}</span>
                            <span className="breakdown-label">Znaczny niedobór</span>
                          </div>
                          <div className="breakdown-item critical">
                            <span className="breakdown-count">{criticalDays}</span>
                            <span className="breakdown-label">Duży problem</span>
                          </div>
                        </div>
                      </>
                    );
                  })()}
                </div>
              </div>
            )}
            
            <div className="metrics-grid">
              <div className="metric-card">
                <h5>Podstawowe metryki</h5>
                <div className="metric-item">
                  <label>Łączne godziny:</label>
                  <span>{getMetricsValue('totalHours', 0)} h</span>
                </div>
                <div className="metric-item">
                  <label>Liczba agentów:</label>
                  <span>{getMetricsValue('agentCount', 0)}</span>
                </div>
                <div className="metric-item">
                  <label>Średnie godziny na agenta:</label>
                  <span>{getMetricsValue('averageHoursPerAgent', 0).toFixed(2)} h</span>
                </div>
                <div className="metric-item">
                  <label>Maksymalne godziny na agenta:</label>
                  <span>{getMetricsValue('maxHoursPerAgent', 0)} h</span>
                </div>
                <div className="metric-item">
                  <label>Minimalne godziny na agenta:</label>
                  <span>{getMetricsValue('minHoursPerAgent', 0)} h</span>
                </div>
              </div>

              <div className="metric-card">
                <h5>Pokrycie rozmów dzienne</h5>
                <div className="call-coverage-description">
                  <p className="description-text">
                    <strong>Format:</strong> Oczekiwane rozmowy / Pojemność agentów (Procent pokrycia)
                  </p>
                  <p className="description-text">
                    <strong>Interpretacja:</strong>
                  </p>
                  <ul className="coverage-legend">
                    <li><span className="legend-item good">≥ 100%</span> - Wystarczające pokrycie (nadmiar agentów)</li>
                    <li><span className="legend-item warning">80-99%</span> - Prawie wystarczające (lekki niedobór)</li>
                    <li><span className="legend-item poor">60-79%</span> - Niewystarczające (znaczny niedobór)</li>
                    <li><span className="legend-item critical">&lt; 60%</span> - Krytycznie niewystarczające (duży problem)</li>
                  </ul>
                </div>
                <div className="call-coverage">
                  {getMetricsValue('callCoverage') && Object.entries(getMetricsValue('callCoverage', {})).slice(0, 10).map(([day, coverage]) => {
                    // Nowa logika: >100% = nadmiar agentów (dobrze), <100% = za mało agentów (problem)
                    const statusClass = coverage.coverage >= 100 ? 'good' : 
                                      coverage.coverage >= 80 ? 'warning' : 
                                      coverage.coverage >= 60 ? 'poor' : 'critical';
                    return (
                      <div key={day} className={`coverage-item ${statusClass}`}>
                        <span className="day">{day}</span>
                        <div className="coverage-details">
                          <div className="coverage-main">
                            <span className="expected">Oczekiwane: {coverage.expectedCalls}</span>
                            <span className="separator">/</span>
                            <span className="capacity">Pojemność: {coverage.agentCapacity}</span>
                          </div>
                          <div className="coverage-percentage">
                            <span className={`percentage ${statusClass}`}>
                              {coverage.coverage}%
                            </span>
                          </div>
                        </div>
                      </div>
                    );
                  })}
                  {getMetricsValue('callCoverage') && Object.keys(getMetricsValue('callCoverage', {})).length > 10 && (
                    <div className="coverage-more">
                      <small>... i {Object.keys(getMetricsValue('callCoverage', {})).length - 10} więcej</small>
                    </div>
                  )}
                  {!getMetricsValue('callCoverage') && (
                    <div className="no-data">
                      <small>Brak danych o pokryciu rozmów</small>
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
              <div className={`validation-indicator ${getValidationValue('isValid', false) ? 'valid' : 'invalid'}`}>
                {getValidationValue('isValid', false) ? '✓ Harmonogram jest poprawny' : '✗ Harmonogram zawiera błędy'}
              </div>
              
              {getValidationValue('totalViolations', 0) > 0 && (
                <div className="violations-list">
                  <h5>Wykryte problemy ({getValidationValue('totalViolations', 0)}):</h5>
                  <ul>
                    {getValidationValue('violations', []) && getValidationValue('violations', []).map((violation, index) => (
                      <li key={index} className="violation-item">
                        {violation}
                      </li>
                    ))}
                  </ul>
                </div>
              )}
              
              {getValidationValue('isValid', false) && (
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
