<?php include 'includes/header.php'; ?>

<style>
.hero-sobre {
    background: linear-gradient(135deg, #4a5530 0%, #3a4428 50%, #2b3319 100%);
    min-height: 260px;
    display: flex;
    align-items: flex-end;
    padding: 120px 0 35px 0;
    position: relative;
    overflow: hidden;
    color: white;
}

.diagonal-layout {
    position: relative;
    min-height: 600px;
    padding: 80px 0;
}

.diagonal-connector {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.dashed-line-diagonal {
    position: absolute;
    top: 15%;
    left: 35%;
    width: 40%;
    height: 70%;
}

.dashed-line-diagonal svg {
    width: 100%;
    height: 100%;
}

.paper-plane {
    position: absolute;
    top: 45%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(45deg);
    font-size: 2.5rem;
    animation: bobPlane 2s ease-in-out infinite;
    filter: drop-shadow(0 3px 10px rgba(74, 140, 101, 0.3));
    z-index: 2;
}

.paper-plane i {
    color: #0b3d22ec;
}

@keyframes bobPlane {
    0%, 100% { transform: translate(-50%, -50%) rotate(45deg) translateY(0px); }
    50% { transform: translate(-50%, -50%) rotate(45deg) translateY(-10px); }
}

.content-box {
    position: relative;
    z-index: 3;
    background: white;
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

@media (max-width: 991px) {
    .diagonal-layout { min-height: auto; }
    .diagonal-connector { display: none; }
}
</style>

<!-- Hero Sobre -->
<section class="hero-sobre">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center text-white">
                <h1 class="display-3 fw-bold mb-4">
                    <i class="fas fa-users me-3"></i>
                    <span data-pt="Sobre Nós" data-en="About Us">Sobre Nós</span>
                </h1>
                <p class="lead fs-4"
                   data-pt="Conheça a Tribo Verde e a nossa missão"
                   data-en="Get to know Tribo Verde and our mission">
                    Conheça a Tribo Verde e a nossa missão
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Layout Diagonal -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="diagonal-layout">

            <!-- Linha tracejada diagonal com aviãozinho -->
            <div class="diagonal-connector">
                <div class="dashed-line-diagonal">
                    <svg viewBox="0 0 400 500" preserveAspectRatio="none">
                        <line 
                            x1="50" y1="50" 
                            x2="350" y2="450" 
                            stroke="#4a8c65" 
                            stroke-width="3" 
                            stroke-dasharray="15,10"
                            opacity="0.7"
                        />
                    </svg>
                </div>
                <div class="paper-plane">
                    <i class="fas fa-paper-plane"></i>
                </div>
            </div>

            <!-- Quem Somos -->
            <div class="row mb-5">
                <div class="col-lg-6">
                    <div class="content-box">
                        <h2 class="display-5 fw-bold text-success mb-4">
                            <i class="fas fa-tree me-2"></i>
                            <span data-pt="Quem Somos" data-en="Who We Are">Quem Somos</span>
                        </h2>
                        <p class="lead text-muted mb-4"
                           data-pt="A Tribo Verde é uma Forest School Montessori localizada na Charneca de Caparica, Setúbal, dedicada ao desenvolvimento integral de crianças dos 3 aos 6 anos."
                           data-en="Tribo Verde is a Montessori Forest School located in Charneca de Caparica, Setúbal, dedicated to the holistic development of children aged 3 to 6 years.">
                            A <strong>Tribo Verde</strong> é uma Forest School Montessori localizada na Charneca de Caparica, Setúbal, dedicada ao desenvolvimento integral de crianças dos 3 aos 6 anos.
                        </p>
                        <p class="text-muted mb-3"
                           data-pt="Fundada com a paixão pela educação ao ar livre e pelo respeito ao ritmo natural da criança, criamos um espaço onde a aprendizagem acontece através da exploração livre, do contacto com a natureza e do brincar espontâneo."
                           data-en="Founded with a passion for outdoor education and respect for the child's natural rhythm, we created a space where learning happens through free exploration, contact with nature and spontaneous play.">
                            Fundada com a paixão pela educação ao ar livre e pelo respeito ao ritmo natural da criança, criamos um espaço onde a aprendizagem acontece através da exploração livre, do contacto com a natureza e do brincar espontâneo.
                        </p>
                        <p class="text-muted"
                           data-pt="Cada dia na Tribo Verde é uma aventura única, onde as crianças desenvolvem autonomia, criatividade e conexão profunda com o ambiente natural que as rodeia."
                           data-en="Every day at Tribo Verde is a unique adventure, where children develop autonomy, creativity and a deep connection with the natural environment around them.">
                            Cada dia na Tribo Verde é uma aventura única, onde as crianças desenvolvem autonomia, criatividade e conexão profunda com o ambiente natural que as rodeia.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Nossa Missão -->
            <div class="row">
                <div class="col-lg-6 offset-lg-6">
                    <div class="content-box">
                        <h2 class="display-5 fw-bold text-success mb-4">
                            <i class="fas fa-compass me-2"></i>
                            <span data-pt="A Nossa Missão" data-en="Our Mission">A Nossa Missão</span>
                        </h2>
                        <p class="lead text-muted mb-4"
                           data-pt="Proporcionar uma educação que respeita o ritmo da criança, promove a autonomia e fortalece a conexão com a natureza."
                           data-en="To provide an education that respects the child's pace, promotes autonomy and strengthens the connection with nature.">
                            Proporcionar uma educação que <strong>respeita o ritmo da criança</strong>, promove a <strong>autonomia</strong> e fortalece a <strong>conexão com a natureza</strong>.
                        </p>
                        
                        <div class="mb-3">
                            <h5 class="text-success fw-bold mb-2">
                                <i class="fas fa-check-circle me-2"></i>
                                <span data-pt="Educação Montessori" data-en="Montessori Education">Educação Montessori</span>
                            </h5>
                            <p class="text-muted mb-0"
                               data-pt="Seguimos os princípios Montessori de autonomia, respeito e aprendizagem ao ritmo natural de cada criança."
                               data-en="We follow Montessori principles of autonomy, respect and learning at each child's natural pace.">
                                Seguimos os princípios Montessori de autonomia, respeito e aprendizagem ao ritmo natural de cada criança.
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <h5 class="text-success fw-bold mb-2">
                                <i class="fas fa-check-circle me-2"></i>Forest School
                            </h5>
                            <p class="text-muted mb-0"
                               data-pt="O ambiente natural é a nossa sala de aula, onde as crianças exploram livremente e aprendem através da experiência."
                               data-en="The natural environment is our classroom, where children explore freely and learn through experience.">
                                O ambiente natural é a nossa sala de aula, onde as crianças exploram livremente e aprendem através da experiência.
                            </p>
                        </div>
                        
                        <div>
                            <h5 class="text-success fw-bold mb-2">
                                <i class="fas fa-check-circle me-2"></i>
                                <span data-pt="Desenvolvimento Integral" data-en="Holistic Development">Desenvolvimento Integral</span>
                            </h5>
                            <p class="text-muted mb-0"
                               data-pt="Focamos no desenvolvimento emocional, social, cognitivo e físico de forma equilibrada e harmoniosa."
                               data-en="We focus on emotional, social, cognitive and physical development in a balanced and harmonious way.">
                                Focamos no desenvolvimento emocional, social, cognitivo e físico de forma equilibrada e harmoniosa.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Final -->
<section class="py-5 bg-success text-white">
    <div class="container text-center">
        <h3 class="display-6 fw-bold mb-4"
            data-pt="Vamos explorar, aprender e crescer juntos?"
            data-en="Shall we explore, learn and grow together?">
            Vamos explorar, aprender e crescer juntos?
        </h3>
        <a href="/contacto" 
           class="btn btn-light btn-lg rounded-pill px-5 py-3"
           data-pt="Inscreva-se Agora"
           data-en="Sign Up Now">
            <i class="fas fa-user-plus me-2"></i>Inscreva-se Agora
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>