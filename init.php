<?php
define('BEGLOBAL', dirname(__FILE__).'/');
define('GLCONFIG', BEGLOBAL.'config/');
define('GLMODEL', BEGLOBAL.'model/');
define('GLLIB', BEGLOBAL.'lib/');
define('GLUTIL', BEGLOBAL.'util/');

DAutoloader::getInstance()->addAutoloadPathArray(
    array(
        GLLIB,
        GLMODEL,
        GLUTIL,
    )
);

Config::loadConfig(GLCONFIG.'global');
