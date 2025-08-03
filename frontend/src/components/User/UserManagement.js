import React, { useState, useEffect } from 'react';
import UserList from './UserList';
import UserForm from './UserForm';
import userService from '../../services/userService';
import './User.css';

const UserManagement = () => {
  const [users, setUsers] = useState([]);
  const [availableRoles, setAvailableRoles] = useState([]);
  const [availableQueueTypes, setAvailableQueueTypes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [formLoading, setFormLoading] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [editingUser, setEditingUser] = useState(null);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Funkcja do ładowania użytkowników
  const loadUsers = async () => {
    try {
      setLoading(true);
      const data = await userService.getAll();
      setUsers(data);
      setError('');
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  // Funkcja do ładowania dostępnych ról
  const loadRoles = async () => {
    try {
      const roles = await userService.getRoles();
      setAvailableRoles(roles);
    } catch (err) {
      console.error('Failed to load roles:', err.message);
      // Fallback roles if API fails
      setAvailableRoles([
        { value: 'Agent', label: 'Agent' },
        { value: 'Manager', label: 'Manager' }
      ]);
    }
  };

  // Funkcja do ładowania dostępnych typów kolejek
  const loadQueueTypes = async () => {
    try {
      const queueTypes = await userService.getQueueTypes();
      setAvailableQueueTypes(queueTypes);
    } catch (err) {
      console.error('Failed to load queue types:', err.message);
      setAvailableQueueTypes([]);
    }
  };

  // Ładowanie danych przy pierwszym renderowaniu
  useEffect(() => {
    Promise.all([loadUsers(), loadRoles(), loadQueueTypes()]);
  }, []);

  // Funkcja do obsługi dodawania nowego użytkownika
  const handleAddNew = () => {
    setEditingUser(null);
    setShowForm(true);
    setError('');
    setSuccess('');
  };

  // Funkcja do obsługi edycji użytkownika
  const handleEdit = (user) => {
    setEditingUser(user);
    setShowForm(true);
    setError('');
    setSuccess('');
  };

  // Funkcja do anulowania formularza
  const handleCancel = () => {
    setShowForm(false);
    setEditingUser(null);
    setError('');
    setSuccess('');
  };

  // Funkcja do obsługi zapisu formularza
  const handleSubmit = async (formData) => {
    try {
      setFormLoading(true);
      setError('');
      
      if (editingUser) {
        // Aktualizacja istniejącego użytkownika
        await userService.update(editingUser.id, formData);
        setSuccess('Użytkownik został zaktualizowany pomyślnie');
      } else {
        // Dodawanie nowego użytkownika
        await userService.create(formData);
        setSuccess('Użytkownik został dodany pomyślnie');
      }
      
      // Odśwież listę i zamknij formularz
      await loadUsers();
      setShowForm(false);
      setEditingUser(null);
      
      // Usuń komunikat sukcesu po 3 sekundach
      setTimeout(() => setSuccess(''), 3000);
      
    } catch (err) {
      setError(err.message);
    } finally {
      setFormLoading(false);
    }
  };

  return (
    <div className="user-management">
      <div className="page-header">
        <h2>Zarządzanie użytkownikami</h2>
        {!showForm && (
          <button 
            onClick={handleAddNew}
            className="btn btn-primary"
          >
            Dodaj nowego użytkownika
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
          <UserForm
            user={editingUser}
            onSubmit={handleSubmit}
            onCancel={handleCancel}
            isLoading={formLoading}
            availableRoles={availableRoles}
            availableQueueTypes={availableQueueTypes}
          />
        </div>
      )}

      <div className="list-section">
        <UserList
          users={users}
          onEdit={handleEdit}
          isLoading={loading}
        />
      </div>
    </div>
  );
};

export default UserManagement;