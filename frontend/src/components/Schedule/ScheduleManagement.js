import React, { useState, useEffect } from 'react';
import ScheduleList from './ScheduleList';
import ScheduleGenerateForm from './ScheduleGenerateForm';
import scheduleService from '../../services/scheduleService';
import './Schedule.css';

const ScheduleManagement = () => {
  const [schedules, setSchedules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [generateLoading, setGenerateLoading] = useState(false);
  const [showGenerateForm, setShowGenerateForm] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Funkcja do ładowania harmonogramów
  const loadSchedules = async () => {
    try {
      setLoading(true);
      const data = await scheduleService.getAll();
      setSchedules(data);
      setError('');
    } catch (err) {
      setError(err.message);
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
    // TODO: Implementacja edycji harmonogramu
    console.log('Edycja harmonogramu:', schedule);
    setError('');
    setSuccess('');
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
          
          // Utwórz harmonogram
          const newSchedule = await scheduleService.create({
            queueTypeId: formData.queueTypeId,
            weekStartDate: formData.weekStartDate,
            status: 'draft'
          });
          
          setSuccess('Harmonogram został utworzony pomyślnie.');
          
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
          {!showGenerateForm && (
            <button 
              onClick={handleShowGenerateForm}
              className="btn btn-primary"
            >
              Generuj harmonogram
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

      <div className="list-section">
        <ScheduleList
          schedules={schedules}
          onEdit={handleEdit}
          isLoading={loading}
        />
      </div>
    </div>
  );
};

export default ScheduleManagement;