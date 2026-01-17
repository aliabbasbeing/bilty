<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'dashboard/dashboard/index';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Dashboard routes
$route['dashboard'] = 'dashboard/dashboard/index';

// Auth routes
$route['login'] = 'auth/auth/login';
$route['logout'] = 'auth/auth/logout';
$route['auth/login'] = 'auth/auth/login';
$route['auth/logout'] = 'auth/auth/logout';

// Bilty routes
$route['bilty'] = 'bilty/bilty/index';
$route['bilty/add'] = 'bilty/bilty/add';
$route['bilty/save'] = 'bilty/bilty/save';
$route['bilty/view/(:num)'] = 'bilty/bilty/view/$1';
$route['bilty/edit/(:num)'] = 'bilty/bilty/edit/$1';
$route['bilty/delete/(:num)'] = 'bilty/bilty/delete/$1';
$route['bilty/print/(:num)'] = 'bilty/bilty/print_bilty/$1';
$route['bilty/manage'] = 'bilty/bilty/manage';

// Finance routes
$route['finance/reports'] = 'finance/payments/reports';
$route['finance/payments'] = 'finance/payments/index';
$route['finance/update-payment'] = 'finance/payments/update_payment';

// Vehicles routes
$route['vehicles/maintenance'] = 'vehicles/maintenance/index';
$route['vehicles/maintenance/save'] = 'vehicles/maintenance/save';

