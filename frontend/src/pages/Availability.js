import React, { useState, useEffect } from 'react';
import AgentAvailability from '../components/Availability/AgentAvailability';
import userService from '../services/userService';

const Availability = () => {
  const [agents, setAgents] = useState([]);
  const [selectedAgent, setSelectedAgent] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  // Wczytaj agentów
  const loadAgents = async () => {
    try {
      setLoading(true);
      const users = await userService.getAll();
      // Filtruj tylko agentów
      const agentUsers = users.filter(user => user.role === 'Agent');
      setAgents(agentUsers);
      setError('');
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadAgents();
  }, []);

  const handleAgentSelect = (agent) => {
    setSelectedAgent(agent);
  };

  return (
    <div className="availability-page">
      <div className="page-header">
        <h2>Zarządzanie dostępnością agentów</h2>
      </div>

      {error && (
        <div className="alert alert-error">
          {error}
        </div>
      )}

      <div className="availability-content">
        <div className="agent-selector">
          <h3>Wybierz agenta:</h3>
          {loading ? (
            <div className="loading">Ładowanie agentów...</div>
          ) : agents.length === 0 ? (
            <div className="empty-state">
              <p>Brak dostępnych agentów</p>
            </div>
          ) : (
            <div className="agent-list">
              {agents.map((agent) => (
                <button
                  key={agent.id}
                  onClick={() => handleAgentSelect(agent)}
                  className={`agent-button ${selectedAgent?.id === agent.id ? 'selected' : ''}`}
                >
                  {agent.name}
                </button>
              ))}
            </div>
          )}
        </div>

        {selectedAgent && (
          <div className="availability-section">
            <AgentAvailability 
              agentId={selectedAgent.id} 
              agentName={selectedAgent.name} 
            />
          </div>
        )}
      </div>
    </div>
  );
};

export default Availability;