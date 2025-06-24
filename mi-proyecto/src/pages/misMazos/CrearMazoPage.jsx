import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import './CrearMazoPage.css';

const CrearMazoPage = () => {
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [cartas, setCartas] = useState([]);
  const [filtroNombre, setFiltroNombre] = useState('');
  const [filtroAtributo, setFiltroAtributo] = useState('');
  const [seleccionadas, setSeleccionadas] = useState([]);
  const [nombreMazo, setNombreMazo] = useState('');
  const navigate = useNavigate();

  const getAuthHeaders = () => {
    const token = localStorage.getItem('token');
    return {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    };
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

  const buscarCartas = async () => {
    try {
      const response = await axios.get('http://localhost:8000/cartas', {
        headers: getAuthHeaders(),
        params: {
          nombre: filtroNombre,
          atributo: filtroAtributo
        }
      });
      if (Array.isArray(response.data)) {
        const nuevasCartas = response.data;
        setCartas(nuevasCartas);
        const nuevasIds = nuevasCartas.map(c => c.id);
        setSeleccionadas((prev) => prev.filter(id => nuevasIds.includes(id)));
      } else {
        setCartas([]);
        setSeleccionadas([]);
      }
      setError('');
    } catch (err) {
      console.error('Error al buscar cartas:', err);
      setError('No se pudieron cargar las cartas');
      setCartas([]);
      setSeleccionadas([]);
    }
  };

  const alternarCarta = (carta) => {
    if (seleccionadas.includes(carta.id)) {
      setSeleccionadas(seleccionadas.filter(id => id !== carta.id));
    } else {
      if (seleccionadas.length >= 5) return;
      setSeleccionadas([...seleccionadas, carta.id]);
    }
  };

  const confirmarMazo = async () => {
    if (nombreMazo.trim() === '') {
      setError('El nombre del mazo es obligatorio');
      return;
    }

    try {
      const response = await axios.post('http://localhost:8000/mazos', {
        nombre: nombreMazo,
        cartas: seleccionadas
      }, {
        headers: getAuthHeaders()
      });

      alert('Mazo creado con éxito');
      navigate('/mis-mazos');
    } catch (err) {
      console.error('Error creando el mazo:', err);
      setError('No se pudo crear el mazo');
    }
  };

  if (loading) return <div className="crear-mazo-page">Verificando mazos...</div>;

  return (
    <div className="crear-mazo-container">
      <div className="crear-mazo-card">
        <div className="crear-mazo-header">
          <h2>Crear un nuevo mazo</h2>
          <p>Agregá hasta 5 cartas</p>
        </div>

        <input
          type="text"
          placeholder="Nombre del mazo"
          value={nombreMazo}
          onChange={(e) => setNombreMazo(e.target.value)}
          className="form-filter"
          style={{ marginBottom: '8px' }}
        />

        <input
          type="text"
          placeholder="Buscar por nombre de carta"
          value={filtroNombre}
          onChange={(e) => setFiltroNombre(e.target.value)}
          className="form-filter"
          style={{ marginBottom: '8px' }}
        />
        <input
          type="text"
          placeholder="Buscar por atributo (nombre o ID)"
          value={filtroAtributo}
          onChange={(e) => setFiltroAtributo(e.target.value)}
          className="form-filter"
          style={{ marginBottom: '8px' }}
        />

        <button onClick={buscarCartas} className="login-button">Buscar</button>

        {error && <div className="error-message">{error}</div>}

        <div className="cartas-listado">
          {cartas.length === 0 && <p>No se encontraron cartas.</p>}
          {cartas.map((carta, index) => {
            const estaSeleccionada = seleccionadas.includes(carta.id);
            return (
              <div
                key={carta.id ?? `${carta.nombre}-${index}`}
                className={`carta-card ${estaSeleccionada ? 'seleccionada' : ''}`}
                onClick={() => alternarCarta(carta)}
              >
                <h4 style={{ color: 'black' }}>{carta.nombre}</h4>
                <p style={{ color: 'black' }}>{carta.atributo}</p>
              </div>
            );
          })}
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
