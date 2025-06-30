import { useParams } from 'react-router-dom';
import { useState, useEffect } from 'react';
import axios from 'axios';
import tableroFondo from '../../assets/TABLERO.jpg';
import cartaServidorDorso from '../../assets/CARTASERVIDOR.jpg';
import './GamePage.css';

const GamePage = () => {
  const { id: mazoId } = useParams(); 
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [partidaId, setPartidaId] = useState(null);
  const [ganadorFinal, setGanadorFinal] = useState(null);
  const [gameState, setGameState] = useState({
    userCards: [],
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

  useEffect(() => {
    verificarPartidaExistente();
  }, [mazoId]);

  const verificarPartidaExistente = async () => {
    try {
      setLoading(true);
      setError(null);

      const userId = localStorage.getItem('userId');
      if (!userId) {
        setError('No estás logueado');
        return;
      }

      const mazosResp = await axios.get(`http://localhost:8000/usuarios/${userId}/mazos`, {
        headers: getAuthHeaders()
      });

      const mazos = mazosResp.data;
      const mazoSeleccionado = mazos.find(mazo => mazo.id === parseInt(mazoId));

      if (!mazoSeleccionado || !mazoSeleccionado.cartas || mazoSeleccionado.cartas.length === 0) {
        setError('Este mazo no tiene cartas. Ve a "Crear Mazo" para agregar cartas.');
        return;
      }

      setMazoInfo(mazoSeleccionado);

      const partidaResp = await axios.get('http://localhost:8000/partida/en-curso', {
        headers: getAuthHeaders()
      });

      const partidaEnCurso = partidaResp.data.partida;
      const esPropia = partidaResp.data.es_propia;

      if (partidaEnCurso) {
        if (esPropia) {
          setPartidaId(partidaEnCurso.id);
          setGameState(prev => ({
            ...prev,
            userCards: [...mazoSeleccionado.cartas],
            currentRound: 1,
            userWins: 0,
            serverWins: 0,
            gameFinished: false,
            roundWinner: null
          }));
        } else {
          setError('Ya hay una partida en curso por otro usuario. Esperá a que termine.');
        }
      } else {
        const nuevaResp = await axios.post('http://localhost:8000/partidas', {
          mazo_id: mazoSeleccionado.id
        }, {
          headers: getAuthHeaders()
        });

        setPartidaId(nuevaResp.data.partida_id);
        setGameState(prev => ({
          ...prev,
          userCards: [...mazoSeleccionado.cartas],
          currentRound: 1,
          userWins: 0,
          serverWins: 0,
          gameFinished: false,
          roundWinner: null
        }));
      }
    } catch (error) {
      console.error('Error al verificar o crear partida:', error);
      setError(error.response?.data?.error || 'Error inesperado.');
    } finally {
      setLoading(false);
    }
  };

  const playCard = async (userCard) => {
    if (gameState.playedUserCard || gameState.gameFinished || !partidaId) return;

    try {
      const jugadaResp = await axios.post('http://localhost:8000/jugadas', {
        carta_id: userCard.id,
        partida_id: partidaId
      }, {
        headers: getAuthHeaders()
      });

      const serverCard = {
        atributo: jugadaResp.data.carta_servidor.atributo,
        ataque: jugadaResp.data.ataque_servidor,
      };

      const userWins = jugadaResp.data.ataque_jugador > serverCard.ataque;

      setGameState(prev => ({
        ...prev,
        playedUserCard: userCard,
        playedServerCard: serverCard,
        roundWinner: userWins ? 'user' : 'server',
        userWins: userWins ? prev.userWins + 1 : prev.userWins,
        serverWins: !userWins ? prev.serverWins + 1 : prev.serverWins,
        userCards: prev.userCards.filter(card => card.id !== userCard.id)
      }));

      setGanadorFinal(jugadaResp.data.ganador_final || null);

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
    } catch (error) {
      console.error('Error al jugar carta:', error);
      setError('No se pudo procesar la jugada.');
    }
  };

  const resetGame = () => {
    setPartidaId(null);
    setGanadorFinal(null);
    setGameState({
      userCards: [],
      playedUserCard: null,
      playedServerCard: null,
      currentRound: 1,
      userWins: 0,
      serverWins: 0,
      gameFinished: false,
      roundWinner: null
    });
    verificarPartidaExistente();
  };

  if (loading) return <div className="game-loading">Cargando partida...</div>;
  if (error) return <div className="game-error">{error}</div>;

  return (
    <div className="game-page" style={{ backgroundImage: `url(${tableroFondo})` }}>
      <h1>Jugando con: {mazoInfo?.nombre}</h1>
      <p>Ronda {gameState.currentRound} de {mazoInfo?.cartas.length}</p>

      <div className="server-cards">
        {gameState.userCards.map((_, idx) => (
          <img key={idx} src={cartaServidorDorso} alt="Carta del servidor" className="server-card-back" />
        ))}
      </div>

      {gameState.playedUserCard && gameState.playedServerCard && (
        <div className="battle-area">
          <h2 className="battle-title">Batalla</h2>
          <div className="battle-cards">
            <div className="played-card">
              <h4>Tu carta</h4>
              <p>{gameState.playedUserCard.nombre}</p>
              <p>Ataque: {gameState.playedUserCard.ataque}</p>
            </div>
            <div className="vs-section">
              <h3>VS</h3>
              <div className="round-result">
                <h4>{gameState.roundWinner === 'user' ? '¡Ganaste!' : 'Perdiste'}</h4>
                <p>{gameState.playedUserCard.ataque} vs {gameState.playedServerCard.ataque}</p>
              </div>
            </div>
            <div className="played-card">
              <h4>Carta del servidor</h4>
              <p>Atributo: {gameState.playedServerCard.atributo}</p>
              <p>Ataque: {gameState.playedServerCard.ataque}</p>
            </div>
          </div>
        </div>
      )}

      <div className="user-area">
        <h3>Tus Cartas</h3>
        <div className="user-cards">
          {gameState.userCards.map(carta => (
            <div key={carta.id} onClick={() => playCard(carta)} className="user-card">
              <div className="carta-imagen">
                <img src={obtenerImagenPokemon(carta.nombre)} onError={(e) => { e.target.src = '/flame.svg'; }} alt={carta.nombre} />
              </div>
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
          ))}
        </div>
      </div>

      {gameState.gameFinished && (
        <div className="game-result">
          <h2>Partida finalizada</h2>
          <p>Ganador final: {ganadorFinal ? (ganadorFinal === 'jugador' ? '¡Tú!' : 'Servidor') : 'No determinado'}</p>
          <button className="play-again-btn" onClick={resetGame}>Jugar otra vez</button>
        </div>
      )}
    </div>
  );
};

export default GamePage;
