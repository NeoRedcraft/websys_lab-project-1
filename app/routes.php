<?php

use App\Router;

$router = new Router();

// ========== Page Routes ==========
$router->get('/', 'PagesController@home');
$router->get('/dashboard', 'PagesController@dashboard');
$router->get('/directory', 'PagesController@directory');
$router->get('/account', 'PagesController@accountSettings');

// ========== Authentication Routes ==========
$router->get('/signin', 'AuthController@signIn');
$router->post('/signin', 'AuthController@signIn');
$router->get('/signup', 'AuthController@signUp');
$router->post('/signup', 'AuthController@signUp');
$router->get('/signout', 'AuthController@signOut');
$router->get('/profile', 'AuthController@profile');

// ========== Admin Routes (System Admin Only) ==========
$router->get('/admin/dashboard', 'AdminController@dashboard');
$router->get('/admin', 'AdminController@dashboard');

// Organization Management
$router->get('/admin/organizations', 'AdminController@listOrganizations');
$router->get('/admin/organizations/create', 'AdminController@createOrganization');
$router->post('/admin/organizations/create', 'AdminController@createOrganization');
$router->get('/admin/organizations/edit/{id}', 'AdminController@editOrganization');
$router->post('/admin/organizations/edit/{id}', 'AdminController@editOrganization');
$router->post('/admin/organizations/delete', 'AdminController@deleteOrganization');

// User Management
$router->get('/admin/users', 'AdminController@listUsers');
$router->post('/admin/users/assign-role', 'AdminController@assignRole');

// Pre-register Presidents
$router->post('/admin/users/preregister-president', 'AdminController@preregisterPresident');

// Audit Logs
$router->get('/admin/audit-logs', 'AdminController@auditLogs');

// ========== Organization Admin Routes (Org Admin Only) ==========
$router->get('/org-admin/dashboard', 'OrgAdminController@dashboard');
$router->get('/org-admin', 'OrgAdminController@dashboard');

// Org Profile Management
$router->get('/org-admin/profile', 'OrgAdminController@viewProfile');
$router->get('/org-admin/profile/edit', 'OrgAdminController@editProfile');
$router->post('/org-admin/profile/edit', 'OrgAdminController@editProfile');

// Booking Inbox
$router->get('/org-admin/bookings', 'OrgAdminController@inboxBookings');
$router->get('/org-admin/bookings/{id}', 'OrgAdminController@viewBooking');
$router->post('/org-admin/bookings/accept', 'OrgAdminController@acceptBooking');
$router->post('/org-admin/bookings/decline', 'OrgAdminController@declineBooking');

// Statistics
$router->get('/org-admin/statistics', 'OrgAdminController@statistics');

// ========== Booking Routes (Organizer & Org Admin) ==========
$router->get('/bookings', 'BookingController@listMyBookings');
$router->get('/bookings/my-bookings', 'BookingController@listMyBookings');
$router->get('/bookings/create', 'BookingController@createBooking');
$router->post('/bookings/create', 'BookingController@createBooking');
$router->get('/bookings/view/{id}', 'BookingController@viewBooking');
$router->get('/bookings/edit/{id}', 'BookingController@editBooking');
$router->post('/bookings/edit/{id}', 'BookingController@editBooking');
$router->post('/bookings/delete', 'BookingController@deleteBooking');
$router->get('/bookings/search-orgs', 'BookingController@searchOrganizations');

// ========== Fallback ==========
// 404 fallback
$router->get('/*', 'PagesController@notFound');

return $router;
