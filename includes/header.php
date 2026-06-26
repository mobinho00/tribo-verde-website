<?php
// Regista visita silenciosamente (só páginas públicas)
$pagina_atual = basename($_SERVER['PHP_SELF']);
$is_home = in_array($pagina_atual, ['index.php', 'sobre.php', 'atividades.php', '']);

if ($pagina_atual !== 'admin.php') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
    $ip = $_SERVER['REMOTE_ADDR'];
    $pdo->prepare("INSERT INTO visitas (ip, pagina) VALUES (?, ?)")
        ->execute([$ip, $pagina_atual]);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tribo Verde - Forest School</title>

    <!-- Anti-flash de língua: esconde página até setLang correr -->
    <script>
        if (localStorage.getItem('lang') === 'en') {
            document.documentElement.style.visibility = 'hidden';
        }
    </script>

    <link rel="icon" type="image/png" href="/img/logo.jpg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&display=swap" rel="stylesheet">

    <style>
        /* todo o teu CSS existente sem alterações */
        :root {
            --verde-militar: #4a5530;
            --verde-escuro: #2b3319;
            --verde-medio: #3a4428;
            --bege: #f5f2e8;
            --creme: #e8dcc0;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        @keyframes glow {
            0% { box-shadow: 0 5px 20px rgba(74, 85, 48, 0.3); }
            100% { box-shadow: 0 10px 40px rgba(74, 85, 48, 0.6); }
        }
        
        body { background-color: var(--bege) !important; }
        
        .navbar {
            background: linear-gradient(135deg, var(--verde-militar) 0%, var(--verde-escuro) 100%) !important;
            box-shadow: 0 4px 20px rgba(43, 51, 25, 0.4) !important;
            padding-top: 12px !important;
            padding-bottom: 12px !important;
            min-height: 90px;
        }

        .navbar-brand {
            position: relative;
            display: flex;
            align-items: center;
            height: 70px;
            min-width: 220px;
        }

        .navbar-brand img {
            transition: transform 0.3s ease;
        }

        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            transform: translateY(-2px);
            background: rgba(255,255,255,0.1) !important;
            border-radius: 10px;
        }

        #lang-btn {
            border: 2px solid rgba(255,255,255,0.5);
            color: white;
            background: transparent;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        #lang-btn:hover {
            background: rgba(255,255,255,0.15);
            border-color: white;
            transform: scale(1.05);
        }

        .hero {
            background: linear-gradient(135deg, var(--verde-militar) 0%, var(--verde-medio) 50%, var(--verde-escuro) 100%);
            display: block !important;
            min-height: unset !important;
            height: auto !important;
            position: relative;
            overflow: hidden;
        }

        .hero .btn {
            animation: glow 2s ease-in-out infinite alternate;
            transition: all 0.4s ease;
        }

        .hero .btn:hover {
            transform: scale(1.1) rotate(2deg);
        }

        .hero-title {
            font-size: 7rem;
            font-weight: 900;
            letter-spacing: 15px;
            text-shadow: 3px 3px 15px rgba(0,0,0,0.5);
            font-family: 'Montserrat', sans-serif;
        }

        .hero-title .letter {
            display: inline-block;
            animation: bounce 1s ease-in-out forwards;
            opacity: 0;
            transform: translateY(-100px) rotate(-10deg);
        }

        .hero-title .letter:nth-child(1) { animation-delay: 0.1s; }
        .hero-title .letter:nth-child(2) { animation-delay: 0.2s; }
        .hero-title .letter:nth-child(3) { animation-delay: 0.3s; }
        .hero-title .letter:nth-child(4) { animation-delay: 0.4s; }
        .hero-title .letter:nth-child(5) { animation-delay: 0.5s; }
        .hero-title .letter:nth-child(7) { animation-delay: 0.7s; }
        .hero-title .letter:nth-child(8) { animation-delay: 0.8s; }
        .hero-title .letter:nth-child(9) { animation-delay: 0.9s; }
        .hero-title .letter:nth-child(10) { animation-delay: 1s; }
        .hero-title .letter:nth-child(11) { animation-delay: 1.1s; }

        .space { display: inline-block; width: 30px; }

        @keyframes bounce {
            0% { opacity: 0; transform: translateY(-100px) rotate(-10deg); }
            60% { opacity: 1; transform: translateY(20px) rotate(5deg); }
            80% { transform: translateY(-10px) rotate(-3deg); }
            100% { opacity: 1; transform: translateY(0) rotate(0deg); }
        }

        .fade-in {
            animation: fadeInUp 1s ease-out forwards;
            opacity: 0;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero-title .letter:hover { animation: wiggle 0.5s ease; }

        @keyframes wiggle {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(5deg); }
            75% { transform: rotate(-5deg); }
        }

        .card {
            transition: all 0.4s ease;
            border: none;
            cursor: pointer;
        }

        .card:not(.no-hover):hover {
            transform: translateY(-15px) scale(1.05);
            box-shadow: 0 20px 50px rgba(74, 85, 48, 0.3) !important;
        }

        .card:not(.no-hover):hover .fs-1 {
            transform: scale(1.3) rotate(10deg);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--verde-militar), var(--verde-escuro)) !important;
            border: none;
            transition: all 0.4s ease;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #5a6540, var(--verde-medio)) !important;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 30px rgba(74, 85, 48, 0.5) !important;
        }

        .bg-success { background-color: var(--verde-militar) !important; }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--verde-militar) !important;
            box-shadow: 0 0 0 4px rgba(74, 85, 48, 0.1) !important;
            transform: scale(1.02);
        }

        footer { background-color: var(--verde-escuro) !important; }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 3.5rem;
                letter-spacing: 8px;
            }

            .hero {
                height: auto !important;
                max-height: unset !important;
            }

            #lang-btn {
                margin-top: 10px;
            }

            .navbar {
                min-height: 70px !important;
                padding-top: 8px !important;
                padding-bottom: 8px !important;
            }

            .navbar-brand {
                min-width: 120px;
                height: 45px;
            }

            #brand-text {
                height: 20px !important;
            }

            #brand-logo {
                height: 42px !important;
            }

            .card:not(.no-hover):hover {
                transform: none !important;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
            }

            .card:not(.no-hover):hover .fs-1,
            .card:not(.no-hover):hover i {
                transform: none !important;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/">

                <img src="/img/tribo-verde-navbar.png"
                     id="brand-text"
                     alt="Tribo Verde"
                     style="height: 28px;
                            position: absolute;
                            left: 0;
                            top: 50%;
                            transform: translateY(-50%);
                            transition: opacity 0.4s ease, transform 0.4s ease;
                            opacity: <?= $is_home ? '1' : '0' ?>;
                            pointer-events: <?= $is_home ? 'auto' : 'none' ?>;">

                <img src="/img/logo-tribo-verde.png"
                     id="brand-logo"
                     alt="Tribo Verde"
                     style="height: 70px;
                            position: absolute;
                            left: 0;
                            top: 50%;
                            transform: translateY(-50%) scale(<?= $is_home ? '0.3' : '1' ?>);
                            opacity: <?= $is_home ? '0' : '1' ?>;
                            transition: opacity 0.5s ease, transform 0.5s ease;
                            pointer-events: <?= $is_home ? 'none' : 'auto' ?>;
                            transform-origin: left center;">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link px-4 py-2 fs-5" href="/" data-nav="0">
                            <i class="fas fa-home me-1"></i>Início
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-4 py-2 fs-5" href="/sobre" data-nav="1">
                            <i class="fas fa-leaf me-1"></i>Sobre Nós
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-4 py-2 fs-5" href="/atividades" data-nav="2">
                            <i class="fas fa-play me-1"></i>Atividades
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-4 py-2 fs-5" href="/contacto" data-nav="3">
                            <i class="fas fa-user-plus me-1"></i>Inscrições
                        </a>
                    </li>
                    <li class="nav-item ms-3">
                        <button id="lang-btn" class="btn rounded-pill px-3 py-1">
                            🇬🇧 EN
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="pt-0">