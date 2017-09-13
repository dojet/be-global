<?php
define("GLUTIL", __DIR__.'/util/');

DAutoloader::getInstance()->addAutoloadPathArray(
    array(
        __DIR__.'/lib/',
        __DIR__.'/model/',
        __DIR__.'/util/',
    )
);
