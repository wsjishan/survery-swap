<?php

declare(strict_types=1);

function set_validation_errors(array $errors): void
{
    $_SESSION['_errors'] = $errors;
}

function pull_validation_errors(): array
{
    $errors = $_SESSION['_errors'] ?? [];
    unset($_SESSION['_errors']);

    return $errors;
}

function validate_required(string $value, string $label): ?string
{
    return trim($value) === '' ? $label . ' is required.' : null;
}

function validate_email(string $value): ?string
{
    if (trim($value) === '') {
        return 'Email is required.';
    }

    return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Please enter a valid email address.';
}

function validate_password(string $value, int $min = 6): ?string
{
    if ($value === '') {
        return 'Password is required.';
    }

    if (strlen($value) < $min) {
        return sprintf('Password must be at least %d characters.', $min);
    }

    return null;
}

function validate_positive_int(string $value, string $label): ?string
{
    if ($value === '' || !ctype_digit($value) || (int) $value < 1) {
        return $label . ' must be a positive number.';
    }

    return null;
}
