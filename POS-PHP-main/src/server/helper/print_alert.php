<?php

function print_alert()
{
    $message = "";

    if (isset($_GET["error"])) {
        $message = (string) $_GET["error"];
    } elseif (isset($_GET["success"])) {
        $message = (string) $_GET["success"];
    } elseif (isset($_GET["code"])) {
        if ($_GET["code"] === "200") {
            $message = "Transaksi berhasil.";
        } else {
            $message = (string) ($_GET["msg"] ?? "Terjadi kesalahan.");
        }
    }

    if ($message === "") {
        return;
    }

    $is_success = isset($_GET["success"]) ||
        (isset($_GET["code"]) && $_GET["code"] === "200");

    $class = $is_success ? "alert-success" : "alert-danger";
    $title = $is_success ? "Berhasil!" : "Gagal!";

    echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
    echo '<strong>' . $title . '</strong> ';
    echo htmlspecialchars($message, ENT_QUOTES, "UTF-8");
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}