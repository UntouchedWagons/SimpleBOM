<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteContext;

$app->delete('/api/tax/{name}', function (Request $request, Response $response, $args) use ($dbh)
{
    $routeContext = RouteContext::fromRequest($request);
    $route = $routeContext->getRoute();
        
    $tax_name = $route->getArgument('name');

    $statement = $dbh->prepare('DELETE FROM tax_codes WHERE name = :name;');
    $statement->bindParam(":name", $tax_name);
    $statement->execute();

    if ($statement->rowCount() == 0)
    {
        $response->getBody()->write(json_encode([ "error" => "Tax code `".$tax_name."` does not exist" ]));
        return $response->withStatus(404);
    }

    return $response;
});

$app->get('/api/tax', function (Request $request, Response $response, $args) use ($dbh)
{
    $tax_rates = [];

    foreach($dbh->query('SELECT * from tax_codes;', PDO::FETCH_ASSOC) as $row)
    {
        $tax_rates[] = $row;
    }

    $response->getBody()->write(json_encode($tax_rates));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/tax', function (Request $request, Response $response, $args) use ($dbh)
{
    $data = $request->getParsedBody();

    if (!array_key_exists ("name", $data))
    {
        $response->getBody()->write(json_encode([ "error" => "Tax code name not set" ]));
        return $response->withStatus(400);
    }

    if (!array_key_exists ("rate", $data))
    {
        $response->getBody()->write(json_encode([ "error" => "Tax code rate not set" ]));
        return $response->withStatus(400);
    }

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

$app->put('/api/tax', function (Request $request, Response $response, $args) use ($dbh)
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
