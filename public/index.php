<?php
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request; // since I don't have a namespace declaration, Psr\Http... is same as \Psr\Http...
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__ . '/../vendor/autoload.php';

// Create and configure Slim app
$config = ['settings' => [
    'addContentLengthHeader' => true,
]];
$app = new App($config);

$container = $app->getContainer();

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

// Define app routes
$app->get('/hello/{name}', function (Request $request, Response $response, $args) {
    $this->logger->addInfo('Something interesting happened');
    return $response->write("Hello " . $args['name']);
});

// Run app
$app->run();