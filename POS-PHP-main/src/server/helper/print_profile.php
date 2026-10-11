<?php

// Handler for printing profile

function print_profile()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $fullname = htmlspecialchars(
        $_SESSION["fullname"] ?? "User",
        ENT_QUOTES,
        "UTF-8"
    );

    $role = htmlspecialchars(
        $_SESSION["role"] ?? "User",
        ENT_QUOTES,
        "UTF-8"
    );

    echo "
        <h3>Hello, {$fullname} 👋</h3>
        <p class=\"text-muted\">You are logged in as {$role}</p>
    ";
}
