import React from 'react';
import { Link } from 'react-router-dom';

function Header() {
  return (
    <header className="header">
      <h1>Call Center</h1>
      <nav className="nav">
        <Link to="/">Dashboard</Link>
        <Link to="/calls">Połączenia</Link>
        <Link to="/agents">Agenci</Link>
      </nav>
    </header>
  );
}

export default Header; 