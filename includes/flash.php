<?php

declare(strict_types=1);

function set_flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pull_flashes(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return $messages;
}
