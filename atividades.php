<?php 
include 'includes/header.php'; 
include 'config.php';

$mes_atual = date('Y-m');
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        a.vagas_mes,
        COUNT(i.id) as inscritos_mes,
        GREATEST(a.vagas_mes - COUNT(i.id), 0) as vagas_disponiveis
    FROM atividades a
    LEFT JOIN inscricoes i ON i.atividade_id = a.id 
        AND DATE_FORMAT(i.data_inscricao, '%Y-%m') = ?
    GROUP BY a.id
    ORDER BY a.ordem ASC
");
$stmt->execute([$mes_atual]);
$atividades_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.icone-atividades {
    height: 1em; width: auto; vertical-align: middle; margin-bottom: 0.15em;
    filter: invert(31%) sepia(60%) saturate(400%) hue-rotate(90deg) brightness(80%) contrast(90%);
}
.btn-ler-mais-inline:hover { color: #1a6b3a !important; }

/* Animações entrada */
@keyframes titleSlide {
    0%   { opacity: 0; transform: translateX(-80px); }
    70%  { opacity: 1; transform: translateX(15px); }
    100% { opacity: 1; transform: translateX(0); }
}
@keyframes faqSpawn {
    0%   { opacity: 0; transform: translateY(18px); }
    100% { opacity: 1; transform: translateY(0); }
}
.anim-titulo { opacity: 0; }
.anim-titulo.play {
    animation: titleSlide 0.65s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}
.faq-row-item { opacity: 0; }
.faq-row-item.play {
    animation: faqSpawn 0.35s ease forwards;
}
</style>

<div class="container my-5" style="padding-top: 110px; max-width: 1300px;">

    <!-- Título -->
    <div class="mb-5 anim-titulo">
        <h2 class="display-5 fw-bold text-success">
            <span data-pt="Atividades Disponíveis" data-en="Available Activities">Atividades Disponíveis</span>
        </h2>
    </div>

    <!-- Layout 2 colunas: cards | FAQs -->
    <div class="row g-5 align-items-start">

        <!-- Coluna esquerda: cards -->
        <div class="col-lg-7">
            <div class="row g-4">
            <?php foreach ($atividades_lista as $atividade):
                if ($atividade['nome'] === 'Playgroups') continue;
                $vagas = (int) $atividade['vagas_disponiveis'];
                if ($vagas === 0) {
                    $badge_class = 'bg-danger';
                    $badge_texto_pt = 'Sem vagas disponíveis';
                    $badge_texto_en = 'No spots available';
                    $btn_disabled = 'disabled';
                } elseif ($vagas <= 3) {
                    $badge_class = 'bg-warning text-dark';
                    $badge_texto_pt = "Apenas $vagas vaga" . ($vagas > 1 ? 's' : '') . " disponível este mês";
                    $badge_texto_en = "Only $vagas spot" . ($vagas > 1 ? 's' : '') . " available this month";
                    $btn_disabled = '';
                } else {
                    $badge_class = 'bg-success';
                    $badge_texto_pt = "$vagas vagas disponíveis este mês";
                    $badge_texto_en = "$vagas spots available this month";
                    $btn_disabled = '';
                }
            ?>
            <div class="col-md-6">
                <div class="card shadow-lg border-0">
                    <?php if ($atividade['imagem']): ?>
                        <img src="img/<?php echo htmlspecialchars($atividade['imagem']); ?>"
                             class="card-img-top"
                             alt="<?php echo htmlspecialchars($atividade['nome']); ?>"
                             style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-success fw-bold"
                            data-pt="<?= htmlspecialchars($atividade['nome']) ?>"
                            data-en="<?= htmlspecialchars($atividade['nome_en'] ?? $atividade['nome']) ?>">
                            <?= htmlspecialchars($atividade['nome']) ?>
                        </h5>
                        <span class="badge <?php echo $badge_class; ?> mb-2 text-start px-3 py-2"
                              <?php if (in_array($atividade['nome'], ['Aniversários', 'A Escola vai à Tribo Verde'])) echo 'style="display:none"'; ?>
                              data-pt="<?php echo $badge_texto_pt; ?>"
                              data-en="<?php echo $badge_texto_en; ?>">
                            <i class="fas fa-users me-1"></i>
                            <?php echo $badge_texto_pt; ?>
                        </span>
                        <p class="card-text flex-grow-1">
                            <span data-pt="<?= htmlspecialchars($atividade['descricao']) ?>"
                                  data-en="<?= htmlspecialchars($atividade['descricao_en'] ?? $atividade['descricao']) ?>">
                                <?= nl2br(htmlspecialchars($atividade['descricao'])) ?>
                            </span>
                            <?php if (!empty($atividade['descricao_completa'])): ?>
                                <span class="text-success fw-semibold ms-1 btn-ler-mais-inline"
                                      style="cursor:pointer; text-decoration:underline; text-underline-offset:3px;"
                                      data-ativ-id="<?= $atividade['id'] ?>"
                                      data-ativ-nome="<?= htmlspecialchars($atividade['nome']) ?>"
                                      data-ativ-descricao="<?= htmlspecialchars($atividade['descricao_completa']) ?>"
                                      data-pt="ler mais" data-en="read more">
                                    ler mais
                                </span>
                            <?php endif; ?>
                        </p>
                        <button class="btn btn-outline-success w-100 mb-2 btn-mais-info"
                                data-ativ-id="<?php echo $atividade['id']; ?>"
                                data-ativ-nome="<?php echo htmlspecialchars($atividade['nome']); ?>"
                                data-pt="Receber mais informações"
                                data-en="Receive more information">
                            <i class="fas fa-envelope me-2"></i>
                            <span data-pt="Receber mais informações" data-en="Receive more information">Receber mais informações</span>
                        </button>
                        <a href="contacto.php?atividade_id=<?php echo $atividade['id']; ?>"
                           class="btn btn-success mt-auto <?php echo $btn_disabled; ?>"
                           data-pt="<?php echo $vagas === 0 ? 'Sem vagas' : ($atividade['nome'] === 'Aniversários' ? 'Reservar Data' : 'Inscrever-se'); ?>"
                           data-en="<?php echo $vagas === 0 ? 'No spots' : ($atividade['nome'] === 'Aniversários' ? 'Reserve Date' : 'Sign up'); ?>"
                           style="transition: none; transform: none;">
                            <i class="fas fa-user-plus me-1"></i>
                            <?php echo $vagas === 0 ? 'Sem vagas' : ($atividade['nome'] === 'Aniversários' ? 'Reservar Data' : 'Inscrever-se'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>

        <!-- Coluna direita: FAQs -->
        <div class="col-lg-5">
            <h3 class="fw-bold mb-4" style="color:#2b3319; font-size:1.5rem; margin-top: 80px;">
                <span data-pt="Perguntas Frequentes" data-en="Frequently Asked Questions">Perguntas Frequentes</span>
            </h3>

            <?php
            $faqs = [
                ['pt'=>'O que é uma Forest School?','en'=>'What is a Forest School?','resp_pt'=>'Uma Forest School é uma abordagem educativa com origem na Escandinávia, onde as crianças aprendem e brincam regularmente em contacto directo com a natureza. O foco não são os conteúdos académicos, mas o desenvolvimento da confiança, criatividade, autonomia e resiliência.','resp_en'=>'A Forest School is an educational approach from Scandinavia, where children regularly learn and play in direct contact with nature. The focus is not on academic content but on developing confidence, creativity, autonomy and resilience.'],
                ['pt'=>'É igual a Waldorf, Montessori ou ao MEM?','en'=>'Is it the same as Waldorf, Montessori or MEM?','resp_pt'=>'Não, embora partilhe valores com essas pedagogias. A Forest School é uma abordagem própria, com princípios definidos internacionalmente, centrada na natureza como ambiente de aprendizagem e não num método de sala de aula.','resp_en'=>'No, although it shares values with those pedagogies. Forest School is its own approach, with internationally defined principles, centred on nature as a learning environment.'],
                ['pt'=>'Porquê o nome em inglês? Está na moda?','en'=>'Why the English name? Is it just a trend?','resp_pt'=>'O termo vem do Reino Unido, onde a abordagem foi sistematizada e internacionalmente reconhecida. Não é moda — é uma metodologia com décadas de investigação sobre os benefícios para o desenvolvimento infantil.','resp_en'=>'The term comes from the UK, where the approach was systematised and internationally recognised. It\'s not a trend — it\'s a methodology with decades of research on child development benefits.'],
                ['pt'=>'As crianças andam à chuva e ficam sujas?','en'=>'Do children go out in the rain and get dirty?','resp_pt'=>'Sim, e é exactamente isso que as faz crescer! Lama, chuva, folhas, paus e pedras fazem parte da experiência. A sujidade é sinal de exploração e aprendizagem. Pedimos apenas roupa adequada e fácil de lavar.','resp_en'=>'Yes, and that\'s exactly what helps them grow! Mud, rain, leaves, sticks and stones are all part of the experience. Getting dirty is a sign of exploration and learning.'],
                ['pt'=>'Como é a alimentação?','en'=>'What about food?','resp_pt'=>'Valorizamos uma alimentação natural, sem processados e sem açúcar adicionado. As famílias trazem a marmita de casa com snacks e almoço simples e nutritivos. Partilhamos as refeições em grupo, ao ar livre.','resp_en'=>'We value natural food, free from processed ingredients and added sugar. Families bring a lunchbox from home. We share meals as a group outdoors.'],
                ['pt'=>'Estar tanto tempo ao ar livre não vai fazer-lhes mal à saúde?','en'=>'Won\'t so much time outdoors be bad for their health?','resp_pt'=>'A investigação científica mostra o contrário. O contacto regular com a natureza fortalece o sistema imunitário, reduz o stress e melhora a saúde mental e física. As crianças que brincam ao ar livre tendem a ficar menos doentes.','resp_en'=>'Scientific research shows the opposite. Regular contact with nature strengthens the immune system, reduces stress and improves mental and physical health.'],
                ['pt'=>'Como é que os meus filhos se vão adaptar ao ensino tradicional?','en'=>'How will my children adapt to traditional schooling?','resp_pt'=>'Melhor do que imagina. As crianças que passam tempo em Forest School chegam ao ensino formal com maior autonomia, concentração, autoestima e competências sociais. A transição tende a ser tranquila.','resp_en'=>'Better than you think. Children from Forest School enter formal education with greater autonomy, concentration, self-esteem and social skills. The transition tends to be smooth.'],
                ['pt'=>'O que se aprende numa Forest School?','en'=>'What do children learn at a Forest School?','resp_pt'=>'Aprende-se tudo — mas de dentro para fora. Matemática na contagem de bolotas, ciências na observação de insectos, linguagem na partilha de histórias. Aprende-se a gerir o risco, resolver problemas e trabalhar em equipa.','resp_en'=>'Everything is learned — but from the inside out. Maths through counting acorns, science through observing insects, language through sharing stories. Children learn to manage risk and work as a team.'],
                ['pt'=>'Que tipo de brinquedos usam?','en'=>'What kind of toys do they use?','resp_pt'=>'A natureza é o brinquedo. Paus, pedras, folhas, lama, água e terra são os materiais preferidos. Não há plásticos nem ecrãs — há imaginação, criatividade e contacto real com o mundo.','resp_en'=>'Nature is the toy. Sticks, stones, leaves, mud, water and earth are the favourite materials. No plastic or screens — just imagination and creativity.'],
                ['pt'=>'Estão divididos por idades?','en'=>'Are they split by age?','resp_pt'=>'Na Tribo Verde trabalhamos em grupos mistos de idades. Esta mistura é intencional — as crianças mais velhas aprendem a liderar e cuidar, as mais novas aprendem por observação e imitação.','resp_en'=>'At Tribo Verde we work in mixed-age groups. This mix is intentional — older children learn to lead, younger ones learn through observation and imitation.'],
                ['pt'=>'Que formação têm os profissionais?','en'=>'What training do the professionals have?','resp_pt'=>'A equipa tem formação certificada em Forest School (Forest School Leader), experiência em educação de infância, primeiros socorros e gestão do risco em ambiente natural.','resp_en'=>'The team has certified Forest School training (Forest School Leader), experience in early childhood education, first aid and risk management in natural environments.'],
                ['pt'=>'De que tipo de roupa precisam?','en'=>'What clothing do they need?','resp_pt'=>'Roupa confortável que possa ficar suja, botas de borracha ou sapatos impermeáveis e, em dias frios, casaco impermeável com roupa quente por baixo. Funcional é a palavra.','resp_en'=>'Comfortable clothes that can get dirty, rubber boots or waterproof shoes, and on cold days, a waterproof jacket with warm layers. Functional is the word.'],
                ['pt'=>'Acreditam em vacinas?','en'=>'Do you believe in vaccines?','resp_pt'=>'Sim, acreditamos na ciência. O plano nacional de vacinação é seguido por todas as crianças da Tribo Verde. A saúde do grupo é uma responsabilidade colectiva.','resp_en'=>'Yes, we believe in science. The national vaccination plan is followed by all children at Tribo Verde. Group health is a collective responsibility.'],
            ];
            foreach ($faqs as $i => $faq): ?>
            <div class="faq-row-item" style="border-top:1px solid #ddd; cursor:pointer;" onclick="toggleFaq(<?= $i ?>)">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; padding:18px 0;">
                    <span style="font-weight:600; color:#2b3319; font-size:0.95rem; line-height:1.4;"
                          data-pt="<?= htmlspecialchars($faq['pt']) ?>"
                          data-en="<?= htmlspecialchars($faq['en']) ?>">
                        <?= htmlspecialchars($faq['pt']) ?>
                    </span>
                    <span id="seta-<?= $i ?>" style="color:#aaa; font-size:1rem; flex-shrink:0; transition:transform 0.3s, color 0.3s; display:inline-block;">↓</span>
                </div>
                <div id="resp-<?= $i ?>" style="max-height:0; overflow:hidden; transition:max-height 0.4s ease;">
                    <p style="margin:0 0 18px 0; color:#555; line-height:1.75; font-size:0.92rem;"
                       data-pt="<?= htmlspecialchars($faq['resp_pt']) ?>"
                       data-en="<?= htmlspecialchars($faq['resp_en']) ?>">
                        <?= htmlspecialchars($faq['resp_pt']) ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="border-top:1px solid #ddd;"></div>
        </div>

    </div><!-- fim row -->
</div><!-- fim container -->

<!-- Modal Ler Mais -->
<div class="modal fade" id="modalLerMais" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white border-0" style="background:linear-gradient(135deg,#4a8c65,#3a7055);border-radius:12px 12px 0 0;">
                <h5 class="modal-title"><i class="fas fa-leaf me-2"></i><span id="modalLerMaisNome"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="white-space:pre-line;line-height:1.8;">
                <p id="modalLerMaisTexto" class="mb-0" style="color:#212529;"></p>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal"><span data-pt="Fechar" data-en="Close">Fechar</span></button>
                <a href="#" id="btnReservarLerMais" class="btn btn-success px-4">
                    <i class="fas fa-calendar-check me-2"></i><span data-pt="Reservar Data" data-en="Reserve Date">Reservar Data</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mais Informações -->
<div class="modal fade" id="modalMaisInfo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white border-0" style="background:linear-gradient(135deg,#4a8c65,#3a7055);border-radius:12px 12px 0 0;">
                <h5 class="modal-title"><i class="fas fa-envelope me-2"></i><span data-pt="Receber mais informações" data-en="Receive more information">Receber mais informações</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-3">
                    <span data-pt="Vamos enviar informações detalhadas sobre" data-en="We will send detailed information about">Vamos enviar informações detalhadas sobre</span>
                    <strong id="modalAtivNome"></strong>
                    <span data-pt="para o teu email." data-en="to your email.">para o teu email.</span>
                </p>
                <div id="modalAlerta" class="alert d-none"></div>
                <div class="mb-3">
                    <label class="form-label fw-bold" data-pt="O teu email" data-en="Your email">O teu email</label>
                    <input type="email" id="inputEmailInfo" class="form-control form-control-lg" placeholder="exemplo@email.com">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal"><span data-pt="Cancelar" data-en="Cancel">Cancelar</span></button>
                <button type="button" class="btn btn-success px-4" id="btnEnviarInfo">
                    <i class="fas fa-paper-plane me-2"></i><span data-pt="Enviar informações" data-en="Send information">Enviar informações</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFaq(i) {
    const resp  = document.getElementById('resp-' + i);
    const seta  = document.getElementById('seta-' + i);
    const aberto = resp.style.maxHeight && resp.style.maxHeight !== '0px';
    document.querySelectorAll('[id^="resp-"]').forEach((el, idx) => {
        el.style.maxHeight = '0px';
        const s = document.getElementById('seta-' + idx);
        if (s) { s.style.transform = 'rotate(0deg)'; s.style.color = '#aaa'; }
    });
    if (!aberto) {
        resp.style.maxHeight = '400px';
        seta.style.transform = 'rotate(180deg)';
        seta.style.color = '#4a8c65';
    }
}

let ativIdSelecionada = null;
function getLang() { return localStorage.getItem('lang') || 'pt'; }
const i18n = {
    pt: { enviando:'<i class="fas fa-spinner fa-spin me-2"></i>A enviar...', sucesso:'✅ Email enviado com sucesso!', erro:'❌ Erro ao enviar.', erroLigacao:'❌ Erro de ligação.', btnEnviar:'<i class="fas fa-paper-plane me-2"></i>Enviar informações' },
    en: { enviando:'<i class="fas fa-spinner fa-spin me-2"></i>Sending...', sucesso:'✅ Email sent successfully!', erro:'❌ Error sending.', erroLigacao:'❌ Connection error.', btnEnviar:'<i class="fas fa-paper-plane me-2"></i>Send information' }
};

document.querySelectorAll('.btn-ler-mais-inline').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('modalLerMaisNome').textContent = this.dataset.ativNome;
        document.getElementById('modalLerMaisTexto').textContent = this.dataset.ativDescricao;
        document.getElementById('btnReservarLerMais').href = 'contacto.php?atividade_id=' + this.dataset.ativId;
        new bootstrap.Modal(document.getElementById('modalLerMais')).show();
    });
});

