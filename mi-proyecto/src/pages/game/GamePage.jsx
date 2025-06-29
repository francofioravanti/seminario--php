import { useParams } from 'react-router-dom';
import { useState, useEffect } from 'react';
import axios from 'axios';
import './GamePage.css';

const GamePage = () => {
  const { id: mazoId } = useParams(); // id del mazo seleccionado
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [gameState, setGameState] = useState({
    userCards: [],
    serverCards: [],
    playedUserCard: null,
    playedServerCard: null,
    currentRound: 1,
    userWins: 0,
    serverWins: 0,
    gameFinished: false,
    roundWinner: null
  });
  const [mazoInfo, setMazoInfo] = useState(null);

  const getAuthHeaders = () => {
    const token = localStorage.getItem('token');
    return {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json'
    };
  };

  const obtenerImagenPokemon = (nombreCarta) => {
    const nombreFormateado = nombreCarta.toLowerCase().replace(/\s+/g, '-');
    return `https://img.pokemondb.net/artwork/large/${nombreFormateado}.jpg`;
  };

  // Generar cartas del servidor con atributos y ataques aleatorios
  const generarCartasServidor = (cantidad = 5) => {
    const atributos = ['Fuego', 'Agua', 'Eléctrico', 'Planta', 'Psíquico', 'Tierra', 'Volador', 'Veneno'];
    const cartas = [];
    
    for (let i = 1; i <= cantidad; i++) {
      cartas.push({
        id: `server-${i}`,
        atributo: atributos[Math.floor(Math.random() * atributos.length)],
        ataque: Math.floor(Math.random() * 50) + 70 // Ataque entre 70-120
      });
    }
    
    return cartas;
  };

  useEffect(() => {
    cargarMazoYCartas();
  }, [mazoId]);

  const cargarMazoYCartas = async () => {
    try {
      setLoading(true);
      setError(null);

      const userId = localStorage.getItem('userId');
      if (!userId) {
        setError('No estás logueado');
        return;
      }

      // Cargar los mazos del usuario
      const response = await axios.get(`http://localhost:8000/usuarios/${userId}/mazos`, {
        headers: getAuthHeaders()
      });

      const mazos = response.data;
      const mazoSeleccionado = mazos.find(mazo => mazo.id === parseInt(mazoId));

      if (!mazoSeleccionado) {
        setError('Mazo no encontrado');
        return;
      }

      if (!mazoSeleccionado.cartas || mazoSeleccionado.cartas.length === 0) {
        setError('Este mazo no tiene cartas. Ve a "Crear Mazo" para agregar cartas.');
        return;
      }

      setMazoInfo(mazoSeleccionado);
      
      // Configurar el estado del juego con las cartas reales
      setGameState({
        userCards: [...mazoSeleccionado.cartas], // Copiar las cartas del mazo
        serverCards: generarCartasServidor(mazoSeleccionado.cartas.length),
        playedUserCard: null,
        playedServerCard: null,
        currentRound: 1,
        userWins: 0,
        serverWins: 0,
        gameFinished: false,
        roundWinner: null
      });

    } catch (error) {
      console.error('Error al cargar mazo:', error);
      if (error.response?.status === 401) {
        setError('Sesión expirada. Por favor, inicia sesión nuevamente.');
      } else {
        setError(error.response?.data?.error || 'Error al cargar el mazo');
      }
    } finally {
      setLoading(false);
    }
  };

  const playCard = (userCard) => {
    if (gameState.playedUserCard || gameState.gameFinished) return;

    const serverCard = gameState.serverCards[0]; // Tomar la primera carta del servidor
    const userWins = userCard.ataque > serverCard.ataque;
    
    setGameState(prev => ({
      ...prev,
      playedUserCard: userCard,
      playedServerCard: serverCard,
      roundWinner: userWins ? 'user' : 'server',
      userWins: userWins ? prev.userWins + 1 : prev.userWins,
      serverWins: !userWins ? prev.serverWins + 1 : prev.serverWins,
      userCards: prev.userCards.filter(card => card.id !== userCard.id),
      serverCards: prev.serverCards.slice(1)
    }));

    // Después de 3 segundos, preparar para la siguiente ronda
    setTimeout(() => {
      setGameState(prev => {
        const newRound = prev.currentRound + 1;
        const gameFinished = newRound > mazoInfo.cartas.length;
        
        return {
          ...prev,
          currentRound: newRound,
          playedUserCard: null,
          playedServerCard: null,
          roundWinner: null,
          gameFinished
        };
      });
    }, 3000);
  };

  const resetGame = () => {
    if (mazoInfo) {
      setGameState({
        userCards: [...mazoInfo.cartas],
        serverCards: generarCartasServidor(mazoInfo.cartas.length),
        playedUserCard: null,
        playedServerCard: null,
        currentRound: 1,
        userWins: 0,
        serverWins: 0,
        gameFinished: false,
        roundWinner: null
      });
    }
  };

  // Componente de carta del usuario (mismo estilo que en MisMazosPage)
  const CartaUsuario = ({ carta, onPlay }) => (
    <div className="user-card" onDoubleClick={() => onPlay(carta)}>
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
        <h4 className="carta-nombre">{carta.nombre}</h4>
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
      </div>
    </div>
  );

  if (loading) {
    return (
      <div className="game-loading">
        <h2>Cargando partida...</h2>
        <p>Mazo ID: {mazoId}</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="game-error">
        <h2>Error al cargar la partida</h2>
        <p>{error}</p>
        <button onClick={() => window.history.back()} className="btn-back">
          Volver
        </button>
      </div>
    );
  }

  return (
    <div className="game-page">
      <h1>Jugando con: {mazoInfo?.nombre}</h1>
      <p>Ronda {gameState.currentRound} de {mazoInfo?.cartas.length}</p>
      
      <div className="game-board"
        style={{
          backgroundImage: `url('/fondo-tablero-poke.png')`,
          backgroundSize: 'contain',
          backgroundPosition: 'center',
          backgroundRepeat: 'no-repeat',
          minHeight: '70vh'
        }}
      >
        {/* Área del servidor */}
        <div className="server-area">
          <h3>Cartas del Servidor (Victorias: {gameState.serverWins})</h3>
          <div className="server-cards">
            {gameState.serverCards.map((card, index) => (
              <div key={card.id} className="server-card">
                <img src="/cartasServer.jpg" alt="Carta del servidor" className="card-back" />
                <div className="card-attribute">{card.atributo}</div>
              </div>
            ))}
          </div>
        </div>

        {/* Centro del tablero */}
        <div className="battle-area">
          {gameState.playedUserCard && gameState.playedServerCard && (
            <div className="battle-cards">
              <div className="played-card user-played">
                <h4>Tu carta</h4>
                <div className="card">
                  <img 
                    src={obtenerImagenPokemon(gameState.playedUserCard.nombre)} 
                    alt={gameState.playedUserCard.nombre}
                    onError={(e) => { e.target.src = '/flame.svg'; }}
                  />
                  <h5>{gameState.playedUserCard.nombre}</h5>
                  <p>Atributo: {gameState.playedUserCard.atributo}</p>
                  <p>Ataque: {gameState.playedUserCard.ataque}</p>
                </div>
              </div>
              
              <div className="vs-section">
                <h3>VS</h3>
                {gameState.roundWinner && (
                  <div className="round-result">
                    <h4>{gameState.roundWinner === 'user' ? '¡Ganaste!' : 'Perdiste'}</h4>
                    <p>{gameState.playedUserCard.ataque} vs {gameState.playedServerCard.ataque}</p>
                  </div>
                )}
              </div>
              
              <div className="played-card server-played">
                <h4>Carta del servidor</h4>
                <div className="card">
                  <div className="server-card-revealed">
                    <p>Atributo: {gameState.playedServerCard.atributo}</p>
                    <p>Ataque: {gameState.playedServerCard.ataque}</p>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Área del usuario */}
        <div className="user-area">
          <h3>Tus Cartas (Victorias: {gameState.userWins})</h3>
          <div className="user-cards">
            {gameState.userCards.map(carta => (
              <CartaUsuario 
                key={carta.id} 
                carta={carta} 
                onPlay={playCard}
              />
            ))}
          </div>
          {gameState.userCards.length === 0 && !gameState.gameFinished && (
            <p>No tienes más cartas</p>
          )}
        </div>

        {/* Resultado final */}
        {gameState.gameFinished && (
          <div className="game-result">
            <h2>
              {gameState.userWins > gameState.serverWins 
                ? '¡Ganaste la partida!' 
                : gameState.serverWins > gameState.userWins 
                ? 'Perdiste la partida' 
                : 'Empate'}
            </h2>
            <p>Resultado final: {gameState.userWins} - {gameState.serverWins}</p>
            <button onClick={resetGame} className="play-again-btn">
              ¿Jugar otra vez?
            </button>
            <button onClick={() => window.history.back()} className="btn-back">
              Volver a Mis Mazos
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default GamePage;