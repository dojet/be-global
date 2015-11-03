<?php
include(dirname(__FILE__).'/../be-dojet/dojet.php');

DAutoloader::getInstance()->addAutoloadPathArray(
    array(
        __DIR__.'/lib/',
        __DIR__.'/model/',
        __DIR__.'/util/',
    )
);
