import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import './CrearMazoPage.css';

const CrearMazoPage = () => {
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [cartas, setCartas] = useState([]);
  const [cartasFiltradas, setCartasFiltradas] = useState([]);
  const [filtroNombre, setFiltroNombre] = useState('');
  const [filtroAtributo, setFiltroAtributo] = useState('');
  const [seleccionadas, setSeleccionadas] = useState([]);
  const [nombreMazo, setNombreMazo] = useState('');
  const [cartasSeleccionadas, setCartasSeleccionadas] = useState([]);
  const [atributos, setAtributos] = useState([]);
  const navigate = useNavigate();

  const getAuthHeaders = () => {
    const token = localStorage.getItem('token');
    return {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    };
  };

  // Cargar atributos al inicio
  const cargarAtributos = async () => {
    try {
      // Usar atributos hardcodeados temporalmente
      setAtributos([
        { id: 1, nombre: 'Fuego' },
        { id: 2, nombre: 'Agua' },
        { id: 3, nombre: 'Planta' },
        { id: 4, nombre: 'Eléctrico' },
        { id: 5, nombre: 'Psíquico' }
      ]);
    } catch (err) {
      console.error('Error al cargar atributos:', err);
    }
  };

  // Cargar todas las cartas al inicio
  const cargarTodasLasCartas = async () => {
    try {
      const response = await axios.get('http://localhost:8000/cartas', {
        headers: getAuthHeaders()
      });
      if (Array.isArray(response.data)) {
        setCartas(response.data);
        setCartasFiltradas(response.data);
      } else {
        setCartas([]);
        setCartasFiltradas([]);
      }
      setError('');
    } catch (err) {
      console.error('Error al cargar todas las cartas:', err);
      setError('No se pudieron cargar las cartas');
      setCartas([]);
      setCartasFiltradas([]);
    }
  };

  useEffect(() => {
    const verificarLimiteDeMazos = async () => {
      try {
        const userId = localStorage.getItem('userId');
        if (!userId) {
          setError('No estás logueado');
          navigate('/login');
          return;
        }
        const response = await axios.get(`http://localhost:8000/usuarios/${userId}/mazos`, {
          headers: getAuthHeaders()
        });
        const mazos = Array.isArray(response.data) ? response.data : response.data.mazos || [];
        if (mazos.length >= 3) {
          alert('Ya tienes el máximo de 3 mazos.');
          navigate('/mis-mazos');
        } else {
          await cargarTodasLasCartas();
          await cargarAtributos();
          setLoading(false);
        }
      } catch (err) {
        console.error('Error verificando mazos:', err);
        setError('Error al verificar los mazos');
        navigate('/mis-mazos');
      }
    };
    verificarLimiteDeMazos();
  }, [navigate]);

  // Función para buscar cartas con filtros en el backend
  const buscarCartasConFiltros = async () => {
    try {
      const params = new URLSearchParams();
      
      if (filtroNombre.trim() !== '') {
        params.append('nombre', filtroNombre);
      }
      
      if (filtroAtributo !== '') {
        params.append('atributo', filtroAtributo);
      }
      
      const url = `http://localhost:8000/cartas${params.toString() ? '?' + params.toString() : ''}`;
      const response = await axios.get(url, {
        headers: getAuthHeaders()
      });
      
      if (Array.isArray(response.data)) {
        setCartasFiltradas(response.data);
      }
    } catch (err) {
      console.error('Error al buscar cartas:', err);
      setError('Error al buscar cartas');
    }
  };
  
  // Aplicar filtros cuando cambien los valores
  useEffect(() => {
    buscarCartasConFiltros();
  }, [filtroNombre, filtroAtributo]);

  const limpiarFiltros = () => {
    setFiltroNombre('');
    setFiltroAtributo('');
    setCartasFiltradas(cartas);
  };
  
  // Función corregida para selección individual de cartas
  const alternarCarta = (carta) => {
    // Usar la misma lógica que en el renderizado
    const cartaId = carta.id || `${carta.nombre}-${carta.atributo}`;
    
    const yaSeleccionada = cartasSeleccionadas.find(c => {
      const cId = c.id || `${c.nombre}-${c.atributo}`;
      return cId === cartaId;
    });
    
    if (yaSeleccionada) {
      setCartasSeleccionadas(cartasSeleccionadas.filter(c => {
        const cId = c.id || `${c.nombre}-${c.atributo}`;
        return cId !== cartaId;
      }));
      setSeleccionadas(seleccionadas.filter(id => id !== cartaId));
      setError('');
    } else {
      if (cartasSeleccionadas.length >= 5) {
        setError('Solo puedes seleccionar máximo 5 cartas');
        return;
      }
      setCartasSeleccionadas([...cartasSeleccionadas, carta]);
      setSeleccionadas([...seleccionadas, cartaId]);
      setError('');
    }
  };

  const confirmarMazo = async () => {
    if (nombreMazo.trim() === '') {
      setError('El nombre del mazo es obligatorio');
      return;
    }
    
    if (nombreMazo.length > 20) {
      setError('El nombre del mazo no puede tener más de 20 caracteres');
      return;
    }

    if (seleccionadas.length === 0) {
      setError('Debes seleccionar al menos una carta');
      return;
    }

    if (seleccionadas.length > 5) {
      setError('No puedes seleccionar más de 5 cartas');
      return;
    }

    try {
      const token = localStorage.getItem('token');
      const userId = localStorage.getItem('userId');
      if (!token || !userId) {
        setError('No estás logueado. Por favor, inicia sesión.');
        navigate('/login');
        return;
      }
      // Solo IDs únicos de cartas
      console.log('Cartas seleccionadas:', cartasSeleccionadas); // <-- Agrega esto
      const cartasIds = [...new Set(cartasSeleccionadas.filter(carta => typeof carta.id === 'number').map(carta => carta.id))];
      const datosParaEnviar = {
        nombre: nombreMazo.trim(),
        cartas: cartasIds
      };
      console.log('Datos que se enviarán al backend:', datosParaEnviar); //
      await axios.post('http://localhost:8000/mazos', datosParaEnviar, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });
      alert('Mazo creado con éxito');
      navigate('/mis-mazos');
    } catch (err) {
      console.error('Error completo:', err);
      
      if (err.response) {
        console.error('Datos de respuesta del error:', err.response.data);
        console.error('Status del error:', err.response.status);
        
        if (err.response.status === 401) {
          setError('Sesión expirada. Por favor, inicia sesión nuevamente.');
          localStorage.removeItem('token');
          localStorage.removeItem('userId');
          navigate('/login');
        } else if (err.response.status === 400) {
          // Error de validación del backend
          if (err.response.data && err.response.data.error) {
            setError(`Error de validación: ${err.response.data.error}`);
          } else if (err.response.data && err.response.data.message) {
            setError(`Error: ${err.response.data.message}`);
          } else {
            setError('Los datos enviados no son válidos. Verifica que hayas seleccionado cartas correctamente.');
          }
        } else if (err.response.data) {
          if (err.response.data.errores && Array.isArray(err.response.data.errores)) {
            setError(err.response.data.errores.join(', '));
          } else if (err.response.data.error) {
            setError(err.response.data.error);
          } else if (err.response.data.message) {
            setError(err.response.data.message);
          } else {
            setError('Error del servidor: ' + JSON.stringify(err.response.data));
          }
        } else {
          setError(`Error del servidor (${err.response.status})`);
        }
      } else if (err.request) {
        setError('No se pudo conectar con el servidor. Verifica que esté ejecutándose en http://localhost:8000');
      } else {
        setError('Error inesperado: ' + err.message);
      }
    }
  };

  // Función para agrupar cartas por atributo
  const agruparCartasPorAtributo = (cartas) => {
    const grupos = {};
    cartas.forEach(carta => {
      const atributo = carta.atributo || 'Sin atributo';
      if (!grupos[atributo]) {
        grupos[atributo] = [];
      }
      grupos[atributo].push(carta);
    });
    return grupos;
  };

  // Función para obtener imagen de Pokémon
  const obtenerImagenPokemon = (nombreCarta) => {
    const nombreFormateado = nombreCarta.toLowerCase().replace(/\s+/g, '-');
    return `https://img.pokemondb.net/artwork/large/${nombreFormateado}.jpg`;
  };

  // Componente de carta individual mejorado
  const CartaComponent = ({ carta, estaSeleccionada, onClick }) => {
    const [imagenError, setImagenError] = useState(false);
    
    return (
      <div
        className={`carta-card ${estaSeleccionada ? 'seleccionada' : ''}`}
        onClick={(e) => {
          e.preventDefault();
          e.stopPropagation();
          onClick(carta);
        }}
        style={{ 
          border: estaSeleccionada ? '3px solid #764ba2' : '1px solid #ddd',
          borderRadius: '8px',
          padding: '10px',
          margin: '5px',
          cursor: 'pointer',
          backgroundColor: estaSeleccionada ? '#f3e8ff' : 'white',
          transition: 'all 0.3s ease',
          transform: estaSeleccionada ? 'scale(1.05)' : 'scale(1)',
          boxShadow: estaSeleccionada ? '0 4px 12px rgba(118, 75, 162, 0.3)' : '0 2px 4px rgba(0,0,0,0.1)'
        }}
      >
        {!imagenError ? (
          <img 
            src={obtenerImagenPokemon(carta.nombre)}
            alt={carta.nombre}
            style={{ width: '80px', height: '80px', objectFit: 'cover', borderRadius: '4px' }}
            onError={() => setImagenError(true)}
          />
        ) : (
          <div style={{ 
            width: '80px', 
            height: '80px', 
            backgroundColor: '#f0f0f0',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: '10px',
            borderRadius: '4px'
          }}>
            Sin imagen
          </div>
        )}
        <h4 style={{ color: 'black', margin: '5px 0', fontSize: '14px' }}>{carta.nombre}</h4>
        <p style={{ color: 'black', margin: '2px 0', fontSize: '12px' }}>⚡ {carta.atributo}</p>
        <p style={{ color: 'black', margin: '2px 0', fontSize: '12px' }}>🗡️ {carta.ataque}</p>
        {estaSeleccionada && (
          <div style={{ color: '#764ba2', fontWeight: 'bold', fontSize: '12px' }}>✓ Seleccionada</div>
        )}
      </div>
    );
  };

  if (loading) return <div className="crear-mazo-page">Verificando mazos...</div>;

  const cartasAgrupadas = agruparCartasPorAtributo(cartasFiltradas);

  return (
    <div className="crear-mazo-container">
      <div className="crear-mazo-card">
        <div className="crear-mazo-header">
          <h2>Crear un nuevo mazo</h2>
          <p>Agregá hasta 5 cartas</p>
        </div>

        <input
          type="text"
          placeholder="Nombre del mazo (máx. 20 caracteres)"
          value={nombreMazo}
          onChange={(e) => setNombreMazo(e.target.value)}
          className="form-filter"
          style={{ 
            marginBottom: '8px',
            borderColor: nombreMazo.length > 20 ? 'red' : '#ddd'
          }}
          maxLength={20}
        />
        {nombreMazo.length > 20 && (
          <small style={{ color: 'red' }}>Máximo 20 caracteres</small>
        )}

        <input
          type="text"
          placeholder="Buscar por nombre de carta"
          value={filtroNombre}
          onChange={(e) => setFiltroNombre(e.target.value)}
          className="form-filter"
          style={{ marginBottom: '8px' }}
        />
        
        <select 
          value={filtroAtributo}
          onChange={(e) => setFiltroAtributo(e.target.value)}
          className="form-filter"
        >
          <option value="">Todos los atributos</option>
          {atributos.map(atributo => (
            <option key={atributo.id} value={atributo.id}>{atributo.nombre}</option>
          ))}
        </select>

        <div style={{ display: 'flex', gap: '10px', marginBottom: '8px' }}>
          <button onClick={limpiarFiltros} className="login-button">Limpiar Filtros</button>
        </div>

        {error && <div className="error-message">{error}</div>}

        <div className="cartas-container">
          {Object.keys(cartasAgrupadas).length === 0 && <p>No se encontraron cartas con los filtros aplicados.</p>}
          
          {Object.entries(cartasAgrupadas).map(([atributo, cartasDelAtributo]) => (
            <div key={atributo} className="grupo-atributo">
              <h3 className="titulo-atributo">{atributo.toUpperCase()}</h3>
              <div className="cartas-listado">
                {cartasDelAtributo.map((carta, index) => {
                  const cartaId = carta.id || `${carta.nombre}-${carta.atributo}`;
                  const estaSeleccionada = seleccionadas.includes(cartaId);
                  return (
                    <CartaComponent
                      key={cartaId || `${carta.nombre}-${index}`}
                      carta={carta}
                      estaSeleccionada={estaSeleccionada}
                      onClick={alternarCarta}
                    />
                  );
                })}
              </div>
            </div>
          ))}
        </div>

        <div style={{ marginTop: '1rem' }}>
          <button
            onClick={confirmarMazo}
            className="login-button"
            disabled={seleccionadas.length === 0 || seleccionadas.length > 5}
          >
            Confirmar Mazo ({seleccionadas.length}/5)
          </button>
        </div>
      </div>
    </div>
  );
};

export default CrearMazoPage;