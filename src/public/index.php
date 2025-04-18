<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;


require __DIR__ . '/../../vendor/autoload.php';





$app = AppFactory::create();
$app->addBodyParsingMiddleware();


(require __DIR__ . '/../routes/usuario.php')($app);
(require __DIR__ . '/../routes/partida.php')($app);
(require __DIR__ . '/../routes/mazo.php')($app);







$app->run();
?>
