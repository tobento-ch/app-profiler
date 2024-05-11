# App Profiler

The profiler is a development tool that gives detailed information about the execution of a HTTP request or console commands if enabled in the config file.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Profiler Boot](#profiler-boot)
    - [Profiler Config](#profiler-config)
    - [Profiles](#profiles)
    - [Available Collectors](#available-collectors)
        - [Boots Collector](#boots-collector)
        - [Events Collector](#events-collector)
        - [Logs Collector](#logs-collector)
        - [Jobs Collector](#jobs-collector)
        - [Middleware Collector](#middleware-collector)
        - [Request And Response Collector](#request-and-response-collector)
        - [Routes Collector](#routes-collector)
        - [Session Collector](#session-collector)
        - [Storage Queries Collector](#storage-queries-collector)
        - [Translation Collector](#translation-collector)
        - [View Collector](#view-collector)
    - [Creating A Collector](#creating-a-collector)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app profiler project running this command.

```
composer require tobento/app-profiler
```

## Requirements

- PHP 8.0 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Profiler Boot

The profiler boot does the following:

* installs and loads profiler config file
* implements interfaces based on config
* adds collectors based on config
* boots late profiler boot
* on response emit collects data from collectors and may inject the profiler toolbar

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots:
$app->boot(\Tobento\App\Profiler\Boot\Profiler::class);
// ...

// Run the app:
$app->run();
```

## Profiler Config

The configuration for the profiler is located in the ```app/config/profiler.php``` file at the default App Skeleton config location where you can configure your profiler for your application.

## Profiles

The profiler will inject a toolbar from the current profile into HTML responses only. Any AJAX requests will update the profiles selection in the toolbar where you can switch between them.

For other kinds of contents such as JSON responses, use the profiles page. Just browse the the ```/profiler/profiles``` URL to see all profiles.

## Available Collectors

By default, all collectors are defined in the [Profiler Config](#profiler-config). Even if they are set, they will only collect data if the service they collect data from are installed. But you may uncomment the collector if not needed at all.

### Boots Collector

This collector will show the currently booted boots in the order they were called as well as all registered boots.

In the ```app/config/profiler.php``` file:

```php
'collectors' => [
    \Tobento\App\Profiler\Collector\Boots::class,
],
```

### Events Collector

If you have booted the [App Event - Event Boot](https://github.com/tobento-ch/app-event#event-boot), this collector will show the currently dispatched events and listeners.

In the ```app/config/profiler.php``` file:

```php
'collectors' => [
    \Tobento\App\Profiler\Collector\Events::class,
],
```

### Jobs Collector

If you have booted the [App Queue - Queue Boot](https://github.com/tobento-ch/app-queue#queue-boot), this collector will show the currently pushed jobs.

In the ```app/config/profiler.php``` file:

```php
'collectors' => [
    \Tobento\App\Profiler\Collector\Jobs::class,
],
```

### Logs Collector

If you have booted the [App Logging - Logging Boot](https://github.com/tobento-ch/app-logging#logging-boot), this collector will show the currently logged messages from each logger specified.

In the ```app/config/profiler.php``` file:

```php
'collectors' => [
    \Tobento\App\Profiler\Collector\Logs::class,
    
    // or
    \Tobento\App\Profiler\Collector\Logs::class => [
        // specify the logger names not to collect messages from:
        'exceptLoggers' => ['null'],
    ],
],
```

### Middleware Collector

If you have booted the [App Http - Middleware Boot](https://github.com/tobento-ch/app-http#middleware-boot), this collector will show the currently dispatched middlewares as well as the available aliases.

In the ```app/config/profiler.php``` file:

```php
'collectors' => [
    \Tobento\App\Profiler\Collector\Middleware::class,
],
```

### Request And Response Collector

If you have booted the [App Http - Http Boot](https://github.com/tobento-ch/app-http#http-boot), this collector will show the current request and response data.

In the ```app/config/profiler.php``` file:

```php
'collectors' => [
    \Tobento\App\Profiler\Collector\RequestResponse::class,
],
```

### Routes Collector

If you have booted the [App Http - Routing Boot](https://github.com/tobento-ch/app-http#routing-boot), this collector will show all registered routes.

In the ```app/config/profiler.php``` file:

```php
'collectors' => [
    \Tobento\App\Profiler\Collector\Routes::class,
],
```

### Session Collector

If you have booted the [App Http - Session Boot](https://github.com/tobento-ch/app-http#session-boot), this collector will show all session data.

In the ```app/config/profiler.php``` file:

```php
'collectors' => [
    \Tobento\App\Profiler\Collector\Session::class,
    
    // or:
    \Tobento\App\Profiler\Collector\Session::class => [
        // specify the data you wont't to hide:
        'hiddens' => [
            '_session_flash_once',
            '_session_flash.old',
        ],
    ],
],
```

### Storage Queries Collector

If you have booted the [App Database - Database Boot](https://github.com/tobento-ch/app-database#database-boot), this collector will show all queries executed from any [Storage Databases](https://github.com/tobento-ch/service-database-storage) configured.

In the ```app/config/profiler.php``` file:

```php
'collectors' => [
    \Tobento\App\Profiler\Collector\StorageQueries::class,
],
```

### Translation Collector

If you have booted the [App Translation - Translation Boot](https://github.com/tobento-ch/app-translation#translation-boot), this collector will show all missed translations.

In the ```app/config/profiler.php``` file:

```php
'collectors' => [
    \Tobento\App\Profiler\Collector\Translation::class,
],
```

### View Collector

If you have booted the [App View - View Boot](https://github.com/tobento-ch/app-view#view-boot), this collector will show the currently rendered views and assets.

In the ```app/config/profiler.php``` file:

```php
'collectors' => [
    \Tobento\App\Profiler\Collector\View::class,
    
    // or you may configure only the data to collect:
    \Tobento\App\Profiler\Collector\View::class => [
        'collectViews' => true,
        'collectAssets' => false,
    ],
],
```

## Creating A Collector

You may create your own data collector by implementing the ```Tobento\App\Profiler\Collector\CollectorInterface``` or the ```Tobento\App\Profiler\Collector\LateCollectorInterface```.

By implementing the ```CollectorInterface```, the collector class will be created after the profiler gets booted, whereas the class implementing the ```LateCollectorInterface``` will be created after the late profiler boot gets booted.

I recommend investing the available collectors for its implementation.

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)