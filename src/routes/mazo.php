<?php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;  
use Firebase\JWT\JWT;
use App\Application\Middleware\IsLoggedMiddleware;

require_once __DIR__ . '/../public/Usuario.php';
require_once __DIR__ . '/../public/Mazo.php';
require_once __DIR__ . '/../public/Partida.php';

return function (App $app) {


   
    $app->post('/mazos', function(Request $request, Response $response) {
    $data = $request->getParsedBody();
    $errores = [];

    if (empty($data['nombre']) || trim($data['nombre']) === '') {
        $errores[] = 'El campo nombre del mazo es obligatorio.';
    }

    
    if (!isset($data['cartas']) || !is_array($data['cartas'])) {
        $errores[] = 'El campo cartas es obligatorio y debe ser un arreglo.';
    }

    if (!empty($errores)) {
        $response->getBody()->write(json_encode(['errores' => $errores]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $nombreMazo = trim($data['nombre']);
    $cartas = $data['cartas'];

    $usuario_id = $request->getAttribute('usuario'); 

    $mazo = new Mazo();
    $resultado = $mazo->crearMazo($usuario_id, $nombreMazo, $cartas);

    if (is_array($resultado)) {
        $response->getBody()->write(json_encode($resultado));
        return $response->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write(json_encode(['error' => $resultado]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
})->add(\App\Application\Middleware\IsLoggedMiddleware::class);

    $app->delete('/mazos/{mazo}', function(Request $request, Response $response, array $args) { 
    $mazoId = (int)$args['mazo'];
    
    try {
        $usuarioLogueadoId = $request->getAttribute('usuario'); 

        if (!$usuarioLogueadoId) {
            throw new Exception("No está logueado");
        }

        $partida = new Partida();

        if (!$partida->verificarPertenenciaMazo($usuarioLogueadoId, $mazoId)) {
            throw new Exception("El mazo no te pertenece");
        }

        $mazo = new Mazo();
        $resultado = $mazo->eliminarMazo($mazoId);

        $response->getBody()->write(json_encode($resultado));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }
})->add(\App\Application\Middleware\IsLoggedMiddleware::class); 


   $app->get('/usuarios/{usuario}/mazos', function(Request $request , Response $response , array $args){
        $usuarioEnUrl = (int)$args['usuario'];

    
        $usuarioLogueadoId = $request->getAttribute('usuario');

        if (!$usuarioLogueadoId) {
         $response->getBody()->write(json_encode([
               'error' => 'No está logueado'
            ]));
         return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

    
        if ($usuarioEnUrl !== (int)$usuarioLogueadoId && $usuarioLogueadoId !== 1) {
         $response->getBody()->write(json_encode([
                'error' => 'No tienes permisos para acceder a los mazos de este usuario.'
         ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $mazos = Mazo::obtenerMazoConCartarDeUsuario($usuarioEnUrl);

        $response->getBody()->write(json_encode($mazos));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(\App\Application\Middleware\IsLoggedMiddleware::class); 



    $app->put('/mazos/{mazo}', function (Request $request, Response $response, array $args) {
    $mazoId = (int)$args['mazo'];

    $usuarioId = $request->getAttribute('usuario'); 

    if (!$usuarioId) {
        $response->getBody()->write(json_encode(['error' => 'No está logueado']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    try {
      
        $partida = new Partida();
        if (!$partida->verificarPertenenciaMazo($usuarioId, $mazoId)) {
            throw new Exception("El mazo no te pertenece");
        }

    
        $data = $request->getParsedBody();
        $nuevoNombre = $data['nombre'] ?? null;
        if (!$nuevoNombre || trim($nuevoNombre) === '') {
            throw new Exception("El campo nombre es requerido");
        }

      
        $mazo = new Mazo();
        if (!$mazo->actualizarNombre($mazoId, $nuevoNombre)) {
            throw new Exception("No se pudo actualizar el nombre del mazo");
        }

        $response->getBody()->write(json_encode(['mensaje' => 'Mazo actualizado correctamente']));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }

})->add(\App\Application\Middleware\IsLoggedMiddleware::class);


    $app->get('/cartas',function(Request $request,Response $response){
        $queryParams = $request->getQueryParams();
        $atributo = $queryParams['atributo'] ?? null;
        $nombre = $queryParams['nombre'] ?? null;

        $cartas= Mazo::buscarCartas($nombre,$atributo);

        $response->getBody()->write(json_encode($cartas));
        return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
    });



 };

?>



