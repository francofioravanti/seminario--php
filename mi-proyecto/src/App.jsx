import { useState, useEffect } from 'react';
import HeaderComponent from './components/HeaderComponent';
import FooterComponent from './components/FooterComponent';
import './App.css';
import { Flame } from 'lucide-react';
import { Routes, Route } from 'react-router-dom';
import RegistroPage from './pages/registro/RegistroPage';
import LoginPage from './pages/login/LoginPage';
import InicioPage from './pages/stat/InicioPage';

function App() {
  const [token, setToken] = useState(localStorage.getItem('token'));
  const [username, setUsername] = useState(localStorage.getItem('username'));

  // Escuchar cambios en localStorage
  useEffect(() => {
    const handleStorageChange = () => {
      setToken(localStorage.getItem('token'));
      setUsername(localStorage.getItem('username'));
    };

    // Escuchar el evento storage (para cambios desde otras pestañas)
    window.addEventListener('storage', handleStorageChange);

    // Escuchar un evento personalizado para cambios en la misma pestaña
    window.addEventListener('authChange', handleStorageChange);

    return () => {
      window.removeEventListener('storage', handleStorageChange);
      window.removeEventListener('authChange', handleStorageChange);
    };
  }, []);

  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('username');
    setToken(null);
    setUsername(null);
  };

  return (
    <div className="app-container">
      <HeaderComponent
        isLoggedIn={!!token}
        username={username}
        onLogout={handleLogout}
      />
      <Routes>
        <Route path="/" element={<InicioPage />} />
        <Route path="/registro" element={<RegistroPage />} />
        <Route path="/login" element={<LoginPage />} />
      </Routes>
      <FooterComponent />
    </div>
  );
}

export default App;