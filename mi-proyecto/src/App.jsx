import { useState, useEffect } from 'react';
import HeaderComponent from './components/HeaderComponent';
import FooterComponent from './components/FooterComponent';
import './App.css';
import { Flame } from 'lucide-react';
import { Routes, Route } from 'react-router-dom';
import RegistroPage from './pages/registro/RegistroPage';
import LoginPage from './pages/login/LoginPage';

function App() {
  const [token, setToken] = useState(localStorage.getItem('token'));
  const [username, setUsername] = useState(localStorage.getItem('username'));

 
  useEffect(() => {
    const handleStorageChange = () => {
      setToken(localStorage.getItem('token'));
      setUsername(localStorage.getItem('username'));
    };

    
    window.addEventListener('storage', handleStorageChange);

    
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
      </Routes>
      <FooterComponent />
    </div>
  );
}

export default App;