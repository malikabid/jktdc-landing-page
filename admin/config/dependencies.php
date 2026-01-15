<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Slim\Views\Twig;
use Psr\Container\ContainerInterface;

return function (ContainerInterface $container) {
    $settings = $container->get('settings');
    
    // Database (Eloquent ORM)
    $container->set('db', function () use ($settings) {
        $capsule = new Capsule;
        $capsule->addConnection($settings['db']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        return $capsule;
    });
    
    // Logger
    $container->set('logger', function () use ($settings) {
        $logger = new Logger($settings['logger']['name']);
        $logger->pushHandler(new StreamHandler(
            $settings['logger']['path'],
            $settings['logger']['level']
        ));
        return $logger;
    });
    
    // Twig Views
    $container->set('view', function () use ($settings) {
        return Twig::create($settings['twig']['path'], [
            'cache' => $settings['twig']['cache']
        ]);
    });
    
    // JWT Service (will be implemented later)
    $container->set('jwt', function () use ($settings) {
        return new \App\Services\JwtService($settings['jwt']);
    });
    
    // 2FA Service (will be implemented later)
    $container->set('twoFactor', function () use ($settings) {
        return new \RobThree\Auth\TwoFactorAuth($settings['twoFactor']['issuer']);
    });
};
