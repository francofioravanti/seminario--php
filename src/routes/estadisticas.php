<?php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;  
require_once __DIR__ . '/../public/Estadistica.php';
return function (App $app) {

    $app->get('/estadisticas',function(Request $request,Response $response){
        $estadisticas = (new Estadistica())->obtenerEstadisticas();
        $response->getBody()->write(json_encode($estadisticas));
        return $response->withHeader('Content-Type', 'application/json');
    });

}

?>