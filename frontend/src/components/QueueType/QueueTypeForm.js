import React, { useState, useEffect } from 'react';

const QueueTypeForm = ({ queueType, onSubmit, onCancel, isLoading = false }) => {
  const [name, setName] = useState('');
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (queueType) {
      setName(queueType.name || '');
    }
  }, [queueType]);

  const validateForm = () => {
    const newErrors = {};
    
    if (!name.trim()) {
      newErrors.name = 'Nazwa jest wymagana';
    } else if (name.trim().length < 2) {
      newErrors.name = 'Nazwa musi mieć co najmniej 2 znaki';
    } else if (name.trim().length > 255) {
      newErrors.name = 'Nazwa nie może przekraczać 255 znaków';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    
    if (validateForm()) {
      onSubmit({ name: name.trim() });
    }
  };

  return (
    <div className="queue-type-form">
      <h3>{queueType ? 'Edytuj typ kolejki' : 'Dodaj nowy typ kolejki'}</h3>
      
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
            placeholder="Wprowadź nazwę typu kolejki"
          />
          {errors.name && <span className="error-message">{errors.name}</span>}
        </div>

        <div className="form-buttons">
          <button 
            type="submit" 
            disabled={isLoading}
            className="btn btn-primary"
          >
            {isLoading ? 'Zapisywanie...' : (queueType ? 'Zaktualizuj' : 'Dodaj')}
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

export default QueueTypeForm;