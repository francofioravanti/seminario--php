import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import './RegistroPage.css';

function RegistroPage() {
  const [usuario, setUsuario] = useState('');
  const [nombre, setNombre] = useState('');
  const [password, setPassword] = useState('');
  const [errores, setErrores] = useState([]);
  const navigate = useNavigate();

  const validarFormulario = () => {
    const nuevosErrores = [];

    if (!/^[a-zA-Z0-9]{6,20}$/.test(usuario)) {
      nuevosErrores.push("El usuario debe tener entre 6 y 20 caracteres alfanuméricos.");
    }

    if (!nombre || nombre.length > 30) {
      nuevosErrores.push("El nombre debe tener entre 1 y 30 caracteres.");
    }

    if (!/^.*(?=.{8,})(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).*$/.test(password)) {
      nuevosErrores.push("La contraseña debe tener al menos 8 caracteres con mayúsculas, minúsculas, números y símbolos.");
    }

    setErrores(nuevosErrores);
    return nuevosErrores.length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (validarFormulario()) {
      try {
        const response = await fetch('http://localhost:8000/registro', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            usuario,
            nombre,
            password
          })
        });

        

        if (response.ok) {
          alert("¡Registro exitoso!");
          navigate('/login');
        } else {
          const data = await response.json();
          setErrores([data.error || "Error al registrar usuario."]);
        }
      } catch (error) {
        setErrores(["Error de conexión con el servidor."]);
      }
    }
  };

  return (
    <div className="registro-container">
      <h2>Registro de Usuario</h2>
      <form onSubmit={handleSubmit}>
        <input
          type="text"
          placeholder="Usuario"
          value={usuario}
          onChange={(e) => setUsuario(e.target.value)}
        />
        <input
          type="text"
          placeholder="Nombre público"
          value={nombre}
          onChange={(e) => setNombre(e.target.value)}
        />
        <input
          type="password"
          placeholder="Contraseña"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
        />
        <button type="submit">Registrarse</button>
      </form>
      {errores.length > 0 && (
        <ul className="errores">
          {errores.map((err, i) => (
            <li key={i}>{err}</li>
          ))}
        </ul>
      )}
    </div>
  );
}

export default RegistroPage;