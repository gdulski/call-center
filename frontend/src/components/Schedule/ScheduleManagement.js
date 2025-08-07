import React, { useState, useEffect } from 'react';
import ScheduleList from './ScheduleList';
import ScheduleGenerateForm from './ScheduleGenerateForm';
import ScheduleEditForm from './ScheduleEditForm';
import scheduleService from '../../services/scheduleService';
import './Schedule.css';

const ScheduleManagement = () => {
  const [schedules, setSchedules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [generateLoading, setGenerateLoading] = useState(false);
  const [showGenerateForm, setShowGenerateForm] = useState(false);
  const [showEditForm, setShowEditForm] = useState(false);
  const [selectedSchedule, setSelectedSchedule] = useState(null);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Funkcja do ładowania harmonogramów
  const loadSchedules = async () => {
    try {
      setLoading(true);
      const data = await scheduleService.getAll();
      // Upewnij się, że data jest tablicą
      setSchedules(Array.isArray(data) ? data : []);
      setError('');
    } catch (err) {
      console.error('Błąd podczas ładowania harmonogramów:', err);
      setError(err.message || 'Wystąpił błąd podczas ładowania harmonogramów');
      setSchedules([]);
    } finally {
      setLoading(false);
    }
  };

  // Ładowanie danych przy pierwszym renderowaniu
  useEffect(() => {
    loadSchedules();
  }, []);

  // Funkcja do obsługi edycji harmonogramu
  const handleEdit = (schedule) => {
    setSelectedSchedule(schedule);
    setShowEditForm(true);
    setError('');
    setSuccess('');
  };

  // Funkcja do zamykania formularza edycji
  const handleCloseEditForm = () => {
    setShowEditForm(false);
    setSelectedSchedule(null);
    setError('');
    setSuccess('');
  };

  // Funkcja do aktualizacji harmonogramu
  const handleUpdateSchedule = async (updatedSchedule) => {
    try {
      // Tutaj można dodać logikę aktualizacji jeśli będzie potrzebna
      setSuccess('Harmonogram został zaktualizowany pomyślnie.');
      await loadSchedules();
      handleCloseEditForm();
      
      // Usuń komunikat sukcesu po 5 sekundach
      setTimeout(() => setSuccess(''), 5000);
    } catch (err) {
      setError(err.message || 'Wystąpił błąd podczas aktualizacji harmonogramu');
    }
  };

  // Funkcja do pokazania formularza generowania
  const handleShowGenerateForm = () => {
    setShowGenerateForm(true);
    setError('');
    setSuccess('');
  };

  // Funkcja do anulowania formularza generowania
  const handleCancelGenerateForm = () => {
    setShowGenerateForm(false);
    setError('');
    setSuccess('');
  };

        // Funkcja do obsługi generowania harmonogramu
      const handleGenerateSchedule = async (formData) => {
        try {
          setGenerateLoading(true);
          setError('');
          
          // Utwórz harmonogram (teraz automatycznie generuje przypisania)
          const newSchedule = await scheduleService.create({
            queueTypeId: formData.queueTypeId,
            weekStartDate: formData.weekStartDate,
            optimizationType: formData.optimizationType
          });
          
          const optimizationTypeName = formData.optimizationType === 'ilp' ? 'ILP (Integer Linear Programming)' : 'Heurystyczna';
          setSuccess(`Harmonogram został utworzony i zoptymalizowany pomyślnie używając algorytmu ${optimizationTypeName}.`);
          
          // Zamknij formularz i odśwież listę
          setShowGenerateForm(false);
          await loadSchedules();
          
          // Usuń komunikat sukcesu po 5 sekundach
          setTimeout(() => setSuccess(''), 5000);
          
        } catch (err) {
          setError(err.message);
        } finally {
          setGenerateLoading(false);
        }
      };

  return (
    <div className="schedule-management">
      <div className="page-header">
        <h2>Zarządzanie harmonogramami</h2>
        <div className="header-actions">
          {!showGenerateForm && !showEditForm && (
            <button 
              onClick={handleShowGenerateForm}
              className="btn btn-primary"
            >
              Utwórz i wygeneruj harmonogram
            </button>
          )}
        </div>
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

      {showGenerateForm && (
        <div className="form-section">
          <ScheduleGenerateForm
            onSubmit={handleGenerateSchedule}
            onCancel={handleCancelGenerateForm}
            isLoading={generateLoading}
          />
        </div>
      )}

      {showEditForm && selectedSchedule && (
        <div className="form-section">
          <ScheduleEditForm
            schedule={selectedSchedule}
            onClose={handleCloseEditForm}
            onUpdate={handleUpdateSchedule}
          />
        </div>
      )}

      {!showGenerateForm && !showEditForm && (
        <div className="list-section">
          <ScheduleList
            schedules={schedules}
            onEdit={handleEdit}
            isLoading={loading}
          />
        </div>
      )}
    </div>
  );
};

export default ScheduleManagement;