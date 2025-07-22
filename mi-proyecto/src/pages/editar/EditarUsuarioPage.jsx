import React, { useState, useEffect } from 'react';
import './EditarUsuarioPage.css';

function EditarUsuarioPage() {
  const [nombre, setNombre] = useState('');
  const [password, setPassword] = useState('');
  const [repetirPassword, setRepetirPassword] = useState('');
  const [mensaje, setMensaje] = useState('');
  const [errores, setErrores] = useState([]);

  const token = localStorage.getItem('token');
  const userId = Number(localStorage.getItem('userId'));

  useEffect(() => {
    const cargarNombre = async () => {
      if (!token || !userId) return;

      try {
        const response = await fetch(`http://localhost:8000/usuarios/${userId}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });

        const data = await response.json();
        if (response.ok && data.nombre) {
          setNombre(data.nombre); // ✅ precarga el nombre
        } else {
          setErrores([data.error || 'No se pudo cargar el nombre del usuario.']);
        }
      } catch {
        setErrores(['Error al obtener los datos del usuario.']);
      }
    };

    cargarNombre();
  }, [token, userId]);

  const validarFormulario = () => {
    const nuevosErrores = [];

    if (!nombre || nombre.length > 30) {
      nuevosErrores.push("El nombre debe tener entre 1 y 30 caracteres.");
    }

    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
    if (!passwordRegex.test(password)) {
      nuevosErrores.push("La contraseña debe tener al menos 8 caracteres con mayúsculas, minúsculas, números y símbolos.");
    }

    if (password !== repetirPassword) {
      nuevosErrores.push("Las contraseñas no coinciden.");
    }

    setErrores(nuevosErrores);
    return nuevosErrores.length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!token || !userId) {
      setErrores(["No estás logueado."]);
      return;
    }

    if (!validarFormulario()) return;

    try {
      const response = await fetch(`http://localhost:8000/usuarios/${userId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ nombre, clave: password })
      });

      const data = await response.json();

      if (response.ok) {
        setMensaje("Usuario actualizado correctamente.");
        setErrores([]);
        localStorage.setItem('username', nombre);
      } else {
        setMensaje('');
        setErrores(data.errores || [data.error || "Error al actualizar."]);
      }
    } catch (err) {
      setMensaje('');
      setErrores(["No se pudo conectar con el servidor."]);
    }
  };

  return (
    <div className="editar-container">
      <div className="editar-card">
        <div className="editar-header">
          <h2>Editar Usuario</h2>
          <p>¡Cambiá tu nombre de usuario y contraseña!</p>
        </div>

        <form onSubmit={handleSubmit}>
          <input
            type="text"
            placeholder="Nuevo nombre"
            value={nombre}
            onChange={(e) => setNombre(e.target.value)}
          />
          <input
            type="password"
            placeholder="Nueva contraseña"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
          <input
            type="password"
            placeholder="Repetir contraseña"
            value={repetirPassword}
            onChange={(e) => setRepetirPassword(e.target.value)}
          />
          <button type="submit">Guardar cambios</button>
        </form>

        {mensaje && <p className="success">{mensaje}</p>}
        {errores.length > 0 && (
          <ul className="errores">
            {errores.map((err, i) => (
              <li key={i}>{err}</li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}

export default EditarUsuarioPage;
