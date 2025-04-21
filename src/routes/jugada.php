<?php

$app->post('/jugadas', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $cartaId = $data['carta_id'] ?? null;
    $partidaId = $data['partida_id'] ?? null;

    //  Aca validamos que vengan carta_id y partida_id
    if (!$cartaId || !$partidaId) {
        $response->getBody()->write(json_encode(['error' => 'Faltan carta_id o partida_id']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Se obtiene el token del header
    $token = $request->getHeaderLine('Authorization');
    $token = str_replace('Bearer ', '', $token);

    if (!$token) {
        $response->getBody()->write(json_encode(['error' => 'Token no enviado']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    // Validamos token y obtenemos el usuario
    $usuario = Usuario::obtenerUsuarioPorToken($token);
    if (!$usuario) {
        $response->getBody()->write(json_encode(['error' => 'Token inválido o expirado']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    // //aca llamamos al metodo cartaValidaParaPartida y validamos que la carta esté en su mazo
    $partida = new Partida();
    $resultado = $partida->procesarJugada($usuario['id'], $cartaId, $partidaId);

    if (!$resultado) {
        $response->getBody()->write(json_encode(['error' => 'Error al procesar la jugada']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode([ 'mensaje' => 'Se registro la jugada correctamente', 'carta_servidor' => $resultado['carta_servidor', 'ataque_jugador' => $resultado['ataque_jugador'],
    'ataque_servidor' => $resultado['ataque_servidor'],
    'ganador_final' => $resultado['ganador_final'] ?? null
]));
    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
});

?> 
