<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteContext;

$app->delete('/api/products/{id}', function (Request $request, Response $response, $args) use ($dbh)
{
    $component_id = RouteContext::fromRequest($request)->getRoute()->getArgument('id');

    $statement = $dbh->prepare('DELETE FROM products WHERE id = :id;');
    $statement->bindParam(":id", $component_id);
    $statement->execute();

    if ($statement->rowCount() == 0)
    {
        $response->getBody()->write(json_encode([ "error" => "Product with id ".$component_id." does not exist" ]));
        return $response->withStatus(404);
    }

    return $response;
});

$app->get('/api/products', function (Request $request, Response $response, $args) use ($dbh)
{
    $products = [];

    foreach($dbh->query('SELECT * from products;', PDO::FETCH_ASSOC) as $row)
    {
        $products[] = $row;
    }

    $response->getBody()->write(json_encode($products));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/products', function (Request $request, Response $response, $args) use ($dbh)
{ 
    $data = $request->getParsedBody();

    if (!array_key_exists ("name", $data))
    {
        $response->getBody()->write(json_encode([ "error" => "Product name not set" ]));
        return $response->withStatus(400);
    }

    if (!array_key_exists ("tax_code", $data))
    {
        $response->getBody()->write(json_encode([ "error" => "Product tax code not set" ]));
        return $response->withStatus(400);
    }

    $statement = $dbh->prepare('SELECT * FROM products WHERE name = :name;');
    $statement->bindParam(":name", $data["name"]);
    $statement->execute();

    if ($statement->rowCount())
    {
        $response->getBody()->write(json_encode([ "error" => "Product with name `".$data["name"]."` already exists" ]));
        return $response->withStatus(400);
    }

    $statement = $dbh->prepare('SELECT * FROM tax_codes WHERE name = :name;');
    $statement->bindParam(":name", $data["tax_code"]);
    $statement->execute();

    if ($statement->rowCount() == 0)
    {
        $response->getBody()->write(json_encode([ "error" => "Tax code `".$data["tax_code"]."` does not exist" ]));
        return $response->withStatus(400);
    }

    $statement = $dbh->prepare('INSERT INTO products VALUES (NULL, :name, :description, :tax_code);');
    $statement->bindParam(":name", $data["name"]);
    $statement->bindParam(":description", $data["description"]);
    $statement->bindParam(":tax_code", $data["tax_code"]);

    $statement->execute();
    
    return $response;
});

$app->put('/api/products/{id}', function (Request $request, Response $response, $args) use ($dbh)
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
        $response->getBody()->write(json_encode([ "error" => "Product name not set" ]));
        return $response->withStatus(400);
    }

    if (!array_key_exists ("tax_code", $data))
    {
        $response->getBody()->write(json_encode([ "error" => "Product tax code not set" ]));
        return $response->withStatus(400);
    }

    $statement = $dbh->prepare('SELECT * FROM products WHERE name = :name;');
    $statement->bindParam(":name", $data["name"]);
    $statement->execute();

    if ($statement->rowCount())
    {
        $response->getBody()->write(json_encode([ "error" => "Product with name `".$data["name"]."` already exists" ]));
        return $response->withStatus(400);
    }

    $statement = $dbh->prepare('SELECT * FROM tax_codes WHERE name = :name;');
    $statement->bindParam(":name", $data["tax_code"]);
    $statement->execute();

    if ($statement->rowCount() == 0)
    {
        $response->getBody()->write(json_encode([ "error" => "Tax code `".$data["tax_code"]."` does not exist" ]));
        return $response->withStatus(400);
    }

    $statement = $dbh->prepare('REPLACE INTO products VALUES (:id, :name, :description, :tax_code);');
    $statement->bindParam(":id", $data["id"]);
    $statement->bindParam(":name", $data["name"]);
    $statement->bindParam(":description", $data["description"]);
    $statement->bindParam(":tax_code", $data["tax_code"]);

    $statement->execute();
    
    return $response;
});
