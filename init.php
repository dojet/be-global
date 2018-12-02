<?php
define("GLUTIL", __DIR__.'/util/');

Autoloader::getInstance()->addAutoloadPath(
    array(
        __DIR__.'/lib/',
        __DIR__.'/model/',
        __DIR__.'/util/',
    )
);
