
<p align="center"><img width="150" height="150" src="https://data-star.dev/static/images/rocket-512x512.png"></p>

# Datastar SDK for Swoldier

This package offers an SDK for working with [Datastar](https://data-star.dev) in [Swoldier](https://github.com/wilaak/swoldier).

## Install 

    composer require wilaak/datastar-swoldier

## Usage

A minimal example of using Datastar with Swoldier:

```PHP
<?php 

use Swoole\{Runtime, Http\Server};
use Swoldier\{Router, HttpContext};

use DatastarSwoldier\{SSE, ElementPatchMode};

// Replace blocking PHP functions with coroutine-friendly versions
Runtime::enableCoroutine();

$server = new Server("0.0.0.0", 8082);
$router = new Router($server);

$router->map('GET', '/', function (HttpContext $ctx) {
    if (SSE::isDatastarRequest($ctx)) {
        $sse = new SSE($ctx);
        $sse->patchElements('<h1 id="message">Welcome to Datastar with Swoldier!</h1>');
        return;
    }
    $ctx->html(<<<'HTML'
     <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script type="module" src="/path/to/datastar.js"></script>
    </head>
    <body data-init="@get('/')">
        <div id="message"></div>
    </body>
    </html>
    HTML);
});

$server->start();
```

This example covers most of the usage possible with this SDK:

```php
use Swoole\{Runtime, Http\Server};
use Swoldier\{Router, HttpContext};

use DatastarSwoldier\{SSE, ElementPatchMode};

// Replace blocking PHP functions with coroutine-friendly versions
Runtime::enableCoroutine();

$server = new Server("0.0.0.0", 8082);
$router = new Router($server);

$router->map('GET', '/sse-endpoint', function (HttpContext $ctx) {

    // Creates a new SSE instance.
    $sse = new SSE($ctx);

    // Reads signals from the request.
    $signals = $sse->readSignals();

    // Patches elements into the DOM.
    $sse->patchElements('<div></div>', [
        'selector' => '#my-div',
        'mode' => ElementPatchMode::Append,
        'useViewTransition' => true,
    ]);

    // Removes elements from the DOM.
    $sse->removeElements('#my-div', [
        'useViewTransition' => true,
    ]);

    // Patches signals.
    $sse->patchSignals(['foo' => 123], [
        'onlyIfMissing' => true,
    ]);

    // Executes JavaScript in the browser.
    $sse->executeScript('console.log("Hello, world!")', [
        'autoRemove' => true,
        'attributes' => [
            'type' => 'application/javascript',
        ],
    ]);

    // Redirects the browser by setting the location to the provided URI.
    $sse->location('/guide');
});

$server->start();
```

## License

MIT
