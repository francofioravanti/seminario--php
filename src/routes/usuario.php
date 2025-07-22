<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;  
use Firebase\JWT\JWT;
use App\Application\Middleware\IsLoggedMiddleware;
require_once __DIR__ . '/../public/Usuario.php';
require_once __DIR__ . '/../Conexion.php';


return function (App $app) {

   
$app->post('/login', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $usuario = $data['usuario'] ?? null;
    $clave = $data['password'] ?? null;

    if (!$usuario || !$clave) {
        $errores = [];
        if (!$usuario) $errores[] = 'Falta el campo usuario';
        if (!$clave) $errores[] = 'Falta el campo password';

        $response->getBody()->write(json_encode(['error' => $errores]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $servicio = new Usuario();
    $usuarioData = $servicio->validarUsuario($usuario, $clave);

    if (!$usuarioData) {
        $response->getBody()->write(json_encode(['error' => 'Credenciales inválidas']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    $usuarioId = $usuarioData['id'];

    $exp = time() + 3600; 

    $token = JWT::encode([
        "usuario" => $usuarioId,
        "exp" => $exp
    ], \App\Application\Middleware\IsLoggedMiddleware::$secret, 'HS256');
    //
    $response->getBody()->write(json_encode([
        'mensaje' => 'Login correcto',
        'token' => $token,
        'username' => $usuarioData['nombre'], 
        'id' => $usuarioId                    
    ]));

    return $response->withHeader("Content-Type", "application/json");
});


    $app->post('/registro', function (Request $request, Response $response) {
    $data = $request->getParsedBody();

    $errores = [];

    if (empty($data['nombre'])) {
        $errores[] = 'Falta el campo nombre.';
    }

    if (empty($data['usuario'])) {
        $errores[] = 'Falta el campo usuario.';
    }

    if (empty($data['password'])) {
        $errores[] = 'Falta el campo password.';
    }

    if (!empty($errores)) {
        $response->getBody()->write(json_encode(['errores' => $errores]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $nombre = $data['nombre'];
    $usuario = $data['usuario'];
    $clave = $data['password'];

    $servicio = new Usuario();
    $errores = $servicio->registrarUsuario($nombre, $usuario, $clave);

    if (!empty($errores)) {
        $response->getBody()->write(json_encode(['errores' => $errores]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode(['mensaje' => 'Usuario registrado correctamente']));
    return $response->withHeader('Content-Type', 'application/json');
});


    
   $app->put('/usuarios/{id}', function (Request $request, Response $response, array $args) {
    $data = $request->getParsedBody();
    $errores = [];

    if (empty($data['nombre'])) {
        $errores[] = 'Falta el campo nombre.';
    }

    if (empty($data['clave'])) {
        $errores[] = 'Falta el campo clave.';
    }

    if (!empty($errores)) {
        $response->getBody()->write(json_encode(['errores' => $errores]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $nombre = $data['nombre'];
    $clave = $data['clave'];

    
    $usuarioLogueadoId = (int) $request->getAttribute('usuario');
    $usuarioEnUrl = (int) $args['id'];

    if (!$usuarioLogueadoId || $usuarioEnUrl !== $usuarioLogueadoId) {
        $response->getBody()->write(json_encode([
            'error' => 'No tiene permisos para modificar este usuario',
            'logueado_id' => $usuarioLogueadoId,
            'url_id' => $usuarioEnUrl
        ]));
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }

    $servicio = new Usuario();

    $erroresValidacion = [];
    if (!$servicio->validarClave($clave, $erroresValidacion)) {
        $errores[] = 'La nueva clave no cumple las condiciones.';
    }

    if (strlen($nombre) < 1 || strlen($nombre) > 30) {
        $errores[] = 'El nuevo nombre debe tener entre 1 y 30 caracteres.';
    }

    if (!empty($errores)) {
        $response->getBody()->write(json_encode(['errores' => array_merge($errores, $erroresValidacion)]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    if ($servicio->actualizarCredencialesPorId($usuarioLogueadoId, $nombre, $clave)) {
        $response->getBody()->write(json_encode(['exito' => 'Se actualizó el usuario correctamente']));
        return $response->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write(json_encode(['error' => 'No se pudo actualizar el usuario']));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
})->add(\App\Application\Middleware\IsLoggedMiddleware::class);




   
    $app->get('/usuarios/{usuario}', function (Request $request, Response $response, array $args) {
        $usuarioLogueadoId = $request->getAttribute('usuario');

        if (!$usuarioLogueadoId) {
            $response->getBody()->write(json_encode(['error' => 'No está logueado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $usuarioEnUrl = $args['usuario'];

        if ($usuarioLogueadoId !== $usuarioEnUrl) {
            $response->getBody()->write(json_encode([
            'logueado_id' => $usuarioLogueadoId,
            'url_id' => $usuarioEnUrl,
            'error' => 'No tiene permisos para modificar este usuario'
        ]));
             return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $servicio = new Usuario();
        $info = $servicio->info($usuarioLogueadoId);

        if (!$info) {
            $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'usuario' => $info['usuario'],
            'nombre' => $info['nombre']
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    })->add(\App\Application\Middleware\IsLoggedMiddleware::class);



};

?>