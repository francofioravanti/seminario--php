import { useState, useEffect } from 'react';
import { jwtDecode } from 'jwt-decode';
import HeaderComponent from './components/HeaderComponent';
import FooterComponent from './components/FooterComponent';
import './App.css';
import { Routes, Route } from 'react-router-dom';
import RegistroPage from './pages/registro/RegistroPage';
import LoginPage from './pages/login/LoginPage';
import EditarUsuarioPage from './pages/editar/EditarUsuarioPage';

function App() {
  const [token, setToken] = useState(null);
  const [username, setUsername] = useState(null);

  // Cargar token al iniciar
  useEffect(() => {
    const rawToken = localStorage.getItem('token');
    const storedUsername = localStorage.getItem('username');

    if (rawToken) {
      try {
        const decoded = jwtDecode(rawToken);
        const now = Date.now() / 1000;

        if (decoded.exp && decoded.exp > now) {
          setToken(rawToken);
          setUsername(storedUsername);
        } else {
          localStorage.removeItem('token');
          localStorage.removeItem('username');
        }
      } catch (err) {
        localStorage.removeItem('token');
        localStorage.removeItem('username');
      }
    }
  }, []);

  // Escuchar evento personalizado para actualizar el estado después del login
  useEffect(() => {
    const handleAuthChange = () => {
      const newToken = localStorage.getItem('token');
      const newUsername = localStorage.getItem('username');
      setToken(newToken);
      setUsername(newUsername);
    };

    window.addEventListener('authChange', handleAuthChange);
    return () => {
      window.removeEventListener('authChange', handleAuthChange);
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
        <Route
          path="/"
          element={
            <div className="main-content">
              <h2>Bienvenido a Pokebattle</h2>
            </div>
          }
        />
        <Route path="/registro" element={<RegistroPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/editar-usuario" element={<EditarUsuarioPage />} />
      </Routes>
      <FooterComponent />
    </div>
  );
}

export default App;