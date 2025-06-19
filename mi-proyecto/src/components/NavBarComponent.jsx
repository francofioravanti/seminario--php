import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import './NavBarComponent.css';

function NavBarComponent({ isLoggedIn, username, onLogout }) {
  const location = useLocation();

  return (
    <nav className="nav-right">
      {!isLoggedIn ? (
        <>
          {location.pathname !== '/registro' && (
            <Link to="/registro" className="nav-button">Registro</Link>
          )}
          <Link to="/login" className="nav-button">Login</Link>
        </>
      ) : (
        <>
          <span>Hola, {username}!</span>
          <Link to="/mis-mazos" className="nav-button">Mis Mazos</Link>
          <Link to="/editar-usuario" className="nav-button">Editar</Link>
          <button onClick={onLogout} className="logout-btn">Logout</button>
        </>
      )}
    </nav>
  );
}

export default NavBarComponent;