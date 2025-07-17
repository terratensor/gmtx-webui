<?php

use App\Frontend\FrontendUrlTwigExtension;
use Twig\Loader\FilesystemLoader;

return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    'user.rememberMeDuration' => 3600 * 24 * 30,
    'cookieDomain' => getenv('COOKIE_DOMAIN'),
    'frontendHostInfo' => getenv('FRONTEND_URL'),
    'backendHostInfo' => getenv('BACKEND_URL'),
    'staticHostInfo' => getenv('STATIC_URL'),

    'auth' => [
        'token_ttl' => 'PT1H',
    ],

    'from' => ['email' => getenv('MAILER_FROM_EMAIL'), 'name' => getenv('MAILER_FROM_NAME')],

    'mailer' => [
        'host' => getenv('MAILER_HOST'),
        'username' => getenv('MAILER_USERNAME'),
        'password' => trim(file_get_contents(getenv('MAILER_PASSWORD_FILE'))),
        'port' => (int)getenv('MAILER_PORT'),
    ],

    'twig' => [
        'debug' => (bool)getenv('APP_DEBUG'),
        'template_dirs' => [
            FilesystemLoader::MAIN_NAMESPACE => __DIR__ . '/../../templates',
        ],
        'cache_dir' => __DIR__ . '/../../var/cache/twig',
        'extensions' => [
            FrontendUrlTwigExtension::class
        ],
    ],
];
