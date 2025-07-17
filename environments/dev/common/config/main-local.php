<?php

return [
    'components' => [
       'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => getenv('DB_DSN_PREFIX').':host='.getenv('DB_HOST').';dbname=' . getenv('DB_NAME'),
            'username' => getenv('DB_USER'),
            'password' => trim(file_get_contents(getenv('DB_PASSWORD_FILE'))),
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
    ],
];
