import React, { useState, useEffect } from 'react';
import './MisMazosPage.css';

const MisMazosPage = () => {
  const [user, setUser] = useState(null);
  const [mazos, setMazos] = useState([]);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [editingMazo, setEditingMazo] = useState(null);
  const [newMazoName, setNewMazoName] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    cargarMazos();
  }, []);

  const cargarMazos = async () => {
    try {
      setLoading(true);
      setError(''); // Limpiar errores previos

      const token = localStorage.getItem('token');
      const username = localStorage.getItem('username');

      if (!token || !username) {
        setError('No estás logueado');
        setLoading(false);
        return;
      }

      setUser(username);
      
      const response = await fetch('http://localhost:8000/mazos', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.ok) {
        const data = await response.json();
        console.log('Datos recibidos:', data); // Debug
        
        // Asegurar que mazos sea siempre un array
        if (Array.isArray(data)) {
          setMazos(data);
        } else if (data && Array.isArray(data.mazos)) {
          setMazos(data.mazos);
        } else if (data && typeof data === 'object') {
          // Si data es un objeto pero no tiene mazos, puede ser que los mazos estén en la raíz
          const possibleMazos = Object.values(data).find(val => Array.isArray(val));
          setMazos(possibleMazos || []);
        } else {
          setMazos([]);
        }
        
        // NO establecer error si la carga fue exitosa
        setError('');
      } else {
        // Solo manejar error si realmente hay un problema con la respuesta
        if (response.status === 401) {
          setError('Sesión expirada. Por favor, inicia sesión nuevamente.');
          // Opcional: redirigir a login
        } else if (response.status === 404) {
          // 404 podría significar que no hay mazos, no necesariamente un error
          setMazos([]);
          setError('');
        } else {
          const text = await response.text();
          try {
            const errorData = JSON.parse(text);
            setError(errorData.error || 'Error al cargar los mazos');
          } catch {
            setError(`Error del servidor (${response.status})`);
          }
        }
      }
    } catch (error) {
      console.error('Error al cargar mazos:', error);
      // Solo mostrar error de conexión si realmente no se pudo conectar
      if (error.name === 'TypeError' && error.message.includes('fetch')) {
        setError('Error de conexión. Verifica que el servidor esté funcionando.');
      } else {
        setError('Error inesperado al cargar mazos');
      }
    } finally {
      setLoading(false);
    }
  };

  const crearMazo = async () => {
    if (!newMazoName.trim()) {
      setError('El nombre del mazo no puede estar vacío');
      return;
    }

    if (mazos.length >= 3) {
      setError('No puedes tener más de 3 mazos');
      return;
    }

    try {
      setError(''); // Limpiar errores previos
      const token = localStorage.getItem('token');

      console.log('Creando mazo con nombre:', newMazoName.trim());

      const response = await fetch('http://localhost:8000/mazos', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          nombre: newMazoName.trim(),
          cartas: []
        })
      });

      if (response.ok) {
        console.log('Mazo creado correctamente');
        setNewMazoName('');
        setShowCreateForm(false);
        await cargarMazos(); // Esperar a que se recarguen los mazos
      } else {
        const text = await response.text();
        console.warn('Error respuesta crear mazo:', text);
        try {
          const errorData = JSON.parse(text);
          setError(errorData.error || 'Error al crear el mazo');
        } catch {
          setError('Error inesperado del servidor');
        }
      }
    } catch (error) {
      console.error('Error al crear mazo:', error);
      setError('Error de conexión');
    }
  };

  const eliminarMazo = async (mazoId) => {
    if (!window.confirm('¿Estás seguro de que quieres eliminar este mazo?')) {
      return;
    }

    try {
      setError(''); // Limpiar errores previos
      const token = localStorage.getItem('token');

      const response = await fetch(`http://localhost:8000/mazos/${mazoId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.ok) {
        await cargarMazos(); // Esperar a que se recarguen los mazos
      } else {
        const text = await response.text();
        try {
          const errorData = JSON.parse(text);
          setError(errorData.error || 'Error al eliminar el mazo');
        } catch {
          setError('Error inesperado del servidor');
        }
      }
    } catch (error) {
      console.error('Error al eliminar mazo:', error);
      setError('Error de conexión');
    }
  };

  const editarMazo = async (mazoId, newName) => {
    if (!newName.trim()) {
      setError('El nombre del mazo no puede estar vacío');
      return;
    }

    try {
      setError(''); // Limpiar errores previos
      const token = localStorage.getItem('token');

      const response = await fetch(`http://localhost:8000/mazos/${mazoId}`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          nombre: newName.trim()
        })
      });

      if (response.ok) {
        setEditingMazo(null);
        await cargarMazos(); // Esperar a que se recarguen los mazos
      } else {
        const text = await response.text();
        try {
          const errorData = JSON.parse(text);
          setError(errorData.error || 'Error al intentar editar el mazo');
        } catch {
          setError('Error inesperado del servidor');
        }
      }
    } catch (error) {
      console.error('Error al editar mazo:', error);
      setError('Error de conexión');
    }
  };

  const jugarConMazo = (mazoId) => {
    console.log('Jugar con mazo:', mazoId);
    alert('Función de jugar aún no implementada');
  };

  const verCartasDeMazo = (mazoId) => {
    console.log('Ver cartas del mazo:', mazoId);
    alert('Función de ver cartas aún no implementada');
  };

  if (loading) {
    return (
      <div className="mis-mazos-page">
        <div className="loading">Cargando mazos...</div>
      </div>
    );
  }

  return (
    <div className="mis-mazos-page">
      <div className="mis-mazos-container">
        <h1>Mis Mazos</h1>
        {user && <p className="welcome-message">Bienvenido, {user}</p>}
        
        {/* Solo mostrar error si hay uno y no está vacío */}
        {error && error.trim() !== '' && (
          <div className="error-message">{error}</div>
        )}

        <div className="create-mazo-section">
          {!showCreateForm ? (
            <button
              className="btn-create-mazo"
              onClick={() => setShowCreateForm(true)}
              disabled={mazos.length >= 3}
            >
              {mazos.length >= 3 ? 'Máximo 3 mazos alcanzado' : 'Crear Nuevo Mazo'}
            </button>
          ) : (
            <div className="create-form">
              <input
                type="text"
                placeholder="Nombre del mazo"
                value={newMazoName}
                onChange={(e) => setNewMazoName(e.target.value)}
                maxLength={50}
              />
              <div className="form-buttons">
                <button onClick={crearMazo} className="btn-save">Crear</button>
                <button onClick={() => {
                  setShowCreateForm(false);
                  setNewMazoName('');
                  setError(''); // Limpiar error al cancelar
                }} className="btn-cancel">Cancelar</button>
              </div>
            </div>
          )}
        </div>

        <div className="mazos-list">
          {mazos.length === 0 ? (
            <p className="no-mazos">No tienes mazos creados aún.</p>
          ) : (
            mazos.map((mazo) => (
              <div key={mazo.id} className="mazo-card">
                <div className="mazo-header">
                  {editingMazo === mazo.id ? (
                    <div className="edit-form">
                      <input
                        type="text"
                        defaultValue={mazo.nombre}
                        onKeyPress={(e) => {
                          if (e.key === 'Enter') editarMazo(mazo.id, e.target.value);
                        }}
                        onBlur={(e) => editarMazo(mazo.id, e.target.value)}
                        autoFocus
                      />
                    </div>
                  ) : (
                    <h3 className="mazo-name">{mazo.nombre}</h3>
                  )}
                </div>

                <div className="mazo-info">
                  <p>Cartas: {mazo.cartas ? mazo.cartas.length : 0}</p>
                </div>

                <div className="mazo-actions">
                  <button onClick={() => verCartasDeMazo(mazo.id)} className="btn-action btn-view">Ver</button>
                  <button onClick={() => setEditingMazo(mazo.id)} className="btn-action btn-edit">Editar</button>
                  <button onClick={() => eliminarMazo(mazo.id)} className="btn-action btn-delete">Eliminar</button>
                  <button onClick={() => jugarConMazo(mazo.id)} className="btn-action btn-play">Jugar</button>
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
};

export default MisMazosPage;