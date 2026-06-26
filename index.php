<?php include 'includes/header.php'; ?>

<section class="hero">
    <div class="container text-center text-white">
        <div class="hero-logo-wrapper">
            <img src="/img/logo-tribo-verde.png" alt="Tribo Verde" class="hero-logo-img">
        </div>
        <p class="lead"
           style="letter-spacing: 1px;"
           data-pt="Montessori Forest School para crianças dos 3 aos 6 anos"
           data-en="Montessori Forest School for children aged 3 to 6 years">
            Montessori Forest School para crianças dos 3 aos 6 anos
        </p>
    </div>
</section>

<!-- Sobre — com vídeo de fundo -->
<section class="py-5 section-video-bg">

    <!-- Vídeo de fundo -->
    <video autoplay muted loop playsinline class="video-bg">
        <source src="https://res.cloudinary.com/dpkpea5d5/video/upload/v1782068238/video_site_tribo_cdyehw.mp4" type="video/mp4">
    </video>

    <!-- Overlay escuro -->
    <div class="video-overlay"></div>

    <!-- Conteúdo -->
    <div class="container position-relative" style="z-index:2;">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="display-4 fw-bold mb-3 text-white"
                    data-pt="O que oferecemos"
                    data-en="What we offer">
                    O que oferecemos
                </h2>
                <p class="lead mb-0" style="color:rgba(255,255,255,0.75);"
                   data-pt="Aprendizagem através da brincadeira livre na natureza"
                   data-en="Learning through free play in nature">
                    Aprendizagem através da brincadeira livre na natureza
                </p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Forest School -->
            <div class="col-md-4">
                <div class="card-glass h-100 text-center p-5">
                    <div class="icon-box mb-4">
                        <i class="fas fa-tree"></i>
                    </div>
                    <h3 class="fw-bold mb-3 text-white">Forest School</h3>
                    <p style="color:rgba(255,255,255,0.8);"
                       data-pt="Ambiente natural como sala de aula. Exploração livre e segura."
                       data-en="Natural environment as a classroom. Free and safe exploration.">
                        Ambiente natural como sala de aula. Exploração livre e segura.
                    </p>
                </div>
            </div>

            <!-- 3-6 Anos -->
            <div class="col-md-4">
                <div class="card-glass h-100 text-center p-5">
                    <div class="icon-box mb-4">
                        <i class="fas fa-child"></i>
                    </div>
                    <h3 class="fw-bold mb-3 text-white"
                        data-pt="3-6 Anos"
                        data-en="3-6 Years">
                        3-6 Anos
                    </h3>
                    <p style="color:rgba(255,255,255,0.8);"
                       data-pt="Idade perfeita para desenvolvimento sensorial e motor."
                       data-en="The perfect age for sensory and motor development.">
                        Idade perfeita para desenvolvimento sensorial e motor.
                    </p>
                </div>
            </div>

            <!-- Montessori -->
            <div class="col-md-4">
                <div class="card-glass h-100 text-center p-5">
                    <div class="icon-box mb-4">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="fw-bold mb-3 text-white">Montessori</h3>
                    <p style="color:rgba(255,255,255,0.8);"
                       data-pt="Autonomia, respeito e aprendizagem ao ritmo da criança."
                       data-en="Autonomy, respect and learning at the child's own pace.">
                        Autonomia, respeito e aprendizagem ao ritmo da criança.
                    </p>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center">
                <a href="/atividades"
                   class="btn btn-light btn-lg rounded-pill px-5 py-3"
                   style="color:#2d6a4f; font-weight:600;"
                   data-pt="Explorar Atividades"
                   data-en="Explore Activities">
                    <i class="fas fa-leaf me-2"></i>Explorar Atividades
                </a>
            </div>
        </div>
    </div>
</section>

<style>
/* ── Hero ── */
.hero {
    min-height: unset !important;
    height: auto !important;
    padding: 120px 20px 50px 20px !important;
    display: block !important;
}
.hero .container {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    gap: 16px !important;
}
.hero-logo-img { max-width: 480px !important; }
.hero .lead { font-size: 1.4rem !important; margin: 0 !important; }

/* ── Secção com vídeo ── */
.section-video-bg {
    position: relative;
    overflow: hidden;
}
.video-bg {
    position: absolute;
    top: 50%; left: 50%;
    min-width: 100%; min-height: 100%;
    width: auto; height: auto;
    transform: translate(-50%, -50%);
    object-fit: cover;
    z-index: 0;
}
.video-overlay {
    position: absolute;
    inset: 0;
    background: rgba(20, 40, 20, 0.78);
    z-index: 1;
}

/* ── Cards glassmorphism ── */
.card-glass {
    background: rgba(255, 255, 255, 0.10);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 18px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
    transition: transform 0.3s, box-shadow 0.3s, background 0.3s;
}
.card-glass:hover {
    transform: translateY(-6px);
    background: rgba(255, 255, 255, 0.16);
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.3);
}

/* ── Ícones ── */
.icon-box i {
    color: #a8d5b5;
    font-size: 3.5rem;
    transition: all 0.3s;
}
.card-glass:hover .icon-box i {
    transform: scale(1.1);
    color: #ffffff;
}

/* ── Responsive ── */
@media (max-width: 991px) {
    .hero { padding: 110px 20px 40px 20px !important; }
    .hero-logo-img { max-width: 380px !important; }
    .hero .lead { font-size: 1.2rem !important; }
}
@media (max-width: 767px) {
    .hero { padding: 100px 20px 30px 20px !important; }
    .hero .container { gap: 10px !important; }
    .hero-logo-img { max-width: 90% !important; }
    .hero .lead { font-size: 1.1rem !important; }
    .card-glass:hover { transform: none; }
}
@media (max-width: 575px) {
    .hero { padding: 95px 15px 25px 15px !important; }
    .hero .container { gap: 8px !important; }
    .hero-logo-img { max-width: 85% !important; }
    .hero .lead { font-size: 1rem !important; }
}
@media (max-width: 375px) {
    .hero { padding: 90px 10px 20px 10px !important; }
    .hero-logo-img { max-width: 80% !important; }
    .hero .lead { font-size: 0.9rem !important; }
}
</style>

<?php include 'includes/footer.php'; ?>