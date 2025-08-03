import React from 'react';

const UserList = ({ users, onEdit, isLoading = false }) => {
  if (isLoading) {
    return <div className="loading">Ładowanie użytkowników...</div>;
  }

  if (users.length === 0) {
    return (
      <div className="empty-state">
        <p>Brak użytkowników do wyświetlenia.</p>
      </div>
    );
  }

  const getRoleBadgeClass = (role) => {
    switch (role) {
      case 'Agent':
        return 'role-badge role-agent';
      case 'Manager':
        return 'role-badge role-manager';
      default:
        return 'role-badge';
    }
  };

  return (
    <div className="user-list">
      <h3>Lista użytkowników</h3>
      
      <div className="list-container">
        <table className="users-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nazwa</th>
              <th>Rola</th>
              <th>Przypisane kolejki</th>
              <th>Akcje</th>
            </tr>
          </thead>
          <tbody>
            {users.map((user) => (
              <tr key={user.id}>
                <td>{user.id}</td>
                <td>{user.name}</td>
                <td>
                  <span className={getRoleBadgeClass(user.role)}>
                    {user.role}
                  </span>
                </td>
                <td>
                  <div className="queue-types-list">
                    {user.agentQueueTypes && user.agentQueueTypes.length > 0 ? (
                      user.agentQueueTypes.map((aqt, index) => (
                        <span key={aqt.queueType.id} className="queue-type-tag">
                          {aqt.queueType.name}
                        </span>
                      ))
                    ) : (
                      <span className="no-queues">Brak przypisań</span>
                    )}
                  </div>
                </td>
                <td>
                  <button
                    onClick={() => onEdit(user)}
                    className="btn btn-edit"
                    title="Edytuj użytkownika"
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

export default UserList;