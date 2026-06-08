<?php
$form_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_sent'])) {
    $name = strip_tags($_POST['name'] ?? '');
    $email = strip_tags($_POST['email'] ?? '');
    $taller = strip_tags($_POST['taller'] ?? '');
    $interes = strip_tags($_POST['interes'] ?? '');
    $message = strip_tags($_POST['message'] ?? '');
    $to = 'danipiw@gmail.com';
    $subject = "FullTaller - Consulta de $name";
    $body = "Nombre: $name\nEmail: $email\nTaller: $taller\nInterés: $interes\n\nMensaje:\n$message";
    $headers = "From: $email\r\nReply-To: $email\r\nX-Mailer: PHP/" . phpversion();
    if (mail($to, $subject, $body, $headers)) {
        $form_msg = 'ok';
    } else {
        $form_msg = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FullTaller — Sistema de Gestión para Talleres Técnicos</title>
    <meta name="description" content="Sistema completo de gestión de órdenes, punto de venta e inventario para talleres de servicio técnico. Prueba gratuita disponible.">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --jb-cyan: #00a8e8;
            --jb-azul: #0077b6;
            --jb-azul-oscuro: #023e8a;
            --jb-navy: #001845;
            --jb-navy-deep: #000d26;
            --jb-celeste: #48cae4;
            --jb-celeste-claro: #90e0ef;
            --jb-blanco: #f0f4f8;
            --jb-gris: #94a3b8;
            --jb-gris-oscuro: #475569;
            --jb-success: #10b981;
            --jb-warning: #f59e0b;
            --jb-danger: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--jb-navy-deep);
            color: var(--jb-blanco);
            overflow-x: hidden;
            line-height: 1.6;
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--jb-navy); }
        ::-webkit-scrollbar-thumb { background: var(--jb-cyan); border-radius: 4px; }

        /* ===== NAVBAR ===== */
        nav {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            background: rgba(0, 13, 38, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 168, 232, 0.15);
            transition: all 0.3s ease;
        }
        nav.scrolled {
            background: rgba(0, 13, 38, 0.95);
            box-shadow: 0 4px 30px rgba(0, 119, 182, 0.2);
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0.75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.4rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--jb-cyan), var(--jb-azul));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            box-shadow: 0 0 20px rgba(0, 168, 232, 0.3);
        }
        .nav-links {
            display: flex;
            gap: 2.5rem;
            list-style: none;
            align-items: center;
        }
        .nav-links a {
            color: var(--jb-gris);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            position: relative;
        }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--jb-cyan), var(--jb-celeste));
            transition: width 0.3s ease;
            border-radius: 2px;
        }
        .nav-links a:hover { color: var(--jb-cyan); }
        .nav-links a:hover::after { width: 100%; }
        .nav-cta {
            background: linear-gradient(135deg, var(--jb-cyan), var(--jb-azul));
            color: white !important;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 168, 232, 0.3);
            transition: all 0.3s ease;
        }
        .nav-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 168, 232, 0.4);
        }
        .nav-cta::after { display: none !important; }
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--jb-blanco);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* ===== HERO ===== */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 8rem 2rem 4rem;
        }
        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 50%, rgba(0, 168, 232, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 119, 182, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 50% 80%, rgba(2, 62, 138, 0.1) 0%, transparent 50%);
        }
        .hero-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(rgba(0, 168, 232, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 168, 232, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        .hero-content {
            max-width: 1100px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            z-index: 1;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(0, 168, 232, 0.1);
            border: 1px solid rgba(0, 168, 232, 0.3);
            border-radius: 50px;
            font-size: 0.85rem;
            color: var(--jb-cyan);
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .hero-badge i { animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .hero h1 {
            font-size: clamp(2.2rem, 4vw, 3.5rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            color: white;
        }
        .hero h1 .gradient-text {
            background: linear-gradient(135deg, var(--jb-cyan), var(--jb-celeste), var(--jb-azul));
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-shift 5s ease infinite;
        }
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .hero-subtitle {
            font-size: 1.15rem;
            color: var(--jb-gris);
            margin-bottom: 2rem;
            line-height: 1.7;
        }
        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn {
            padding: 0.9rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-family: inherit;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--jb-cyan), var(--jb-azul));
            color: white;
            box-shadow: 0 4px 20px rgba(0, 168, 232, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 168, 232, 0.4);
        }
        .btn-outline {
            background: transparent;
            color: var(--jb-blanco);
            border: 2px solid rgba(0, 168, 232, 0.3);
        }
        .btn-outline:hover {
            border-color: var(--jb-cyan);
            color: var(--jb-cyan);
            transform: translateY(-2px);
        }
        .btn-lg {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
        }

        /* Hero visual - mockup */
        .hero-visual { position: relative; }
        .mockup-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        .mockup-screen {
            background: linear-gradient(135deg, var(--jb-navy), var(--jb-azul-oscuro));
            border: 1px solid rgba(0, 168, 232, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4), 0 0 40px rgba(0, 168, 232, 0.1);
            position: relative;
            overflow: hidden;
        }
        .mockup-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0, 168, 232, 0.1);
        }
        .mockup-dot { width: 10px; height: 10px; border-radius: 50%; }
        .mockup-dot.red { background: var(--jb-danger); }
        .mockup-dot.yellow { background: var(--jb-warning); }
        .mockup-dot.green { background: var(--jb-success); }
        .mockup-title {
            margin-left: auto;
            font-size: 0.75rem;
            color: var(--jb-gris);
            font-weight: 500;
        }
        .mockup-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            background: rgba(0, 168, 232, 0.05);
            border-radius: 8px;
            margin-bottom: 8px;
            border-left: 3px solid var(--jb-cyan);
            animation: mockupSlide 3s ease-in-out infinite;
        }
        .mockup-row:nth-child(2) { animation-delay: 0.2s; border-left-color: var(--jb-warning); }
        .mockup-row:nth-child(3) { animation-delay: 0.4s; border-left-color: var(--jb-success); }
        .mockup-row:nth-child(4) { animation-delay: 0.6s; border-left-color: var(--jb-celeste); }
        .mockup-row:nth-child(5) { animation-delay: 0.8s; border-left-color: var(--jb-danger); }
        @keyframes mockupSlide {
            0%, 100% { transform: translateX(0); opacity: 1; }
            50% { transform: translateX(5px); opacity: 0.8; }
        }
        .mockup-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: rgba(0, 168, 232, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--jb-cyan);
            font-size: 0.8rem;
        }
        .mockup-text { flex: 1; }
        .mockup-text-line {
            height: 8px;
            background: rgba(148, 163, 184, 0.2);
            border-radius: 4px;
            margin-bottom: 6px;
        }
        .mockup-text-line.short { width: 60%; }
        .mockup-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            background: rgba(0, 168, 232, 0.15);
            color: var(--jb-cyan);
        }
        .mockup-float {
            position: absolute;
            width: 180px;
            background: linear-gradient(135deg, var(--jb-navy), var(--jb-azul-oscuro));
            border: 1px solid rgba(0, 168, 232, 0.2);
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            animation: float 6s ease-in-out infinite;
        }
        .mockup-float.right {
            top: -30px;
            right: -40px;
            animation-delay: 1s;
        }
        .mockup-float.bottom {
            bottom: -20px;
            left: -30px;
            animation-delay: 2s;
            width: 160px;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        /* ===== SECTIONS ===== */
        section { padding: 5rem 2rem; position: relative; }
        .section-header {
            text-align: center;
            margin-bottom: 3.5rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .section-tag {
            display: inline-block;
            padding: 0.35rem 1rem;
            background: rgba(0, 168, 232, 0.1);
            border: 1px solid rgba(0, 168, 232, 0.2);
            border-radius: 50px;
            color: var(--jb-cyan);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .section-title {
            font-size: clamp(1.8rem, 3.5vw, 2.8rem);
            font-weight: 800;
            margin-bottom: 1rem;
            color: white;
            line-height: 1.2;
        }
        .section-subtitle {
            color: var(--jb-gris);
            font-size: 1.1rem;
            line-height: 1.7;
        }

        /* ===== FEATURES ===== */
        .features {
            background: linear-gradient(180deg, var(--jb-navy-deep) 0%, var(--jb-navy) 100%);
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .feature-card {
            background: linear-gradient(135deg, rgba(0, 24, 69, 0.6), rgba(2, 62, 138, 0.3));
            border: 1px solid rgba(0, 168, 232, 0.15);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--jb-cyan), var(--jb-celeste));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }
        .feature-card:hover::before { transform: scaleX(1); }
        .feature-card:hover {
            transform: translateY(-8px);
            border-color: rgba(0, 168, 232, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 0 30px rgba(0, 168, 232, 0.05);
        }
        .feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--jb-cyan), var(--jb-azul));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: white;
            margin-bottom: 1.25rem;
            box-shadow: 0 8px 20px rgba(0, 168, 232, 0.2);
        }
        .feature-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: white;
        }
        .feature-desc {
            color: var(--jb-gris);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* ===== MODULES SHOWCASE ===== */
        .modules { background: var(--jb-navy); }
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
            max-width: 1000px;
            margin: 0 auto;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(0, 168, 232, 0.15);
        }
        .module-item {
            background: linear-gradient(135deg, rgba(0, 24, 69, 0.8), rgba(2, 62, 138, 0.4));
            padding: 3rem 2rem;
            text-align: center;
            border-right: 1px solid rgba(0, 168, 232, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }
        .module-item:last-child { border-right: none; }
        .module-item:hover {
            background: linear-gradient(135deg, rgba(0, 168, 232, 0.1), rgba(0, 119, 182, 0.1));
        }
        .module-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--jb-cyan), var(--jb-azul));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 168, 232, 0.2);
        }
        .module-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.75rem;
        }
        .module-desc {
            color: var(--jb-gris);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* ===== PRICING ===== */
        .pricing {
            background: linear-gradient(180deg, var(--jb-navy) 0%, var(--jb-navy-deep) 100%);
        }
        .pricing-card {
            max-width: 450px;
            margin: 0 auto;
            background: linear-gradient(135deg, rgba(0, 24, 69, 0.8), rgba(2, 62, 138, 0.4));
            border: 2px solid rgba(0, 168, 232, 0.2);
            border-radius: 24px;
            padding: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .pricing-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 168, 232, 0.05) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .pricing-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            background: linear-gradient(135deg, var(--jb-cyan), var(--jb-azul));
            color: white;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }
        .pricing-amount {
            font-size: 4rem;
            font-weight: 900;
            color: white;
            line-height: 1;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        .pricing-amount span {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--jb-gris);
            vertical-align: super;
        }
        .pricing-period {
            color: var(--jb-gris);
            font-size: 1rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        .pricing-features {
            list-style: none;
            text-align: left;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        .pricing-features li {
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0, 168, 232, 0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--jb-blanco);
            font-size: 0.95rem;
        }
        .pricing-features li i {
            color: var(--jb-cyan);
            font-size: 1.1rem;
        }
        .pricing-features li.disabled {
            color: var(--jb-gris-oscuro);
            text-decoration: line-through;
        }
        .pricing-features li.disabled i {
            color: var(--jb-gris-oscuro);
        }
        .pricing-note {
            background: rgba(0, 168, 232, 0.08);
            border: 1px solid rgba(0, 168, 232, 0.15);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: var(--jb-celeste);
            position: relative;
            z-index: 1;
        }
        .pricing-note i { margin-right: 0.5rem; }

        /* ===== TRIAL BANNER ===== */
        .trial-banner {
            background: linear-gradient(135deg, rgba(0, 168, 232, 0.1), rgba(0, 119, 182, 0.1));
            border: 1px solid rgba(0, 168, 232, 0.2);
            border-radius: 16px;
            padding: 2rem;
            max-width: 800px;
            margin: 3rem auto 0;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .trial-banner-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--jb-cyan), var(--jb-azul));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }
        .trial-banner-text {
            text-align: left;
            flex: 1;
            min-width: 250px;
        }
        .trial-banner-text h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }
        .trial-banner-text p {
            color: var(--jb-gris);
            font-size: 0.95rem;
        }

        /* ===== CONTACT ===== */
        .contact {
            background: linear-gradient(180deg, var(--jb-navy-deep) 0%, var(--jb-navy) 100%);
        }
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            max-width: 1000px;
            margin: 0 auto;
            align-items: start;
        }
        .contact-info h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
        }
        .contact-info > p {
            color: var(--jb-gris);
            margin-bottom: 2rem;
            line-height: 1.7;
        }
        .contact-links {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .contact-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, rgba(0, 24, 69, 0.6), rgba(2, 62, 138, 0.3));
            border: 1px solid rgba(0, 168, 232, 0.15);
            border-radius: 12px;
            text-decoration: none;
            color: var(--jb-blanco);
            transition: all 0.3s ease;
        }
        .contact-link:hover {
            border-color: var(--jb-cyan);
            transform: translateX(5px);
            background: linear-gradient(135deg, rgba(0, 168, 232, 0.1), rgba(0, 119, 182, 0.1));
        }
        .contact-link-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background: rgba(0, 168, 232, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--jb-cyan);
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .contact-link-text div:first-child {
            font-weight: 600;
            font-size: 0.95rem;
        }
        .contact-link-text div:last-child {
            color: var(--jb-gris);
            font-size: 0.85rem;
        }
        .contact-form {
            background: linear-gradient(135deg, rgba(0, 24, 69, 0.6), rgba(2, 62, 138, 0.3));
            border: 1px solid rgba(0, 168, 232, 0.15);
            border-radius: 20px;
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--jb-gris);
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(0, 13, 38, 0.6);
            border: 1px solid rgba(0, 168, 232, 0.2);
            border-radius: 10px;
            color: var(--jb-blanco);
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--jb-cyan);
            box-shadow: 0 0 0 3px rgba(0, 168, 232, 0.1);
        }
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--jb-gris-oscuro);
        }
        .form-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--jb-cyan), var(--jb-azul));
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        .form-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 168, 232, 0.3);
        }

        /* ===== FOOTER ===== */
        footer {
            background: var(--jb-navy-deep);
            border-top: 1px solid rgba(0, 168, 232, 0.1);
            padding: 3rem 2rem;
            text-align: center;
        }
        .footer-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
            text-decoration: none;
        }
        .footer-text {
            color: var(--jb-gris);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        .footer-copy {
            color: var(--jb-gris-oscuro);
            font-size: 0.85rem;
        }

        /* ===== TOAST ===== */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, var(--jb-cyan), var(--jb-azul));
            color: white;
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.4s ease;
            z-index: 2000;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* ===== SCROLL TOP ===== */
        .scroll-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--jb-cyan), var(--jb-azul));
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
            box-shadow: 0 4px 15px rgba(0, 168, 232, 0.3);
        }
        .scroll-top.visible {
            opacity: 1;
            visibility: visible;
        }
        .scroll-top:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 168, 232, 0.4);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 968px) {
            .hero-content { grid-template-columns: 1fr; text-align: center; gap: 3rem; }
            .hero-buttons { justify-content: center; }
            .hero-visual { order: -1; }
            .mockup-container { max-width: 350px; }
            .mockup-float { display: none; }
            .modules-grid { grid-template-columns: 1fr; }
            .module-item { border-right: none; border-bottom: 1px solid rgba(0, 168, 232, 0.1); }
            .contact-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .nav-links { display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--jb-navy); flex-direction: column; padding: 1rem 2rem; gap: 1rem; border-bottom: 1px solid rgba(0, 168, 232, 0.1); }
            .nav-links.active { display: flex; }
            .mobile-menu-btn { display: block; }
            .nav-cta { width: 100%; text-align: center; }
            section { padding: 3rem 1.5rem; }
            .pricing-card { padding: 2rem 1.5rem; }
            .pricing-amount { font-size: 3rem; }
        }
    </style>
