<?php

require_once('./vendor/autoload.php');

use GuzzleHttp\Client;

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

$endpoint = getenv('PRESTASHOP_ENDPOINT');

$key = getenv('PRESTASHOP_APIKEY');

$client = new Client([
	'base_uri' => $endpoint,
	'debug' => true,
	'auth' => [ $key, '' ]
]);

$response = $client->request('GET', '/api');

function getTarget($obj, $path) {
	return array_reduce(explode('.', $path), function($carry, $item) {
		return is_int($item) ? $carry[$item] : $carry->{$item};
	}, $obj);
}

function getTargetAttr($obj, $path, $attr, $namespace = 'http://www.w3.org/1999/xlink') {
	return getAttr(getTarget($obj, $path), $attr, $namespace);
}

function getAttr($obj, $attr, $namespace = 'http://www.w3.org/1999/xlink') {
	return current($obj->attributes($namespace)->{$attr});
}

if ($response->getStatusCode() === 200) {
	$prestashop = simplexml_load_string($response->getBody());
	$products_url = getTargetAttr($prestashop, 'api.products', 'href');

	$response = $client->request('GET', $products_url);

	$prestashop = simplexml_load_string($response->getBody());

	$products = getTarget($prestashop, 'products');
	foreach($products->children() as $product) {
		echo getAttr($product,'href');
		echo "\n";
	}
}
