<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$app->add(function (Request $request, $handler) {
    $origin = $request->getHeaderLine('Origin');

    try {
        $response = $handler->handle($request);
    } catch (\Throwable $e) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Error interno']));
        $response = $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }

    if ($origin === 'http://localhost:5173') {
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }

    return $response;
});

$app->options('/{routes:.+}', function (Request $request, Response $response): Response {
    $origin = $request->getHeaderLine('Origin');

    if ($origin === 'http://localhost:5173') {
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }

    return $response;
});

(require __DIR__ . '/../routes/usuario.php')($app);
(require __DIR__ . '/../routes/partida.php')($app);
(require __DIR__ . '/../routes/mazo.php')($app);
(require __DIR__ . '/../routes/estadisticas.php')($app);

$app->run();