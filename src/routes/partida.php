<?php
    use Slim\App;
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Message\ServerRequestInterface as Request;  
    use Firebase\JWT\JWT;
    use App\Application\Middleware\IsLoggedMiddleware;
    require_once __DIR__ . '/../public/Partida.php';
    
return function (App $app) {

    $app->post('/partidas', function(Request $request , Response $response){
    $servicio = new Partida();
    $data = $request->getParsedBody();
    $mazoid = $data['mazo_id'] ?? null;
    $usuario_id = $request->getAttribute('usuario');

    if (!$mazoid) {
        $response->getBody()->write(json_encode(['error' => 'Falta el ID del mazo']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $resultado = $servicio->puedeJugar($usuario_id, $mazoid); 

    if (isset($resultado['partida_id'])) {
        $response->getBody()->write(json_encode([
            'mensaje' => 'Partida creada correctamente',
            'partida_id' => $resultado['partida_id']
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode(['error' => $resultado['error'] ?? 'No se pudo crear la partida']));
    return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
})->add(IsLoggedMiddleware::class);


$app->post('/jugadas', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $cartaId = $data['carta_id'] ?? null;
    $partidaId = $data['partida_id'] ?? null;
    $usuarioId = $request->getAttribute('usuario'); 

    if (!$cartaId || !$partidaId) {
        $response->getBody()->write(json_encode(['error' => 'Faltan carta_id o partida_id']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $partida = new Partida();
    $resultado = $partida->procesarJugada($usuarioId, $cartaId, $partidaId);

    if (isset($resultado['error'])) {
        $response->getBody()->write(json_encode(['error' => $resultado['error']]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode([
        'carta_servidor' => $resultado['carta_servidor'],
        'ataque_jugador' => $resultado['ataque_jugador'],
        'ataque_servidor' => $resultado['ataque_servidor'],
        'ganador_final' => $resultado['resultado_final'] 
    ]));

    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
})->add(IsLoggedMiddleware::class);


$app->get('/usuarios/{usuario}/partidas/{partida}/cartas', function (Request $request, Response $response, array $args){
    $usuarioEnUrl = (int) $args['usuario'];
    $partidaId = (int) $args['partida'];
    $usuarioLogueadoId = $request->getAttribute('usuario'); 

    if (!$usuarioLogueadoId) {
        $response->getBody()->write(json_encode(['error' => 'No está logueado']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    if ($usuarioLogueadoId !== $usuarioEnUrl && $usuarioLogueadoId !== 1) {
        $response->getBody()->write(json_encode(['error' => 'No autorizado para ver estas cartas']));
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }

    $partida = new Partida();
    $cartas = $partida->obtenerCartasEnMano($usuarioEnUrl, $partidaId);
    $response->getBody()->write(json_encode(['cartas' => $cartas]));

    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
})->add(IsLoggedMiddleware::class);


}
?>