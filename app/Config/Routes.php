<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('checkout', 'Payment::checkout');
$routes->get('payment/pay', 'Payment::pay');
$routes->get('payment/check-status/(:any)', 'Payment::checkStatus/$1');
$routes->get('payment/mutasi', 'Payment::mutasiList');
$routes->post('payment/update-amount', 'Payment::updateAmount');
$routes->post('payment/cpm-pay', 'Payment::processCPM');
$routes->post('payment/save-merchant', 'Payment::saveMerchantAccount');
$routes->post('payment/simulate-success/(:any)', 'Payment::simulateSuccess/$1');
$routes->post('api/notif', 'Payment::receiveNotif');
