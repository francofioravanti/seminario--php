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
  const [viewingMazo, setViewingMazo] = useState(null);
  const [cartasMazo, setCartasMazo] = useState([]);
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

      console.log('Datos recibidos del backend:', response.data);

      const data = response.data;

      // Verificar que los datos sean un array
      if (!Array.isArray(data)) {
        console.error('Los datos recibidos no son un array:', data);
        setError('Error en el formato de datos del servidor');
        setMazos([]);
        return;
      }

      // Verificar que cada mazo tenga la estructura correcta
      const mazosFormateados = data.map(mazo => ({
        id: mazo.id,
        nombre: mazo.nombre,
        cartas: Array.isArray(mazo.cartas) ? mazo.cartas : []
      }));

      setMazos(mazosFormateados);
    } catch (error) {
      console.error('Error al cargar mazos:', error);
      if (error.response) {
        if (error.response.status === 401) {
          setError('Sesión expirada. Por favor, inicia sesión nuevamente.');
        } else if (error.response.status === 403) {
          setError('No tienes permisos para acceder a estos mazos.');
        } else {
          setError(error.response.data?.error || 'Error al cargar los mazos');
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
      setEditingMazo(null); // Cancelar edición
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
      setEditingMazo(null);
    }
  };

  const verCartasDeMazo = (mazoId) => {
    const mazo = mazos.find(m => m.id === mazoId);
    setCartasMazo(mazo ? mazo.cartas : []);
    setViewingMazo(mazoId);
  };

  const jugarConMazo = (mazoId) => {
    navigate(`/jugar/${mazoId}`);
  };

  const obtenerImagenPokemon = (nombreCarta) => {
    const nombreFormateado = nombreCarta.toLowerCase().replace(/\s+/g, '-');
    return `https://img.pokemondb.net/artwork/large/${nombreFormateado}.jpg`;
  };

  const CartaComponent = ({ carta }) => (
    <div className="carta-item">
      <div className="carta-imagen">
        <img
          src={obtenerImagenPokemon(carta.nombre)}
          alt={carta.nombre}
          onError={(e) => {
            e.target.src = '/flame.svg';
          }}
        />
      </div>
      <div className="carta-info">
        <h3 className="carta-nombre">{carta.nombre}</h3>
        <div className="carta-stats">
          <div className="stat-item">
            <span className="stat-label">Ataque:</span>
            <span className="stat-value">{carta.ataque}</span>
          </div>
          <div className="stat-item">
            <span className="stat-label">Atributo:</span>
            <span className={`stat-value tipo-badge tipo-${carta.atributo.toLowerCase()}`}>{carta.atributo}</span>
          </div>
        </div>
        <div className="carta-id">
          <small>ID: {carta.id}</small>
        </div>
      </div>
    </div>
  );

  // Función para cancelar edición
  const cancelarEdicion = () => {
    setEditingMazo(null);
  };

  if (loading) {
    return (
      <div className="mis-mazos-page">
        <div className="loading">Cargando mazos...</div>
      </div>
    );
  }

  if (viewingMazo) {
    return (
      <div className="mis-mazos-page">
        <div className="mis-mazos-container">
          <div className="mazo-viewer">
            <div className="mazo-viewer-header">
              <button 
                onClick={() => {
                  setViewingMazo(null);
                  setCartasMazo([]);
                }} 
                className="btn-back"
              >
                ← Volver a Mis Mazos
              </button>
              <h2>Cartas del Mazo</h2>
            </div>
            
            <div className="cartas-grid">
              {cartasMazo.map((carta, index) => (
                <CartaComponent key={`${carta.id}-${index}`} carta={carta} />
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="mis-mazos-page">
      <div className="mis-mazos-container">
        <h1>Mis Mazos</h1>
        
        {error && (
          <div className="error-message">{error}</div>
        )}

        <div className="create-mazo-section">
          <button
            className="btn-create-mazo"
            onClick={() => navigate('/crear-mazo')}
            disabled={mazos.length >= 3}
          >
            {mazos.length >= 3 ? 'Máximo 3 mazos alcanzado' : 'Crear Nuevo Mazo'}
          </button>
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
                          if (e.key === 'Enter') {
                            editarMazo(mazo.id, e.target.value);
                          }
                          if (e.key === 'Escape') {
                            cancelarEdicion();
                          }
                        }}
                        onBlur={(e) => editarMazo(mazo.id, e.target.value)}
                        autoFocus
                      />
                      <button 
                        onClick={cancelarEdicion}
                        className="btn-cancel-edit"
                        title="Cancelar edición"
                      >
                        ✕
                      </button>
                    </div>
                  ) : (
                    <div className="mazo-title">
                      <h3 className="mazo-name">{mazo.nombre || 'Sin nombre'}</h3>
                      <button 
                        onClick={() => setEditingMazo(mazo.id)} 
                        className="btn-edit-icon"
                        title="Editar nombre"
                      >
                        ✏️
                      </button>
                    </div>
                  )}
                </div>

                <div className="mazo-info">
                  <p className="mazo-details">
                    Cartas: {mazo.cartas ? mazo.cartas.length : 0}
                  </p>
                </div>

                <div className="mazo-actions">
                  <button 
                    onClick={() => verCartasDeMazo(mazo.id)} 
                    className="btn-action btn-view"
                  >
                    Ver Mazo
                  </button>
                  <button 
                    onClick={() => eliminarMazo(mazo.id)} 
                    className="btn-action btn-delete"
                  >
                    Eliminar
                  </button>
                  <button 
                    onClick={() => jugarConMazo(mazo.id)} 
                    className="btn-action btn-play"
                  >
                    Jugar
                  </button>
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