import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Flame } from 'lucide-react';
import './HeaderComponent.css';
import NavBarComponent from './NavBarComponent';

function HeaderComponent({ isLoggedIn, username, onLogout }) {
  const navigate = useNavigate();

  const handleClick = () => {
    navigate('/');
  };

  return (
    <header className="header">
      <div className="header-left" onClick={handleClick}>
        <Flame size={28} color="white" />
        <h1 className="titulo">Pokebattle</h1>
      </div>
      <NavBarComponent
        isLoggedIn={isLoggedIn}
        username={username}
        onLogout={onLogout}
      />
    </header>
  );
}

export default HeaderComponent;