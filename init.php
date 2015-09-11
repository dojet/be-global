<?php
namespace BEGLOBAL;

use \Dojet\DAutoloader;
use \Dojet\Config;

define('DGLOBAL', dirname(__FILE__).'/');
define('GLCONFIG', DGLOBAL.'config/');
define('GLMODEL', DGLOBAL.'model/');
define('GLLIB', DGLOBAL.'lib/');
define('GLUTIL', DGLOBAL.'util/');

DAutoloader::getInstance()->addNamespacePathArray(__NAMESPACE__,
    array(
        GLLIB,
        GLMODEL,
        GLUTIL,
    )
);

Config::loadConfig(GLCONFIG.'global');
