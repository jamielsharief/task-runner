<?php
/**
 * Task Runner
 * Copyright 2021 Jamiel Sharief.
 *
 * Licensed under The Apache License 2.0
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @license     https://opensource.org/licenses/Apache-2.0 Apache License 2.0
 */
declare(strict_types = 1);

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/src');

/**
 * Work with copy to bin and in phar files. Only use working directory
 * if local copy not exists since this will cause problems with PHAR
 */
if (file_exists(ROOT . '/vendor/autoload.php')) {
    require ROOT . '/vendor/autoload.php';
} elseif (file_exists(getcwd() . '/vendor/autoload.php')) {
    require getcwd() . '/vendor/autoload.php';
}

use Origin\Core\Config;
use Origin\Console\ErrorHandler;

(new ErrorHandler())->register();

Config::write('App.namespace', 'TaskRunner');
Config::write('App.debug', false);
