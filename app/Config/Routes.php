<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('checkout', 'Payment::checkout');
$routes->post('api/notif', 'Payment::receiveNotif');
$routes->get('payment/check-status/(:any)', 'Payment::checkStatus/$1');
// File: app/Config/Routes.php di domain PEMBAYARAN

// Route lama (buat cek manual)
$routes->get('checkout', 'Payment::checkout'); 

// Route baru (buat koneksi dari toko-kelontong)
$routes->get('payment/pay', 'Payment::pay'); 

// Route buat terima notifikasi DANA
$routes->post('api/notif', 'Payment::receiveNotif');