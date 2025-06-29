import fondoTablero from '../../../assets/fondo-tablero-poke.png';
import React from 'react';
import PlayerCard from './PlayerCard';
import ServerCard from './ServerCard';
import GameCenter from './GameCenter';
import GameResult from './GameResult';
import './GameBoard.css';
import flame from '../../../../flame.svg';

const GameBoard = ({ gameState, onCardPlay, onRestart }) => {
  const availablePlayerCards = gameState.playerCards.slice(gameState.currentRound);
  const availableServerCards = gameState.serverCards.slice(gameState.currentRound);

  return (
    <div 
      className="game-board" 
      style={{ backgroundImage: `url(${fondoTablero})` }}
    >
      {/* Área del servidor */}
      <div className="server-area">
        <div className="server-info">
          <h3>Servidor - Puntuación: {gameState.serverScore}</h3>
          <p>Cartas restantes: {availableServerCards.length}</p>
        </div>
        <div className="server-cards">
          {availableServerCards.map((card, index) => (
            <ServerCard 
              key={card.id} 
              card={card} 
              isNext={index === 0}
            />
          ))}
        </div>
      </div>

      {/* Centro del tablero */}
      <GameCenter gameState={gameState} />

      {/* Área del jugador */}
      <div className="player-area">
        <div className="player-info">
          <h3>Jugador - Puntuación: {gameState.playerScore}</h3>
          <p>Ronda: {gameState.currentRound + 1}/5</p>
        </div>
        <div className="player-cards">
          {availablePlayerCards.map((card, index) => (
            <PlayerCard 
              key={card.id} 
              card={card} 
              onPlay={onCardPlay}
              disabled={gameState.gameFinished || index > 0}
            />
          ))}
        </div>
      </div>

      {/* Resultado del juego */}
      {gameState.gameFinished && (
        <GameResult 
          gameState={gameState}
          onRestart={onRestart}
        />
      )}
    </div>
  );
};

export default GameBoard;

function GameBoard() {
  return (
    <div
      className="game-board"
      style={{
        backgroundImage: `url('/flame.svg')`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat',
        minHeight: '100vh',
      }}
    >
      {/* ...contenido del tablero... */}
    </div>
  );
}
function GameBoard() {
  return (
    <div
      className="game-board"
      style={{
        backgroundImage: `url('/flame.svg')`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat',
        minHeight: '100vh',
      }}
    >
      {/* ...contenido del tablero... */}
    </div>
  );
}