<?php
// index.php - COMPLETE WITH GALLERY SECTION
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();

// Get stats from database
$memberCount = $db->query("SELECT COUNT(*) as count FROM Members WHERE status = 'approved'")->fetch()['count'] ?? 0;
$instructorCount = $db->query("SELECT COUNT(*) as count FROM Instructor WHERE status = 'active'")->fetch()['count'] ?? 0;
$sectionCount = $db->query("SELECT COUNT(*) as count FROM Training_Section WHERE status = 'active'")->fetch()['count'] ?? 0;
$memberships = $db->query("SELECT * FROM Membership_Type WHERE status = 'active'")->fetchAll();

// Check for dark mode preference
$darkMode = isset($_COOKIE['dark_mode']) ? $_COOKIE['dark_mode'] : 'dark';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USTED-K Gym Center - Premium Fitness</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* ============================================
           CSS VARIABLES - DARK/LIGHT MODE
           ============================================ */
        :root {
            /* Dark Mode (Default) */
            --bg-primary: #0D0D0D;
            --bg-secondary: #1A1A1A;
            --bg-card: #1A1A1A;
            --bg-card-hover: #242424;
            --text-primary: #FFFFFF;
            --text-secondary: #A68A7A;
            --text-muted: #5A4A3A;
            --border-color: rgba(255,107,0,0.08);
            --shadow-color: rgba(255,107,0,0.15);
            --nav-bg: rgba(13, 13, 13, 0.85);
            --nav-bg-scrolled: rgba(13, 13, 13, 0.95);
            --input-bg: #1E1E1E;
            --glass-bg: rgba(26, 26, 26, 0.85);
            --glass-border: rgba(255,107,0,0.1);
            --gradient-start: #FF6B00;
            --gradient-end: #FF8C00;
            --gradient-text: linear-gradient(135deg, #FF6B00, #FF8C00, #FFA500);
            --overlay-color: rgba(0, 0, 0, 0.5);
        }

        /* Light Mode */
        [data-theme="light"] {
            --bg-primary: #F5F5F5;
            --bg-secondary: #FFFFFF;
            --bg-card: #FFFFFF;
            --bg-card-hover: #F0F0F0;
            --text-primary: #1A1A1A;
            --text-secondary: #5A4A3A;
            --text-muted: #A68A7A;
            --border-color: rgba(255,107,0,0.12);
            --shadow-color: rgba(0, 0, 0, 0.08);
            --nav-bg: rgba(255, 255, 255, 0.85);
            --nav-bg-scrolled: rgba(255, 255, 255, 0.95);
            --input-bg: #F0F0F0;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255,107,0,0.15);
            --overlay-color: rgba(255, 255, 255, 0.3);
        }

        /* ============================================
           BASE STYLES
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #FF8C00;
        }

        /* ============================================
           ANIMATIONS
           ============================================ */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-60px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(60px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }

        @keyframes floatSlow {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes floatParticles {
            0% { transform: translateY(0px) rotate(0deg); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: translateY(-200px) rotate(720deg); opacity: 0; }
        }

        @keyframes scrollPulse {
            0%, 100% { opacity: 1; transform: translateX(-50%) translateY(0); }
            50% { opacity: 0; transform: translateX(-50%) translateY(12px); }
        }

        @keyframes marquee {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes overlaySlide {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ============================================
           ANIMATION CLASSES
           ============================================ */
        .animate-fade-up { animation: fadeInUp 0.8s ease forwards; }
        .animate-fade-left { animation: fadeInLeft 0.8s ease forwards; }
        .animate-fade-right { animation: fadeInRight 0.8s ease forwards; }
        .animate-float { animation: float 4s ease-in-out infinite; }
        .animate-float-slow { animation: floatSlow 3s ease-in-out infinite; }
        .animate-zoom { animation: zoomIn 0.6s ease forwards; }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }
        .delay-6 { animation-delay: 0.6s; }
        .delay-7 { animation-delay: 0.7s; }
        .delay-8 { animation-delay: 0.8s; }

        .opacity-0 { opacity: 0; }

        /* ============================================
           PARTICLES BACKGROUND
           ============================================ */
        .particles-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            border-radius: 50%;
            animation: floatParticles 8s linear infinite;
            opacity: 0;
        }

        .particle:nth-child(1) { left: 10%; animation-delay: 0s; width: 6px; height: 6px; }
        .particle:nth-child(2) { left: 20%; animation-delay: 1s; width: 4px; height: 4px; }
        .particle:nth-child(3) { left: 30%; animation-delay: 2s; width: 8px; height: 8px; }
        .particle:nth-child(4) { left: 40%; animation-delay: 0.5s; width: 5px; height: 5px; }
        .particle:nth-child(5) { left: 50%; animation-delay: 1.5s; width: 7px; height: 7px; }
        .particle:nth-child(6) { left: 60%; animation-delay: 2.5s; width: 4px; height: 4px; }
        .particle:nth-child(7) { left: 70%; animation-delay: 0.8s; width: 6px; height: 6px; }
        .particle:nth-child(8) { left: 80%; animation-delay: 1.8s; width: 5px; height: 5px; }
        .particle:nth-child(9) { left: 90%; animation-delay: 3s; width: 8px; height: 8px; }
        .particle:nth-child(10) { left: 15%; animation-delay: 2.2s; width: 4px; height: 4px; }
        .particle:nth-child(11) { left: 45%; animation-delay: 3.5s; width: 6px; height: 6px; }
        .particle:nth-child(12) { left: 75%; animation-delay: 1.2s; width: 5px; height: 5px; }

        /* ============================================
           NAVIGATION
           ============================================ */
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background: var(--nav-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: all 0.4s ease;
        }

        .nav.scrolled {
            background: var(--nav-bg-scrolled);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }

        .nav .logo {
            font-weight: 900;
            font-size: 1.3rem;
            background: var(--gradient-text);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientMove 3s ease infinite;
            cursor: pointer;
        }

        .nav .logo i {
            -webkit-text-fill-color: #FF6B00;
            margin-right: 8px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-links a:hover {
            color: var(--text-primary);
        }

        .nav-links a:hover::after {
            width: 60%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            color: #FFFFFF !important;
            box-shadow: 0 4px 20px rgba(255,107,0,0.25);
            padding: 8px 20px !important;
            border-radius: 8px !important;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 30px rgba(255,107,0,0.4);
        }

        .btn-primary::after {
            display: none !important;
        }

        .btn-outlin {
            border: 2px solid rgba(255,107,0,0.2);
            color: var(--text-secondary);
            background: transparent;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-outlin:hover {
            background: #FF6B00;
            color: #FFFFFF;
            transform: translateY(-3px) scale(1.05);
            border-color: #FF6B00;
        }

        /* ============================================
           THEME TOGGLE
           ============================================ */
        .theme-toggle {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            transform: rotate(20deg) scale(1.1);
            border-color: #FF6B00;
            color: #FF6B00;
        }

        .theme-toggle .sun-icon { display: none; }
        .theme-toggle .moon-icon { display: block; }

        [data-theme="light"] .theme-toggle .sun-icon { display: block; }
        [data-theme="light"] .theme-toggle .moon-icon { display: none; }

        /* ============================================
           HERO SECTION
           ============================================ */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            padding-top: 100px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -20%;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(255,107,0,0.06) 0%, transparent 70%);
            border-radius: 50%;
            animation: floatSlow 8s ease-in-out infinite;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,107,0,0.04) 0%, transparent 70%);
            border-radius: 50%;
            animation: floatSlow 10s ease-in-out infinite reverse;
        }

        .hero-content {
            max-width: 1200px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .hero-text h1 {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1.05;
            font-family: 'Montserrat', sans-serif;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .hero-text h1 .highlight {
            background: var(--gradient-text);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientMove 3s ease infinite;
        }

        .hero-text .subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            line-height: 1.8;
            margin: 16px 0 32px;
            max-width: 500px;
        }

        .hero-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-hero {
            padding: 14px 36px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-hero-primary {
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            color: #FFFFFF;
            box-shadow: 0 4px 25px rgba(255,107,0,0.3);
        }

        .btn-hero-primary:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 8px 40px rgba(255,107,0,0.5);
        }

        .btn-hero-secondary {
            border: 2px solid rgba(255,107,0,0.3);
            color: var(--text-primary);
            background: transparent;
        }

        .btn-hero-secondary:hover {
            background: rgba(255,107,0,0.1);
            border-color: #FF6B00;
            transform: translateY(-4px) scale(1.05);
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 48px;
            padding-top: 40px;
            border-top: 1px solid var(--border-color);
        }

        .hero-stats .stat-item h3 {
            font-size: 2.2rem;
            font-weight: 800;
            font-family: 'Montserrat', sans-serif;
            background: var(--gradient-text);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        .hero-stats .stat-item p {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-top: 4px;
        }

        /* ============================================
           HERO IMAGE WITH MARQUEE
           ============================================ */
        .hero-image {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            position: relative;
        }

        .hero-image .image-wrapper {
            width: 100%;
            max-width: 500px;
            aspect-ratio: 1;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 30px 80px var(--shadow-color);
            position: relative;
            animation: float 4s ease-in-out infinite;
        }

        .hero-image .image-wrapper::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,107,0,0.2), transparent 50%);
            z-index: 1;
        }

        .hero-image .image-wrapper::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 30px;
            background: linear-gradient(135deg, #FF6B00, #FF8C00, #FF6B00);
            background-size: 200% 200%;
            z-index: -1;
            animation: gradientMove 3s ease infinite;
        }

        .hero-image .image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s ease;
        }

        .hero-image .image-wrapper:hover img {
            transform: scale(1.05);
        }

        /* ============================================
           MARQUEE SECTION
           ============================================ */
        .marquee-container {
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            background: var(--bg-card);
            border-radius: 14px;
            border: 1px solid var(--border-color);
            padding: 12px 0;
            position: relative;
            transition: all 0.5s ease;
            opacity: 1;
            transform: translateY(0);
            max-height: 100px;
        }

        .marquee-container.hidden {
            opacity: 0;
            transform: translateY(-30px);
            max-height: 0;
            padding: 0;
            margin: 0;
            border: none;
        }

        .marquee-content {
            display: flex;
            align-items: center;
            gap: 40px;
            animation: marquee 20s linear infinite;
            white-space: nowrap;
            width: fit-content;
        }

        .marquee-content .marquee-item {
            margin: top 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .marquee-content .marquee-item i {
            color: #FF6B00;
            font-size: 1.1rem;
        }

        .marquee-content .marquee-item .highlight-text {
            background: var(--gradient-text);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        /* ============================================
           SCROLL INDICATOR
           ============================================ */
        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            animation: floatSlow 2s ease-in-out infinite;
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        .scroll-indicator.hidden {
            opacity: 0;
        }

        .scroll-indicator .mouse {
            width: 24px;
            height: 38px;
            border: 2px solid rgba(255,107,0,0.3);
            border-radius: 12px;
            position: relative;
        }

        .scroll-indicator .mouse::after {
            content: '';
            position: absolute;
            top: 6px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 8px;
            background: #FF6B00;
            border-radius: 2px;
            animation: scrollPulse 2s ease-in-out infinite;
        }

        /* ============================================
           GALLERY SECTION - 4 COLUMNS × 2 ROWS
           ============================================ */
        .gallery-section {
            padding: 80px 20px;
            position: relative;
            z-index: 1;
        }

        .gallery-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,107,0,0.2), transparent);
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .gallery-item {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            aspect-ratio: 1;
            cursor: pointer;
            transition: all 0.4s ease;
            border: 2px solid var(--border-color);
            background: var(--bg-card);
        }

        .gallery-item:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: #FF6B00;
            box-shadow: 0 20px 50px var(--shadow-color);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.1);
        }

        .gallery-item .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 30px 20px 20px;
            background: linear-gradient(transparent, var(--overlay-color));
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.4s ease;
            color:#ffff

        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
            transform: translateY(0);
        }

        .gallery-item .gallery-overlay h4 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .gallery-item .gallery-overlay p {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .gallery-item .gallery-overlay .icon-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 32px;
            height: 32px;
            background: rgba(255,107,0,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FF8C00;
            font-size: 0.8rem;
            backdrop-filter: blur(10px);
        }

        /* Gallery Item Background Colors (for images that don't load) */
        .gallery-item:nth-child(1) { background: linear-gradient(135deg, #1a1a2e, #16213e); }
        .gallery-item:nth-child(2) { background: linear-gradient(135deg, #1a1a2e, #1b1b3a); }
        .gallery-item:nth-child(3) { background: linear-gradient(135deg, #16213e, #0f3460); }
        .gallery-item:nth-child(4) { background: linear-gradient(135deg, #1a1a2e, #16213e); }
        .gallery-item:nth-child(5) { background: linear-gradient(135deg, #0f3460, #1a1a2e); }
        .gallery-item:nth-child(6) { background: linear-gradient(135deg, #16213e, #1a1a2e); }
        .gallery-item:nth-child(7) { background: linear-gradient(135deg, #1b1b3a, #0f3460); }
        .gallery-item:nth-child(8) { background: linear-gradient(135deg, #0f3460, #16213e); }

        [data-theme="light"] .gallery-item:nth-child(1) { background: linear-gradient(135deg, #e8e8e8, #d4d4d4); }
        [data-theme="light"] .gallery-item:nth-child(2) { background: linear-gradient(135deg, #d4d4d4, #c8c8c8); }
        [data-theme="light"] .gallery-item:nth-child(3) { background: linear-gradient(135deg, #c8c8c8, #b8b8b8); }
        [data-theme="light"] .gallery-item:nth-child(4) { background: linear-gradient(135deg, #e0e0e0, #d0d0d0); }
        [data-theme="light"] .gallery-item:nth-child(5) { background: linear-gradient(135deg, #d8d8d8, #c8c8c8); }
        [data-theme="light"] .gallery-item:nth-child(6) { background: linear-gradient(135deg, #e8e8e8, #d8d8d8); }
        [data-theme="light"] .gallery-item:nth-child(7) { background: linear-gradient(135deg, #d0d0d0, #c0c0c0); }
        [data-theme="light"] .gallery-item:nth-child(8) { background: linear-gradient(135deg, #c8c8c8, #b8b8b8); }

        /* ============================================
           MEMBERSHIP SECTION
           ============================================ */
        .membership-section {
            padding: 80px 20px;
            position: relative;
            z-index: 1;
        }

        .membership-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,107,0,0.2), transparent);
        }

        .section-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .section-header h2 {
            font-size: 2.8rem;
            font-weight: 800;
            font-family: 'Montserrat', sans-serif;
            color: var(--text-primary);
        }

        .section-header h2 span {
            background: var(--gradient-text);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientMove 3s ease infinite;
        }

        .section-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-top: 8px;
        }

        .membership-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 28px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .membership-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 32px 28px;
            border: 1px solid var(--border-color);
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            cursor: default;
        }

        .membership-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            transform: scaleX(0);
            transition: transform 0.4s ease;
            transform-origin: left;
        }

        .membership-card:hover {
            transform: translateY(-12px) scale(1.02);
            border-color: rgba(255,107,0,0.2);
            box-shadow: 0 20px 60px var(--shadow-color);
        }

        .membership-card:hover::before {
            transform: scaleX(1);
        }

        .membership-card .card-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            padding: 4px 14px;
            border-radius: 100px;
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #FFFFFF;
            letter-spacing: 0.05em;
        }

        .membership-card .price {
            font-size: 2.8rem;
            font-weight: 800;
            font-family: 'Montserrat', sans-serif;
            background: var(--gradient-text);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        .membership-card .duration {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-top: 2px;
        }

        .membership-card .plan-icon {
            font-size: 2.5rem;
            color: #FF6B00;
            margin: 12px 0;
            display: block;
        }

        .membership-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .membership-card p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.6;
        }

        .membership-card ul {
            text-align: left;
            padding: 0;
            list-style: none;
            margin: 16px 0 24px;
        }

        .membership-card ul li {
            padding: 6px 0;
            color: var(--text-secondary);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .membership-card ul li:last-child {
            border-bottom: none;
        }

        .membership-card ul li i {
            color: #FF6B00;
            font-size: 0.9rem;
        }

        .btn-card {
            display: block;
            padding: 12px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            margin-top: 8px;
            border: none;
            cursor: pointer;
        }

        .btn-card-primary {
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            color: #FFFFFF;
        }

        .btn-card-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 30px rgba(255,107,0,0.3);
        }

        .btn-card-outline {
            border: 2px solid rgba(255,107,0,0.2);
            color: var(--text-primary);
            background: transparent;
        }

        .btn-card-outline:hover {
            background: rgba(255,107,0,0.08);
            border-color: #FF6B00;
            transform: scale(1.05);
        }

        /* ============================================
           FEATURES SECTION
           ============================================ */
        .features-section {
            padding: 60px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
        }

        .feature-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 30px 24px;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            border-color: rgba(255,107,0,0.2);
            box-shadow: 0 20px 50px var(--shadow-color);
        }

        .feature-card .icon-wrapper {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, rgba(255,107,0,0.1), rgba(255,140,0,0.05));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 1.8rem;
            color: #FF8C00;
            transition: all 0.4s ease;
        }

        .feature-card:hover .icon-wrapper {
            transform: rotate(10deg) scale(1.1);
            background: linear-gradient(135deg, rgba(255,107,0,0.2), rgba(255,140,0,0.1));
        }

        .feature-card h4 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .feature-card p {
            color: var(--text-secondary);
            font-size: 0.85rem;
            line-height: 1.6;
        }

        /* ============================================
           FOOTER
           ============================================ */
        .footer {
            padding: 60px 20px 30px;
            border-top: 1px solid var(--border-color);
            position: relative;
            z-index: 1;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto 40px;
        }

        .footer-grid h4 {
            color: #FF6B00;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .footer-grid p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.8;
        }

        .footer-grid .social-links {
            display: flex;
            gap: 12px;
            margin-top: 12px;
        }

        .footer-grid .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255,107,0,0.06);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-grid .social-links a:hover {
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            color: #FFFFFF;
            transform: translateY(-4px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 0.75rem;
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 992px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-text .subtitle {
                margin: 16px auto 32px;
                max-width: 600px;
            }

            .hero-buttons {
                justify-content: center;
            }

            .hero-stats {
                justify-content: center;
            }

            .hero-stats .stat-item {
                text-align: center;
            }

            .hero-image .image-wrapper {
                max-width: 400px;
            }

            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .nav {
                padding: 12px 16px;
                flex-wrap: wrap;
                gap: 10px;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-links a {
                font-size: 0.8rem;
                padding: 6px 12px;
            }

            .hero-text h1 {
                font-size: 2.8rem;
            }

            .hero-stats {
                grid-template-columns: 1fr 1fr 1fr;
                gap: 12px;
                margin-top: 32px;
                padding-top: 24px;
            }

            .hero-stats .stat-item h3 {
                font-size: 1.8rem;
            }

            .hero-image .image-wrapper {
                max-width: 300px;
            }

            .marquee-container {
                max-width: 300px;
            }

            .section-header h2 {
                font-size: 2.2rem;
            }

            .membership-grid {
                grid-template-columns: 1fr;
                max-width: 400px;
            }

            .features-grid {
                grid-template-columns: 1fr 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 24px;
            }

            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            .hero-text h1 {
                font-size: 2.2rem;
            }

            .hero-stats {
                grid-template-columns: 1fr 1fr;
            }

            .hero-stats .stat-item h3 {
                font-size: 1.5rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }

            .marquee-container {
                max-width: 280px;
            }

            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .gallery-item .gallery-overlay {
                padding: 16px 12px 12px;
            }

            .gallery-item .gallery-overlay h4 {
                font-size: 0.8rem;
            }

            .gallery-item .gallery-overlay p {
                font-size: 0.65rem;
            }
        }
    </style>
</head>
<body data-theme="<?= $darkMode ?>">

    <!-- ===== PARTICLES BACKGROUND ===== -->
    <div class="particles-container">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- ===== NAVIGATION ===== -->
    <nav class="nav" id="navbar">
        <a href="index.php" style="text-decoration: none;">
            <div class="logo">
                <i class="fas fa-dumbbell" style="-webkit-text-fill-color: #FF6B00;"></i> USTED-K GYM
            </div>
        </a>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#gallery">Gallery</a>
            <a href="#about">Plans</a>
            <button class="theme-toggle" id="themeToggle" title="Toggle Dark/Light Mode">
                <i class="fas fa-sun sun-icon"></i>
                <i class="fas fa-moon moon-icon"></i>
            </button>
            <a href="login.php" class="btn-outlin">Login</a>
            <a href="register.php" class="btn-primary">Join Now</a>
        </div>
    </nav>

    <!-- ===== HERO SECTION ===== -->
    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="opacity-0 animate-fade-left delay-1">
                    Train Hard.<br>
                    <span class="highlight">Stay Healthy.</span><br>
                    Become Stronger.
                </h1>
                <p class="subtitle opacity-0 animate-fade-left delay-2">
                    Your fitness journey starts here. Join USTED-K Gym Center and transform your life with expert trainers and world-class facilities.
                </p>
                <div class="hero-buttons opacity-0 animate-fade-left delay-3">
                    <a href="register.php" class="btn-hero btn-hero-primary">
                        <i class="fas fa-user-plus"></i> Join Now
                    </a>
                    <a href="login.php" class="btn-hero btn-hero-secondary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </div>
                <div class="hero-stats opacity-0 animate-fade-up delay-4">
                    <div class="stat-item">
                        <h3><?= number_format($memberCount) ?></h3>
                        <p><i class="fas fa-users"></i> Active Members</p>
                    </div>
                    <div class="stat-item">
                        <h3><?= number_format($instructorCount) ?></h3>
                        <p><i class="fas fa-chalkboard-teacher"></i> Expert Trainers</p>
                    </div>
                    <div class="stat-item">
                        <h3><?= number_format($sectionCount) ?></h3>
                        <p><i class="fas fa-calendar-alt"></i> Classes Weekly</p>
                    </div>
                </div>
            </div>
            <div class="hero-image opacity-0 animate-fade-right delay-2">
                <div class="image-wrapper animate-float">
                    <img src="./image/meng.jpg" alt="USTED-K Gym Center">
                </div>

                <!-- ===== MARQUEE SECTION ===== -->
                <div class="marquee-container" id="marqueeContainer">
                    <div class="marquee-content">
                        <span class="marquee-item">
                            <i class="fas fa-star"></i>
                            <span class="highlight-text">★★★★★</span> 5.0 Rating
                        </span>
                        <span class="marquee-item">
                            <i class="fas fa-users"></i>
                            <span class="highlight-text">500+</span> Happy Members
                        </span>
                        <span class="marquee-item">
                            <i class="fas fa-trophy"></i>
                            <span class="highlight-text">#1</span> Gym in Campus
                        </span>
                        <span class="marquee-item">
                            <i class="fas fa-certificate"></i>
                            <span class="highlight-text">Certified</span> Trainers
                        </span>
                        <span class="marquee-item">
                            <i class="fas fa-star"></i>
                            <span class="highlight-text">★★★★★</span> 5.0 Rating
                        </span>
                        <span class="marquee-item">
                            <i class="fas fa-users"></i>
                            <span class="highlight-text">500+</span> Happy Members
                        </span>
                        <span class="marquee-item">
                            <i class="fas fa-trophy"></i>
                            <span class="highlight-text">#1</span> Gym in Campus
                        </span>
                        <span class="marquee-item">
                            <i class="fas fa-certificate"></i>
                            <span class="highlight-text">Certified</span> Trainers
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SCROLL INDICATOR ===== -->
        <div class="scroll-indicator" id="scrollIndicator">
            <div class="mouse"></div>
        </div>
    </section>

    <!-- ============================================
    GALLERY SECTION - 4 COLUMNS × 2 ROWS
    ============================================ -->
    <section class="gallery-section" id="gallery">
        <div class="section-header" data-aos="fade-up">
            <h2>Gym <span>Gallery</span></h2>
            <p>Explore our state-of-the-art facilities and training environment</p>
        </div>
        <div class="gallery-grid">
            <!-- Image 1 -->
            <div class="gallery-item" data-aos="zoom-in" data-aos-delay="100">
                <img src="./image/gym1.WEBP" alt="Modern Gym Equipment" onerror="this.style.display='none'">
                <div class="gallery-overlay">
                    <div class="icon-badge"><i class="fas fa-dumbbell"></i></div>
                    <h4>Modern Equipment</h4>
                    <p>State-of-the-art fitness machines</p>
                </div>
            </div>

            <!-- Image 2 -->
            <div class="gallery-item" data-aos="zoom-in" data-aos-delay="200">
                <img src="./image/gym2.jpg" alt="Group Training" onerror="this.style.display='none'">
                <div class="gallery-overlay">
                    <div class="icon-badge"><i class="fas fa-users"></i></div>
                    <h4>Group Training</h4>
                    <p>High-energy group fitness classes</p>
                </div>
            </div>

            <!-- Image 3 -->
            <div class="gallery-item" data-aos="zoom-in" data-aos-delay="300">
                <img src="./image/gym3.jpg" alt="Personal Training" onerror="this.style.display='none'">
                <div class="gallery-overlay">
                    <div class="icon-badge"><i class="fas fa-user-check"></i></div>
                    <h4>Personal Training</h4>
                    <p>One-on-one expert coaching</p>
                </div>
            </div>

            <!-- Image 4 -->
            <div class="gallery-item" data-aos="zoom-in" data-aos-delay="400">
                <img src="./image/gym4.WEBP" alt="Yoga Studio" onerror="this.style.display='none'">
                <div class="gallery-overlay">
                    <div class="icon-badge"><i class="fas fa-spa"></i></div>
                    <h4>Yoga Studio</h4>
                    <p>Peaceful mind-body wellness</p>
                </div>
            </div>

            <!-- Image 5 -->
            <div class="gallery-item" data-aos="zoom-in" data-aos-delay="500">
                <img src="./image/gym5.jpg" alt="Cardio Area" onerror="this.style.display='none'">
                <div class="gallery-overlay">
                    <div class="icon-badge"><i class="fas fa-heartbeat"></i></div>
                    <h4>Cardio Zone</h4>
                    <p>Top-tier cardiovascular equipment</p>
                </div>
            </div>

            <!-- Image 6 -->
            <div class="gallery-item" data-aos="zoom-in" data-aos-delay="600">
                <img src="./image/gym6.jpg" alt="Weight Training" onerror="this.style.display='none'">
                <div class="gallery-overlay">
                    <div class="icon-badge"><i class="fas fa-weight-hanging"></i></div>
                    <h4>Weight Training</h4>
                    <p>Full free-weight and cable station</p>
                </div>
            </div>

            <!-- Image 7 -->
            <div class="gallery-item" data-aos="zoom-in" data-aos-delay="700">
                <img src="./image/gym7.WEBP" alt="Locker Rooms" onerror="this.style.display='none'">
                <div class="gallery-overlay">
                    <div class="icon-badge"><i class="fas fa-shower"></i></div>
                    <h4>Premium Locker Rooms</h4>
                    <p>Modern changing & shower facilities</p>
                </div>
            </div>

            <!-- Image 8 -->
            <div class="gallery-item" data-aos="zoom-in" data-aos-delay="800">
                <img src="./image/gym8.jpg" alt="Reception Area" onerror="this.style.display='none'">
                <div class="gallery-overlay">
                    <div class="icon-badge"><i class="fas fa-concierge-bell"></i></div>
                    <h4>Reception Lounge</h4>
                    <p>Welcoming and comfortable lobby</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FEATURES SECTION ===== -->
    <section class="features-section" id="features">
        <div class="section-header" data-aos="fade-up">
            <h2>Why Choose <span>USTED-K Gym</span></h2>
            <p>We provide everything you need to achieve your fitness goals</p>
        </div>
        <div class="features-grid">
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="icon-wrapper"><i class="fas fa-chalkboard-teacher"></i></div>
                <h4>Expert Trainers</h4>
                <p>Professional certified trainers to guide your fitness journey</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <div class="icon-wrapper"><i class="fas fa-calendar-alt"></i></div>
                <h4>Flexible Schedule</h4>
                <p>Classes available throughout the day to fit your routine</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                <div class="icon-wrapper"><i class="fas fa-heartbeat"></i></div>
                <h4>Holistic Health</h4>
                <p>Complete wellness programs including nutrition guidance</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                <div class="icon-wrapper"><i class="fas fa-users"></i></div>
                <h4>Community</h4>
                <p>Join a supportive community of fitness enthusiasts</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="500">
                <div class="icon-wrapper"><i class="fas fa-dumbbell"></i></div>
                <h4>Modern Equipment</h4>
                <p>State-of-the-art gym equipment for all fitness levels</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="600">
                <div class="icon-wrapper"><i class="fas fa-shield-alt"></i></div>
                <h4>Safe Environment</h4>
                <p>Clean, sanitized, and safe training environment</p>
            </div>
        </div>
    </section>

    <!-- ===== MEMBERSHIP SECTION ===== -->
    <section class="membership-section" id="about">
        <div class="section-header" data-aos="fade-up">
            <h2>Membership <span>Plans</span></h2>
            <p>Choose the plan that fits your fitness journey</p>
        </div>
        <div class="membership-grid">
            <?php foreach ($memberships as $index => $m): ?>
            <div class="membership-card" data-aos="zoom-in" data-aos-delay="<?= $index * 100 + 100 ?>">
                <?php if ($index == 2): ?>
                    <span class="card-badge">Popular</span>
                <?php endif; ?>
                <span class="plan-icon">
                    <i class="fas fa-<?= $index == 0 ? 'user' : ($index == 1 ? 'users' : ($index == 2 ? 'crown' : 'star')) ?>"></i>
                </span>
                <div class="price"><?= formatCurrency($m['fee']) ?></div>
                <div class="duration">/ <?= $m['duration_months'] ?> month<?= $m['duration_months'] > 1 ? 's' : '' ?></div>
                <h3><?= htmlspecialchars($m['name']) ?></h3>
                <p><?= htmlspecialchars($m['description']) ?></p>
                <?php if ($m['benefits']): ?>
                    <ul>
                        <?php foreach (explode("\n", $m['benefits']) as $benefit): ?>
                            <?php if (trim($benefit)): ?>
                                <li><i class="fas fa-check-circle"></i> <?= htmlspecialchars(trim($benefit)) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a href="register.php" class="btn-card <?= $index == 2 ? 'btn-card-primary' : 'btn-card-outline' ?>">
                    Get Started <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="footer-grid">
            <div>
                <h4><i class="fas fa-dumbbell" style="color: #FF6B00;"></i> USTED-K Gym</h4>
                <p>Premium fitness center dedicated to your health and wellness journey.</p>
                <div class="social-links">
                    <a href="http://facebook.com"><i class="fab fa-facebook-f"></i></a>
                    <a href="http://instagram.com"><i class="fab fa-instagram"></i></a>
                    <a href="http://twitter.com"><i class="fab fa-twitter"></i></a>
                    <a href="http://youtube.com"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div>
                <h4>Quick Links</h4>
                <p><a href="#home" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='#FF8C00'" onmouseout="this.style.color='var(--text-secondary)'">Home</a></p>
                <p><a href="#gallery" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='#FF8C00'" onmouseout="this.style.color='var(--text-secondary)'">Gallery</a></p>
                <p><a href="#features" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='#FF8C00'" onmouseout="this.style.color='var(--text-secondary)'">Features</a></p>
                <p><a href="#about" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='#FF8C00'" onmouseout="this.style.color='var(--text-secondary)'">Plans</a></p>
            </div>
            <div>
                <h4>Contact</h4>
                <p><i class="fas fa-map-marker-alt" style="color: #FF6B00;"></i> USTED-K Campus</p>
                <p><i class="fas fa-phone" style="color: #FF6B00;"></i> +233 550 669 957</p>
                <p><i class="fas fa-envelope" style="color: #FF6B00;"></i> info@ustedkgym.com</p>
            </div>
            <div>
                <h4>Hours</h4>
                <p>Mon-Fri: 6:00 AM - 10:00 PM</p>
                <p>Sat: 7:00 AM - 8:00 PM</p>
                <p>Sun: 8:00 AM - 6:00 PM</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> USTED-K Gym Center. All rights reserved. | Made with <i class="fas fa-heart" style="color: #FF6B6B;"></i></p>
        </div>
    </footer>

    <!-- ===== SCRIPTS ===== -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // ============================================
        // AOS INITIALIZATION
        // ============================================
        AOS.init({
            duration: 800,
            once: true,
            offset: 80
        });

        // ============================================
        // NAVBAR SCROLL EFFECT
        // ============================================
        const navbar = document.getElementById('navbar');
        const scrollIndicator = document.getElementById('scrollIndicator');
        const marqueeContainer = document.getElementById('marqueeContainer');

        window.addEventListener('scroll', function() {
            const scrollY = window.pageYOffset;
            
            if (scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            const heroHeight = document.querySelector('.hero').offsetHeight;
            const scrollPercent = scrollY / heroHeight;

            if (scrollPercent > 0.05) {
                marqueeContainer.classList.add('hidden');
            } else {
                marqueeContainer.classList.remove('hidden');
            }

            if (scrollPercent > 0.1) {
                scrollIndicator.classList.add('hidden');
            } else {
                scrollIndicator.classList.remove('hidden');
            }
        });

        // ============================================
        // SMOOTH SCROLL FOR NAV LINKS
        // ============================================
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // ============================================
        // PARALLAX EFFECT
        // ============================================
        document.addEventListener('mousemove', function(e) {
            const x = (window.innerWidth / 2 - e.pageX) / 50;
            const y = (window.innerHeight / 2 - e.pageY) / 50;
            const image = document.querySelector('.hero-image .image-wrapper');
            if (image) {
                image.style.transform = `translate(${x * 0.5}px, ${y * 0.5}px)`;
            }
        });

        // ============================================
        // COUNTER ANIMATION
        // ============================================
        function animateCounter(element, target, duration) {
            let start = 0;
            const increment = target / (duration / 16);
            const timer = setInterval(() => {
                start += increment;
                if (start >= target) {
                    start = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(start).toLocaleString();
            }, 16);
        }

        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const stats = entry.target.querySelectorAll('.stat-item h3');
                    stats.forEach(stat => {
                        const target = parseInt(stat.textContent.replace(/,/g, ''));
                        if (target > 0) {
                            animateCounter(stat, target, 2000);
                        }
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        const heroStats = document.querySelector('.hero-stats');
        if (heroStats) {
            statsObserver.observe(heroStats);
        }

        // ============================================
        // DARK / LIGHT MODE TOGGLE
        // ============================================
        const themeToggle = document.getElementById('themeToggle');

        function setTheme(theme) {
            document.body.setAttribute('data-theme', theme);
            document.cookie = `dark_mode=${theme}; path=/; max-age=${60 * 60 * 24 * 365}`;
            
            const sunIcon = themeToggle.querySelector('.sun-icon');
            const moonIcon = themeToggle.querySelector('.moon-icon');
            if (theme === 'light') {
                sunIcon.style.display = 'block';
                moonIcon.style.display = 'none';
            } else {
                sunIcon.style.display = 'none';
                moonIcon.style.display = 'block';
            }
        }

        const currentTheme = document.cookie.split('; ').find(row => row.startsWith('dark_mode='));
        const initialTheme = currentTheme ? currentTheme.split('=')[1] : 'dark';
        setTheme(initialTheme);

        themeToggle.addEventListener('click', function() {
            const current = document.body.getAttribute('data-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
            
            this.style.transform = 'rotate(360deg) scale(1.2)';
            setTimeout(() => {
                this.style.transform = '';
            }, 400);
        });

        console.log('🚀 USTED-K Gym Center - Premium Landing Page');
        console.log('📸 Gallery Section: 8 Images (4 Columns × 2 Rows)');
        console.log('🌓 Dark/Light Mode: ' + initialTheme);
    </script>

</body>
</html>