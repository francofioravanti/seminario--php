import React, { useState, useEffect } from 'react';
import axios from 'axios';
import './StatPage.css';

function StatPage() {
  const [estadisticas, setEstadisticas] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [ordenamiento, setOrdenamiento] = useState('mejor'); // 'mejor' o 'peor'
  const [paginaActual, setPaginaActual] = useState(1);
  const resultadosPorPagina = 5;

  useEffect(() => {
    cargarEstadisticas();
  }, []);

  const cargarEstadisticas = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await axios.get('http://localhost:8000/estadisticas');
      console.log('Respuesta completa:', response);
      console.log('Datos recibidos:', response.data);
      console.log('Tipo de datos:', typeof response.data);
      console.log('Es array:', Array.isArray(response.data));
      
      if (!response.data || !Array.isArray(response.data)) {
        throw new Error('Formato de datos inválido');
      }
      
      const datosFormateados = Object.entries(response.data).map(([nombre, stats]) => {
        const totalPartidas = stats.ganó + stats.perdió + stats.empató;
        const promedio = totalPartidas > 0 ? (stats.ganó / totalPartidas * 100).toFixed(2) : 0;
        
        return {
          nombre,
          totalPartidas,
          ganadas: stats.ganó,
          perdidas: stats.perdió,
          empatadas: stats.empató,
          promedio: parseFloat(promedio)
        };
      });
      
      setEstadisticas(datosFormateados);
    } catch (error) {
      console.error('Error completo:', error);
      console.error('Respuesta del error:', error.response);
      setError('Error al cargar estadísticas: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  const estadisticasOrdenadas = [...estadisticas].sort((a, b) => {
    if (ordenamiento === 'mejor') {
      return b.promedio - a.promedio;
    } else {
      return a.promedio - b.promedio;
    }
  });

  // Paginación
  const indiceInicio = (paginaActual - 1) * resultadosPorPagina;
  const indiceFin = indiceInicio + resultadosPorPagina;
  const estadisticasPaginadas = estadisticasOrdenadas.slice(indiceInicio, indiceFin);
  const totalPaginas = Math.ceil(estadisticasOrdenadas.length / resultadosPorPagina);

  const mejorJugador = estadisticasOrdenadas.length > 0 ? estadisticasOrdenadas[0] : null;

  if (loading) {
    return (
      <div className="stat-page">
        <div className="loading">Cargando estadísticas...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="stat-page">
        <div className="error-message">{error}</div>
      </div>
    );
  }

  return (
    <div className="stat-page">
      <div className="stat-container">
        <h1>Estadísticas de Jugadores</h1>
        
        <div className="controles">
          <div className="ordenamiento">
            <label>Ordenar por:</label>
            <select 
              value={ordenamiento} 
              onChange={(e) => {
                setOrdenamiento(e.target.value);
                setPaginaActual(1);
              }}
            >
              <option value="mejor">Mejor Performance</option>
              <option value="peor">Peor Performance</option>
            </select>
          </div>
        </div>

        <div className="estadisticas-lista">
          {estadisticasPaginadas.map((jugador, index) => {
            const esMejorJugador = mejorJugador && jugador.nombre === mejorJugador.nombre && ordenamiento === 'mejor';
            const posicionGlobal = indiceInicio + index + 1;
            
            return (
              <div 
                key={jugador.nombre} 
                className={`jugador-card ${esMejorJugador ? 'mejor-jugador' : ''}`}
              >
                {esMejorJugador && <div className="corona">👑</div>}
                <div className="jugador-info">
                  <h3 className="jugador-nombre">
                    #{posicionGlobal} {jugador.nombre}
                  </h3>
                  <div className="estadisticas-grid">
                    <div className="stat-item">
                      <span className="stat-label">Total Partidas:</span>
                      <span className="stat-value">{jugador.totalPartidas}</span>
                    </div>
                    <div className="stat-item">
                      <span className="stat-label">Ganadas:</span>
                      <span className="stat-value ganadas">{jugador.ganadas}</span>
                    </div>
                    <div className="stat-item">
                      <span className="stat-label">Perdidas:</span>
                      <span className="stat-value perdidas">{jugador.perdidas}</span>
                    </div>
                    <div className="stat-item">
                      <span className="stat-label">Empatadas:</span>
                      <span className="stat-value empatadas">{jugador.empatadas}</span>
                    </div>
                    <div className="stat-item promedio-destacado">
                      <span className="stat-label">Promedio de Victoria:</span>
                      <span className="stat-value">{jugador.promedio}%</span>
                    </div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>

        {/* Paginación */}
        {totalPaginas > 1 && (
          <div className="paginacion">
            <button 
              onClick={() => setPaginaActual(prev => Math.max(prev - 1, 1))}
              disabled={paginaActual === 1}
              className="btn-pagina"
            >
              Anterior
            </button>
            
            <span className="info-pagina">
              Página {paginaActual} de {totalPaginas}
            </span>
            
            <button 
              onClick={() => setPaginaActual(prev => Math.min(prev + 1, totalPaginas))}
              disabled={paginaActual === totalPaginas}
              className="btn-pagina"
            >
              Siguiente
            </button>
          </div>
        )}

        {estadisticas.length === 0 && (
          <div className="no-datos">
            <p>No hay estadísticas disponibles aún.</p>
          </div>
        )}
      </div>
    </div>
  );
}

export default StatPage;