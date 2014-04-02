<?php
// This is global bootstrap for autoloading
Codeception\Util\Autoload::register('sweelix\curl','Response', dirname(__DIR__).'/src/');
Codeception\Util\Autoload::register('sweelix\curl','Request', dirname(__DIR__).'/src/');