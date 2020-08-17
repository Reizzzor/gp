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
|	https://codeigniter.com/user_guide/general/routing.html
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
$route['login'] = 'login/index';
$route['logout'] = 'login/logout';
$route['orders/info/(\d+.\d)'] = 'orders/order_info/$1';
$route['orders/sendbill/(\d+.\d)'] = 'orders/send_bill/$1';
$route['orders/status/(:num)'] = 'orders/index/$1';
$route['orders'] = 'orders/index';
$route['preorders/new'] = 'orders/new_pre_order';
$route['preorders/(:num)'] = 'orders/edit_pre_order/$1';
$route['preorders/save/(:num)'] = 'orders/save_pre_order/$1';
$route['preorders/save'] = 'orders/save_pre_order';
$route['preorders/upload/(:num)'] = 'orders/upload_pre_orders/$1';
$route['preorders/upload'] = 'orders/upload_pre_orders';
$route['preorders/delete/(:num)'] = 'orders/delete_pre_orders/$1';
$route['preorders/show/(:num)'] = 'orders/show_pre_order/$1';
$route['preorders/show_po/(:num)'] = 'orders/show_po_entry/$1';
$route['preorders/show_drp/(:num)'] = 'orders/show_drp_entry/$1';
$route['poentry/new/(:num)'] = 'orders/new_po_entry/$1';
$route['poentry/new'] = 'orders/new_po_entry';
$route['poentry/(:num)'] = 'orders/edit_po_entry/$1';
$route['poentry/save/(:num)/(:num)'] = 'orders/save_po_entry/$1/$2';
$route['poentry/save/(:num)'] = 'orders/save_po_entry/$1';
$route['poentry/delete/(:num)'] = 'orders/del_po_entry/$1';
$route['poedrp/new/(:num)'] = 'orders/new_poe_drp/$1';
$route['poedrp/new'] = 'orders/new_poe_drp';
$route['poedrp/(:num)'] = 'orders/edit_poe_drp/$1';
$route['poedrp/save/(:num)/(:num)'] = 'orders/save_poe_drp/$1/$2';
$route['poedrp/save/(:num)'] = 'orders/save_poe_drp/$1';
$route['poedrp/delete/(:num)'] = 'orders/del_poe_drp/$1';
$route['preorders'] = 'orders/pre_orders';
$route['pohandler'] = 'orders/pre_orders_handler';
$route['poedrphandler'] = 'orders/poe_drp_handler';
$route['orders/drapery/archive'] = 'orders/drp_layout_archive';
$route['orders/drapery'] = 'orders/drapery_layout';
$route['print/poe/(:num)'] = 'orders/print_po_entry/$1';
$route['print/poedrp/(:num)'] = 'orders/print_poe_drp/$1';
$route['storage'] = 'orders/storage';
$route['reserve/(\d+.\d)'] = 'orders/reserve/$1';
$route['reserve/submit/(\d+.\d)'] = 'orders/submit_reserve/$1';
$route['podium/(\d+.\d)'] = 'orders/podium_reserve/$1';
$route['podium/submit/(\d+.\d)'] = 'orders/podium_submit/$1';
$route['manager/status/(:num)/page/(:num)'] = 'manager/orders/$1/$2';
$route['manager/status/(:num)/page'] = 'manager/orders/$1';
$route['manager/status/(:num)'] = 'manager/orders/$1';
$route['manager/page/(:num)'] = 'manager/orders/0/$1';
$route['manager/page'] = 'manager/orders';
$route['manager/info/(\d+.\d)'] = 'manager/order_info/$1';
$route['manager/sendbill/(\d+.\d)'] = 'manager/send_bill/$1';
$route['manager/drapery/archive'] = 'manager/drp_layout_archive';
$route['manager/drapery'] = 'manager/drapery_layout';
//$route['managers/podium'] = 'manager/podium';
$route['store/edit/(:num)'] = 'store/edit_order/$1';
$route['store/submit/(:num)'] = 'store/submit_order/$1';
$route['store/delete/(:num)'] = 'store/delete_order/$1';
$route['store/upload/(:num)'] = 'store/upload_order/$1';
$route['store/drapery/archive'] = 'store/drp_layout_archive';
$route['store/drapery'] = 'store/drapery_layout';
$route['store'] = 'store/index';
$route['admin/users/submit/(:num)'] = 'admin/submit_user/$1';
$route['admin/users/submit'] = 'admin/submit_user';
$route['admin/users/delete/(:num)'] = 'admin/delete_user/$1';
$route['admin/users/create'] = 'admin/create_user';
$route['admin/users/edit/(:num)'] = 'admin/edit_user/$1';
$route['admin/orders/(\d+.\d)'] = 'admin/order_info/$1';
$route['admin/orders/page/(:num)'] = 'admin/orders/$1';
$route['admin/orders/page'] = 'admin/orders';
$route['admin/drapery/archive'] = 'admin/drp_layout_archive';
$route['admin/drapery'] = 'admin/drapery_layout';
$route['slninfohandler'] = 'admin/sln_info_handler';
$route['admin'] = 'admin/index';
$route['default_controller'] = 'login/index';
$route['404_override'] = 'custom404/index';

