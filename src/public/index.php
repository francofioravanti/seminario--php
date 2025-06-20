<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Middleware CORS
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    $origin = $request->getHeaderLine('Origin');

    // Permití solo tu frontend (React)
    if ($origin === 'http://localhost:5173') {
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }

    return $response;
});

// Ruta para responder preflight (CORS preflight requests)
$app->options('/{routes:.+}', function (Request $request, Response $response): Response {
    return $response;
});

// Rutas
(require __DIR__ . '/../routes/usuario.php')($app);
(require __DIR__ . '/../routes/partida.php')($app);
(require __DIR__ . '/../routes/mazo.php')($app);
(require __DIR__ . '/../routes/estadisticas.php')($app);

$app->run();