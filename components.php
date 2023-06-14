<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteContext;

$app->delete('/api/components/{id}', function (Request $request, Response $response, $args) use ($dbh)
{
    $component_id = RouteContext::fromRequest($request)->getRoute()->getArgument('id');

    $statement = $dbh->prepare('DELETE FROM components WHERE id = :id;');
    $statement->bindParam(":id", $component_id);
    $statement->execute();

    if ($statement->rowCount() == 0)
    {
        $response->getBody()->write(json_encode([ "error" => "Component with id ".$component_id." does not exist" ]));
        return $response->withStatus(404);
    }

    return $response;
});

$app->get('/api/components', function (Request $request, Response $response, $args) use ($dbh)
{
    $tax_rates = [];

    foreach($dbh->query('SELECT * from components;', PDO::FETCH_ASSOC) as $row)
    {
        $tax_rates[] = $row;
    }

    $response->getBody()->write(json_encode($tax_rates));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/components', function (Request $request, Response $response, $args) use ($dbh)
{ 
    $data = $request->getParsedBody();

    if (!array_key_exists ("name", $data))
    {
        $response->getBody()->write(json_encode([ "error" => "Component name not set" ]));
        return $response->withStatus(400);
    }

    if (!array_key_exists ("price", $data))
    {
        $response->getBody()->write(json_encode([ "error" => "Component price not set" ]));
        return $response->withStatus(400);
    }

    $statement = $dbh->prepare('SELECT * FROM components WHERE name = :name;');
    $statement->bindParam(":name", $data["name"]);
    $statement->execute();

    # Tax code already exists
    if ($statement->rowCount())
    {
        $response->getBody()->write(json_encode([ "error" => "Component with name `".$data["name"]."` already exists" ]));
        return $response->withStatus(400);
    }

    if ($data["price"] <= 0)
    {
        $response->getBody()->write(json_encode([ "error" => "Component price `".$data["price"]."` cannot be less than or equal to 0" ]));
        return $response->withStatus(400);
    }

    if (!is_numeric($data["price"]))
    {
        $response->getBody()->write(json_encode([ "error" => "Component price `".$data["price"]."` must be numeric." ]));
        return $response->withStatus(400);
    }

    $statement = $dbh->prepare('INSERT INTO components VALUES (NULL, :name, :description, :price);');
    $statement->bindParam(":name", $data["name"]);
    $statement->bindParam(":description", $data["description"]);
    $statement->bindParam(":price", $data["price"]);

    $statement->execute();
    
    return $response;
});

$app->put('/api/components/{id}', function (Request $request, Response $response, $args) use ($dbh)
{ 
    $data = $request->getParsedBody();
    $data["id"] = RouteContext::fromRequest($request)->getRoute()->getArgument('id');

    if (!is_numeric ($data["id"]))
    {
        $response->getBody()->write(json_encode([ "error" => "Component id must be numeric" ]));
        return $response->withStatus(400);
    }

    if (!array_key_exists ("name", $data))
    {
        $response->getBody()->write(json_encode([ "error" => "Component name not set" ]));
        return $response->withStatus(400);
    }

    if (!array_key_exists ("price", $data))
    {
        $response->getBody()->write(json_encode([ "error" => "Component price not set" ]));
        return $response->withStatus(400);
    }

    if ($data["price"] <= 0)
    {
        $response->getBody()->write(json_encode([ "error" => "Component price `".$data["price"]."` cannot be less than or equal to 0" ]));
        return $response->withStatus(400);
    }

    if (!is_numeric($data["price"]))
    {
        $response->getBody()->write(json_encode([ "error" => "Component price `".$data["price"]."` must be numeric." ]));
        return $response->withStatus(400);
    }

    $statement = $dbh->prepare('REPLACE INTO components VALUES (:id, :name, :description, :price);');
    $statement->bindParam(":id", $data["id"], PDO::PARAM_INT);
    $statement->bindParam(":name", $data["name"]);
    $statement->bindParam(":description", $data["description"]);
    $statement->bindParam(":price", $data["price"]);

    $statement->execute();
    
    return $response;
});
