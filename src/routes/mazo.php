<?php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;  

require_once __DIR__ . '/../public/Usuario.php';
require_once __DIR__ . '/../public/Mazo.php';

return function (App $app) {


    //HAY Q CREAR UN MAZO
    $app->post('/mazos',function(Request $request,Response $response){
        //obtnego info mazo y cartas del JSON.
        $data=$request->getParsedBody();
        $nombreMazo=$data['nombre'];
        $nombreMazo = trim($nombreMazo);
        $cartas = $data['cartas'] ?? [];
        //obtengo token.
        $token = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $token);    
        $usuario = Usuario::obtenerUsuarioPorToken($token);

        if (!$usuario) {
            $response->getBody()->write(json_encode([
                'error' => 'Usuario no logueado o token inválido'
            ]));
            
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
            
        }

        $usuario_id = $usuario['id'];

        $mazo=new Mazo();
        $resultado=$mazo->crearMazo($usuario_id,$nombreMazo,$cartas);
    

        if (is_array($resultado)) {
            $response->getBody()->write(json_encode($resultado));
            return $response->withHeader('Content-Type', 'application/json');

        } else {
            $response->getBody()->write(json_encode(['error' => $resultado]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

    });



}


?>