</head>
<body>

    <!-- NAV -->
    <nav id="navbar">
        <div class="nav-container">
            <a href="#" class="logo">
                <div class="logo-icon"><i class="fas fa-tools"></i></div>
                FullTaller
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="#funciones">Funciones</a></li>
                <li><a href="#modulos">Módulos</a></li>
                <li><a href="#precios">Precios</a></li>
                <li><a href="#contacto">Contacto</a></li>
                <li><a href="#contacto" class="nav-cta">Probar Gratis</a></li>
            </ul>
            <button class="mobile-menu-btn" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero" id="inicio">
        <div class="hero-bg"></div>
        <div class="hero-grid"></div>
        <div class="hero-content">
            <div>
                <div class="hero-badge">
                    <i class="fas fa-circle"></i>
                    Disponible para nuevos talleres
                </div>
                <h1>
                    El sistema que tu<br>
                    <span class="gradient-text">taller necesita</span>
                </h1>
                <p class="hero-subtitle">
                    Gestión de órdenes de reparación, punto de venta e inventario integrados en una sola plataforma.
                    Diseñado específicamente para talleres de servicio técnico que también venden.
                </p>
                <div class="hero-buttons">
                    <a href="#contacto" class="btn btn-primary btn-lg">
                        <i class="fas fa-rocket"></i> Empezar Prueba Gratis
                    </a>
                    <a href="#funciones" class="btn btn-outline btn-lg">
                        <i class="fas fa-play"></i> Ver Funciones
                    </a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="mockup-container">
                    <div class="mockup-screen">
                        <div class="mockup-header">
                            <div class="mockup-dot red"></div>
                            <div class="mockup-dot yellow"></div>
                            <div class="mockup-dot green"></div>
                            <span class="mockup-title">FullTaller — Órdenes Activas</span>
                        </div>
                        <div class="mockup-row">
                            <div class="mockup-icon"><i class="fas fa-mobile-alt"></i></div>
                            <div class="mockup-text">
                                <div class="mockup-text-line"></div>
                                <div class="mockup-text-line short"></div>
                            </div>
                            <span class="mockup-status">En Revisión</span>
                        </div>
                        <div class="mockup-row">
                            <div class="mockup-icon"><i class="fas fa-laptop"></i></div>
                            <div class="mockup-text">
                                <div class="mockup-text-line"></div>
                                <div class="mockup-text-line short"></div>
                            </div>
                            <span class="mockup-status" style="background: rgba(245, 158, 11, 0.15); color: var(--jb-warning);">En Espera</span>
                        </div>
                        <div class="mockup-row">
                            <div class="mockup-icon"><i class="fas fa-tablet-alt"></i></div>
                            <div class="mockup-text">
                                <div class="mockup-text-line"></div>
                                <div class="mockup-text-line short"></div>
                            </div>
                            <span class="mockup-status" style="background: rgba(16, 185, 129, 0.15); color: var(--jb-success);">Reparado</span>
                        </div>
                        <div class="mockup-row">
                            <div class="mockup-icon"><i class="fas fa-headphones"></i></div>
                            <div class="mockup-text">
                                <div class="mockup-text-line"></div>
                                <div class="mockup-text-line short"></div>
                            </div>
                            <span class="mockup-status">Aprobado</span>
                        </div>
                        <div class="mockup-row">
                            <div class="mockup-icon"><i class="fas fa-gamepad"></i></div>
                            <div class="mockup-text">
                                <div class="mockup-text-line"></div>
                                <div class="mockup-text-line short"></div>
                            </div>
                            <span class="mockup-status" style="background: rgba(239, 68, 68, 0.15); color: var(--jb-danger);">Sin Reparación</span>
                        </div>
                    </div>
                    <div class="mockup-float right">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <i class="fas fa-chart-line" style="color:var(--jb-cyan);"></i>
                            <span style="font-size:0.8rem;font-weight:600;color:white;">Ventas Hoy</span>
                        </div>
                        <div style="font-size:1.5rem;font-weight:800;color:white;">$45.200</div>
                        <div style="font-size:0.75rem;color:var(--jb-success);"><i class="fas fa-arrow-up"></i> +12% vs ayer</div>
                    </div>
                    <div class="mockup-float bottom">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <i class="fas fa-box" style="color:var(--jb-celeste);"></i>
                            <span style="font-size:0.8rem;font-weight:600;color:white;">Stock Bajo</span>
                        </div>
                        <div style="font-size:0.85rem;color:var(--jb-gris);">3 repuestos por reponer</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="features" id="funciones">
        <div class="section-header">
            <span class="section-tag">Funciones</span>
            <h2 class="section-title">Todo lo que necesitás en un solo lugar</h2>
            <p class="section-subtitle">Olvidate de las planillas, los papeles y el caos. FullTaller organiza tu taller de principio a fin.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-clipboard-list"></i></div>
                <h3 class="feature-title">Órdenes de Reparación</h3>
                <p class="feature-desc">Seguimiento completo desde el ingreso hasta la entrega. Estados configurables, historial de cambios, impresión de comprobantes y chat interno entre recepción y técnico.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-cash-register"></i></div>
                <h3 class="feature-title">Punto de Venta</h3>
                <p class="feature-desc">Facturación rápida para ventas de accesorios, repuestos y servicios. Compatible con impresoras térmicas y generación de comprobantes en PDF.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-boxes-stacked"></i></div>
                <h3 class="feature-title">Inventario Inteligente</h3>
                <p class="feature-desc">Control de stock de repuestos, alertas de reposición, registro de ingresos y retiros. Ideal para aprovechar componentes de equipos abandonados.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-users"></i></div>
                <h3 class="feature-title">Gestión de Clientes</h3>
                <p class="feature-desc">Base de datos de clientes con historial de reparaciones, contacto directo por WhatsApp y seguimiento de garantías.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
                <h3 class="feature-title">Reportes y Estadísticas</h3>
                <p class="feature-desc">Visualizá el rendimiento de tu taller: órdenes por estado, ingresos mensuales, técnicos más productivos y productos más vendidos.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                <h3 class="feature-title">100% Responsive</h3>
                <p class="feature-desc">Accedé desde tu celular, tablet o computadora. El técnico puede actualizar estados desde el banco de trabajo y la recepción desde el mostrador.</p>
            </div>
        </div>
    </section>

    <!-- MODULES -->
    <section class="modules" id="modulos">
        <div class="section-header">
            <span class="section-tag">Módulos</span>
            <h2 class="section-title">Tres pilares, una solución</h2>
            <p class="section-subtitle">FullTaller integra los tres aspectos críticos de tu negocio en tiempo real.</p>
        </div>
        <div class="modules-grid">
            <div class="module-item">
                <div class="module-icon"><i class="fas fa-clipboard-check"></i></div>
                <h3 class="module-title">Gestión de Órdenes</h3>
                <p class="module-desc">Ingreso, diagnóstico, presupuesto, aprobación, reparación y entrega. Cada paso registrado y trazable.</p>
            </div>
            <div class="module-item">
                <div class="module-icon"><i class="fas fa-shopping-cart"></i></div>
                <h3 class="module-title">Punto de Venta</h3>
                <p class="module-desc">Ventas de mostrador, accesorios, repuestos y servicios. Caja diaria, cierres y reportes de ventas.</p>
            </div>
            <div class="module-item">
                <div class="module-icon"><i class="fas fa-warehouse"></i></div>
                <h3 class="module-title">Inventario</h3>
                <p class="module-desc">Stock de repuestos, productos en venta, alertas automáticas y trazabilidad de cada movimiento.</p>
            </div>
        </div>
    </section>

    <!-- PRICING -->
    <section class="pricing" id="precios">
        <div class="section-header">
            <span class="section-tag">Precios</span>
            <h2 class="section-title">Accesible para cualquier taller</h2>
            <p class="section-subtitle">Un solo plan, todo incluido. Sin sorpresas, sin límites ocultos.</p>
        </div>
        <div class="pricing-card">
            <div class="pricing-badge">PLAN FULLTALLER</div>
            <div class="pricing-amount">$25.000<span>ARS</span></div>
            <div class="pricing-period">por mes · sin contrato de permanencia</div>
            <ul class="pricing-features">
                <li><i class="fas fa-check"></i> Órdenes de reparación ilimitadas</li>
                <li><i class="fas fa-check"></i> Punto de venta integrado</li>
                <li><i class="fas fa-check"></i> Inventario de repuestos</li>
                <li><i class="fas fa-check"></i> Gestión de clientes</li>
                <li><i class="fas fa-check"></i> Usuarios ilimitados (recepción, técnico, admin)</li>
                <li><i class="fas fa-check"></i> Impresión de comprobantes</li>
                <li><i class="fas fa-check"></i> Notificaciones en tiempo real</li>
                <li><i class="fas fa-check"></i> Modo oscuro</li>
                <li><i class="fas fa-check"></i> Soporte por WhatsApp</li>
                <li><i class="fas fa-check"></i> Actualizaciones incluidas</li>
            </ul>
            <div class="pricing-note">
                <i class="fas fa-info-circle"></i> Precio en pesos argentinos. Se factura mensualmente.
            </div>
            <a href="#contacto" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;position:relative;z-index:1;">
                <i class="fas fa-rocket"></i> Contratar Ahora
            </a>
        </div>

        <div class="trial-banner">
            <div class="trial-banner-icon"><i class="fas fa-gift"></i></div>
            <div class="trial-banner-text">
                <h3>Probá FullTaller gratis por 14 días</h3>
                <p>Sin tarjeta de crédito, sin compromiso. Configuramos tu taller y empezás a usarlo inmediatamente. Si no te convence, no pagás nada.</p>
            </div>
            <a href="#contacto" class="btn btn-primary" style="flex-shrink:0;">
                <i class="fas fa-arrow-right"></i> Solicitar Prueba
            </a>
        </div>
    </section>

    <!-- CONTACT -->
    <section class="contact" id="contacto">
        <div class="section-header">
            <span class="section-tag">Contacto</span>
            <h2 class="section-title">Hablemos de tu taller</h2>
            <p class="section-subtitle">¿Tenés preguntas? ¿Querés agendar una demo? Escribime y te respondo en minutos.</p>
        </div>
        <div class="contact-grid">
            <div class="contact-info">
                <h3>¿Por qué elegir FullTaller?</h3>
                <p>Soy desarrollador independiente especializado en soluciones para talleres de servicio técnico. FullTaller nació de la necesidad real de un taller de electrónica y crece con el feedback de cada cliente. No es un software genérico: está pensado para cómo trabajás vos.</p>
                <div class="contact-links">
                    <a href="mailto:danipiw@gmail.com" class="contact-link">
                        <div class="contact-link-icon"><i class="fas fa-envelope"></i></div>
                        <div class="contact-link-text">
                            <div>Email</div>
                            <div>danipiw@gmail.com</div>
                        </div>
                    </a>
                    <a href="https://wa.me/542915347732" target="_blank" class="contact-link">
                        <div class="contact-link-icon" style="background: rgba(37, 211, 102, 0.1); color: #25d366;"><i class="fab fa-whatsapp"></i></div>
                        <div class="contact-link-text">
                            <div>WhatsApp</div>
                            <div>+54 291 534 7732</div>
                        </div>
                    </a>
                </div>
            </div>
            <form class="contact-form" id="contactForm" method="POST">
                <input type="hidden" name="form_sent" value="1">
                <div class="form-group">
                    <label for="name">Nombre</label>
                    <input type="text" id="name" name="name" placeholder="Tu nombre" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="tu@email.com" required>
                </div>
                <div class="form-group">
                    <label for="taller">Nombre del Taller</label>
                    <input type="text" id="taller" name="taller" placeholder="Ej: Tecnología Express">
                </div>
                <div class="form-group">
                    <label for="interes">Me interesa...</label>
                    <select id="interes" name="interes" required>
                        <option value="">Seleccionar...</option>
                        <option value="prueba">Prueba gratuita de 14 días</option>
                        <option value="demo">Agendar una demo personalizada</option>
                        <option value="info">Más información sobre el sistema</option>
                        <option value="otro">Otro consulta</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="message">Mensaje</label>
                    <textarea id="message" name="message" placeholder="Contame sobre tu taller, cuántos técnicos tenés, qué problemas querés resolver..."></textarea>
                </div>
                <button type="submit" class="form-submit">
                    <i class="fas fa-paper-plane"></i> Enviar Mensaje
                </button>
            </form>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <a href="#" class="footer-logo">
            <div class="logo-icon" style="width:36px;height:36px;font-size:1rem;"><i class="fas fa-tools"></i></div>
            FullTaller
        </a>
        <p class="footer-text">Sistema de gestión para talleres de servicio técnico con punto de venta e inventario.</p>
        <p class="footer-copy">© 2026 FullTaller. Todos los derechos reservados. Hecho con <i class="fas fa-heart" style="color: var(--jb-cyan);"></i> en Argentina.</p>
    </footer>

    <!-- TOAST -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i> ¡Mensaje enviado! Te contacto pronto.
    </div>

    <!-- SCROLL TOP -->
    <button class="scroll-top" id="scrollTop"><i class="fas fa-arrow-up"></i></button>

    <script>
        // Navbar scroll
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        // Mobile menu
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');
        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    navLinks.classList.remove('active');
                }
            });
        });

        // Scroll top
        const scrollTopBtn = document.getElementById('scrollTop');
        window.addEventListener('scroll', () => {
            scrollTopBtn.classList.toggle('visible', window.scrollY > 500);
        });
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Form - send via fetch (fallback to regular submit)
        const contactForm = document.getElementById('contactForm');
        const toast = document.getElementById('toast');
        const formMsg = '<?php echo $form_msg; ?>';

        if (formMsg === 'ok') {
            toast.innerHTML = '<i class="fas fa-check-circle"></i> ¡Mensaje enviado! Te contacto pronto.';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 4000);
        } else if (formMsg === 'error') {
            toast.innerHTML = '<i class="fas fa-times-circle"></i> Error al enviar. Escribime directo a danipiw@gmail.com';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 6000);
        }

        contactForm.addEventListener('submit', function(e) {
            if (formMsg) return; // already submitted via POST
            e.preventDefault();
            const formData = new FormData(this);
            fetch('index.php', { method: 'POST', body: formData })
                .then(r => r.text())
                .then(() => {
                    toast.classList.add('show');
                    contactForm.reset();
                    setTimeout(() => toast.classList.remove('show'), 4000);
                })
                .catch(() => {
                    // fallback: regular submit
                    e.target.submit();
                });
        });

        // Intersection Observer for fade-in
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.feature-card, .module-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>
