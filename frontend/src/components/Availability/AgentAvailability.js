import React, { useState, useEffect } from 'react';
import availabilityService from '../../services/availabilityService';
import './AgentAvailability.css';

const AgentAvailability = ({ agentId, agentName }) => {
  const [availabilities, setAvailabilities] = useState([]);
  const [loading, setLoading] = useState(true);
  const [formLoading, setFormLoading] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [editingAvailability, setEditingAvailability] = useState(null);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  
  // Form state
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [errors, setErrors] = useState({});

  // Wczytaj dostępności dla agenta
  const loadAvailabilities = async () => {
    try {
      setLoading(true);
      const data = await availabilityService.getByAgent(agentId);
      setAvailabilities(data);
      setError('');
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (agentId) {
      loadAvailabilities();
    }
  }, [agentId]);

  // Funkcje formularza
  const handleAddNew = () => {
    setEditingAvailability(null);
    setStartDate('');
    setEndDate('');
    setErrors({});
    setShowForm(true);
    setError('');
    setSuccess('');
  };

  const handleEdit = (availability) => {
    setEditingAvailability(availability);
    setStartDate(formatDateForInput(availability.startDate));
    setEndDate(formatDateForInput(availability.endDate));
    setErrors({});
    setShowForm(true);
    setError('');
    setSuccess('');
  };

  const handleCancel = () => {
    setShowForm(false);
    setEditingAvailability(null);
    setStartDate('');
    setEndDate('');
    setErrors({});
    setError('');
    setSuccess('');
  };

  const validateForm = () => {
    const newErrors = {};
    
    if (!startDate) {
      newErrors.startDate = 'Data rozpoczęcia jest wymagana';
    }
    
    if (!endDate) {
      newErrors.endDate = 'Data zakończenia jest wymagana';
    }
    
    if (startDate && endDate && new Date(startDate) >= new Date(endDate)) {
      newErrors.endDate = 'Data zakończenia musi być późniejsza niż data rozpoczęcia';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    try {
      setFormLoading(true);
      setError('');
      
      const availabilityData = {
        agentId: agentId,
        startDate: formatDateForAPI(startDate),
        endDate: formatDateForAPI(endDate)
      };
      
      if (editingAvailability) {
        await availabilityService.update(editingAvailability.id, availabilityData);
        setSuccess('Dostępność została zaktualizowana pomyślnie');
      } else {
        await availabilityService.create(availabilityData);
        setSuccess('Dostępność została dodana pomyślnie');
      }
      
      await loadAvailabilities();
      setShowForm(false);
      setEditingAvailability(null);
      setStartDate('');
      setEndDate('');
      
      // Usuń komunikat sukcesu po 3 sekundach
      setTimeout(() => setSuccess(''), 3000);
      
    } catch (err) {
      setError(err.message);
    } finally {
      setFormLoading(false);
    }
  };

  const handleDelete = async (availability) => {
    if (!window.confirm('Czy na pewno chcesz usunąć tę dostępność?')) {
      return;
    }

    try {
      await availabilityService.delete(availability.id);
      setSuccess('Dostępność została usunięta pomyślnie');
      await loadAvailabilities();
      
      setTimeout(() => setSuccess(''), 3000);
    } catch (err) {
      setError(err.message);
    }
  };

  // Pomocnicze funkcje formatowania
  const roundToFullHour = (dateTimeString) => {
    if (!dateTimeString) return '';
    const date = new Date(dateTimeString);
    // Ustaw minuty, sekundy i milisekundy na 0
    date.setMinutes(0, 0, 0);
    
    // Formatuj w lokalnym timezone zamiast UTC
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:00`;
  };

  const formatDateForInput = (dateString) => {
    const date = new Date(dateString);
    // Zaokrąglij do pełnej godziny (ustaw minuty i sekundy na 0)
    date.setMinutes(0, 0, 0);
    
    // Formatuj w lokalnym timezone zamiast UTC
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:00`;
  };

  const formatDateForAPI = (dateTimeString) => {
    if (!dateTimeString) return '';
    const date = new Date(dateTimeString);
    // Ustaw minuty, sekundy i milisekundy na 0
    date.setMinutes(0, 0, 0);
    
    // Formatuj w lokalnym timezone (backend oczekuje lokalnych dat)
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:00:00`;
  };

  const formatDateForDisplay = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('pl-PL');
  };

  if (!agentId) {
    return (
      <div className="agent-availability">
        <p className="no-agent">Wybierz agenta, aby zarządzać jego dostępnością</p>
      </div>
    );
  }

  return (
    <div className="agent-availability">
      <div className="availability-header">
        <h3>Dostępność agenta: {agentName}</h3>
        {!showForm && (
          <button 
            onClick={handleAddNew}
            className="btn btn-primary"
          >
            Dodaj dostępność
          </button>
        )}
      </div>

      {error && (
        <div className="alert alert-error">
          {error}
        </div>
      )}

      {success && (
        <div className="alert alert-success">
          {success}
        </div>
      )}

      {showForm && (
        <div className="availability-form">
          <h4>{editingAvailability ? 'Edytuj dostępność' : 'Dodaj nową dostępność'}</h4>
          
          <form onSubmit={handleSubmit}>
            <div className="form-group">
              <label htmlFor="startDate">Data i czas rozpoczęcia:</label>
              <div className="form-field-wrapper">
                <input
                  type="datetime-local"
                  id="startDate"
                  value={startDate}
                  onChange={(e) => setStartDate(roundToFullHour(e.target.value))}
                  disabled={formLoading}
                  className={errors.startDate ? 'error' : ''}
                  title="Wybierz datę i godzinę. Minuty będą automatycznie ustawione na :00"

                />
                {errors.startDate && <span className="error-message">{errors.startDate}</span>}
                <small className="help-text">Minuty będą automatycznie ustawione na :00</small>
              </div>
            </div>

            <div className="form-group">
              <label htmlFor="endDate">Data i czas zakończenia:</label>
              <div className="form-field-wrapper">
                <input
                  type="datetime-local"
                  id="endDate"
                  value={endDate}
                  onChange={(e) => setEndDate(roundToFullHour(e.target.value))}
                  disabled={formLoading}
                  className={errors.endDate ? 'error' : ''}
                  title="Wybierz datę i godzinę. Minuty będą automatycznie ustawione na :00"

                />
                {errors.endDate && <span className="error-message">{errors.endDate}</span>}
                <small className="help-text">Minuty będą automatycznie ustawione na :00</small>
              </div>
            </div>

            <div className="form-buttons">
              <button 
                type="submit" 
                disabled={formLoading}
                className="btn btn-primary"
              >
                {formLoading ? 'Zapisywanie...' : (editingAvailability ? 'Zaktualizuj' : 'Dodaj')}
              </button>
              <button 
                type="button" 
                onClick={handleCancel}
                disabled={formLoading}
                className="btn btn-secondary"
              >
                Anuluj
              </button>
            </div>
          </form>
        </div>
      )}

      <div className="availability-list">
        {loading ? (
          <div className="loading">Ładowanie dostępności...</div>
        ) : availabilities.length === 0 ? (
          <div className="empty-state">
            <p>Brak dostępności dla tego agenta</p>
            {!showForm && (
              <button 
                onClick={handleAddNew}
                className="btn btn-primary"
              >
                Dodaj pierwszą dostępność
              </button>
            )}
          </div>
        ) : (
          <table className="availability-table">
            <thead>
              <tr>
                <th>Data rozpoczęcia</th>
                <th>Data zakończenia</th>
                <th>Akcje</th>
              </tr>
            </thead>
            <tbody>
              {availabilities.map((availability) => (
                <tr key={availability.id}>
                  <td>{formatDateForDisplay(availability.startDate)}</td>
                  <td>{formatDateForDisplay(availability.endDate)}</td>
                  <td>
                    <button 
                      onClick={() => handleEdit(availability)}
                      className="btn btn-edit"
                    >
                      Edytuj
                    </button>
                    <button 
                      onClick={() => handleDelete(availability)}
                      className="btn btn-delete"
                    >
                      Usuń
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
};

export default AgentAvailability;