<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Alerta de ausência
    |--------------------------------------------------------------------------
    | Número de reuniões consecutivas sem presença a partir do qual o membro é
    | listado em "Precisam de atenção pastoral" na página do PGI.
    */
    'absence_alert_threshold' => 3,

    /*
    | Quantidade de reuniões consideradas ao calcular a frequência exibida ao
    | lado de cada membro e a média de presença dos indicadores.
    */
    'attendance_window' => 10,

    /*
    | Reuniões por página na listagem da página do PGI.
    */
    'meetings_per_page' => 10,

    /*
    | Quantos membros são exibidos antes do botão "Ver todos".
    */
    'members_preview' => 8,

    /*
    | Limite de reuniões criadas de uma vez pela recorrência semanal.
    */
    'recurring_limit' => 12,

    /*
    |--------------------------------------------------------------------------
    | Contexto de geocodificação
    |--------------------------------------------------------------------------
    | Sufixo acrescentado ao endereço do PGI ao consultar o Nominatim. Sem a
    | cidade e o estado, bairros como "Capão Comprido" caem em outro estado.
    */
    'geocoding_context' => 'São Sebastião, Brasília, DF, Brasil',
];
