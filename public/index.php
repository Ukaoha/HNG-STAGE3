<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Dotenv\Dotenv;
use App\Database;
use App\CountryModel;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Ensure database exists (for SQLite)
$dbFile = __DIR__ . '/..//database/country_api.sqlite';
if (!file_exists(dirname($dbFile))) {
    mkdir(dirname($dbFile), 0777, true);
}

$app = AppFactory::create();

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$app->addRoutingMiddleware();
$app->addErrorMiddleware($_ENV['APP_DEBUG'] ?? false, true, true);

// Routes

$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write('Stage3 Country API');
    return $response;
});

$app->get('/countries/image', function (Request $request, Response $response) {
    $model = new CountryModel();
    $imagePath = $model->getSummaryImagePath();
    if (!file_exists($imagePath)) {
        $error = ['error' => 'Summary image not found'];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(file_get_contents($imagePath));
    return $response->withHeader('Content-Type', 'image/png');
});

$app->get('/countries/{name}', function (Request $request, Response $c, $args) {
    $model = new CountryModel();
    $country = $model->findByName($args['name']);
    if (!$country) {
        $error = ['error' => 'Country not found'];
        $c->getBody()->write(json_encode($error));
        return $c->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
    $c->getBody()->write(json_encode($country->toArray()));
    return $c->withHeader('Content-Type', 'application/json');
});

$app->get('/countries', function (Request $request, Response $response) {
    $model = new CountryModel();

    $query = $request->getQueryParams();
    $filters = [];
    if (isset($query['region'])) {
        $filters['region'] = $query['region'];
    }
    if (isset($query['currency'])) {
        $filters['currency'] = $query['currency'];
    }
    $sort = $query['sort'] ?? null;

    $countries = $model->fetchAll($filters, $sort);
    $data = array_map(fn($c) => $c->toArray(), $countries);

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->delete('/countries/{name}', function (Request $request, Response $c, $args) {
    $model = new CountryModel();
    $deleted = $model->deleteByName($args['name']);
    if (!$deleted) {
        $error = ['error' => 'Country not found'];
        $c->getBody()->write(json_encode($error));
        return $c->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
    $c->getBody()->write(json_encode(['message' => 'Country deleted']));
    return $c->withHeader('Content-Type', 'application/json');
});

$app->post('/countries/refresh', function (Request $request, Response $c) {
    $model = new CountryModel();
    try {
        $model->refreshData();
        $c->getBody()->write(json_encode(['message' => 'Refresh successful']));
        return $c->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $error = ['error' => $e->getMessage()];
        $c->getBody()->write(json_encode($error));
        return $c->withStatus(503)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/status', function (Request $request, Response $response) {
    $model = new CountryModel();
    $status = $model->getStatus();
    $response->getBody()->write(json_encode($status));
    return $response->withHeader('Content-Type', 'application/json');
});

try {
    $app->run();
} catch (\Exception $e) {
    echo $e->getMessage();
}