document.querySelectorAll('.btn-mais-info').forEach(btn => {
    btn.addEventListener('click', function () {
        ativIdSelecionada = this.dataset.ativId;
        document.getElementById('modalAtivNome').textContent = this.dataset.ativNome;
        document.getElementById('inputEmailInfo').value = '';
        document.getElementById('modalAlerta').className = 'alert d-none';
        const b = document.getElementById('btnEnviarInfo');
        b.disabled = false; b.innerHTML = i18n[getLang()].btnEnviar; b.style.display = '';
        new bootstrap.Modal(document.getElementById('modalMaisInfo')).show();
    });
});

document.getElementById('btnEnviarInfo').addEventListener('click', function () {
    const email = document.getElementById('inputEmailInfo').value.trim();
    const alerta = document.getElementById('modalAlerta');
    const btn = this;
    const lang = getLang();
    if (!email) {
        alerta.className = 'alert alert-warning';
        alerta.innerHTML = lang === 'en' ? '⚠️ <strong>Email required.</strong>' : '⚠️ <strong>Email obrigatório.</strong>';
        return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alerta.className = 'alert alert-warning';
        alerta.innerHTML = lang === 'en' ? '⚠️ Invalid email.' : '⚠️ Email inválido.';
        return;
    }
    btn.disabled = true; btn.innerHTML = i18n[lang].enviando;
    const fd = new FormData();
    fd.append('email', email); fd.append('atividade_id', ativIdSelecionada);
    fetch('envia_info_atividade.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) { alerta.className = 'alert alert-success'; alerta.textContent = i18n[lang].sucesso; btn.style.display = 'none'; }
            else { alerta.className = 'alert alert-danger'; alerta.textContent = i18n[lang].erro; btn.disabled = false; btn.innerHTML = i18n[lang].btnEnviar; }
        })
        .catch(() => { alerta.className = 'alert alert-danger'; alerta.textContent = i18n[lang].erroLigacao; btn.disabled = false; btn.innerHTML = i18n[lang].btnEnviar; });
});

// ── Animações — só na primeira visita da sessão ──────────────
if (!sessionStorage.getItem('anim_atividades')) {
    sessionStorage.setItem('anim_atividades', '1');

    // Título desliza da esquerda com overshoot
    const titulo = document.querySelector('.anim-titulo');
    if (titulo) setTimeout(() => titulo.classList.add('play'), 100);

    // FAQs em cascata uma a uma
    document.querySelectorAll('.faq-row-item').forEach((el, i) => {
        setTimeout(() => el.classList.add('play'), 500 + i * 70);
    });
} else {
    // Visitas seguintes: mostra tudo imediatamente sem animação
    document.querySelector('.anim-titulo')?.classList.add('play');
    document.querySelectorAll('.faq-row-item').forEach(el => el.classList.add('play'));
}
</script>

<?php include 'includes/footer.php'; ?>