<?php
include(dirname(__FILE__).'/../be-dojet/dojet.php');

define('DGLOBAL', dirname(__FILE__).'/');
define('GLCONFIG', DGLOBAL.'config/');
define('GLMODEL', DGLOBAL.'model/');
define('GLLIB', DGLOBAL.'lib/');
define('GLUTIL', DGLOBAL.'util/');

DAutoloader::getInstance()->addAutoloadPathArray(
    array(
        GLLIB,
        GLMODEL,
        GLUTIL,
    )
);
