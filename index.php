<?php

ob_start();

require __DIR__ . "/vendor/autoload.php";

/*
 * BOOTSTRAP
 */

use Source\Core\Session;
use CoffeeCode\Router\Router;

$session = new Session();
$route = new Router(url(), "@");

/*
 * WEB ROUTES
 */

$route->namespace("Source\App");
$route->get("/", "Web@home");
$route->get("/sobre", "Web@about");

// blog
$route->group("/blog"); // GRUPO
$route->get("/", "Web@blog");
$route->get("/p/{page}", "Web@blog");
$route->get("/{uri}", "Web@blogPost");
$route->post("/buscar", "Web@blogSearch");
$route->get("/buscar/{terms}/{page}", "Web@blogSearch");

$route->get("/em/{category}", "Web@blogCategory");
$route->get("/em/{category}/{page}", "Web@blogCategory");

//auth
$route->group(null);
$route->get("/entrar", "Web@login");
$route->post("/entrar", "Web@login");

$route->get("/cadastrar", "Web@register");
$route->post("/cadastrar", "Web@register");

$route->get("/recuperar", "Web@forget");
$route->post("/recuperar", "Web@forget");

$route->get("/recuperar/{code}", "Web@reset");
$route->post("/recuperar/restore", "Web@reset");

//opt-in
$route->get("/confirma", "Web@confirm");
$route->get("/obrigado/{email}", "Web@success");

// services
$route->get("/termos", "Web@terms");

/**
 * APP
 */
// Start GRUPO
$route->group("/app");
$route->get("/", "App@home");

$route->get("/receber", "App@income");
$route->get("/receber/{status}/{category}/{date}", "App@income");

$route->get("/pagar", "App@expense");
$route->get("/pagar/{status}/{category}/{date}", "App@expense");

$route->get("/fatura/{invoice}", "App@invoice");
$route->get("/perfil", "App@profile");
$route->get("/sair", "App@logout");

$route->post("/launch", "App@launch");
$route->post("/support", "App@support");
$route->post("/filter", "App@filter");
// End GRUPO

/*
 * ERROR ROUTES
 */
$route->namespace("Source\App")->group("/oops");
$route->get("/{err_code}", "Web@error");

/*
 * ROUTE
 */

$route->dispatch();

/*
 * ERROR REDIRECT
 */

if ($route->error()) {
    $route->redirect("/oops/{$route->error()}");
}


ob_end_flush();