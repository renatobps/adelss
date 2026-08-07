<?php

namespace App\Support;

use App\Models\Pgi;

/**
 * Fonte única de dados da navegação por módulos.
 *
 * Alimenta tanto o menu lateral (layouts/porto.blade.php) quanto o bloco
 * "Acessos Rápidos" do dashboard, garantindo que ambos usem exatamente a
 * mesma checagem de permissões e nunca fiquem dessincronizados.
 */
class ModuleMenu
{
    public const CATEGORY_ADMIN = 'Administração';
    public const CATEGORY_MODULES = 'Módulos';
    public const CATEGORY_COMMUNICATION = 'Comunicação';

    /**
     * Ordem de exibição das categorias no menu lateral.
     * Para adicionar um módulo novo, basta incluí-lo em itemsFor()
     * com a 'category' desejada — o agrupamento é automático.
     */
    public const CATEGORIES = [
        self::CATEGORY_ADMIN,
        self::CATEGORY_MODULES,
        self::CATEGORY_COMMUNICATION,
    ];

    /**
     * Retorna somente os módulos visíveis para o usuário informado.
     *
     * Cada item: [
     *   'key'         => identificador do módulo,
     *   'label'       => rótulo exibido no menu lateral,
     *   'short_label' => rótulo curto para tiles compactos (opcional),
     *   'icon'        => classes do ícone,
     *   'url'         => rota de destino (já resolvida por permissão),
     *   'patterns'    => padrões de rota para estado ativo (request()->routeIs()),
     *   'category'    => categoria do menu lateral (null = solto no topo),
     * ]
     *
     * @param  \App\Models\User|null  $user
     * @return array<int, array<string, mixed>>
     */
    public static function itemsFor($user): array
    {
        $isAdmin = (bool) ($user->is_admin ?? false);
        $member = $user?->member;

        // Mesma lógica de checagem usada historicamente no menu lateral:
        // admin vê tudo; demais usuários dependem de hasPermission().
        $can = function (string ...$permissions) use ($user, $isAdmin): bool {
            if ($isAdmin) {
                return true;
            }
            if (!$user) {
                return false;
            }
            try {
                foreach ($permissions as $permission) {
                    if ($user->hasPermission($permission)) {
                        return true;
                    }
                }
            } catch (\Throwable $e) {
                return false;
            }

            return false;
        };

        $hasPgi = $member && $member->pgi_id;
        $isLeaderOfPgi = false;
        if ($member) {
            try {
                $isLeaderOfPgi = Pgi::where(function ($q) use ($member) {
                    $q->where('leader_1_id', $member->id)
                        ->orWhere('leader_2_id', $member->id)
                        ->orWhere('leader_training_1_id', $member->id)
                        ->orWhere('leader_training_2_id', $member->id);
                })->exists();
            } catch (\Throwable $e) {
                $isLeaderOfPgi = false;
            }
        }

        $items = [];

        // Início / Visão Geral — sempre visível para usuário logado
        $items[] = [
            'key' => 'dashboard',
            'label' => 'Visão Geral',
            'short_label' => 'Início',
            'icon' => 'bx bx-home-alt',
            'url' => route('dashboard'),
            'patterns' => ['dashboard'],
            'category' => null, // dashboard fica solto no topo, sem categoria
        ];

        // Página Principal
        if ($can('pagina-principal.manage')) {
            $items[] = [
                'key' => 'pagina-principal',
                'label' => 'Página Principal',
                'icon' => 'bx bx-globe',
                'url' => route('pagina-principal.edit'),
                'patterns' => ['pagina-principal.*'],
                'category' => self::CATEGORY_ADMIN,
            ];
        }

        // Membros
        $canViewMembers = $can('members.index.view', 'members.view', 'members.index.manage');
        $canManageRoles = $can(
            'members.roles.view',
            'members.roles.create',
            'members.roles.edit',
            'members.roles.delete',
            'members.roles.manage'
        );
        if ($canViewMembers || $canManageRoles) {
            $items[] = [
                'key' => 'members',
                'label' => 'Membros',
                'icon' => 'bx bx-user',
                'url' => $canViewMembers ? route('members.index') : route('member-roles.index'),
                'patterns' => ['members.*', 'member-roles.*', 'permissions.*'],
                'category' => self::CATEGORY_MODULES,
            ];
        }

        // PGIs
        if ($can('pgis.index.view', 'pgis.index.manage') || $hasPgi || $isLeaderOfPgi) {
            $items[] = [
                'key' => 'pgis',
                'label' => 'PGIs',
                'icon' => 'bx bx-group',
                'url' => route('pgis.index'),
                'patterns' => ['pgis.*'],
                'category' => self::CATEGORY_MODULES,
            ];
        }

        // Ensino — sempre visível (controle fino é feito dentro do módulo)
        $items[] = [
            'key' => 'ensino',
            'label' => 'Ensino',
            'icon' => 'bx bx-book-reader',
            'url' => route('ensino.estudos.index'),
            'patterns' => ['ensino.*'],
            'category' => self::CATEGORY_MODULES,
        ];

        // Financeiro
        $canViewReceitas = $can('financial.receitas.view', 'financial.receitas.manage');
        $canViewDespesas = $can('financial.despesas.view', 'financial.despesas.manage');
        $canViewFinCategories = $can('financial.categories.view', 'financial.categories.manage');
        $canViewAccounts = $can('financial.accounts.view', 'financial.accounts.manage');
        $canViewContacts = $can('financial.contacts.view', 'financial.contacts.manage');
        $canViewCostCenters = $can('financial.cost-centers.view', 'financial.cost-centers.manage');
        $canViewFinReports = $can('financial.reports.view', 'financial.reports.manage');
        $canViewCampanhas = $can('financial.campanhas.view', 'financial.campanhas.manage');
        if ($isAdmin || $canViewReceitas || $canViewDespesas || $canViewFinCategories || $canViewAccounts || $canViewContacts || $canViewCostCenters || $canViewFinReports || $canViewCampanhas) {
            if ($isAdmin || $canViewReceitas || $canViewDespesas) {
                $financialUrl = route('financial.summary');
            } elseif ($canViewFinReports) {
                $financialUrl = route('financial.reports.index');
            } elseif ($canViewCampanhas) {
                $financialUrl = route('financial.campaigns.index');
            } elseif ($canViewAccounts) {
                $financialUrl = route('financial.accounts.index');
            } elseif ($canViewFinCategories) {
                $financialUrl = route('financial.categories.index');
            } elseif ($canViewContacts) {
                $financialUrl = route('financial.contacts.index');
            } else {
                $financialUrl = route('financial.cost-centers.index');
            }

            $items[] = [
                'key' => 'financial',
                'label' => 'Financeiro',
                'icon' => 'bx bx-dollar',
                'url' => $financialUrl,
                'patterns' => ['financial.*'],
                'category' => self::CATEGORY_MODULES,
            ];
        }

        // Relatórios de Culto
        $canViewCultosRelatorios = $can('cultos.relatorios.view');
        $canManageCultosConfig = $can('cultos.configuracoes.manage');
        if ($isAdmin || $canViewCultosRelatorios || $canManageCultosConfig) {
            $items[] = [
                'key' => 'cultos',
                'label' => 'Relatórios de Culto',
                'short_label' => 'Cultos',
                'icon' => 'bx bx-church',
                'url' => ($isAdmin || $canViewCultosRelatorios) ? route('cultos.index') : route('cultos.settings.edit'),
                'patterns' => ['cultos.*'],
                'category' => self::CATEGORY_MODULES,
            ];
        }

        // Mídia
        $canViewMidiaArquivos = $can('midia.arquivos.view');
        $canViewMidiaInstagram = $can('midia.instagram.view');
        $canManageMidiaConfig = $can('midia.configuracoes.manage');
        $canManageMidiaInstagramConfig = $can('midia.instagram.configuracoes.manage');
        if ($isAdmin || $canViewMidiaArquivos || $canViewMidiaInstagram || $canManageMidiaConfig || $canManageMidiaInstagramConfig) {
            if ($isAdmin || $canViewMidiaArquivos) {
                $midiaUrl = route('midia.index');
            } elseif ($canViewMidiaInstagram) {
                $midiaUrl = route('midia.instagram.posts.index');
            } else {
                $midiaUrl = route('midia.settings');
            }

            $items[] = [
                'key' => 'midia',
                'label' => 'Mídia',
                'icon' => 'bx bx-images',
                'url' => $midiaUrl,
                'patterns' => ['midia.*'],
                'category' => self::CATEGORY_COMMUNICATION,
            ];
        }

        // Agenda — sempre visível
        $items[] = [
            'key' => 'agenda',
            'label' => 'Agenda',
            'icon' => 'bx bx-calendar',
            'url' => route('agenda.calendario.index'),
            'patterns' => ['agenda.*'],
            'category' => self::CATEGORY_MODULES,
        ];

        // Serviço
        $canViewDepartments = $can('servico.departments.view', 'servico.departments.manage');
        $canViewVolCadastro = $can('servico.voluntarios.cadastro.view', 'servico.voluntarios.cadastro.manage');
        $canViewVolAreas = $can('servico.voluntarios.areas.view', 'servico.voluntarios.areas.manage');
        $canViewVolEscalas = $can('servico.voluntarios.escalas.view', 'servico.voluntarios.escalas.manage');
        $canViewVolHistorico = $can('servico.voluntarios.historico.view', 'servico.voluntarios.historico.manage');
        $canViewVolRelatorios = $can('servico.voluntarios.relatorios.view', 'servico.voluntarios.relatorios.manage');
        if ($isAdmin || $canViewDepartments || $canViewVolCadastro || $canViewVolAreas || $canViewVolEscalas || $canViewVolHistorico || $canViewVolRelatorios) {
            if ($isAdmin || $canViewDepartments) {
                $servicoUrl = route('departments.index');
            } elseif ($canViewVolAreas) {
                $servicoUrl = route('voluntarios.areas.index');
            } elseif ($canViewVolEscalas) {
                $servicoUrl = route('voluntarios.escalas-mensais.index');
            } elseif ($canViewVolHistorico) {
                $servicoUrl = route('voluntarios.historico.index');
            } else {
                $servicoUrl = route('voluntarios.relatorios.dashboard');
            }

            $items[] = [
                'key' => 'servico',
                'label' => 'Serviço',
                'icon' => 'bx bx-cog',
                'url' => $servicoUrl,
                'patterns' => ['servico.*', 'departments.*', 'voluntarios.*'],
                'category' => self::CATEGORY_MODULES,
            ];
        }

        // Rifas
        $canViewRifas = $can('rifas.index.view', 'rifas.index.manage');
        $canManageRifaSales = $can('rifas.sales.create', 'rifas.sales.manage', 'rifas.index.manage');
        $canViewRifaReports = $can('rifas.reports.view', 'rifas.reports.manage');
        if ($isAdmin || $canViewRifas || $canManageRifaSales || $canViewRifaReports) {
            $items[] = [
                'key' => 'rifas',
                'label' => 'Rifas',
                'icon' => 'bx bx-grid-alt',
                'url' => ($isAdmin || $canViewRifas || $canManageRifaSales)
                    ? route('rifas.index')
                    : route('rifas.relatorios.dashboard'),
                'patterns' => ['rifas.*'],
                'category' => self::CATEGORY_MODULES,
            ];
        }

        // Moriah — sempre visível
        $items[] = [
            'key' => 'moriah',
            'label' => 'Moriah',
            'icon' => 'fa-solid fa-music',
            'url' => route('moriah.schedules.index'),
            'patterns' => ['moriah.*'],
            'category' => self::CATEGORY_MODULES,
        ];

        // Notificações
        if ($isAdmin || $can('notificacoes.view', 'notificacoes.manage')) {
            $items[] = [
                'key' => 'notificacoes',
                'label' => 'Notificações',
                'icon' => 'bx bx-bell',
                'url' => route('notificacoes.painel.index'),
                'patterns' => ['notificacoes.*'],
                'category' => self::CATEGORY_COMMUNICATION,
            ];
        }

        // Discipulado
        $canViewDiscipulado = $can('discipleship.view', 'discipleship.manage');
        $canViewCiclos = $can('discipleship.cycles.view', 'discipleship.cycles.manage');
        $canViewMembrosDisc = $can('discipleship.members.view', 'discipleship.members.manage');
        $canViewEncontros = $can('discipleship.meetings.view', 'discipleship.meetings.manage');
        $canViewIndicadores = $can('discipleship.indicators.view', 'discipleship.indicators.manage');
        $canViewPropositos = $can('discipleship.goals.view', 'discipleship.goals.manage');
        $canViewFeedbacks = $can('discipleship.feedbacks.view', 'discipleship.feedbacks.manage');
        if ($canViewDiscipulado || $canViewCiclos || $canViewMembrosDisc || $canViewEncontros || $canViewIndicadores || $canViewPropositos || $canViewFeedbacks) {
            if ($canViewCiclos) {
                $discipuladoUrl = route('discipleship.cycles.index');
            } elseif ($canViewMembrosDisc) {
                $discipuladoUrl = route('discipleship.members.index');
            } elseif ($canViewEncontros) {
                $discipuladoUrl = route('discipleship.meetings.index');
            } else {
                $discipuladoUrl = route('discipleship.dashboard.discipulador');
            }

            $items[] = [
                'key' => 'discipleship',
                'label' => 'Discipulado',
                'icon' => 'bx bx-group',
                'url' => $discipuladoUrl,
                'patterns' => ['discipleship.*'],
                'category' => self::CATEGORY_MODULES,
            ];
        }

        return $items;
    }
}
