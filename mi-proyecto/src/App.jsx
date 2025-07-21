import React, { useState, useEffect } from 'react';
import { jwtDecode } from 'jwt-decode';
import axios from 'axios';
import HeaderComponent from './components/HeaderComponent';
import FooterComponent from './components/FooterComponent';
import './App.css';
import { Routes, Route, useNavigate } from 'react-router-dom';
import RegistroPage from './pages/registro/RegistroPage';
import LoginPage from './pages/login/LoginPage';
import EditarUsuarioPage from './pages/editar/EditarUsuarioPage';
import MisMazosPage from './pages/misMazos/MisMazosPage';
import CrearMazoPage from './pages/misMazos/CrearMazoPage';
import StatPage from './pages/stat/StatPage';
import GamePage from './pages/game/GamePage';

function App() {
  const [token, setToken] = useState(null);
  const [username, setUsername] = useState(null);
  const navigate = useNavigate();

  const syncToken = () => {
    const rawToken = localStorage.getItem('token');
    const storedUsername = localStorage.getItem('username');

    if (rawToken) {
      try {
        const decoded = jwtDecode(rawToken);
        const now = Math.floor(Date.now() / 1000);

        if (decoded.exp && decoded.exp > now) {
          setToken(rawToken);
          setUsername(storedUsername);
          return;
        }
      } catch (err) {}
    }

    localStorage.removeItem('token');
    localStorage.removeItem('username');
    setToken(null);
    setUsername(null);
  };

  useEffect(syncToken, []);

  useEffect(() => {
    window.addEventListener('storage', syncToken);
    return () => window.removeEventListener('storage', syncToken);
  }, []);

  useEffect(() => {
    if (!token) return;
    try {
      const decoded = jwtDecode(token);
      const now = Math.floor(Date.now() / 1000);

      if (decoded.exp && decoded.exp > now) {
        const timeUntilExpiry = (decoded.exp - now) * 1000;
        const timeoutId = setTimeout(() => handleLogout(), timeUntilExpiry);
        return () => clearTimeout(timeoutId);
      } else {
        handleLogout();
      }
    } catch {
      handleLogout();
    }
  }, [token]);

  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('username');
    localStorage.removeItem('userId');
    setToken(null);
    setUsername(null);
    
    sessionStorage.clear();
    navigate('/'); 
  };

  return (
    <div className="app-container">
      <HeaderComponent
        isLoggedIn={!!token}
        username={username}
        onLogout={handleLogout}
      />
      <Routes>
        <Route path="/" element={<StatPage />} />
        <Route path="/registro" element={<RegistroPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/editar-usuario" element={<EditarUsuarioPage />} />
        <Route path="/mis-mazos" element={<MisMazosPage />} />
        <Route path="/crear-mazo" element={<CrearMazoPage />} />

        <Route path="/jugar/:id" element={<GamePage />} />
       
      </Routes>
      <FooterComponent />
    </div>
  );
}

export default App;