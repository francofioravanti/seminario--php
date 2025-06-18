import { useState } from 'react'
import HeaderComponent from './components/HeaderComponent';
import './App.css';
import { Flame } from 'lucide-react';
import { Routes, Route } from 'react-router-dom';

const token = localStorage.getItem('token');
const username = localStorage.getItem('username');

function App() {
  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('username');
    window.location.reload();
  };

  return (
    <div>
      <HeaderComponent
        isLoggedIn={!!token}
        username={username}
        onLogout={handleLogout}
      />
      
    </div>
  );
}

export default App;