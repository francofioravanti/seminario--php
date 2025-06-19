import React from 'react';
import './FooterComponent.css';

function FooterComponent() {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="footer">
      <div className="footer-content">
        <p className="footer-text">
          © {currentYear} Pokebattle - Desarrollado por Franco Fioravanti y Camila Landucci
        </p>
      </div>
    </footer>
  );
}

export default FooterComponent;