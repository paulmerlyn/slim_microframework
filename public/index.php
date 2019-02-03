<?php
/* Good documentation at: http://www.slimframework.com/docs/v3/tutorial/first-app.html */
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request; // since I don't have a namespace declaration, Psr\Http... is same as \Psr\Http...
use \Psr\Http\Message\ResponseInterface as Response;
use slimdemo\src\classes\Constants;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../../../config.php'; // database credentials

// Create and configure Slim app
$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = true;
$config['db']['host']   = DB_HOST;
$config['db']['user']   = DB_USER;
$config['db']['pass']   = DB_PASS;
$config['db']['dbname'] = DB_DATABASE;

$app = new App(['settings' => $config]);

$container = $app->getContainer();

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$container['view'] = new \Slim\Views\PhpRenderer('../templates/');

// Define app routes
$app->get('/hello/{name}', function (Request $request, Response $response, $args) {
    $this->logger->addInfo('Something interesting happened');
    return $response->write(Constants::FORMALGREETING." my good friend, ".$args['name']);
});

$app->get('/users', function (Request $request, Response $response, $args) {
    $this->logger->addInfo('About to do a query of usernames');
    $stmt = $this->db->query('SELECT Username FROM users_table');
    while ($row = $stmt->fetch())
    {
        $users[] = $row['Username'];
    }
    return $response->write(json_encode($users));
});

$app->post('/hello/postform', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $filtered_data = [];
    $filtered_data['title'] = filter_var($data['title'], FILTER_SANITIZE_STRING);
    $filtered_data['description'] = filter_var($data['description'], FILTER_SANITIZE_STRING);
    return $response->write(join(" - ", $filtered_data));
});

$app->get('/accountsview', function (Request $request, Response $response) {
    $this->logger->addInfo('Rendering a view from a template');
    try {
        if (!$stmt = $this->db->query('SELECT AccountName FROM accounts_table')) {
            throw new \Exception('error in database');
        }
        while ($row = $stmt->fetch()) {
                $accountNames[] = $row['AccountName'];
            }
        $response = $this->view->render($response, 'accountsview.phtml', ['accounts' => $accountNames]);
        return $response;
    } 
    catch(\Exception $e) {
        $this->logger->addInfo($e->getMessage);
        return true;
    }
});

// Run app
$app->run();