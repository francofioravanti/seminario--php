import React, { useState, useEffect } from 'react';
import axios from 'axios';
import './MisMazosPage.css';
import { useNavigate } from 'react-router-dom';

const MisMazosPage = () => {
  const [user, setUser] = useState(null);
  const [mazos, setMazos] = useState([]);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [editingMazo, setEditingMazo] = useState(null);
  const [newMazoName, setNewMazoName] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const navigate = useNavigate();
  useEffect(() => {
    cargarMazos();
  }, []);

  const getAuthHeaders = () => {
    const token = localStorage.getItem('token');
    return {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json'
    };
  };

  const cargarMazos = async () => {
    try {
      setLoading(true);
      setError('');

      const token = localStorage.getItem('token');
      const username = localStorage.getItem('username');
      const userId = localStorage.getItem('userId');

      if (!token || !username || !userId) {
        setError('No estás logueado');
        setLoading(false);
        return;
      }

      setUser(username);

      const response = await axios.get(`http://localhost:8000/usuarios/${userId}/mazos`, {
        headers: getAuthHeaders()
      });

      const data = response.data;

      if (Array.isArray(data)) {
        setMazos(data);
      } else if (data.mazos && Array.isArray(data.mazos)) {
        setMazos(data.mazos);
      } else {
        const fallback = Object.values(data).find((val) => Array.isArray(val));
        setMazos(fallback || []);
      }

    } catch (error) {
      console.error('Error al cargar mazos:', error);
      if (error.response) {
        if (error.response.status === 401) {
          setError('Sesión expirada. Por favor, inicia sesión nuevamente.');
        } else {
          setError(error.response.data?.error || `Error al cargar los mazos`);
        }
      } else {
        setError('Error de conexión');
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
      setError('');
      await axios.post(
        'http://localhost:8000/mazos',
        { nombre: newMazoName.trim(), cartas: [] },
        { headers: getAuthHeaders() }
      );

      setNewMazoName('');
      setShowCreateForm(false);
      await cargarMazos();
    } catch (error) {
      console.error('Error al crear mazo:', error);
      setError(error.response?.data?.error || 'Error al crear el mazo');
    }
  };

  const eliminarMazo = async (mazoId) => {
    if (!window.confirm('¿Estás seguro de que quieres eliminar este mazo?')) return;

    try {
      setError('');
      await axios.delete(`http://localhost:8000/mazos/${mazoId}`, {
        headers: getAuthHeaders()
      });
      await cargarMazos();
    } catch (error) {
      console.error('Error al eliminar mazo:', error);
      setError(error.response?.data?.error || 'Error al eliminar el mazo');
    }
  };

  const editarMazo = async (mazoId, newName) => {
    if (!newName.trim()) {
      setError('El nombre del mazo no puede estar vacío');
      return;
    }

    try {
      setError('');
      await axios.put(
        `http://localhost:8000/mazos/${mazoId}`,
        { nombre: newName.trim() },
        { headers: getAuthHeaders() }
      );

      setEditingMazo(null);
      await cargarMazos();
    } catch (error) {
      console.error('Error al editar mazo:', error);
      setError(error.response?.data?.error || 'Error al editar el mazo');
    }
  };

  const jugarConMazo = (mazoId) => {
    alert('Función de jugar aún no implementada');
  };

  const verCartasDeMazo = (mazoId) => {
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
        {error && error.trim() !== '' && (
          <div className="error-message">{error}</div>
        )}

        <div className="create-mazo-section">
          {!showCreateForm ? (
            <button
              className="btn-create-mazo"
              onClick={() => navigate('/crear-mazo')}
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
                  setError('');
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