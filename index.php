<?php
ini_set('display_errors', 1);
error_reporting(E_ALL); 

require_once __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

define("MARIADB_HOST", "192.168.0.201");
define("MARIADB_NAME", "simplebom");
define("MARIADB_USER", "simplebom");
define("MARIADB_PASS", "FlagMischievousPageantHeel");

$dbh = new \PDO("mysql:host=".MARIADB_HOST.";dbname=".MARIADB_NAME, MARIADB_USER, MARIADB_PASS);

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->delete('/tax', function (Request $request, Response $response, $args) use ($dbh)
{
    $data = $request->getParsedBody();

    $statement = $dbh->prepare('DELETE FROM tax_codes WHERE name = :name;');
    $statement->bindParam(":name", $data["name"]);
    $statement->execute();

    if ($statement->rowCount() == 0)
    {
        $response->getBody()->write(json_encode([ "error" => "Tax code `".$data["name"]."` does not exist" ]));
        return $response->withStatus(404);
    }

    return $response;
});

$app->get('/tax', function (Request $request, Response $response, $args) use ($dbh)
{
    $tax_rates = [];

    foreach($dbh->query('SELECT * from tax_codes;') as $row)
    {
        $tax_rates[] = ["name" => $row["name"], "rate" => $row["rate"]];
    }

    $response->getBody()->write(json_encode($tax_rates));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/tax', function (Request $request, Response $response, $args) use ($dbh)
{
    $data = $request->getParsedBody();

    $statement = $dbh->prepare('SELECT * FROM tax_codes WHERE name = :name;');
    $statement->bindParam(":name", $data["name"]);
    $statement->execute();

    # Tax code already exists
    if ($statement->rowCount())
    {
        $response->getBody()->write(json_encode([ "error" => "Tax code `".$data["name"]."` already exists" ]));
        return $response->withStatus(400);
    }

    if ($data["rate"] <= 0)
    {
        $response->getBody()->write(json_encode([ "error" => "Tax rate `".$data["rate"]."` cannot be less than or equal to 0" ]));
        return $response->withStatus(400);
    }

    if (!is_numeric($data["rate"]))
    {
        $response->getBody()->write(json_encode([ "error" => "Tax rate `".$data["rate"]."` must be numeric." ]));
        return $response->withStatus(400);
    }

    $statement = $dbh->prepare('INSERT INTO tax_codes VALUES (:name, :rate);');
    $statement->bindParam(":name", $data["name"]);
    $statement->bindParam(":rate", $data["rate"]);
    $statement->execute();
    
    return $response;
});

$app->put('/tax', function (Request $request, Response $response, $args) use ($dbh)
{
    $data = $request->getParsedBody();

    if ($data["rate"] <= 0)
    {
        $response->getBody()->write(json_encode([ "error" => "Tax rate `".$data["rate"]."` cannot be less than or equal to 0" ]));
        return $response->withStatus(400);
    }

    if (!is_numeric($data["rate"]))
    {
        $response->getBody()->write(json_encode([ "error" => "Tax rate `".$data["rate"]."` must be numeric." ]));
        return $response->withStatus(400);
    }

    $statement = $dbh->prepare('REPLACE INTO tax_codes VALUES (:name, :rate);');
    $statement->bindParam(":name", $data["name"]);
    $statement->bindParam(":rate", $data["rate"]);
    $statement->execute();
    
    return $response;
});

$app->get('/', function (Request $request, Response $response, $args)
{
    return 'Home page!';
});

$app->run();

$dbh = null;
