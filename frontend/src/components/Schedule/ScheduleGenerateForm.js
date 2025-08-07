import React, { useState, useEffect } from 'react';
import queueTypeService from '../../services/queueTypeService';

const ScheduleGenerateForm = ({ onSubmit, onCancel, isLoading = false }) => {
  const [queueTypes, setQueueTypes] = useState([]);
  const [selectedQueueTypeId, setSelectedQueueTypeId] = useState('');
  const [selectedYear, setSelectedYear] = useState('');
  const [selectedWeek, setSelectedWeek] = useState('');
  const [errors, setErrors] = useState({});
  const [loadingQueueTypes, setLoadingQueueTypes] = useState(true);

  // Pobierz typy kolejek przy pierwszym renderowaniu
  useEffect(() => {
    const loadQueueTypes = async () => {
      try {
        setLoadingQueueTypes(true);
        const data = await queueTypeService.getAll();
        setQueueTypes(data);
        
        // Ustaw domyślnie pierwszy typ kolejki jeśli istnieje
        if (data.length > 0) {
          setSelectedQueueTypeId(data[0].id.toString());
        }
      } catch (error) {
        console.error('Błąd podczas ładowania typów kolejek:', error);
        setErrors({ queueTypes: 'Nie udało się załadować typów kolejek' });
      } finally {
        setLoadingQueueTypes(false);
      }
    };

    loadQueueTypes();
  }, []);

  // Ustaw domyślny rok i tydzień na następny tydzień
  useEffect(() => {
    const today = new Date();
    const nextWeek = new Date(today);
    nextWeek.setDate(today.getDate() + 7);
    
    setSelectedYear(nextWeek.getFullYear().toString());
    setSelectedWeek(getWeekNumber(nextWeek).toString().padStart(2, '0'));
  }, []);

  const validateForm = () => {
    const newErrors = {};
    
    if (!selectedQueueTypeId) {
      newErrors.queueType = 'Wybierz typ kolejki';
    }
    
    if (!selectedYear || !selectedWeek) {
      newErrors.weekPeriod = 'Wybierz rok i tydzień';
    } else {
      const weekNumber = parseInt(selectedWeek);
      if (weekNumber < 1 || weekNumber > 53) {
        newErrors.weekPeriod = 'Numer tygodnia musi być między 1 a 53';
      }
      // Usunięto ograniczenie dla tygodni w przeszłości
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    
    if (validateForm()) {
              // Ustaw datę na poniedziałek wybranego tygodnia
        const weekStartDate = getDateOfISOWeek(parseInt(selectedYear), parseInt(selectedWeek));

        // Użyj lokalnego formatowania daty zamiast UTC
        const formattedDate = weekStartDate.getFullYear() + '-' + 
          String(weekStartDate.getMonth() + 1).padStart(2, '0') + '-' + 
          String(weekStartDate.getDate()).padStart(2, '0');
      onSubmit({
        queueTypeId: parseInt(selectedQueueTypeId),
        weekStartDate: formattedDate
      });
    }
  };

  const formatWeekIdentifier = (year, week) => {
    return `${year}-${week.toString().padStart(2, '0')}`;
  };

  const getWeekNumber = (date) => {
    const firstDayOfYear = new Date(date.getFullYear(), 0, 1);
    const pastDaysOfYear = (date - firstDayOfYear) / 86400000;
    return Math.ceil((pastDaysOfYear + firstDayOfYear.getDay() + 1) / 7);
  };

  const getDateOfISOWeek = (year, week) => {
    // Użyj tej samej logiki co w backendzie
    const simple = new Date(year, 0, 1);
    simple.setDate(simple.getDate() + (week - 1) * 7);
    
    const dayOfWeek = simple.getDay(); // 0 = niedziela, 1 = poniedziałek, ..., 6 = sobota
    if (dayOfWeek > 1) { // jeśli to nie poniedziałek
      simple.setDate(simple.getDate() - (dayOfWeek - 1));
    } else if (dayOfWeek === 0) { // jeśli to niedziela
      simple.setDate(simple.getDate() + 1); // przesuń na poniedziałek
    }
    
    return simple;
  };

  if (loadingQueueTypes) {
    return (
      <div className="schedule-generate-form">
        <h3>Generowanie harmonogramu</h3>
        <div className="loading">Ładowanie typów kolejek...</div>
      </div>
    );
  }

  return (
    <div className="schedule-generate-form">
      <h3>Generowanie harmonogramu</h3>
      
      {errors.queueTypes && (
        <div className="alert alert-error">
          {errors.queueTypes}
        </div>
      )}
      
      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label htmlFor="queueType">Typ kolejki:</label>
          <select
            id="queueType"
            value={selectedQueueTypeId}
            onChange={(e) => setSelectedQueueTypeId(e.target.value)}
            disabled={isLoading}
            className={errors.queueType ? 'error' : ''}
          >
            <option value="">Wybierz typ kolejki</option>
            {queueTypes.map((queueType) => (
              <option key={queueType.id} value={queueType.id}>
                {queueType.name}
              </option>
            ))}
          </select>
          {errors.queueType && <span className="error-message">{errors.queueType}</span>}
        </div>

        <div className="form-group">
          <label htmlFor="weekPeriod">Tydzień:</label>
          <div className="date-inputs">
            <select
              id="selectedYear"
              value={selectedYear}
              onChange={(e) => setSelectedYear(e.target.value)}
              disabled={isLoading}
              className={errors.weekPeriod ? 'error' : ''}
            >
              <option value="">Rok</option>
              {Array.from({ length: 5 }, (_, i) => {
                const year = new Date().getFullYear() + i;
                return (
                  <option key={year} value={year}>
                    {year}
                  </option>
                );
              })}
            </select>
            <select
              id="selectedWeek"
              value={selectedWeek}
              onChange={(e) => setSelectedWeek(e.target.value)}
              disabled={isLoading}
              className={errors.weekPeriod ? 'error' : ''}
            >
              <option value="">Tydzień</option>
              {Array.from({ length: 53 }, (_, i) => {
                const week = (i + 1).toString().padStart(2, '0');
                return (
                  <option key={week} value={week}>
                    Tydzień {week}
                  </option>
                );
              })}
            </select>
          </div>
          {selectedYear && selectedWeek && (
            <div className="date-preview">
              <small>Tydzień: {formatWeekIdentifier(selectedYear, selectedWeek)}</small>
            </div>
          )}
          {errors.weekPeriod && <span className="error-message">{errors.weekPeriod}</span>}
        </div>

        <div className="form-buttons">
          <button 
            type="submit" 
            disabled={isLoading}
            className="btn btn-primary"
          >
            {isLoading ? 'Generowanie...' : 'Generuj harmonogram'}
          </button>
          <button 
            type="button" 
            onClick={onCancel}
            disabled={isLoading}
            className="btn btn-secondary"
          >
            Anuluj
          </button>
        </div>
      </form>
    </div>
  );
};

export default ScheduleGenerateForm; 