<?php
// includes/functions.php - FIXED VERSION
function formatCurrency($amount) {
    return '₵' . number_format($amount, 2);
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return $diff . ' seconds ago';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $time);
}

function generateReferenceNumber() {
    return 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Initials for a letter-avatar: 2 letters from "First Last", else the first letter.
function avatarInitials($text) {
    $text = trim($text ?? '');
    if ($text === '') return 'U';
    $parts = preg_split('/\s+/', $text);
    if (count($parts) >= 2 && $parts[1] !== '') {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    return strtoupper(substr($text, 0, 1));
}

// Renders a circular letter-avatar. $extraClass e.g. 'avatar-sm' or 'avatar-lg'.
function avatarInitialTag($text, $extraClass = '') {
    $cls = trim('avatar-initial ' . $extraClass);
    return '<div class="' . $cls . '">' . htmlspecialchars(avatarInitials($text)) . '</div>';
}