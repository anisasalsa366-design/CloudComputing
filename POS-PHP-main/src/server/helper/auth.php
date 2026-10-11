<?php

function auth()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    return isset($_SESSION["login"]) && isset($_SESSION["role"]);
}
