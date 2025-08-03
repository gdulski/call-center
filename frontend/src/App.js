import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import './App.css';
import Header from './components/Header';
import Dashboard from './pages/Dashboard';
import Calls from './pages/Calls';
import Agents from './pages/Agents';
import QueueTypes from './pages/QueueTypes';

function App() {
  return (
    <Router>
      <div className="App">
        <Header />
        <main className="main-content">
          <Routes>
            <Route path="/" element={<Dashboard />} />
            <Route path="/calls" element={<Calls />} />
            <Route path="/agents" element={<Agents />} />
            <Route path="/queue-types" element={<QueueTypes />} />
          </Routes>
        </main>
      </div>
    </Router>
  );
}

export default App; 