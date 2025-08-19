import React, { useState, useEffect } from 'react';

const UserForm = ({ user, onSubmit, onCancel, isLoading = false, availableRoles = [], availableQueueTypes = [] }) => {
  const [name, setName] = useState('');
  const [role, setRole] = useState('');
  const [selectedQueueTypes, setSelectedQueueTypes] = useState([]);
  const [efficiencyScores, setEfficiencyScores] = useState({});
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
      
      // Extract efficiency scores
      const scores = {};
      if (user.agentQueueTypes) {
        user.agentQueueTypes.forEach(aqt => {
          scores[aqt.queueType.id] = aqt.efficiencyScore || 0.00;
        });
      }
      setEfficiencyScores(scores);
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
        // Remove queue type and its efficiency score
        setEfficiencyScores(prevScores => {
          const newScores = { ...prevScores };
          delete newScores[queueTypeId];
          return newScores;
        });
        return prev.filter(id => id !== queueTypeId);
      } else {
        // Add queue type with default efficiency score
        setEfficiencyScores(prevScores => ({
          ...prevScores,
          [queueTypeId]: 0.00
        }));
        return [...prev, queueTypeId];
      }
    });
  };

  const handleEfficiencyScoreChange = (queueTypeId, score) => {
    setEfficiencyScores(prev => ({
      ...prev,
      [queueTypeId]: parseFloat(score) || 0.00
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    
    if (validateForm()) {
      // Prepare queueTypeIds with efficiency scores
      const queueTypeData = selectedQueueTypes.map(id => ({
        id: id,
        efficiencyScore: efficiencyScores[id] || 0.00
      }));
      
      onSubmit({ 
        name: name.trim(),
        role: role,
        queueTypeIds: queueTypeData
      });
    }
  };

  return (
    <div className="user-form">
      <h3>{user ? 'Edytuj użytkownika' : 'Dodaj nowego użytkownika'}</h3>
      
      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label htmlFor="name">Nazwa:</label>
          <div className="form-field-wrapper">
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
        </div>

        <div className="form-group">
          <label htmlFor="role">Rola:</label>
          <div className="form-field-wrapper">
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
        </div>

        <div className="form-group">
          <label>Przypisane typy kolejek:</label>
          <div className="form-field-wrapper">
            <div className="queue-types-section">
            {availableQueueTypes.length === 0 ? (
              <p className="no-queue-types">Brak dostępnych typów kolejek</p>
            ) : (
              availableQueueTypes.map((queueType) => (
                <div key={queueType.id} className="queue-type-item">
                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      checked={selectedQueueTypes.includes(queueType.id)}
                      onChange={() => handleQueueTypeChange(queueType.id)}
                      disabled={isLoading}
                    />
                    <span className="checkbox-text">{queueType.name}</span>
                  </label>
                  
                  {selectedQueueTypes.includes(queueType.id) && (
                    <div className="efficiency-score-input">
                      <label htmlFor={`efficiency-${queueType.id}`}>
                        Wskaźnik efektywności:
                      </label>
                      <input
                        type="number"
                        id={`efficiency-${queueType.id}`}
                        min="0"
                        max="100"
                        step="0.01"
                        value={efficiencyScores[queueType.id] || 0.00}
                        onChange={(e) => handleEfficiencyScoreChange(queueType.id, e.target.value)}
                        disabled={isLoading}
                        placeholder="0.00"
                        className="efficiency-input"
                      />
                      <small className="help-text">Wartość od 0.00 do 100.00</small>
                    </div>
                  )}
                </div>
              ))
            )}
            </div>
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