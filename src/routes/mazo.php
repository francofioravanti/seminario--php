<?php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;  

require_once __DIR__ . '/../public/Usuario.php';
require_once __DIR__ . '/../public/Mazo.php';
require_once __DIR__ . '/../public/Partida.php';

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

    $app->delete('/mazos/{mazo}', function(Request $request,Response $response, array $args){ 
        $mazoId = (int)$args['mazo'];
        $token = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $token);
    try {
        if (!$token) {
            throw new Exception("Token no enviado");
        }
        $usuarioLogueado=Usuario::obtenerUsuarioPorToken($token);
        if(!$usuarioLogueado){
            throw new Exception("Token inválido o expirado");
        }
        $partida = new Partida(); // consultar si se puede instanciar este tipo de clases en los endpoints.
        if (!$partida->verificarPertenenciaMazo($usuarioLogueado['id'], $mazoId)) {
            throw new Exception("El mazo no te pertenece");
        }
        $mazo=new Mazo();
        $resultado= $mazo->eliminarMazo( $mazoId);
        $response->getBody()->write(json_encode($resultado));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    } 
    catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));//getMessage() te devuelve el texto del error que vos lanzaste con throw new Exception().
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }
    }); 


    $app->get('/usuarios/{usuario}/mazos',function(Request $request , Response $response , $args){
        $usuarioId = (int)$args['usuario'];

        //VERIFICO QUE ESTE LOGEUADO
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

        //VERIFICO Q SEA LE MISMO Y Q NO SEA EL SERVIDOR
        if ($usuarioId !== (int)$usuario['id'] && $usuario['id'] != 1) {
            $response->getBody()->write(json_encode([
                'error' => 'No tienes permisos para acceder a los mazos de este usuario.'
            ]));
            return $response
                ->withStatus(403) 
                ->withHeader('Content-Type', 'application/json');
        }


        $mazos = Mazo::obtenerMazoConCartarDeUsuario($usuarioId);
    
        $response->getBody()->write(json_encode($mazos));
        return $response->withHeader('Content-Type', 'application/json');

    });



    $app->put('/mazos/{mazo}', function (Request $request,Response $response,$args){
        $mazoId = (int)$args['mazo'];
    
        $token = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $token);
        try {
        if (!$token) {
            throw new Exception("Token no enviado");
        }
        $usuario=Usuario::obtenerUsuarioPorToken($token);
        if(!$usuario){
            throw new Exception("Token inválido o expirado");
        }
        $usuarioId=$usuario['id'];
     
        $partida = new Partida();
        if (!$partida->verificarPertenenciaMazo( $usuarioId, $mazoId)) {
            throw new Exception("El mazo no te pertenece");
        }

        // Obtener nuevo nombre del cuerpo del request
        
        $data = $request->getParsedBody();
        $nuevoNombre = $data['nombre'] ?? null;
        if (!$nuevoNombre || trim($nuevoNombre) === '') {
            throw new Exception("El campo nombre es requerido");
        }
        $mazo=new Mazo();
        if(!$mazo->actualizarNombre($mazoId,$nuevoNombre)){
            throw new Exception("No se pudo actualizar el nombre del mazo");
        }
        $response->getBody()->write(json_encode(['mensaje'=>'Mazo actualizado correctamente']));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        }
        catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
    });

 };
//Si el mazo ha participado de una partida, no puede borrarse y debe devolver la excepción correspondiente. Validar que el usuario esté logueado. 
//si llevamos le pertenece el mazo habria que modificar los endpoint y metodos delete y put y eliminar mazo 
?>



