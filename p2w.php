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

$handleRemoteXml = function($url, $action) use ($client) {
	$response = $client->request('GET', '/api');
	if ($response->getStatusCode() === 200) {
		$prestashop = simplexml_load_string($response->getBody());
		if ($prestashop !== false) {
			$action($prestashop);
		}
	}
};

$handleRemoteXml('api', function($prestashop) use ($handleRemoteXml) {
	$products_url = getTargetAttr($prestashop, 'api.products', 'href');

	$handleRemoteXml($products_url, function($products_api) use ($handleRemoteXml) {

		$products = getTarget($products_api, 'products');
		foreach($products->children() as $product_node) {
			$product_url = getAttr($product_node,'href');
			echo "$product_url\n";
			$handleRemoteXml($product_url, function($product_api) use ($handleRemoteXml) {
			});
		}
	});
});
