import React, { useState, useEffect } from 'react';

const UserForm = ({ user, onSubmit, onCancel, isLoading = false, availableRoles = [], availableQueueTypes = [] }) => {
  const [name, setName] = useState('');
  const [role, setRole] = useState('');
  const [selectedQueueTypes, setSelectedQueueTypes] = useState([]);
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (user) {
      setName(user.name || '');
      setRole(user.role || '');
      
      // Extract queue type IDs from user's agentQueueTypes
      const queueTypeIds = user.agentQueueTypes 
        ? user.agentQueueTypes.map(aqt => aqt.queueType.id)
        : [];
      setSelectedQueueTypes(queueTypeIds);
    }
  }, [user]);

  const validateForm = () => {
    const newErrors = {};
    
    if (!name.trim()) {
      newErrors.name = 'Nazwa jest wymagana';
    } else if (name.trim().length < 2) {
      newErrors.name = 'Nazwa musi mieć co najmniej 2 znaki';
    } else if (name.trim().length > 255) {
      newErrors.name = 'Nazwa nie może przekraczać 255 znaków';
    }

    if (!role) {
      newErrors.role = 'Rola jest wymagana';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleQueueTypeChange = (queueTypeId) => {
    setSelectedQueueTypes(prev => {
      if (prev.includes(queueTypeId)) {
        return prev.filter(id => id !== queueTypeId);
      } else {
        return [...prev, queueTypeId];
      }
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    
    if (validateForm()) {
      onSubmit({ 
        name: name.trim(),
        role: role,
        queueTypeIds: selectedQueueTypes
      });
    }
  };

  return (
    <div className="user-form">
      <h3>{user ? 'Edytuj użytkownika' : 'Dodaj nowego użytkownika'}</h3>
      
      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label htmlFor="name">Nazwa:</label>
          <input
            type="text"
            id="name"
            value={name}
            onChange={(e) => setName(e.target.value)}
            disabled={isLoading}
            className={errors.name ? 'error' : ''}
            placeholder="Wprowadź nazwę użytkownika"
          />
          {errors.name && <span className="error-message">{errors.name}</span>}
        </div>

        <div className="form-group">
          <label htmlFor="role">Rola:</label>
          <select
            id="role"
            value={role}
            onChange={(e) => setRole(e.target.value)}
            disabled={isLoading}
            className={errors.role ? 'error' : ''}
          >
            <option value="">Wybierz rolę</option>
            {availableRoles.map((roleOption) => (
              <option key={roleOption.value} value={roleOption.value}>
                {roleOption.label}
              </option>
            ))}
          </select>
          {errors.role && <span className="error-message">{errors.role}</span>}
        </div>

        <div className="form-group">
          <label>Przypisane typy kolejek:</label>
          <div className="checkbox-group">
            {availableQueueTypes.length === 0 ? (
              <p className="no-queue-types">Brak dostępnych typów kolejek</p>
            ) : (
              availableQueueTypes.map((queueType) => (
                <label key={queueType.id} className="checkbox-label">
                  <input
                    type="checkbox"
                    checked={selectedQueueTypes.includes(queueType.id)}
                    onChange={() => handleQueueTypeChange(queueType.id)}
                    disabled={isLoading}
                  />
                  <span className="checkbox-text">{queueType.name}</span>
                </label>
              ))
            )}
          </div>
        </div>

        <div className="form-buttons">
          <button 
            type="submit" 
            disabled={isLoading}
            className="btn btn-primary"
          >
            {isLoading ? 'Zapisywanie...' : (user ? 'Zaktualizuj' : 'Dodaj')}
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

export default UserForm;