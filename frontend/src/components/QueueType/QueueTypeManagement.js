import React, { useState, useEffect } from 'react';
import QueueTypeList from './QueueTypeList';
import QueueTypeForm from './QueueTypeForm';
import queueTypeService from '../../services/queueTypeService';
import './QueueType.css';

const QueueTypeManagement = () => {
  const [queueTypes, setQueueTypes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [formLoading, setFormLoading] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [editingQueueType, setEditingQueueType] = useState(null);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Funkcja do ładowania typów kolejek
  const loadQueueTypes = async () => {
    try {
      setLoading(true);
      const data = await queueTypeService.getAll();
      setQueueTypes(data);
      setError('');
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  // Ładowanie danych przy pierwszym renderowaniu
  useEffect(() => {
    loadQueueTypes();
  }, []);

  // Funkcja do obsługi dodawania nowego typu kolejki
  const handleAddNew = () => {
    setEditingQueueType(null);
    setShowForm(true);
    setError('');
    setSuccess('');
  };

  // Funkcja do obsługi edycji typu kolejki
  const handleEdit = (queueType) => {
    setEditingQueueType(queueType);
    setShowForm(true);
    setError('');
    setSuccess('');
  };

  // Funkcja do anulowania formularza
  const handleCancel = () => {
    setShowForm(false);
    setEditingQueueType(null);
    setError('');
    setSuccess('');
  };

  // Funkcja do obsługi zapisu formularza
  const handleSubmit = async (formData) => {
    try {
      setFormLoading(true);
      setError('');
      
      if (editingQueueType) {
        // Aktualizacja istniejącego typu kolejki
        await queueTypeService.update(editingQueueType.id, formData);
        setSuccess('Typ kolejki został zaktualizowany pomyślnie');
      } else {
        // Dodawanie nowego typu kolejki
        await queueTypeService.create(formData);
        setSuccess('Typ kolejki został dodany pomyślnie');
      }
      
      // Odśwież listę i zamknij formularz
      await loadQueueTypes();
      setShowForm(false);
      setEditingQueueType(null);
      
      // Usuń komunikat sukcesu po 3 sekundach
      setTimeout(() => setSuccess(''), 3000);
      
    } catch (err) {
      setError(err.message);
    } finally {
      setFormLoading(false);
    }
  };

  return (
    <div className="queue-type-management">
      <div className="page-header">
        <h2>Zarządzanie typami kolejek</h2>
        {!showForm && (
          <button 
            onClick={handleAddNew}
            className="btn btn-primary"
          >
            Dodaj nowy typ kolejki
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
        <div className="form-section">
          <QueueTypeForm
            queueType={editingQueueType}
            onSubmit={handleSubmit}
            onCancel={handleCancel}
            isLoading={formLoading}
          />
        </div>
      )}

      <div className="list-section">
        <QueueTypeList
          queueTypes={queueTypes}
          onEdit={handleEdit}
          isLoading={loading}
        />
      </div>
    </div>
  );
};

export default QueueTypeManagement;