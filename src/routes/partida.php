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

}
?>