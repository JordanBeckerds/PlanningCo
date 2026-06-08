<?php
/**
 * General utility functions.
 */

/** HTML-escape a string for safe output. */
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/** Format a date string. */
function format_date(string $date, string $fmt = 'd/m/Y'): string {
    return (new DateTime($date))->format($fmt);
}

/** Strip seconds from a TIME column value (HH:MM:SS → HH:MM). */
function format_time(string $time): string {
    return substr($time, 0, 5);
}

/** Redirect and exit. */
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

/** Store a flash message in the session. */
function flash(string $key, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION[$key] = $message;
}

/** Read and clear a flash message from the session. */
function get_flash(string $key): ?string {
    if (!isset($_SESSION[$key])) return null;
    $msg = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $msg;
}

/** Return a badge HTML string for a leave request status. */
function leave_status_badge(string $status): string {
    $map = [
        'pending'  => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'denied'   => 'bg-red-100 text-red-800',
    ];
    $cls = $map[$status] ?? 'bg-gray-100 text-gray-700';
    return '<span class="inline-block px-2 py-0.5 rounded text-xs font-semibold ' . $cls . '">' . h(ucfirst($status)) . '</span>';
}
