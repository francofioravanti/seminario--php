<?php
    use Slim\App;
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Message\ServerRequestInterface as Request;  
    
    require_once __DIR__ . '/../public/Partida.php';
    
return function (App $app) {

    $app->post('/partidas',function(Request $request , Response $response){
        $servicio= new Partida();

        //obtnego el id del mazo.
        $data=$request->getParsedBody();
        $mazoid=$data['mazo_id'];


        //obtengo el token desde el header.
        $token = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ','', $token);
        

        if (!$token || !$mazoid){
            $response->getBody()->write(json_encode(['error' => 'Faltan datos']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $resultado = $servicio->puedeJugar($token, $mazoid);
        

        if (is_array($resultado)) {
            $response->getBody()->write(json_encode([
                'mensaje' => 'Partida creada correctamente',
                'partida_id' => $resultado['partida_id']
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    
        $response->getBody()->write(json_encode(['error' => "No se pudo crear la partida por vencimiento de token o token invalido"]));
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    });


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
    
        if (isset($resultado['error'])) {
            $response->getBody()->write(json_encode(['error' => $resultado['error']]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        
        
        $response->getBody()->write(json_encode([ 
            'carta_servidor' => $resultado['carta_servidor'],
            'ataque_jugador' => $resultado['ataque_jugador'],
            'ataque_servidor' => $resultado['ataque_servidor'],
            'ganador_final' => isset($resultado['ganador_final']) ? $resultado['ganador_final'] : null
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    });
    
    //get
        $app->get('/usuarios/{usuario}/partidas/{partida}/cartas',function (Request $request,Response $response,array $args){
        $usuario = $args['usuario'];
        $partidaId = $args['partida'];
    
        $token = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $token);
        if (!$token) {
            $response->getBody()->write(json_encode(['error' => 'Token no enviado']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $usuarioLogueado=Usuario::obtenerUsuarioPorToken($token);
        if(!$usuarioLogueado){
            $response->getBody()->write(json_encode(['error' => 'Token inválido o expirado']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    //controlamos si es el mismo que el servidor
        }
        if($usuarioLogueado['id']!=(int)$usuario && $usuarioLogueado ['id'] != 1){
        $response->getBody()->write(json_encode(['error' => 'No autorizado para ver estas cartas']));
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        $partida=new Partida();
        $cartas=$partida->obtenerCartasEnMano((int)$usuario,(int)$partidaId);
        $response->getBody()->write(json_encode(['cartas' => $cartas]));//Responder con las cartas:
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');  
        
    });


}
?>