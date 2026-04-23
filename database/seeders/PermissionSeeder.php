<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Módulo Membros
        $membersModule = [
            'module' => 'Membros',
            'name' => 'Módulo Membros',
            'children' => [
                // Ver Todos (Listagem de Membros)
                [
                    'name' => 'Ver Todos',
                    'key' => 'members.index.manage',
                    'description' => 'Permissões para acessar e gerenciar a listagem de membros',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'members.index.view'],
                        ['name' => 'Criar', 'key' => 'members.index.create'],
                        ['name' => 'Editar', 'key' => 'members.index.edit'],
                        ['name' => 'Remover', 'key' => 'members.index.delete'],
                    ],
                ],
                // Cargos
                [
                    'name' => 'Cargos',
                    'key' => 'members.roles.manage',
                    'description' => 'Permissões para gerenciar cargos de membros',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'members.roles.view'],
                        ['name' => 'Criar', 'key' => 'members.roles.create'],
                        ['name' => 'Editar', 'key' => 'members.roles.edit'],
                        ['name' => 'Remover', 'key' => 'members.roles.delete'],
                    ],
                ],
            ],
        ];

        // Criar módulo principal
        $modulePermission = Permission::updateOrCreate(
            ['key' => 'members.module'],
            [
                'module' => $membersModule['module'],
                'name' => $membersModule['name'],
                'description' => 'Permissões relacionadas ao módulo de membros',
                'parent_id' => null,
            ]
        );

        // Lista de keys válidas para manter
        $validKeys = ['members.module'];
        
        // Criar permissões hierárquicas (grupos e ações)
        foreach ($membersModule['children'] as $group) {
            $validKeys[] = $group['key'];
            
            // Criar o grupo de permissão (ex: "Ver Todos")
            $groupPermission = Permission::updateOrCreate(
                ['key' => $group['key']],
                [
                    'module' => $membersModule['module'],
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'parent_id' => $modulePermission->id,
                ]
            );

            // Criar as ações filhas (Ver, Editar, Adicionar, Remover)
            if (isset($group['actions'])) {
                foreach ($group['actions'] as $action) {
                    $validKeys[] = $action['key'];
                    Permission::updateOrCreate(
                        ['key' => $action['key']],
                        [
                            'module' => $membersModule['module'],
                            'name' => $action['name'],
                            'description' => $group['name'] . ' - ' . $action['name'],
                            'parent_id' => $groupPermission->id,
                        ]
                    );
                }
            }
        }
        
        // Remover permissões antigas do módulo Membros que não estão mais na lista
        Permission::where('module', 'Membros')
            ->whereNotIn('key', $validKeys)
            ->delete();

        // Módulo PGIs
        $pgisModule = [
            'module' => 'PGIs',
            'name' => 'Módulo PGIs',
            'children' => [
                // Ver Todos (Listagem de PGIs)
                [
                    'name' => 'Ver Todos',
                    'key' => 'pgis.index.manage',
                    'description' => 'Permissões para acessar e gerenciar a listagem de PGIs',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'pgis.index.view'],
                        ['name' => 'Criar', 'key' => 'pgis.index.create'],
                        ['name' => 'Editar', 'key' => 'pgis.index.edit'],
                        ['name' => 'Remover', 'key' => 'pgis.index.delete'],
                    ],
                ],
            ],
        ];

        // Criar módulo principal
        $pgisModulePermission = Permission::updateOrCreate(
            ['key' => 'pgis.module'],
            [
                'module' => $pgisModule['module'],
                'name' => $pgisModule['name'],
                'description' => 'Permissões relacionadas ao módulo de PGIs',
                'parent_id' => null,
            ]
        );

        // Lista de keys válidas para manter
        $pgisValidKeys = ['pgis.module'];
        
        // Criar permissões hierárquicas (grupos e ações)
        foreach ($pgisModule['children'] as $group) {
            $pgisValidKeys[] = $group['key'];
            
            // Criar o grupo de permissão
            $groupPermission = Permission::updateOrCreate(
                ['key' => $group['key']],
                [
                    'module' => $pgisModule['module'],
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'parent_id' => $pgisModulePermission->id,
                ]
            );

            // Criar as ações filhas (Ver, Editar, Criar, Remover)
            if (isset($group['actions'])) {
                foreach ($group['actions'] as $action) {
                    $pgisValidKeys[] = $action['key'];
                    Permission::updateOrCreate(
                        ['key' => $action['key']],
                        [
                            'module' => $pgisModule['module'],
                            'name' => $action['name'],
                            'description' => $group['name'] . ' - ' . $action['name'],
                            'parent_id' => $groupPermission->id,
                        ]
                    );
                }
            }
        }
        
        // Remover permissões antigas do módulo PGIs que não estão mais na lista
        Permission::where('module', 'PGIs')
            ->whereNotIn('key', $pgisValidKeys)
            ->delete();

        // Módulo Ensino
        $ensinoModule = [
            'module' => 'Ensino',
            'name' => 'Módulo Ensino',
            'children' => [
                // Estudos
                [
                    'name' => 'Estudos',
                    'key' => 'ensino.estudos.manage',
                    'description' => 'Permissões para gerenciar estudos',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'ensino.estudos.view'],
                        ['name' => 'Criar', 'key' => 'ensino.estudos.create'],
                        ['name' => 'Editar', 'key' => 'ensino.estudos.edit'],
                        ['name' => 'Remover', 'key' => 'ensino.estudos.delete'],
                    ],
                ],
                // Escolas
                [
                    'name' => 'Escolas',
                    'key' => 'ensino.escolas.manage',
                    'description' => 'Permissões para gerenciar escolas',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'ensino.escolas.view'],
                        ['name' => 'Criar', 'key' => 'ensino.escolas.create'],
                        ['name' => 'Editar', 'key' => 'ensino.escolas.edit'],
                        ['name' => 'Remover', 'key' => 'ensino.escolas.delete'],
                    ],
                ],
                // Turmas
                [
                    'name' => 'Turmas',
                    'key' => 'ensino.turmas.manage',
                    'description' => 'Permissões para gerenciar turmas',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'ensino.turmas.view'],
                        ['name' => 'Criar', 'key' => 'ensino.turmas.create'],
                        ['name' => 'Editar', 'key' => 'ensino.turmas.edit'],
                        ['name' => 'Remover', 'key' => 'ensino.turmas.delete'],
                    ],
                ],
            ],
        ];

        // Criar módulo principal
        $ensinoModulePermission = Permission::updateOrCreate(
            ['key' => 'ensino.module'],
            [
                'module' => $ensinoModule['module'],
                'name' => $ensinoModule['name'],
                'description' => 'Permissões relacionadas ao módulo de ensino',
                'parent_id' => null,
            ]
        );

        // Lista de keys válidas para manter
        $ensinoValidKeys = ['ensino.module'];
        
        // Criar permissões hierárquicas (grupos e ações)
        foreach ($ensinoModule['children'] as $group) {
            $ensinoValidKeys[] = $group['key'];
            
            // Criar o grupo de permissão
            $groupPermission = Permission::updateOrCreate(
                ['key' => $group['key']],
                [
                    'module' => $ensinoModule['module'],
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'parent_id' => $ensinoModulePermission->id,
                ]
            );

            // Criar as ações filhas (Ver, Criar, Editar, Remover)
            if (isset($group['actions'])) {
                foreach ($group['actions'] as $action) {
                    $ensinoValidKeys[] = $action['key'];
                    Permission::updateOrCreate(
                        ['key' => $action['key']],
                        [
                            'module' => $ensinoModule['module'],
                            'name' => $action['name'],
                            'description' => $group['name'] . ' - ' . $action['name'],
                            'parent_id' => $groupPermission->id,
                        ]
                    );
                }
            }
        }
        
        // Remover permissões antigas do módulo Ensino que não estão mais na lista
        Permission::where('module', 'Ensino')
            ->whereNotIn('key', $ensinoValidKeys)
            ->delete();

        // Módulo Financeiro
        $financialModule = [
            'module' => 'Financeiro',
            'name' => 'Módulo Financeiro',
            'children' => [
                // Receitas
                [
                    'name' => 'Receitas',
                    'key' => 'financial.receitas.manage',
                    'description' => 'Permissões para gerenciar receitas',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'financial.receitas.view'],
                        ['name' => 'Adicionar', 'key' => 'financial.receitas.create'],
                        ['name' => 'Editar', 'key' => 'financial.receitas.edit'],
                        ['name' => 'Excluir', 'key' => 'financial.receitas.delete'],
                    ],
                ],
                // Despesas
                [
                    'name' => 'Despesas',
                    'key' => 'financial.despesas.manage',
                    'description' => 'Permissões para gerenciar despesas',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'financial.despesas.view'],
                        ['name' => 'Adicionar', 'key' => 'financial.despesas.create'],
                        ['name' => 'Editar', 'key' => 'financial.despesas.edit'],
                        ['name' => 'Excluir', 'key' => 'financial.despesas.delete'],
                    ],
                ],
                // Categorias
                [
                    'name' => 'Categorias',
                    'key' => 'financial.categories.manage',
                    'description' => 'Permissões para gerenciar categorias financeiras',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'financial.categories.view'],
                        ['name' => 'Adicionar', 'key' => 'financial.categories.create'],
                        ['name' => 'Editar', 'key' => 'financial.categories.edit'],
                        ['name' => 'Excluir', 'key' => 'financial.categories.delete'],
                    ],
                ],
                // Contas
                [
                    'name' => 'Contas',
                    'key' => 'financial.accounts.manage',
                    'description' => 'Permissões para gerenciar contas financeiras',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'financial.accounts.view'],
                        ['name' => 'Adicionar', 'key' => 'financial.accounts.create'],
                        ['name' => 'Editar', 'key' => 'financial.accounts.edit'],
                        ['name' => 'Excluir', 'key' => 'financial.accounts.delete'],
                    ],
                ],
                // Contatos
                [
                    'name' => 'Contatos',
                    'key' => 'financial.contacts.manage',
                    'description' => 'Permissões para gerenciar contatos financeiros',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'financial.contacts.view'],
                        ['name' => 'Adicionar', 'key' => 'financial.contacts.create'],
                        ['name' => 'Editar', 'key' => 'financial.contacts.edit'],
                        ['name' => 'Excluir', 'key' => 'financial.contacts.delete'],
                    ],
                ],
                // Centro de custo
                [
                    'name' => 'Centro de custo',
                    'key' => 'financial.cost-centers.manage',
                    'description' => 'Permissões para gerenciar centros de custo',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'financial.cost-centers.view'],
                        ['name' => 'Adicionar', 'key' => 'financial.cost-centers.create'],
                        ['name' => 'Editar', 'key' => 'financial.cost-centers.edit'],
                        ['name' => 'Excluir', 'key' => 'financial.cost-centers.delete'],
                    ],
                ],
                // Relatórios
                [
                    'name' => 'Relatórios',
                    'key' => 'financial.reports.manage',
                    'description' => 'Permissões para visualizar relatórios financeiros',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'financial.reports.view'],
                    ],
                ],
            ],
        ];

        // Criar módulo principal
        $financialModulePermission = Permission::updateOrCreate(
            ['key' => 'financial.module'],
            [
                'module' => $financialModule['module'],
                'name' => $financialModule['name'],
                'description' => 'Permissões relacionadas ao módulo financeiro',
                'parent_id' => null,
            ]
        );

        // Lista de keys válidas para manter
        $financialValidKeys = ['financial.module'];
        
        // Criar permissões hierárquicas (grupos e ações)
        foreach ($financialModule['children'] as $group) {
            $financialValidKeys[] = $group['key'];
            
            // Criar o grupo de permissão
            $groupPermission = Permission::updateOrCreate(
                ['key' => $group['key']],
                [
                    'module' => $financialModule['module'],
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'parent_id' => $financialModulePermission->id,
                ]
            );

            // Criar as ações filhas (Ver, Adicionar, Editar, Excluir)
            if (isset($group['actions'])) {
                foreach ($group['actions'] as $action) {
                    $financialValidKeys[] = $action['key'];
                    Permission::updateOrCreate(
                        ['key' => $action['key']],
                        [
                            'module' => $financialModule['module'],
                            'name' => $action['name'],
                            'description' => $group['name'] . ' - ' . $action['name'],
                            'parent_id' => $groupPermission->id,
                        ]
                    );
                }
            }
        }
        
        // Remover permissões antigas do módulo Financeiro que não estão mais na lista
        Permission::where('module', 'Financeiro')
            ->whereNotIn('key', $financialValidKeys)
            ->delete();

        // Módulo Agenda
        $agendaModule = [
            'module' => 'Agenda',
            'name' => 'Módulo Agenda',
            'children' => [
                // Calendários (Eventos)
                [
                    'name' => 'Calendários',
                    'key' => 'agenda.events.manage',
                    'description' => 'Permissões para gerenciar eventos no calendário',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'agenda.events.view'],
                        ['name' => 'Adicionar', 'key' => 'agenda.events.create'],
                        ['name' => 'Editar', 'key' => 'agenda.events.edit'],
                        ['name' => 'Excluir', 'key' => 'agenda.events.delete'],
                    ],
                ],
                // Categorias
                [
                    'name' => 'Categorias',
                    'key' => 'agenda.categories.manage',
                    'description' => 'Permissões para gerenciar categorias de eventos',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'agenda.categories.view'],
                        ['name' => 'Adicionar', 'key' => 'agenda.categories.create'],
                        ['name' => 'Editar', 'key' => 'agenda.categories.edit'],
                        ['name' => 'Excluir', 'key' => 'agenda.categories.delete'],
                    ],
                ],
            ],
        ];

        // Criar módulo principal
        $agendaModulePermission = Permission::updateOrCreate(
            ['key' => 'agenda.module'],
            [
                'module' => $agendaModule['module'],
                'name' => $agendaModule['name'],
                'description' => 'Permissões relacionadas ao módulo de agenda',
                'parent_id' => null,
            ]
        );

        // Lista de keys válidas para manter
        $agendaValidKeys = ['agenda.module'];
        
        // Criar permissões hierárquicas (grupos e ações)
        foreach ($agendaModule['children'] as $group) {
            $agendaValidKeys[] = $group['key'];
            
            // Criar o grupo de permissão
            $groupPermission = Permission::updateOrCreate(
                ['key' => $group['key']],
                [
                    'module' => $agendaModule['module'],
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'parent_id' => $agendaModulePermission->id,
                ]
            );

            // Criar as ações filhas (Ver, Adicionar, Editar, Excluir)
            if (isset($group['actions'])) {
                foreach ($group['actions'] as $action) {
                    $agendaValidKeys[] = $action['key'];
                    Permission::updateOrCreate(
                        ['key' => $action['key']],
                        [
                            'module' => $agendaModule['module'],
                            'name' => $action['name'],
                            'description' => $group['name'] . ' - ' . $action['name'],
                            'parent_id' => $groupPermission->id,
                        ]
                    );
                }
            }
        }
        
        // Remover permissões antigas do módulo Agenda que não estão mais na lista
        Permission::where('module', 'Agenda')
            ->whereNotIn('key', $agendaValidKeys)
            ->delete();

        // Módulo Serviço
        $servicoModule = [
            'module' => 'Serviço',
            'name' => 'Módulo Serviço',
            'children' => [
                // Departamentos
                [
                    'name' => 'Departamentos',
                    'key' => 'servico.departments.manage',
                    'description' => 'Permissões para gerenciar departamentos',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'servico.departments.view'],
                        ['name' => 'Criar', 'key' => 'servico.departments.create'],
                        ['name' => 'Editar', 'key' => 'servico.departments.edit'],
                        ['name' => 'Excluir', 'key' => 'servico.departments.delete'],
                    ],
                ],
                // Cadastro de Voluntários
                [
                    'name' => 'Cadastro de Voluntários',
                    'key' => 'servico.voluntarios.cadastro.manage',
                    'description' => 'Permissões para gerenciar cadastro de voluntários',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'servico.voluntarios.cadastro.view'],
                        ['name' => 'Criar', 'key' => 'servico.voluntarios.cadastro.create'],
                        ['name' => 'Editar', 'key' => 'servico.voluntarios.cadastro.edit'],
                        ['name' => 'Excluir', 'key' => 'servico.voluntarios.cadastro.delete'],
                    ],
                ],
                // Áreas de Serviço
                [
                    'name' => 'Áreas de Serviço',
                    'key' => 'servico.voluntarios.areas.manage',
                    'description' => 'Permissões para gerenciar áreas de serviço',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'servico.voluntarios.areas.view'],
                        ['name' => 'Criar', 'key' => 'servico.voluntarios.areas.create'],
                        ['name' => 'Editar', 'key' => 'servico.voluntarios.areas.edit'],
                        ['name' => 'Excluir', 'key' => 'servico.voluntarios.areas.delete'],
                    ],
                ],
                // Disponibilidade
                [
                    'name' => 'Disponibilidade',
                    'key' => 'servico.voluntarios.disponibilidade.manage',
                    'description' => 'Permissões para gerenciar disponibilidade de voluntários',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'servico.voluntarios.disponibilidade.view'],
                        ['name' => 'Criar', 'key' => 'servico.voluntarios.disponibilidade.create'],
                        ['name' => 'Editar', 'key' => 'servico.voluntarios.disponibilidade.edit'],
                        ['name' => 'Excluir', 'key' => 'servico.voluntarios.disponibilidade.delete'],
                    ],
                ],
                // Escalas
                [
                    'name' => 'Escalas',
                    'key' => 'servico.voluntarios.escalas.manage',
                    'description' => 'Permissões para gerenciar escalas de voluntários',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'servico.voluntarios.escalas.view'],
                        ['name' => 'Criar', 'key' => 'servico.voluntarios.escalas.create'],
                        ['name' => 'Editar', 'key' => 'servico.voluntarios.escalas.edit'],
                        ['name' => 'Excluir', 'key' => 'servico.voluntarios.escalas.delete'],
                    ],
                ],
                // Histórico de Serviço
                [
                    'name' => 'Histórico de Serviço',
                    'key' => 'servico.voluntarios.historico.manage',
                    'description' => 'Permissões para visualizar histórico de serviço',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'servico.voluntarios.historico.view'],
                    ],
                ],
                // Relatórios
                [
                    'name' => 'Relatórios',
                    'key' => 'servico.voluntarios.relatorios.manage',
                    'description' => 'Permissões para visualizar relatórios de voluntários',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'servico.voluntarios.relatorios.view'],
                    ],
                ],
            ],
        ];

        // Criar módulo principal
        $servicoModulePermission = Permission::updateOrCreate(
            ['key' => 'servico.module'],
            [
                'module' => $servicoModule['module'],
                'name' => $servicoModule['name'],
                'description' => 'Permissões relacionadas ao módulo de serviço',
                'parent_id' => null,
            ]
        );

        // Lista de keys válidas para manter
        $servicoValidKeys = ['servico.module'];
        
        // Criar permissões hierárquicas (grupos e ações)
        foreach ($servicoModule['children'] as $group) {
            $servicoValidKeys[] = $group['key'];
            
            // Criar o grupo de permissão
            $groupPermission = Permission::updateOrCreate(
                ['key' => $group['key']],
                [
                    'module' => $servicoModule['module'],
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'parent_id' => $servicoModulePermission->id,
                ]
            );

            // Criar as ações filhas (Ver, Criar, Editar, Excluir)
            if (isset($group['actions'])) {
                foreach ($group['actions'] as $action) {
                    $servicoValidKeys[] = $action['key'];
                    Permission::updateOrCreate(
                        ['key' => $action['key']],
                        [
                            'module' => $servicoModule['module'],
                            'name' => $action['name'],
                            'description' => $group['name'] . ' - ' . $action['name'],
                            'parent_id' => $groupPermission->id,
                        ]
                    );
                }
            }
        }
        
        // Remover permissões antigas do módulo Serviço que não estão mais na lista
        Permission::where('module', 'Serviço')
            ->whereNotIn('key', $servicoValidKeys)
            ->delete();

        // Módulo Notificações (WhatsApp: grupos, enquetes, painel, configuração, templates)
        $notificacoesModule = [
            'module' => 'Notificações',
            'name' => 'Módulo Notificações',
            'children' => [
                [
                    'name' => 'Notificações',
                    'key' => 'notificacoes.manage',
                    'description' => 'Permissões para gerenciar notificações via WhatsApp',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'notificacoes.view'],
                        ['name' => 'Gerenciar', 'key' => 'notificacoes.manage'],
                    ],
                ],
            ],
        ];

        $notificacoesModulePermission = Permission::updateOrCreate(
            ['key' => 'notificacoes.module'],
            [
                'module' => $notificacoesModule['module'],
                'name' => $notificacoesModule['name'],
                'description' => 'Permissões relacionadas ao módulo de notificações (WhatsApp)',
                'parent_id' => null,
            ]
        );

        $notificacoesValidKeys = ['notificacoes.module'];
        foreach ($notificacoesModule['children'] as $group) {
            $notificacoesValidKeys[] = $group['key'];
            $groupPermission = Permission::updateOrCreate(
                ['key' => $group['key']],
                [
                    'module' => $notificacoesModule['module'],
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'parent_id' => $notificacoesModulePermission->id,
                ]
            );
            if (isset($group['actions'])) {
                foreach ($group['actions'] as $action) {
                    $notificacoesValidKeys[] = $action['key'];
                    Permission::updateOrCreate(
                        ['key' => $action['key']],
                        [
                            'module' => $notificacoesModule['module'],
                            'name' => $action['name'],
                            'description' => $group['name'] . ' - ' . $action['name'],
                            'parent_id' => $groupPermission->id,
                        ]
                    );
                }
            }
        }
        Permission::where('module', 'Notificações')
            ->whereNotIn('key', $notificacoesValidKeys)
            ->delete();

        // Módulo Discipulado
        $discipleshipModule = [
            'module' => 'Discipulado',
            'name' => 'Módulo Discipulado',
            'children' => [
                // Visão Geral / Acesso ao módulo
                [
                    'name' => 'Visão Geral',
                    'key' => 'discipleship.manage',
                    'description' => 'Permissões gerais do módulo de discipulado (dashboards e visão geral)',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'discipleship.view'],
                        ['name' => 'Gerenciar', 'key' => 'discipleship.manage'],
                    ],
                ],
                // Ciclos
                [
                    'name' => 'Ciclos',
                    'key' => 'discipleship.cycles.manage',
                    'description' => 'Permissões para gerenciar ciclos de discipulado',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'discipleship.cycles.view'],
                        ['name' => 'Criar', 'key' => 'discipleship.cycles.create'],
                        ['name' => 'Editar', 'key' => 'discipleship.cycles.edit'],
                        ['name' => 'Remover', 'key' => 'discipleship.cycles.delete'],
                    ],
                ],
                // Membros
                [
                    'name' => 'Membros',
                    'key' => 'discipleship.members.manage',
                    'description' => 'Permissões para gerenciar membros vinculados ao discipulado',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'discipleship.members.view'],
                        ['name' => 'Criar', 'key' => 'discipleship.members.create'],
                        ['name' => 'Editar', 'key' => 'discipleship.members.edit'],
                        ['name' => 'Remover', 'key' => 'discipleship.members.delete'],
                    ],
                ],
                // Encontros
                [
                    'name' => 'Encontros',
                    'key' => 'discipleship.meetings.manage',
                    'description' => 'Permissões para gerenciar encontros de discipulado',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'discipleship.meetings.view'],
                        ['name' => 'Criar', 'key' => 'discipleship.meetings.create'],
                        ['name' => 'Editar', 'key' => 'discipleship.meetings.edit'],
                        ['name' => 'Remover', 'key' => 'discipleship.meetings.delete'],
                    ],
                ],
                // Indicadores
                [
                    'name' => 'Indicadores',
                    'key' => 'discipleship.indicators.manage',
                    'description' => 'Permissões para gerenciar indicadores de discipulado',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'discipleship.indicators.view'],
                        ['name' => 'Criar', 'key' => 'discipleship.indicators.create'],
                        ['name' => 'Editar', 'key' => 'discipleship.indicators.edit'],
                        ['name' => 'Remover', 'key' => 'discipleship.indicators.delete'],
                    ],
                ],
                // Propósitos / Metas
                [
                    'name' => 'Propósitos/Metas',
                    'key' => 'discipleship.goals.manage',
                    'description' => 'Permissões para gerenciar propósitos e metas de discipulado',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'discipleship.goals.view'],
                        ['name' => 'Criar', 'key' => 'discipleship.goals.create'],
                        ['name' => 'Editar', 'key' => 'discipleship.goals.edit'],
                        ['name' => 'Remover', 'key' => 'discipleship.goals.delete'],
                    ],
                ],
                // Feedbacks
                [
                    'name' => 'Feedbacks',
                    'key' => 'discipleship.feedbacks.manage',
                    'description' => 'Permissões para gerenciar feedbacks de discipulado',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'discipleship.feedbacks.view'],
                        ['name' => 'Criar', 'key' => 'discipleship.feedbacks.create'],
                        ['name' => 'Editar', 'key' => 'discipleship.feedbacks.edit'],
                        ['name' => 'Remover', 'key' => 'discipleship.feedbacks.delete'],
                    ],
                ],
            ],
        ];

        // Criar módulo principal de Discipulado
        $discipleshipModulePermission = Permission::updateOrCreate(
            ['key' => 'discipleship.module'],
            [
                'module' => $discipleshipModule['module'],
                'name' => $discipleshipModule['name'],
                'description' => 'Permissões relacionadas ao módulo de discipulado',
                'parent_id' => null,
            ]
        );

        // Lista de keys válidas para manter (Discipulado)
        $discipleshipValidKeys = ['discipleship.module'];

        // Criar permissões hierárquicas (grupos e ações) do módulo Discipulado
        foreach ($discipleshipModule['children'] as $group) {
            $discipleshipValidKeys[] = $group['key'];

            // Criar o grupo de permissão
            $groupPermission = Permission::updateOrCreate(
                ['key' => $group['key']],
                [
                    'module' => $discipleshipModule['module'],
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'parent_id' => $discipleshipModulePermission->id,
                ]
            );

            // Criar as ações filhas (Ver, Criar, Editar, Remover, etc.)
            if (isset($group['actions'])) {
                foreach ($group['actions'] as $action) {
                    $discipleshipValidKeys[] = $action['key'];
                    Permission::updateOrCreate(
                        ['key' => $action['key']],
                        [
                            'module' => $discipleshipModule['module'],
                            'name' => $action['name'],
                            'description' => $group['name'] . ' - ' . $action['name'],
                            'parent_id' => $groupPermission->id,
                        ]
                    );
                }
            }
        }

        // Remover permissões antigas do módulo Discipulado que não estão mais na lista
        Permission::where('module', 'Discipulado')
            ->whereNotIn('key', $discipleshipValidKeys)
            ->delete();

        // Módulo Rifas
        $rifasModule = [
            'module' => 'Rifas',
            'name' => 'Módulo Rifas',
            'children' => [
                [
                    'name' => 'Cadastro de Rifas',
                    'key' => 'rifas.index.manage',
                    'description' => 'Permissões para criar e gerenciar rifas',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'rifas.index.view'],
                        ['name' => 'Criar', 'key' => 'rifas.index.create'],
                        ['name' => 'Editar', 'key' => 'rifas.index.edit'],
                        ['name' => 'Excluir', 'key' => 'rifas.index.delete'],
                    ],
                ],
                [
                    'name' => 'Vendas de Rifas',
                    'key' => 'rifas.sales.manage',
                    'description' => 'Permissões para registrar e cancelar vendas de números',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'rifas.sales.view'],
                        ['name' => 'Registrar', 'key' => 'rifas.sales.create'],
                    ],
                ],
                [
                    'name' => 'Relatórios de Rifas',
                    'key' => 'rifas.reports.manage',
                    'description' => 'Permissões para visualizar relatórios de rifas',
                    'actions' => [
                        ['name' => 'Ver', 'key' => 'rifas.reports.view'],
                    ],
                ],
            ],
        ];

        $rifasModulePermission = Permission::updateOrCreate(
            ['key' => 'rifas.module'],
            [
                'module' => $rifasModule['module'],
                'name' => $rifasModule['name'],
                'description' => 'Permissões relacionadas ao módulo de rifas',
                'parent_id' => null,
            ]
        );

        $rifasValidKeys = ['rifas.module'];
        foreach ($rifasModule['children'] as $group) {
            $rifasValidKeys[] = $group['key'];

            $groupPermission = Permission::updateOrCreate(
                ['key' => $group['key']],
                [
                    'module' => $rifasModule['module'],
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'parent_id' => $rifasModulePermission->id,
                ]
            );

            foreach ($group['actions'] as $action) {
                $rifasValidKeys[] = $action['key'];
                Permission::updateOrCreate(
                    ['key' => $action['key']],
                    [
                        'module' => $rifasModule['module'],
                        'name' => $action['name'],
                        'description' => $group['name'] . ' - ' . $action['name'],
                        'parent_id' => $groupPermission->id,
                    ]
                );
            }
        }

        Permission::where('module', 'Rifas')
            ->whereNotIn('key', $rifasValidKeys)
            ->delete();
    }
}

