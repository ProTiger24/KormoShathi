<?php
if (!function_exists('img_url')) {
    function img_url($p, $base = '../') {
        $p = (string)$p;
        if ($p === '') return '';
        if (preg_match('#^https?://#i', $p)) return $p;      // Cloudinary
        return $base . ltrim($p, '/');                       // পুরনো লোকাল path
    }
}
if (!function_exists('img_ok')) {
    function img_ok($p) {
        $p = (string)$p;
        if ($p === '') return false;
        if (preg_match('#^https?://#i', $p)) return true;
        return file_exists(__DIR__ . '/../' . ltrim($p, '/'));
    }
}
