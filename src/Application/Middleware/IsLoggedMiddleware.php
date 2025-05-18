<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseFactoryInterface;

class IsLoggedMiddleware implements Middleware
{
    private ResponseFactoryInterface $responseFactory;
    
    public static $secret = 'superSecret';

    public function __construct()
    {
        $this->responseFactory = new \Slim\Psr7\Factory\ResponseFactory();
    }
    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
{
    try {
        if ($request->hasHeader("Authorization")) {
            // Limpia correctamente el header si viene como "Bearer <token>"
            $rawHeader = $request->getHeaderLine("Authorization");
            $token = trim(str_replace('Bearer ', '', $rawHeader));

            if (!empty($token)) {
                $key = new Key(self::$secret, "HS256");

                // Decodificar el token
                $dataToken = JWT::decode($token, $key);

                // Validar expiración
                $now = (new \DateTime("now"))->format("Y-m-d H:i:s");
                if ($dataToken->expired_at < $now) {
                    $response = $this->responseFactory->createResponse();
                    $response->getBody()->write(json_encode(["error" => 'Token vencido'], JSON_UNESCAPED_UNICODE));
                    return $response->withHeader("Content-Type", "application/json")->withStatus(401);
                }

                // Si es válido, inyectamos el ID de usuario al request
                $request = $request->withAttribute('usuario', $dataToken->usuario);
                return $handler->handle($request);
            }
        }

        // No hay token
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(json_encode(["error" => 'Acción requiere login'], JSON_UNESCAPED_UNICODE));
        return $response->withHeader("Content-Type", "application/json")->withStatus(401);

    } catch (\Exception $e) {
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(json_encode([
            "error" => "Token inválido: " . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader("Content-Type", "application/json")->withStatus(500);
    }
}
}
