import React from 'react';

const QueueTypeList = ({ queueTypes, onEdit, isLoading = false }) => {
  if (isLoading) {
    return <div className="loading">Ładowanie typów kolejek...</div>;
  }

  if (queueTypes.length === 0) {
    return (
      <div className="empty-state">
        <p>Brak typów kolejek do wyświetlenia.</p>
      </div>
    );
  }

  return (
    <div className="queue-type-list">
      <h3>Lista typów kolejek</h3>
      
      <div className="list-container">
        <table className="queue-types-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nazwa</th>
              <th>Akcje</th>
            </tr>
          </thead>
          <tbody>
            {queueTypes.map((queueType) => (
              <tr key={queueType.id}>
                <td>{queueType.id}</td>
                <td>{queueType.name}</td>
                <td>
                  <button
                    onClick={() => onEdit(queueType)}
                    className="btn btn-edit"
                    title="Edytuj nazwę"
                  >
                    Edytuj
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default QueueTypeList;