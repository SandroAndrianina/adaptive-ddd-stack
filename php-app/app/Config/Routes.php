<?php
namespace Config;

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// UI
$routes->get('/', 'Home::index');

// Java -> CI4 (Flow B)
$routes->post('api/logs', 'Api\Logs::create');

// Schema endpoints — MUST come before the generic api/{entity} routes
$routes->get('api/_entities',         'Api\Schema::entities');
$routes->get('api/_schema/(:segment)', 'Api\Schema::show/$1');

// Generic CRUD proxy: /api/{entity}[/{id}]
$routes->get(   'api/(:segment)',            'Api\Generic::list/$1');
$routes->get(   'api/(:segment)/(:segment)', 'Api\Generic::show/$1/$2');
$routes->post(  'api/(:segment)',            'Api\Generic::create/$1');
$routes->put(   'api/(:segment)/(:segment)', 'Api\Generic::update/$1/$2');
$routes->delete('api/(:segment)/(:segment)', 'Api\Generic::delete/$1/$2');