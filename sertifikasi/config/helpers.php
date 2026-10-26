<?php
require_once __DIR__ . '/security.php';
// Helper kecil yang dipakai semua view.

if (!function_exists('e')) {
    function e($v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('tgl_indo')) {
    function tgl_indo(?string $tanggal): string
    {
        if (!$tanggal) {
            return '-';
        }

        if (!function_exists('admin_contact_email')) {
            function admin_contact_email(): string
            {
                $email = getenv('ADMIN_CONTACT_EMAIL');
                return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)
                    ? $email
                    : '';
            }
        }
        $timestamp = strtotime($tanggal);
        if ($timestamp === false) {
            return '-';
        }

        $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return date('d', $timestamp) . ' ' . $bulan[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    }
}
