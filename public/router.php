<?php

if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|ico)$/', $_SERVER["REQUEST_URI"])) {
    return false;    // serve the requested resource as-is
} else {
    include_once 'index.php';
}
