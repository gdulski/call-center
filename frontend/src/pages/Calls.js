import React from 'react';

function Calls() {
  return (
    <div className="dashboard">
      <h2>Zarządzanie Połączeniami</h2>
      <p>Ta strona będzie zawierać funkcjonalności do zarządzania połączeniami.</p>
      <p>Funkcje do implementacji:</p>
      <ul style={{ textAlign: 'left', maxWidth: '600px', margin: '0 auto' }}>
        <li>Lista wszystkich połączeń</li>
        <li>Filtrowanie po dacie, agencie, statusie</li>
        <li>Szczegóły połączenia</li>
        <li>Nagrywanie rozmów</li>
        <li>Statystyki połączeń</li>
      </ul>
    </div>
  );
}

export default Calls; 