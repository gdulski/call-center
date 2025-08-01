import React, { useState, useEffect } from 'react';
import axios from 'axios';

function Dashboard() {
  const [stats, setStats] = useState({
    totalCalls: 0,
    activeAgents: 0,
    avgCallDuration: 0,
    customerSatisfaction: 0
  });

  useEffect(() => {
    // Test API connection
    const testApi = async () => {
      try {
        const response = await axios.get('/api/health');
        console.log('API Status:', response.data);
      } catch (error) {
        console.error('API Error:', error);
      }
    };

    testApi();
  }, []);

  return (
    <div className="dashboard">
      <h2>Dashboard</h2>
      <p>Witaj w systemie zarządzania call center</p>
      
      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-number">{stats.totalCalls}</div>
          <div className="stat-label">Łączna liczba połączeń</div>
        </div>
        
        <div className="stat-card">
          <div className="stat-number">{stats.activeAgents}</div>
          <div className="stat-label">Aktywni agenci</div>
        </div>
        
        <div className="stat-card">
          <div className="stat-number">{stats.avgCallDuration}min</div>
          <div className="stat-label">Średni czas połączenia</div>
        </div>
        
        <div className="stat-card">
          <div className="stat-number">{stats.customerSatisfaction}%</div>
          <div className="stat-label">Satysfakcja klientów</div>
        </div>
      </div>
    </div>
  );
}

export default Dashboard; 