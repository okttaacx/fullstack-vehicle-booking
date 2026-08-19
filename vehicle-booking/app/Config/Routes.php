<?php
use CodeIgniter\Router\RouteCollection;
/** @var RouteCollection $routes */
$routes->get("/", "Home::index");
$routes->group("api", ["filter" => "cors"], function ($routes) {
    $routes->get("vehicles", "Vehicles::index");
    $routes->post("vehicles", "Vehicles::create");
    $routes->put("vehicles/(:num)", "Vehicles::update/$1");
    $routes->delete("vehicles/(:num)", "Vehicles::delete/$1");
    $routes->post("login", "Auth::login");
    $routes->post("logout", "Auth::logout");
    $routes->get("users", "Users::index");
    $routes->get("drivers", "Drivers::index");
    $routes->post("drivers", "Drivers::create");
    $routes->put("drivers/(:num)", "Drivers::update/$1");
    $routes->delete("drivers/(:num)", "Drivers::delete/$1");
    $routes->get("bookings", "Bookings::index");
    $routes->get("bookings/(:num)", "Bookings::show/$1");
    $routes->post("bookings", "Bookings::create");
    $routes->put("bookings/(:num)", "Bookings::update/$1");
    $routes->delete("bookings/(:num)", "Bookings::delete/$1");
    $routes->post("bookings/(:num)/complete", "Bookings::complete/$1");
    $routes->get("approvals", "Approvals::index");
    $routes->post("approvals/(:num)/approve", "Approvals::approve/$1");
    $routes->post("approvals/(:num)/reject", "Approvals::reject/$1");
    $routes->get("reports/bookings/export", "Reports::exportBookings");
    $routes->options("(:any)", static function () {
        $response = response();
        $response->setStatusCode(204);
        return $response;
    });
});