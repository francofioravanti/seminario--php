import { useState } from 'react'
import HeaderComponent from './components/HeaderComponent';
import FooterComponent from './components/FooterComponent';
import './App.css';
import { Flame } from 'lucide-react';
import { Routes, Route } from 'react-router-dom';
import RegistroPage from './pages/registro/RegistroPage';
const token = localStorage.getItem('token');
const username = localStorage.getItem('username');

function App() {
  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('username');
    window.location.reload();
  };

  return (
    <div className="app-container">
      <HeaderComponent
        isLoggedIn={!!token}
        username={username}
        onLogout={handleLogout}
      />
      <Routes>
        <Route path="/" element={<h2>Bienvenido a Pokebattle</h2>} />
        <Route path="/registro" element={<RegistroPage />} />
      </Routes>
    </div>
  );
}

export default App;