import React from 'react';

const ScheduleList = ({ schedules, onEdit, isLoading }) => {
  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('pl-PL');
  };

  const getStatusText = (status) => {
    const statusMap = {
      'draft': 'Szkic',
      'generated': 'Wygenerowany',
      'published': 'Opublikowany',
      'finalized': 'Sfinalizowany'
    };
    return statusMap[status] || status;
  };

  const getStatusClass = (status) => {
    const classMap = {
      'draft': 'status-draft',
      'generated': 'status-generated',
      'published': 'status-published',
      'finalized': 'status-finalized'
    };
    return classMap[status] || '';
  };

  if (isLoading) {
    return <div className="loading">Ładowanie harmonogramów...</div>;
  }

  if (!schedules || schedules.length === 0) {
    return (
      <div className="empty-state">
        <p>Brak harmonogramów do wyświetlenia.</p>
        <p>Utwórz pierwszy harmonogram używając przycisku "Generuj harmonogram".</p>
      </div>
    );
  }

  return (
    <div className="schedule-list">
      <h3>Lista harmonogramów</h3>
      <div className="table-responsive">
        <table className="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Typ kolejki</th>
              <th>Tydzień</th>
              <th>Data rozpoczęcia</th>
              <th>Status</th>
              <th>Akcje</th>
            </tr>
          </thead>
          <tbody>
            {schedules.map((schedule) => (
              <tr key={schedule.id}>
                <td>{schedule.id}</td>
                <td>
                  {schedule.queueType?.name || 
                   (typeof schedule.queueType === 'string' ? schedule.queueType : 'N/A')}
                </td>
                <td>{schedule.weekIdentifier || 'N/A'}</td>
                <td>{formatDate(schedule.weekStartDate)}</td>
                <td>
                  <span className={`status ${getStatusClass(schedule.status)}`}>
                    {getStatusText(schedule.status)}
                  </span>
                </td>
                <td>
                  <div className="action-buttons">
                    <button
                      onClick={() => onEdit(schedule)}
                      className="btn btn-secondary btn-sm"
                      title="Edytuj harmonogram"
                    >
                      Edytuj
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default ScheduleList;