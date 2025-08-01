import React from 'react';

function Agents() {
  return (
    <div className="dashboard">
      <h2>Zarządzanie Agentami</h2>
      <p>Ta strona będzie zawierać funkcjonalności do zarządzania agentami call center.</p>
      <p>Funkcje do implementacji:</p>
      <ul style={{ textAlign: 'left', maxWidth: '600px', margin: '0 auto' }}>
        <li>Lista wszystkich agentów</li>
        <li>Dodawanie nowych agentów</li>
        <li>Edycja danych agenta</li>
        <li>Status agenta (online/offline)</li>
        <li>Statystyki wydajności</li>
        <li>Harmonogram pracy</li>
      </ul>
    </div>
  );
}

export default Agents; 