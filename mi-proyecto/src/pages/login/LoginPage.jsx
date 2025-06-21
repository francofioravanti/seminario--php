import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import './LoginPage.css';

const LoginPage = () => {
  // Estados para manejar los datos del formulario
  const [formData, setFormData] = useState({
    username: '',
    password: ''
  });
  
  // Estados de errores y carga
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  
  // Hook para navegación
  const navigate = useNavigate();

  // Función para manejar cambios en los inputs
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    // Limpiar error cuando el usuario empiece a escribir
    if (error) setError('');
  };

  // Función para validar el formulario
  const validateForm = () => {
    if (!formData.username.trim()) {
      setError('El nombre de usuario es requerido');
      return false;
    }
    if (!formData.password.trim()) {
      setError('La contraseña es requerida');
      return false;
    }
    return true;
  };

  // Función para manejar el envío del formulario
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Validar formulario
    if (!validateForm()) return;
    
    setIsLoading(true);
    setError('');
    
    try {
      // Realizar petición al backend
      const response = await fetch('http://localhost/seminario--php-1/src/public/index.php/usuario/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          username: formData.username,
          password: formData.password
        })
      });
      
      const data = await response.json();
      
      if (response.ok) {
        // Login exitoso
        localStorage.setItem('token', data.token);
        localStorage.setItem('username', data.username);
        
        // Recargar la página para actualizar el header
        window.location.reload();
        
        // Navegar a la página principal
        navigate('/');
      } else {
        // Error en el login
        setError(data.message || 'Fallo el inicio de sesión');
      }
    } catch (err) {
      console.error('Error en login:', err);
      setError('Error de conexión. Por favor, intenta nuevamente.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="login-container">
      <div className="login-card">
        <div className="login-header">
          <h2>Iniciar Sesión</h2>
          <p>Ingresa a tu cuenta de Pokebattle</p>
        </div>
        
        <form onSubmit={handleSubmit} className="login-form">
          {/* Campo Usuario */}
          <div className="form-group">
            <label htmlFor="username">Usuario</label>
            <input
              type="text"
              id="username"
              name="username"
              value={formData.username}
              onChange={handleInputChange}
              placeholder="Ingrese el nombre de usuario"
              disabled={isLoading}
              className={error && !formData.username ? 'error' : ''}
            />
          </div>
          
          {/* Campo Contraseña */}
          <div className="form-group">
            <label htmlFor="password">Contraseña</label>
            <input
              type="password"
              id="password"
              name="password"
              value={formData.password}
              onChange={handleInputChange}
              placeholder="Ingresa la contraseña"
              disabled={isLoading}
              className={error && !formData.password ? 'error' : ''}
            />
          </div>
          
          {/* Mostrar errores */}
          {error && (
            <div className="error-message">
              {error}
            </div>
          )}
          
          {/* Botón de envío */}
          <button 
            type="submit" 
            className="login-button"
            disabled={isLoading}
          >
            {isLoading ? 'Iniciando sesión...' : 'Iniciar Sesión'}
          </button>
        </form>
        
        {/* Link para registro */}
        <div className="login-footer">
          <p>
            ¿No tienes cuenta? {' '}
            <a href="/registro" className="register-link">
              Regístrate aquí
            </a>
          </p>
        </div>
      </div>
    </div>
  );
};

export default LoginPage;