<?php

use MapasCulturais\Utils;

/**
 * Theme Pnab: tipos de espaço conforme padrão de dados (PR-121).
 * Inclui o core e substitui apenas $items e adiciona o metadado "Especificar tipo" para Outros (2040).
 */

$pnab_items = array(
    \MapasCulturais\i::__('Tipos de Espaços e Equipamentos Culturais') => array(
        'range' => array(2000, 2040),
        'items' => array(
            2000 => array('name' => \MapasCulturais\i::__('Arena ou semi arena de apresentações')),
            2001 => array('name' => \MapasCulturais\i::__('Associação Comunitária')),
            2002 => array('name' => \MapasCulturais\i::__('Atelier')),
            2003 => array('name' => \MapasCulturais\i::__('Auditório')),
            2004 => array('name' => \MapasCulturais\i::__('Biblioteca')),
            2005 => array('name' => \MapasCulturais\i::__('Biblioteca Comunitária')),
            2006 => array('name' => \MapasCulturais\i::__('Biblioteca Parque')),
            2007 => array('name' => \MapasCulturais\i::__('Casa da Cultura')),
            2008 => array('name' => \MapasCulturais\i::__('Casa de Espetáculo')),
            2009 => array('name' => \MapasCulturais\i::__('Centro Cultural')),
            2010 => array('name' => \MapasCulturais\i::__('Centro de Convenções')),
            2011 => array('name' => \MapasCulturais\i::__('Centro de convivência')),
            2012 => array('name' => \MapasCulturais\i::__('Centro de Memória e Patrimônio')),
            2013 => array('name' => \MapasCulturais\i::__('Centro de Tradição Regional')),
            2014 => array('name' => \MapasCulturais\i::__('Cinemas, cineclubes e salas de exibição')),
            2015 => array('name' => \MapasCulturais\i::__('Cinemateca')),
            2016 => array('name' => \MapasCulturais\i::__('Circo (inclusive itinerante)')),
            2017 => array('name' => \MapasCulturais\i::__('Escola de arte e cultura')),
            2018 => array('name' => \MapasCulturais\i::__('Escola de samba')),
            2019 => array('name' => \MapasCulturais\i::__('Escola de alimentação e cultura')),
            2020 => array('name' => \MapasCulturais\i::__('Espaço de Leitura')),
            2021 => array('name' => \MapasCulturais\i::__('Espaço Multiuso')),
            2022 => array('name' => \MapasCulturais\i::__('Espaços makers')),
            2023 => array('name' => \MapasCulturais\i::__('Estúdio de audiovisual')),
            2024 => array('name' => \MapasCulturais\i::__('Estúdio de Dança')),
            2025 => array('name' => \MapasCulturais\i::__('Estúdio de Música')),
            2026 => array('name' => \MapasCulturais\i::__('FabLabs')),
            2027 => array('name' => \MapasCulturais\i::__('Galeria e espaços de exposição')),
            2028 => array('name' => \MapasCulturais\i::__('Imóvel patrimonializado')),
            2029 => array('name' => \MapasCulturais\i::__('Laboratórios de Economia Criativa')),
            2030 => array('name' => \MapasCulturais\i::__('Livraria, alfarrábio ou sebo')),
            2031 => array('name' => \MapasCulturais\i::__('Memorial')),
            2032 => array('name' => \MapasCulturais\i::__('Mercados de arte e artesanato')),
            2033 => array('name' => \MapasCulturais\i::__('Museu')),
            2034 => array('name' => \MapasCulturais\i::__('Ponto de Leitura')),
            2035 => array('name' => \MapasCulturais\i::__('Pontos e Pontões de Cultura')),
            2036 => array('name' => \MapasCulturais\i::__('Rádios comunitárias')),
            2037 => array('name' => \MapasCulturais\i::__('Sala de Concerto')),
            2038 => array('name' => \MapasCulturais\i::__('Sambódromo')),
            2039 => array('name' => \MapasCulturais\i::__('Teatro')),
            2040 => array('name' => \MapasCulturais\i::__('Outros (informar qual)')),
        ),
    ),
);

// Ordena categorias e itens (evita redeclarar ordenaSubcategorias do core)
ksort($pnab_items);
foreach ($pnab_items as &$item) {
    if (isset($item['items'])) {
        uasort($item['items'], function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
    }
}
unset($item);

// Carrega o retorno do core e substitui items + adiciona metadado "Especificar tipo" para Outros (2040)
$result = include APPLICATION_PATH . '/conf/space-types.php';
$result['items'] = $pnab_items;
$result['metadata'] = array(
    'informarQualOutroTipoDeEspaco' => array(
        'label' => \MapasCulturais\i::__('Especificar o tipo de espaço'),
        'type' => 'string',
        'validations' => array(),
        'should_validate' => function ($entity, $value) {
            $type_id = is_object($entity->type) && isset($entity->type->id)
                ? $entity->type->id
                : (int) ($entity->type ?? 0);

            if ($type_id === 2040 && (empty($value) || trim((string) $value) === '')) {
                return \MapasCulturais\i::__('O campo especificar o tipo de espaço é obrigatório.');
            }

            return false;
        },
        'available_for_opportunities' => true,
    ),
) + $result['metadata'];

return $result;
