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

  // Cargar mazos del usuario al montar el componente
  useEffect(() => {
    cargarMazos();
  }, []);

  // cargarMazos() : Para cargar los mazos desde el backend
  const cargarMazos = async () => {
    try {
      setLoading(true);
      setError('');
      
      const token = localStorage.getItem('token');
      const username = localStorage.getItem('username');
      
      if (!token || !username) {
        setError('No estás logueado');
        return;
      }

      setUser(username);
      const response = await fetch(`localhost:8000//mazos`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.ok) {
        const data = await response.json();
        setMazos(data.mazos || []);
      } else {
        const errorData = await response.json();
        setError(errorData.error || 'Error al cargar los mazos');
      }
    } catch (error) {
      console.error('Error:', error);
      setError('Error de conexión');
    } finally {
      setLoading(false);
    }
  };

  // crearMazo() : Para crear un nuevo mazo
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
      const token = localStorage.getItem('token');

      const response = await fetch('http://localhost/seminario--php-1/src/public/index.php/mazos', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          nombre: newMazoName.trim(),
          cartas: [] // Inicialmente vacío, se pueden agregar cartas después
        })
      });

      if (response.ok) {
        setNewMazoName('');
        setShowCreateForm(false);
        cargarMazos(); // Recargar la lista
      } else {
        const errorData = await response.json();
        setError(errorData.error || 'Error al crear el mazo');
      }
    } catch (error) {
      console.error('Error:', error);
      setError('Error de conexión');
    }
  };

  // eliminarMazo() : Para eliminar un mazo
  const eliminarMazo = async (mazoId) => {
    if (!confirm('¿Estás seguro de que quieres eliminar este mazo?')) {
      return;
    }

    try {
      setError('');
      const token = localStorage.getItem('token');

      const response = await fetch(`http://localhost/seminario--php-1/src/public/index.php/mazos/${mazoId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.ok) {
        cargarMazos(); // Recargar la lista
      } else {
        const errorData = await response.json();
        setError(errorData.error || 'Error al eliminar el mazo');
      }
    } catch (error) {
      console.error('Error:', error);
      setError('Error de conexión');
    }
  };

  // editarMazo() : Para editar el nombre de un mazo
  const editarMazo = async (mazoId, newName) => {
    if (!newName.trim()) {
      setError('El nombre del mazo no puede estar vacío');
      return;
    }

    try {
      setError('');
      const token = localStorage.getItem('token');

      const response = await fetch(`http://localhost/seminario--php-1/src/public/index.php/mazos/${mazoId}`, {
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
        cargarMazos(); // Recargar la lista
      } else {
        const errorData = await response.json();
        setError(errorData.error || 'Error al editar el mazo');
      }
    } catch (error) {
      console.error('Error:', error);
      setError('Error de conexión');
    }
  };

  // jugarConMazo() : Para jugar con un mazo
  const jugarConMazo = (mazoId) => {
    // Aquí puedes implementar la lógica para iniciar una partida
    console.log('Jugar con mazo:', mazoId);
    alert('Función de jugar aún no implementada');
  };

  // verCartasDeMazo() : Para ver las cartas de un mazo
  const verCartasDeMazo = (mazoId) => {
    // Aquí puedes implementar la lógica para ver las cartas del mazo
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
        
        {error && <div className="error-message">{error}</div>}
        
        {/* Sección para crear nuevo mazo */}
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
                <button onClick={crearMazo} className="btn-save">
                  Crear
                </button>
                <button 
                  onClick={() => {
                    setShowCreateForm(false);
                    setNewMazoName('');
                    setError('');
                  }} 
                  className="btn-cancel"
                >
                  Cancelar
                </button>
              </div>
            </div>
          )}
        </div>
        
        {/* Lista de mazos */}
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
                  <button 
                    onClick={() => verCartasDeMazo(mazo.id)}
                    className="btn-action btn-view"
                  >
                    Ver
                  </button>
                  <button 
                    onClick={() => setEditingMazo(mazo.id)}
                    className="btn-action btn-edit"
                  >
                    Editar
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
