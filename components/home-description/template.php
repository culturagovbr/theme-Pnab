<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\Pnab\Theme $this
 */

use MapasCulturais\i;

$mapa_img = $app->view->asset('img/home/home-description/mapa.svg', false);
?>

<section class="home-network">
    <div class="home-network__container">
        <div class="home-network__text">
            <h2 class="title">A Política Nacional Aldir Blanc</h2>
            <p class="description">
                A <strong>Pnab</strong> é a Política Nacional Aldir Blanc de Fomento à Cultura, instituída pela Lei nº 14.399, de 08 de julho de 2022, tem como objetivo fomentar a cultura em todos os estados, municípios e Distrito Federal. <br/><br/>

                A Aldir Blanc é uma oportunidade histórica de estruturar o sistema federativo de financiamento à cultura, mediante repasses da União aos demais entes federativos de forma continuada. <br/><br/>

                Diferente das ações da Lei Aldir Blanc 1 e da Lei Paulo Gustavo (LPG), que tinham caráter emergencial, projetos e programas que integrem a Política Nacional Aldir Blanc receberão investimentos regulares. <br/><br/>

                Os recursos da Política Nacional Aldir Blanc podem ser direcionados a editais de fomento, ou realização de ações diretas pelos entes federativos, como festejos e festas populares, aquisição de bens culturais, construção e manutenção de espaços culturais, entre outras possibilidades de ações e atividades destinadas a fomentar a cultura local. <br/><br/>

                <strong>Faça parte!</strong> Experimente a plataforma, crie ou atualize seu perfil, registre seus espaços e iniciativas artísticas e divulgue suas atividades.
            </p>
        </div>
        <div class="home-network__image">
            <img src="<?= $mapa_img ?>" alt="Mapa do Brasil por regiões" />
        </div>
    </div>
</section>
