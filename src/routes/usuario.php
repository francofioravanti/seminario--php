<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;  

require_once __DIR__ . '/../public/Usuario.php';
require_once __DIR__ . '/../Conexion.php';


return function (App $app) {

    $app->post('/login', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $usuario = $data['usuario'];
        $clave = $data['password'];

        $servicio = new Usuario();
        $esValido = $servicio->validarUsuario($usuario, $clave);

        if (!$esValido) {
            $response->getBody()->write(json_encode(['error' => 'Credenciales inválidas']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $token = bin2hex(random_bytes(32));
        $servicio->guardarToken($usuario, $token);

        $response->getBody()->write(json_encode([
            'mensaje' => 'Login correcto',
            'usuario' => $usuario,
            'token' => $token
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    });



#Define una ruta POST en la URL /registro.   Cuando se haga un POST ahí, se ejecuta esta función anónima que recibe:
    $app->post('/registro', function (Request $request, Response $response) { # $request: la solicitud del cliente. $response: el objeto que se va a devolver como respuesta.
        $data = $request->getParsedBody();#extrae el array del http
        $nombre=$data['nombre'];
        $usuario = $data['usuario'];
        $clave = $data['password'];
    
        $servicio = new Usuario(); # instancia de la clase UsuarioService
        $errores = $servicio->registrarUsuario($nombre,$usuario, $clave);# se llama al metodo registrarUsuario. Si todo está bien, lo guarda en la base de datos y devuelve un array vacío.Si hubo errores, devuelve un array con los mensajes. 
        if (!empty($errores)) { #si el array no esta vacio devuelve los errores
            $response->getBody()->write(json_encode([
                'errores' => $errores
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    
        $response->getBody()->write(json_encode([# si esta vacio 
            'mensaje' => 'Usuario registrado correctamente'
        ]));
    
        return $response->withHeader('Content-Type', 'application/json');#devuelve el objeto $response con el header Content-Type: application/json, para que el cliente sepa que la respuesta es JSON.
    });


    
    $app->put('/usuarios/{usuario}',function(Request $request , Response $response ){
        $data= $request->getParsedBody();

        //deberia recibir el token 
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));

        $servicio=new Usuario();
        
        $usuarioLogueado= $servicio::obtenerUsuarioPorToken($token);
        
        $usuario=$data['usuario'];
        $nombre=$data['nombre']; 
        $clave=$data['clave'];

        //verifico si esta logueado.
        if (!$usuarioLogueado){
            $response->getBody()->write(json_encode(['error' => 'No esta logueado']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        if (!$clave) {
            $response->getBody()->write(json_encode(['error' => 'La clave es obligatoria']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        


        $errores=[]; 

        //verfico la NUEVA clave.
        if (!$servicio->validarClave($clave,$errores)){
            $response->getBody()->write(json_encode(['error' => 'La nueva clave no cumple las condiciones']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        //verifico el NUEVO nombre.
        if (!$servicio->validarNombreUsuario($nombre,$errores)){
            $response->getBody()->write(json_encode(['error' => 'El nuevo nombre no cumple las condiciones']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        if($servicio->actualizarCredenciales($usuario,$nombre,$clave)){
            $response->getBody()->write(json_encode(['exito' => 'Se actualizo el dato/los datos']));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        }else{
            $response->getBody()->write(json_encode(['error' => 'No se pudo actualizar el usuario']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

    });




    //CAMBIAR EL ESTALOGEUADO DEL IF 
    $app->get('/usuarios/{usuario}',function (Request $request , Response $response){
        $data=$request->getParsedBody();

        $usuario=$data['usuario'];
        //deberia recibir el token 
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));

        $servicio=new Usuario();

        $usuarioLogueado= $servicio::obtenerUsuarioPorToken($token);
        //verifico que este logueado
        if (!$usuarioLogueado){
            $response->getBody()->write(json_encode(['error' => 'No esta logueado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $name=$servicio->info($usuario)['nombre'];
        $usuario=$servicio->info($usuario)['usuario'];

        $dat = [
             'usuario' => $usuario,
             'nombre' => $name
             
        ];

        $response->getBody()->write(json_encode($dat));
        return $response->withHeader('Content-Type', 'application/json');   


    });



};

?>