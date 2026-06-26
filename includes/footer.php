<!-- Footer -->
<footer class="bg-dark text-white py-5">
    <div class="container">
        <div class="row justify-content-between">
            
            <!-- Coluna 1: Logo e Descrição -->
            <div class="col-md-6 mb-4">
                <h4 class="mb-1">Tribo Verde</h4>
                <p class="text-white-50 mb-3">Montessori Forest School</p>
                <p class="text-white-50 mb-1">
                    Tribo Verde, Charneca de Caparica, Setúbal, Portugal
                </p>
                <a href="https://maps.app.goo.gl/WdFczFhSmG4YocnXA" 
                   target="_blank"
                   class="text-decoration-none hover-green"
                   style="color: #7cb9e8;"
                   data-pt="📍 Ver no Maps"
                   data-en="📍 View on Maps">
                    📍 Ver no Maps
                </a>
            </div>
            
            <!-- Coluna 2: Contactos -->
            <div class="col-md-4 mb-4">
                <h5 class="mb-3"
                    data-pt="Contactos"
                    data-en="Contacts">
                    Contactos
                </h5>
                
                <!-- Instagram -->
                <a href="https://instagram.com/tribo_verde2022" 
                   target="_blank" 
                   class="text-white d-block mb-2 text-decoration-none hover-green">
                    <i class="fab fa-instagram me-2"></i>@tribo_verde2022
                </a>
                
                <!-- Email -->
                <a href="mailto:tribo.verde.2022@gmail.com" 
                   class="text-white d-block text-decoration-none hover-green">
                    <i class="fas fa-envelope me-2"></i>tribo.verde.2022@gmail.com
                </a>
            </div>
            
        </div>
        
        <!-- Linha de Copyright -->
        <hr class="border-secondary my-4">
        <div class="text-center text-white-50">
            <p class="mb-0"
               data-pt="Tribo Verde © 2026 - Todos os direitos reservados"
               data-en="Tribo Verde © 2026 - All rights reserved">
                Tribo Verde © 2026 - Todos os direitos reservados
            </p>
        </div>
    </div>
</footer>

<!-- CSS para Hover -->
<style>
.hover-green:hover {
    color: #4a8c65 !important;
    transition: all 0.3s;
}
</style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Language JS + Navbar Scroll -->
<script>
const translations = {
    nav: {
        pt: ['Início', 'Sobre Nós', 'Atividades', 'Inscrições'],
        en: ['Home', 'About Us', 'Activities', 'Sign Up']
    }
};

function aplicarTraducoes(lang) {
    document.querySelectorAll('[data-pt]').forEach(el => {
        const icon = el.querySelector('i');
        if (icon) {
            el.innerHTML = '';
            el.appendChild(icon);
            el.innerHTML += ' ' + el.getAttribute('data-' + lang);
        } else {
            el.textContent = el.getAttribute('data-' + lang);
        }
    });
    document.querySelectorAll('.nav-link[data-nav]').forEach(el => {
        const index = parseInt(el.getAttribute('data-nav'));
        const icon = el.querySelector('i').cloneNode(true);
        el.innerHTML = '';
        el.appendChild(icon);
        el.innerHTML += translations.nav[lang][index];
    });
    document.querySelectorAll('[data-placeholder-pt]').forEach(el => {
        el.placeholder = el.getAttribute('data-placeholder-' + lang);
    });
    document.querySelectorAll('option[data-pt]').forEach(el => {
        el.textContent = el.getAttribute('data-' + lang);
    });
    document.getElementById('lang-btn').innerHTML = lang === 'pt' ? '🇬🇧 EN' : '🇵🇹 PT';
}

function toggleLang() {
    const current = localStorage.getItem('lang') || 'pt';
    setLang(current === 'pt' ? 'en' : 'pt');
}

function setLang(lang) {
    localStorage.setItem('lang', lang);
    aplicarTraducoes(lang);
    if (typeof atualizarFormulario === 'function') {
        atualizarFormulario();
        aplicarTraducoes(lang);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('lang') || 'pt';
    if (saved === 'en') setLang('en');

    document.documentElement.style.visibility = '';

    document.getElementById('lang-btn').addEventListener('click', toggleLang);

    const brandText = document.getElementById('brand-text');
    const brandLogo = document.getElementById('brand-logo');

    if (!brandText || !brandLogo) return;

    const paginasComAnimacao = ['/', '/index.php', '/sobre', '/sobre.php', '/atividades', '/atividades.php'];
    const temAnimacao = paginasComAnimacao.some(p => window.location.pathname.endsWith(p));

    if (!temAnimacao) return;

    function updateNavbarBrand() {
        if (window.scrollY > 80) {
            brandText.style.opacity = '0';
            brandText.style.pointerEvents = 'none';
            brandLogo.style.opacity = '1';
            brandLogo.style.transform = 'translateY(-50%) scale(1)';
            brandLogo.style.pointerEvents = 'auto';
        } else {
            brandText.style.opacity = '1';
            brandText.style.transform = 'translateY(-50%)';
            brandText.style.pointerEvents = 'auto';
            brandLogo.style.opacity = '0';
            brandLogo.style.transform = 'translateY(-50%) scale(0.3)';
            brandLogo.style.pointerEvents = 'none';
        }
    }

    updateNavbarBrand();
    window.addEventListener('scroll', updateNavbarBrand);
});
</script>
<?php if (basename($_SERVER['PHP_SELF']) === 'contacto.php'): ?>
<?php endif; ?>
</body>
</html>