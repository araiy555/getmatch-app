<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['LOG_FILE'] === 'php://stderr') {
    putenv('LOG_FILE='.$_SERVER['LOG_FILE'] = $_ENV['LOG_FILE'] = PATH_SEPARATOR === '\\' ? 'NUL' : '/dev/null');
}
