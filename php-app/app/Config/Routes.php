<?php
namespace Config;
use CodeIgniter\Router\RouteCollection;
/** @var RouteCollection $routes */
$routes->get('/',         'Home::index');
$routes->post('api/logs', 'Api\Logs::create');
