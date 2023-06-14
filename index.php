<?php
ini_set('display_errors', 1);
error_reporting(E_ALL); 

require_once __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteContext;

define("MARIADB_HOST", "192.168.0.201");
define("MARIADB_NAME", "simplebom");
define("MARIADB_USER", "simplebom");
define("MARIADB_PASS", "FlagMischievousPageantHeel");

$dbh = new \PDO("mysql:host=".MARIADB_HOST.";dbname=".MARIADB_NAME, MARIADB_USER, MARIADB_PASS);

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

include_once("components.php");
include_once("tax.php");

$app->get('/', function (Request $request, Response $response, $args)
{
    return 'Home page!';
});

$app->run();

$dbh = null;
