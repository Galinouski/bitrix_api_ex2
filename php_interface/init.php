<?php

function debug($data){
    echo "Debug results: ";
    echo '<pre>' . print_r($data, 1) . '</pre>';
}

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/functions/order.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/functions/order.php';
}

if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include.php")) {
    include_once $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include.php";
}

if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/sotbit/userAgentRouter.php")) {
    include_once $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/sotbit/userAgentRouter.php";
}


