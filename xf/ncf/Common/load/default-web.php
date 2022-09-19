<?php
/* Code: */

require $system."/Common/load/default.php";

$di->setShared('cookie', function () {
    $cookie = new \Phalcon\Http\Response\Cookies();
    // $cookie->useEncryption(true);
    return $cookie;
});

$di->setShared('session', function () {
    $session = new \Phalcon\Session\Adapter\Files();
    $session->start();

    return $session;
});

$di->set('flash', function () {
    $flash = new \Phalcon\Flash\Direct(array(
        'error'   => 'alert alert-error',
        'success' => 'alert alert-success',
        'notice'  => 'alert alert-info',
    ));

    return $flash;
});

// register rules for router
$di->set('router', function () use ($config) {
    $router = new \Phalcon\Mvc\Router(false);
    $router->removeExtraSlashes(true);
    $router->add('/:controller/([a-zA-Z0-9_\-]+)/:params', array(
        'controller' => 1,
        'action'     => 2,
        'params'     => 3,
    ))->convert('action', function ($action) {
        return strtolower(str_replace('-', '', $action));
        // transform action from foo-bar -> fooBar
        // return lcfirst(Phalcon\Text::camelize($action));
    });
    $router->add('/:controller', array(
        'controller' => 1,
    ));
    $router->handle();
    return $router;
});

/* default-web.php ends here */
