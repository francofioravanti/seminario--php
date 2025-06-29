import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useState } from 'react';
import './NavBarComponent.css';

function NavBarComponent({ isLoggedIn, username, onLogout }) {
  const location = useLocation();
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  const toggleMenu = () => {
    setIsMenuOpen(!isMenuOpen);
  };

  const closeMenu = () => {
    setIsMenuOpen(false);
  };

  return (
    <nav className="nav-right">
      {!isLoggedIn ? (
        <>
          {location.pathname !== '/registro' && (
            <Link to="/registro" className="nav-button">Registro</Link>
          )}
          <Link to="/login" className="nav-button">Iniciar Sesión</Link>
        </>
      ) : (
        <>
          <span className="welcome-text">Hola, {username}!</span>
          <div className="menu-container">
            <button className="hamburger-btn" onClick={toggleMenu}>
              <span className="hamburger-line"></span>
              <span className="hamburger-line"></span>
              <span className="hamburger-line"></span>
            </button>
            {isMenuOpen && (
              <div className="dropdown-menu">
                <Link to="/mis-mazos" className="dropdown-item" onClick={closeMenu}>
                  Mazos
                </Link>
                <Link to="/editar-usuario" className="dropdown-item " onClick={closeMenu}>
                  Editar usuario
                </Link>
                <button onClick={() => { onLogout(); closeMenu(); }} className="dropdown-item " >
                  Cerrar sesión
                </button>
              </div>
            )}
          </div>
        </>
      )}
    </nav>
  );
}

export default NavBarComponent;