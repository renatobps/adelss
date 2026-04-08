<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Administradores têm acesso total - retornar imediatamente
        if ($user->is_admin) {
            return $next($request);
        }

        /**
         * Helper function para redirecionar com mensagem de acesso negado
         */
        $denyAccess = function($message) use ($request) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 403);
            }
            
            // Redirecionar para a página anterior ou dashboard com mensagem de erro
            $redirectUrl = $request->headers->get('referer') ?: route('dashboard');
            return redirect($redirectUrl)->with('access_denied', $message);
        };

        $member = $user->member;

        // Verificar acesso por módulo
        switch ($module) {
            case 'members':
                // Membros: verificar permissões específicas
                $routeName = $request->route()?->getName();
                $action = $request->route()?->getActionMethod();
                
                // Verificar permissões baseadas na rota
                if ($routeName === 'members.index') {
                    // Ver Todos - precisa de permissão para ver
                    if (!$user->hasPermission('members.index.view') && 
                        !$user->hasPermission('members.view') &&
                        !$user->hasPermission('members.index.manage')) {
                        return $denyAccess('Acesso negado. Você não tem permissão para visualizar membros.');
                    }
                } elseif ($routeName === 'members.create' || $action === 'create') {
                    // Novo Membro - precisa de permissão para criar (agora está em Ver Todos)
                    if (!$user->hasPermission('members.index.create') && 
                        !$user->hasPermission('members.create') &&
                        !$user->hasPermission('members.index.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para criar membros.');
                    }
                } elseif ($routeName === 'members.show' || $action === 'show') {
                    // Ver membro específico - precisa de permissão para ver OU ser o próprio perfil
                    $memberId = $request->route('member')?->id ?? $request->route('member');
                    $isOwnProfile = $member && $memberId && (int)$member->id === (int)$memberId;
                    
                    if (!$isOwnProfile && 
                        !$user->hasPermission('members.index.view') && 
                        !$user->hasPermission('members.view') &&
                        !$user->hasPermission('members.index.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar membros.');
                    }
                } elseif ($routeName === 'members.edit' || $action === 'edit') {
                    // Editar membro - precisa de permissão para editar OU ser o próprio perfil
                    $memberId = $request->route('member')?->id ?? $request->route('member');
                    $isOwnProfile = $member && $memberId && (int)$member->id === (int)$memberId;
                    
                    if (!$isOwnProfile && 
                        !$user->hasPermission('members.index.edit') && 
                        !$user->hasPermission('members.edit') &&
                        !$user->hasPermission('members.index.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para editar membros.');
                    }
                } elseif ($routeName === 'members.update' || $action === 'update') {
                    // Atualizar membro - precisa de permissão para editar OU ser o próprio perfil
                    $memberId = $request->route('member')?->id ?? $request->route('member');
                    $isOwnProfile = $member && $memberId && (int)$member->id === (int)$memberId;
                    
                    if (!$isOwnProfile && 
                        !$user->hasPermission('members.index.edit') && 
                        !$user->hasPermission('members.edit') &&
                        !$user->hasPermission('members.index.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para editar membros.');
                    }
                } elseif ($routeName === 'members.destroy' || $action === 'destroy') {
                    // Remover membro - precisa de permissão para remover
                    if (!$user->hasPermission('members.index.delete') && 
                        !$user->hasPermission('members.delete') &&
                        !$user->hasPermission('members.index.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para remover membros.');
                    }
                } elseif ($routeName === 'members.store' || $action === 'store') {
                    // Criar membro - precisa de permissão para criar (agora está em Ver Todos)
                    if (!$user->hasPermission('members.index.create') && 
                        !$user->hasPermission('members.create') &&
                        !$user->hasPermission('members.index.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para criar membros.');
                    }
                } elseif (str_starts_with($routeName ?? '', 'member-roles')) {
                    // Cargos - verificar permissões específicas
                    if ($action === 'index' || $action === 'show') {
                        if (!$user->hasPermission('members.roles.view') && 
                            !$user->hasPermission('members.roles.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar cargos.');
                        }
                    } elseif ($action === 'create' || $action === 'store') {
                        if (!$user->hasPermission('members.roles.create') && 
                            !$user->hasPermission('members.roles.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para criar cargos.');
                        }
                    } elseif ($action === 'edit' || $action === 'update') {
                        if (!$user->hasPermission('members.roles.edit') && 
                            !$user->hasPermission('members.roles.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar cargos.');
                        }
                    } elseif ($action === 'destroy') {
                        if (!$user->hasPermission('members.roles.delete') && 
                            !$user->hasPermission('members.roles.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para remover cargos.');
                        }
                    }
                } else {
                    // Outras rotas de membros - verificar permissão geral
                    if (!$user->hasPermission('members.view') && 
                        !$user->hasPermission('members.index.view') &&
                        !$user->hasPermission('members.index.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para acessar este módulo.');
                    }
                }
                break;

            case 'pgis':
                // PGIs: verificar permissões específicas
                $routeName = $request->route()?->getName();
                $action = $request->route()?->getActionMethod();
                
                // Verificar se faz parte de PGI ou se é líder
                $hasPgi = $member && $member->pgi_id;
                $isLeaderOfAnyPgi = false;
                if ($member) {
                    $isLeaderOfAnyPgi = \App\Models\Pgi::where(function($q) use ($member) {
                        $q->where('leader_1_id', $member->id)
                          ->orWhere('leader_2_id', $member->id)
                          ->orWhere('leader_training_1_id', $member->id)
                          ->orWhere('leader_training_2_id', $member->id);
                    })->exists();
                }
                
                // Verificar permissões baseadas na rota
                if ($routeName === 'pgis.index' || $action === 'index') {
                    // Ver Todos - precisa de permissão para ver, fazer parte de PGI ou ser líder
                    if (!$user->hasPermission('pgis.index.view') && 
                        !$user->hasPermission('pgis.index.manage') &&
                        !$hasPgi &&
                        !$isLeaderOfAnyPgi) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar PGIs.');
                    }
                } elseif ($routeName === 'pgis.show' || $action === 'show') {
                    // Ver PGI específico - precisa de permissão para ver, fazer parte deste PGI ou ser líder
                    $pgi = $request->route('pgi');
                    $pgiId = null;
                    
                    // Obter ID do PGI (pode ser modelo ou ID direto)
                    if ($pgi) {
                        $pgiId = is_object($pgi) ? $pgi->id : $pgi;
                    }
                    
                    // Se for um ID numérico, verificar se o membro faz parte deste PGI ou é líder
                    $isMemberOfThisPgi = false;
                    $isLeaderOfThisPgi = false;
                    if ($member && $pgiId) {
                        $isMemberOfThisPgi = $member->pgi_id && (int)$member->pgi_id === (int)$pgiId;
                        
                        // Verificar se é líder deste PGI específico
                        if (is_object($pgi)) {
                            $isLeaderOfThisPgi = $pgi->isLeader($member);
                        } else {
                            $pgiModel = \App\Models\Pgi::find($pgiId);
                            if ($pgiModel) {
                                $isLeaderOfThisPgi = $pgiModel->isLeader($member);
                            }
                        }
                    }
                    
                    if (!$user->hasPermission('pgis.index.view') && 
                        !$user->hasPermission('pgis.index.manage') &&
                        !$isMemberOfThisPgi &&
                        !$isLeaderOfThisPgi) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar este PGI.');
                    }
                } elseif (str_starts_with($routeName ?? '', 'pgis.meetings')) {
                    // Rotas de reuniões - líderes podem criar/editar/excluir reuniões sem permissão
                    $pgi = $request->route('pgi');
                    $pgiId = null;
                    $isLeaderOfThisPgi = false;
                    
                    // Obter ID do PGI
                    if ($pgi) {
                        $pgiId = is_object($pgi) ? $pgi->id : $pgi;
                    }
                    
                    // Verificar se é líder deste PGI específico
                    if ($member && $pgiId) {
                        if (is_object($pgi)) {
                            $isLeaderOfThisPgi = $pgi->isLeader($member);
                        } else {
                            $pgiModel = \App\Models\Pgi::find($pgiId);
                            if ($pgiModel) {
                                $isLeaderOfThisPgi = $pgiModel->isLeader($member);
                            }
                        }
                    }
                    
                    // Permitir acesso se for admin ou líder do PGI
                    if (!$user->is_admin && !$isLeaderOfThisPgi) {
                        return $denyAccess( 'Acesso negado. Apenas líderes e líderes em treinamento podem gerenciar reuniões.');
                    }
                } elseif ($routeName === 'pgis.create' || $action === 'create') {
                    // Criar PGI - precisa de permissão para criar
                    if (!$user->hasPermission('pgis.index.create') && 
                        !$user->hasPermission('pgis.index.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para criar PGIs.');
                    }
                } elseif ($routeName === 'pgis.edit' || $action === 'edit') {
                    // Editar PGI - precisa de permissão para editar
                    if (!$user->hasPermission('pgis.index.edit') && 
                        !$user->hasPermission('pgis.index.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para editar PGIs.');
                    }
                } elseif ($routeName === 'pgis.update' || $action === 'update') {
                    // Atualizar PGI - precisa de permissão para editar
                    if (!$user->hasPermission('pgis.index.edit') && 
                        !$user->hasPermission('pgis.index.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para editar PGIs.');
                    }
                } elseif ($routeName === 'pgis.destroy' || $action === 'destroy') {
                    // Remover PGI - precisa de permissão para remover
                    if (!$user->hasPermission('pgis.index.delete') && 
                        !$user->hasPermission('pgis.index.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para remover PGIs.');
                    }
                } elseif ($routeName === 'pgis.store' || $action === 'store') {
                    // Criar PGI - precisa de permissão para criar
                    if (!$user->hasPermission('pgis.index.create') && 
                        !$user->hasPermission('pgis.index.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para criar PGIs.');
                    }
                } else {
                    // Outras rotas de PGIs - verificar permissão geral, se faz parte de PGI ou se é líder
                    if (!$user->hasPermission('pgis.index.view') && 
                        !$user->hasPermission('pgis.index.manage') &&
                        !$hasPgi &&
                        !$isLeaderOfAnyPgi) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para acessar este módulo.');
                    }
                }
                break;

            case 'ensino':
                // Ensino: verificar permissões específicas
                $routeName = $request->route()?->getName();
                $action = $request->route()?->getActionMethod();
                
                // Verificar permissões baseadas na rota
                if (str_starts_with($routeName ?? '', 'ensino.estudos')) {
                    // Estudos - visualização disponível para todos
                    if ($routeName === 'ensino.estudos.index' || $action === 'index' || $routeName === 'ensino.estudos.show' || $action === 'show') {
                        // Ver - disponível para todos, sem verificação de permissão
                        // Não precisa verificar permissão
                    } elseif ($routeName === 'ensino.estudos.create' || $action === 'create' || $routeName === 'ensino.estudos.store' || $action === 'store') {
                        // Criar - precisa de permissão para criar
                        if (!$user->hasPermission('ensino.estudos.create') && 
                            !$user->hasPermission('ensino.estudos.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para criar estudos.');
                        }
                    } elseif ($routeName === 'ensino.estudos.edit' || $action === 'edit' || $routeName === 'ensino.estudos.update' || $action === 'update') {
                        // Editar - precisa de permissão para editar
                        if (!$user->hasPermission('ensino.estudos.edit') && 
                            !$user->hasPermission('ensino.estudos.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar estudos.');
                        }
                    } elseif ($routeName === 'ensino.estudos.destroy' || $action === 'destroy') {
                        // Remover - precisa de permissão para remover
                        if (!$user->hasPermission('ensino.estudos.delete') && 
                            !$user->hasPermission('ensino.estudos.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para remover estudos.');
                        }
                    }
                } elseif (str_starts_with($routeName ?? '', 'ensino.escolas')) {
                    // Escolas
                    if ($routeName === 'ensino.escolas.index' || $action === 'index' || $routeName === 'ensino.escolas.show' || $action === 'show') {
                        // Ver - precisa de permissão para ver
                        if (!$user->hasPermission('ensino.escolas.view') && 
                            !$user->hasPermission('ensino.escolas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar escolas.');
                        }
                    } elseif ($routeName === 'ensino.escolas.create' || $action === 'create' || $routeName === 'ensino.escolas.store' || $action === 'store') {
                        // Criar - precisa de permissão para criar
                        if (!$user->hasPermission('ensino.escolas.create') && 
                            !$user->hasPermission('ensino.escolas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para criar escolas.');
                        }
                    } elseif ($routeName === 'ensino.escolas.edit' || $action === 'edit' || $routeName === 'ensino.escolas.update' || $action === 'update') {
                        // Editar - precisa de permissão para editar
                        if (!$user->hasPermission('ensino.escolas.edit') && 
                            !$user->hasPermission('ensino.escolas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar escolas.');
                        }
                    } elseif ($routeName === 'ensino.escolas.destroy' || $action === 'destroy') {
                        // Remover - precisa de permissão para remover
                        if (!$user->hasPermission('ensino.escolas.delete') && 
                            !$user->hasPermission('ensino.escolas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para remover escolas.');
                        }
                    }
                } elseif (str_starts_with($routeName ?? '', 'ensino.turmas')) {
                    // Turmas
                    if ($routeName === 'ensino.turmas.index' || $action === 'index') {
                        // Ver lista - verificar permissão ou se é aluno/professor de alguma turma
                        $hasAccess = false;
                        
                        if ($user->hasPermission('ensino.turmas.view') || 
                            $user->hasPermission('ensino.turmas.manage')) {
                            $hasAccess = true;
                        } elseif ($member) {
                            // Verificar se é aluno ou professor de alguma turma
                            if ($member->turmas()->exists() || $member->isTeacherOfAnyClass()) {
                                $hasAccess = true;
                            }
                        }
                        
                        if (!$hasAccess) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar turmas.');
                        }
                    } elseif ($routeName === 'ensino.turmas.show' || $action === 'show') {
                        // Ver detalhes - verificar se é aluno ou professor da turma
                        $turma = $request->route('turma');
                        $hasAccess = false;
                        
                        if ($user->hasPermission('ensino.turmas.view') || 
                            $user->hasPermission('ensino.turmas.manage')) {
                            $hasAccess = true;
                        } elseif ($member && $turma) {
                            // Verificar se é aluno ou professor
                            if ($turma->isStudent($member) || $turma->isTeacher($member)) {
                                $hasAccess = true;
                            }
                        }
                        
                        if (!$hasAccess) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar esta turma.');
                        }
                    } elseif ($routeName === 'ensino.turmas.create' || $action === 'create' || $routeName === 'ensino.turmas.store' || $action === 'store') {
                        // Criar - precisa de permissão para criar
                        if (!$user->hasPermission('ensino.turmas.create') && 
                            !$user->hasPermission('ensino.turmas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para criar turmas.');
                        }
                    } elseif ($routeName === 'ensino.turmas.edit' || $action === 'edit' || $routeName === 'ensino.turmas.update' || $action === 'update') {
                        // Editar - precisa de permissão para editar
                        if (!$user->hasPermission('ensino.turmas.edit') && 
                            !$user->hasPermission('ensino.turmas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar turmas.');
                        }
                    } elseif ($routeName === 'ensino.turmas.destroy' || $action === 'destroy') {
                        // Remover - precisa de permissão para remover
                        if (!$user->hasPermission('ensino.turmas.delete') && 
                            !$user->hasPermission('ensino.turmas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para remover turmas.');
                        }
                    } elseif ($routeName === 'ensino.turmas.lessons.store' || $action === 'storeLesson') {
                        // Registrar aula - professores podem registrar
                        $turma = $request->route('turma');
                        $hasAccess = false;
                        
                        if ($user->hasPermission('ensino.turmas.edit') || 
                            $user->hasPermission('ensino.turmas.manage')) {
                            $hasAccess = true;
                        } elseif ($member && $turma && $turma->isTeacher($member)) {
                            $hasAccess = true;
                        }
                        
                        if (!$hasAccess) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para registrar aulas.');
                        }
                    } elseif ($routeName === 'ensino.turmas.files.store' || $action === 'storeFile') {
                        // Adicionar arquivo - professores podem adicionar
                        $turma = $request->route('turma');
                        $hasAccess = false;
                        
                        if ($user->hasPermission('ensino.turmas.edit') || 
                            $user->hasPermission('ensino.turmas.manage')) {
                            $hasAccess = true;
                        } elseif ($member && $turma && $turma->isTeacher($member)) {
                            $hasAccess = true;
                        }
                        
                        if (!$hasAccess) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para adicionar arquivos.');
                        }
                    }
                } else {
                    // Outras rotas de ensino - verificar permissão geral
                    if (!$user->hasPermission('ensino.estudos.view') && 
                        !$user->hasPermission('ensino.escolas.view') &&
                        !$user->hasPermission('ensino.turmas.view')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para acessar este módulo.');
                    }
                }
                break;

            case 'agenda':
                // Agenda: visualização disponível para todos, apenas criar/editar/excluir precisa de permissão
                $routeName = $request->route()?->getName();
                $action = $request->route()?->getActionMethod();
                
                // Verificar permissões baseadas na rota
                if (str_starts_with($routeName ?? '', 'agenda.calendario') || 
                    $routeName === 'agenda.events.index' || $action === 'index' ||
                    $routeName === 'agenda.events.show' || $action === 'show' ||
                    $routeName === 'agenda.eventos.index' || $action === 'index') {
                    // Ver calendário/eventos - disponível para todos, sem verificação de permissão
                    // Não precisa verificar permissão
                } elseif ($routeName === 'agenda.events.store' || $action === 'store') {
                    // Criar evento - precisa de permissão para criar
                    if (!$user->hasPermission('agenda.events.create') && 
                        !$user->hasPermission('agenda.events.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para adicionar eventos.');
                    }
                } elseif ($routeName === 'agenda.events.update' || $action === 'update') {
                    // Editar evento - precisa de permissão para editar
                    if (!$user->hasPermission('agenda.events.edit') && 
                        !$user->hasPermission('agenda.events.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para editar eventos.');
                    }
                } elseif ($routeName === 'agenda.events.destroy' || $action === 'destroy') {
                    // Excluir evento - precisa de permissão para excluir
                    if (!$user->hasPermission('agenda.events.delete') && 
                        !$user->hasPermission('agenda.events.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para excluir eventos.');
                    }
                } elseif (str_starts_with($routeName ?? '', 'agenda.eventos')) {
                    if (in_array($routeName, ['agenda.eventos.create', 'agenda.eventos.store', 'agenda.eventos.duplicate'], true)) {
                        if (!$user->hasPermission('agenda.events.create') &&
                            !$user->hasPermission('agenda.events.manage')) {
                            return $denyAccess('Acesso negado. Você não tem permissão para criar eventos.');
                        }
                    } elseif (in_array($routeName, ['agenda.eventos.edit', 'agenda.eventos.update'], true)) {
                        if (!$user->hasPermission('agenda.events.edit') &&
                            !$user->hasPermission('agenda.events.manage')) {
                            return $denyAccess('Acesso negado. Você não tem permissão para editar eventos.');
                        }
                    } elseif ($routeName === 'agenda.eventos.destroy') {
                        if (!$user->hasPermission('agenda.events.delete') &&
                            !$user->hasPermission('agenda.events.manage')) {
                            return $denyAccess('Acesso negado. Você não tem permissão para excluir eventos.');
                        }
                    } elseif ($routeName === 'agenda.eventos.registrations.status') {
                        if (!$user->hasPermission('agenda.events.edit') &&
                            !$user->hasPermission('agenda.events.manage')) {
                            return $denyAccess('Acesso negado. Você não tem permissão para alterar inscrições.');
                        }
                    } elseif ($routeName === 'agenda.eventos.editor-upload') {
                        if (!$user->hasPermission('agenda.events.create') &&
                            !$user->hasPermission('agenda.events.edit') &&
                            !$user->hasPermission('agenda.events.manage')) {
                            return $denyAccess('Acesso negado. Você não tem permissão para enviar imagens no editor.');
                        }
                    }
                } elseif (str_starts_with($routeName ?? '', 'agenda.categories')) {
                    // Categorias
                    if ($routeName === 'agenda.categories.index' || $action === 'index' || 
                        $routeName === 'agenda.categories.show' || $action === 'show') {
                        // Ver categorias - disponível para todos, sem verificação de permissão
                        // Não precisa verificar permissão
                    } elseif ($routeName === 'agenda.categories.store' || $action === 'store') {
                        // Criar - precisa de permissão para criar
                        if (!$user->hasPermission('agenda.categories.create') && 
                            !$user->hasPermission('agenda.categories.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para adicionar categorias.');
                        }
                    } elseif ($routeName === 'agenda.categories.update' || $action === 'update') {
                        // Editar - precisa de permissão para editar
                        if (!$user->hasPermission('agenda.categories.edit') && 
                            !$user->hasPermission('agenda.categories.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar categorias.');
                        }
                    } elseif ($routeName === 'agenda.categories.destroy' || $action === 'destroy') {
                        // Excluir - precisa de permissão para excluir
                        if (!$user->hasPermission('agenda.categories.delete') && 
                            !$user->hasPermission('agenda.categories.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para excluir categorias.');
                        }
                    }
                } else {
                    // Outras rotas de agenda - permitir acesso (visualização disponível para todos)
                    // Não precisa verificar permissão
                }
                break;

            case 'financial':
                // Financeiro: verificar permissões específicas
                $routeName = $request->route()?->getName();
                $action = $request->route()?->getActionMethod();
                
                // Verificar permissões baseadas na rota
                if (str_starts_with($routeName ?? '', 'financial.summary')) {
                    // Resumo - precisa de permissão para ver receitas ou despesas
                    if (!$user->hasPermission('financial.receitas.view') && 
                        !$user->hasPermission('financial.despesas.view') &&
                        !$user->hasPermission('financial.receitas.manage') &&
                        !$user->hasPermission('financial.despesas.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar o resumo financeiro.');
                    }
                } elseif (str_starts_with($routeName ?? '', 'financial.transactions')) {
                    // Transações (Receitas e Despesas)
                    if ($routeName === 'financial.transactions.index' || $action === 'index' || 
                        $routeName === 'financial.transactions.show' || $action === 'show') {
                        // Ver - precisa de permissão para ver receitas ou despesas
                        if (!$user->hasPermission('financial.receitas.view') && 
                            !$user->hasPermission('financial.despesas.view') &&
                            !$user->hasPermission('financial.receitas.manage') &&
                            !$user->hasPermission('financial.despesas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar transações.');
                        }
                    } elseif ($routeName === 'financial.transactions.store.receita' || $action === 'storeReceita') {
                        // Criar receita - precisa de permissão para criar receitas
                        if (!$user->hasPermission('financial.receitas.create') && 
                            !$user->hasPermission('financial.receitas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para adicionar receitas.');
                        }
                    } elseif ($routeName === 'financial.transactions.store.despesa' || $action === 'storeDespesa') {
                        // Criar despesa - precisa de permissão para criar despesas
                        if (!$user->hasPermission('financial.despesas.create') && 
                            !$user->hasPermission('financial.despesas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para adicionar despesas.');
                        }
                    } elseif ($routeName === 'financial.transactions.edit' || $action === 'edit' || 
                              $routeName === 'financial.transactions.update' || $action === 'update') {
                        // Editar - verificar se é receita ou despesa
                        $transaction = $request->route('transaction');
                        $isReceita = false;
                        if ($transaction) {
                            $transactionModel = is_object($transaction) ? $transaction : \App\Models\FinancialTransaction::find($transaction);
                            if ($transactionModel) {
                                $isReceita = $transactionModel->type === 'receita';
                            }
                        }
                        
                        if ($isReceita) {
                            if (!$user->hasPermission('financial.receitas.edit') && 
                                !$user->hasPermission('financial.receitas.manage')) {
                                return $denyAccess( 'Acesso negado. Você não tem permissão para editar receitas.');
                            }
                        } else {
                            if (!$user->hasPermission('financial.despesas.edit') && 
                                !$user->hasPermission('financial.despesas.manage')) {
                                return $denyAccess( 'Acesso negado. Você não tem permissão para editar despesas.');
                            }
                        }
                    } elseif ($routeName === 'financial.transactions.destroy' || $action === 'destroy') {
                        // Excluir - verificar se é receita ou despesa
                        $transaction = $request->route('transaction');
                        $isReceita = false;
                        if ($transaction) {
                            $transactionModel = is_object($transaction) ? $transaction : \App\Models\FinancialTransaction::find($transaction);
                            if ($transactionModel) {
                                $isReceita = $transactionModel->type === 'receita';
                            }
                        }
                        
                        if ($isReceita) {
                            if (!$user->hasPermission('financial.receitas.delete') && 
                                !$user->hasPermission('financial.receitas.manage')) {
                                return $denyAccess( 'Acesso negado. Você não tem permissão para excluir receitas.');
                            }
                        } else {
                            if (!$user->hasPermission('financial.despesas.delete') && 
                                !$user->hasPermission('financial.despesas.manage')) {
                                return $denyAccess( 'Acesso negado. Você não tem permissão para excluir despesas.');
                            }
                        }
                    }
                } elseif (str_starts_with($routeName ?? '', 'financial.reports')) {
                    // Relatórios - precisa de permissão para ver relatórios
                    if (!$user->hasPermission('financial.reports.view') && 
                        !$user->hasPermission('financial.reports.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar relatórios.');
                    }
                } elseif (str_starts_with($routeName ?? '', 'financial.categories')) {
                    // Categorias
                    if ($routeName === 'financial.categories.index' || $action === 'index' || 
                        $routeName === 'financial.categories.show' || $action === 'show') {
                        // Ver - precisa de permissão para ver
                        if (!$user->hasPermission('financial.categories.view') && 
                            !$user->hasPermission('financial.categories.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar categorias.');
                        }
                    } elseif ($routeName === 'financial.categories.create' || $action === 'create' || 
                              $routeName === 'financial.categories.store' || $action === 'store') {
                        // Criar - precisa de permissão para criar
                        if (!$user->hasPermission('financial.categories.create') && 
                            !$user->hasPermission('financial.categories.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para adicionar categorias.');
                        }
                    } elseif ($routeName === 'financial.categories.edit' || $action === 'edit' || 
                              $routeName === 'financial.categories.update' || $action === 'update') {
                        // Editar - precisa de permissão para editar
                        if (!$user->hasPermission('financial.categories.edit') && 
                            !$user->hasPermission('financial.categories.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar categorias.');
                        }
                    } elseif ($routeName === 'financial.categories.destroy' || $action === 'destroy') {
                        // Excluir - precisa de permissão para excluir
                        if (!$user->hasPermission('financial.categories.delete') && 
                            !$user->hasPermission('financial.categories.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para excluir categorias.');
                        }
                    }
                } elseif (str_starts_with($routeName ?? '', 'financial.accounts')) {
                    // Contas
                    if ($routeName === 'financial.accounts.index' || $action === 'index' || 
                        $routeName === 'financial.accounts.show' || $action === 'show') {
                        // Ver - precisa de permissão para ver
                        if (!$user->hasPermission('financial.accounts.view') && 
                            !$user->hasPermission('financial.accounts.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar contas.');
                        }
                    } elseif ($routeName === 'financial.accounts.create' || $action === 'create' || 
                              $routeName === 'financial.accounts.store' || $action === 'store') {
                        // Criar - precisa de permissão para criar
                        if (!$user->hasPermission('financial.accounts.create') && 
                            !$user->hasPermission('financial.accounts.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para adicionar contas.');
                        }
                    } elseif ($routeName === 'financial.accounts.edit' || $action === 'edit' || 
                              $routeName === 'financial.accounts.update' || $action === 'update') {
                        // Editar - precisa de permissão para editar
                        if (!$user->hasPermission('financial.accounts.edit') && 
                            !$user->hasPermission('financial.accounts.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar contas.');
                        }
                    } elseif ($routeName === 'financial.accounts.destroy' || $action === 'destroy') {
                        // Excluir - precisa de permissão para excluir
                        if (!$user->hasPermission('financial.accounts.delete') && 
                            !$user->hasPermission('financial.accounts.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para excluir contas.');
                        }
                    }
                } elseif (str_starts_with($routeName ?? '', 'financial.contacts')) {
                    // Contatos
                    if ($routeName === 'financial.contacts.index' || $action === 'index' || 
                        $routeName === 'financial.contacts.show' || $action === 'show') {
                        // Ver - precisa de permissão para ver
                        if (!$user->hasPermission('financial.contacts.view') && 
                            !$user->hasPermission('financial.contacts.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar contatos.');
                        }
                    } elseif ($routeName === 'financial.contacts.create' || $action === 'create' || 
                              $routeName === 'financial.contacts.store' || $action === 'store') {
                        // Criar - precisa de permissão para criar
                        if (!$user->hasPermission('financial.contacts.create') && 
                            !$user->hasPermission('financial.contacts.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para adicionar contatos.');
                        }
                    } elseif ($routeName === 'financial.contacts.edit' || $action === 'edit' || 
                              $routeName === 'financial.contacts.update' || $action === 'update') {
                        // Editar - precisa de permissão para editar
                        if (!$user->hasPermission('financial.contacts.edit') && 
                            !$user->hasPermission('financial.contacts.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar contatos.');
                        }
                    } elseif ($routeName === 'financial.contacts.destroy' || $action === 'destroy') {
                        // Excluir - precisa de permissão para excluir
                        if (!$user->hasPermission('financial.contacts.delete') && 
                            !$user->hasPermission('financial.contacts.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para excluir contatos.');
                        }
                    }
                } elseif (str_starts_with($routeName ?? '', 'financial.cost-centers')) {
                    // Centro de custo
                    if ($routeName === 'financial.cost-centers.index' || $action === 'index' || 
                        $routeName === 'financial.cost-centers.show' || $action === 'show') {
                        // Ver - precisa de permissão para ver
                        if (!$user->hasPermission('financial.cost-centers.view') && 
                            !$user->hasPermission('financial.cost-centers.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar centros de custo.');
                        }
                    } elseif ($routeName === 'financial.cost-centers.create' || $action === 'create' || 
                              $routeName === 'financial.cost-centers.store' || $action === 'store') {
                        // Criar - precisa de permissão para criar
                        if (!$user->hasPermission('financial.cost-centers.create') && 
                            !$user->hasPermission('financial.cost-centers.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para adicionar centros de custo.');
                        }
                    } elseif ($routeName === 'financial.cost-centers.edit' || $action === 'edit' || 
                              $routeName === 'financial.cost-centers.update' || $action === 'update') {
                        // Editar - precisa de permissão para editar
                        if (!$user->hasPermission('financial.cost-centers.edit') && 
                            !$user->hasPermission('financial.cost-centers.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar centros de custo.');
                        }
                    } elseif ($routeName === 'financial.cost-centers.destroy' || $action === 'destroy') {
                        // Excluir - precisa de permissão para excluir
                        if (!$user->hasPermission('financial.cost-centers.delete') && 
                            !$user->hasPermission('financial.cost-centers.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para excluir centros de custo.');
                        }
                    }
                } else {
                    // Outras rotas financeiras - verificar permissão geral
                    if (!$user->hasPermission('financial.receitas.view') && 
                        !$user->hasPermission('financial.despesas.view') &&
                        !$user->hasPermission('financial.categories.view') &&
                        !$user->hasPermission('financial.accounts.view') &&
                        !$user->hasPermission('financial.contacts.view') &&
                        !$user->hasPermission('financial.cost-centers.view') &&
                        !$user->hasPermission('financial.reports.view')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para acessar este módulo.');
                    }
                }
                break;

            case 'servico':
                // Serviço: verificar permissões específicas
                $routeName = $request->route()?->getName();
                $action = $request->route()?->getActionMethod();
                
                // Verificar permissões baseadas na rota
                if (str_starts_with($routeName ?? '', 'departments')) {
                    // Departamentos
                    if ($routeName === 'departments.index' || $action === 'index' || 
                        $routeName === 'departments.show' || $action === 'show') {
                        // Ver - precisa de permissão para ver
                        if (!$user->hasPermission('servico.departments.view') && 
                            !$user->hasPermission('servico.departments.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar departamentos.');
                        }
                    } elseif ($routeName === 'departments.create' || $action === 'create' || 
                              $routeName === 'departments.store' || $action === 'store') {
                        // Criar - precisa de permissão para criar
                        if (!$user->hasPermission('servico.departments.create') && 
                            !$user->hasPermission('servico.departments.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para criar departamentos.');
                        }
                    } elseif ($routeName === 'departments.edit' || $action === 'edit' || 
                              $routeName === 'departments.update' || $action === 'update') {
                        // Editar - precisa de permissão para editar
                        if (!$user->hasPermission('servico.departments.edit') && 
                            !$user->hasPermission('servico.departments.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar departamentos.');
                        }
                    } elseif ($routeName === 'departments.destroy' || $action === 'destroy') {
                        // Excluir - precisa de permissão para excluir
                        if (!$user->hasPermission('servico.departments.delete') && 
                            !$user->hasPermission('servico.departments.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para excluir departamentos.');
                        }
                    }
                } elseif (str_starts_with($routeName ?? '', 'voluntarios.cadastro')) {
                    // Cadastro de Voluntários
                    if ($routeName === 'voluntarios.cadastro.index' || $action === 'index' || 
                        $routeName === 'voluntarios.cadastro.show' || $action === 'show') {
                        // Ver - precisa de permissão para ver
                        if (!$user->hasPermission('servico.voluntarios.cadastro.view') && 
                            !$user->hasPermission('servico.voluntarios.cadastro.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar voluntários.');
                        }
                    } elseif ($routeName === 'voluntarios.cadastro.create' || $action === 'create' || 
                              $routeName === 'voluntarios.cadastro.store' || $action === 'store') {
                        // Criar - precisa de permissão para criar
                        if (!$user->hasPermission('servico.voluntarios.cadastro.create') && 
                            !$user->hasPermission('servico.voluntarios.cadastro.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para criar voluntários.');
                        }
                    } elseif ($routeName === 'voluntarios.cadastro.edit' || $action === 'edit' || 
                              $routeName === 'voluntarios.cadastro.update' || $action === 'update') {
                        // Editar - precisa de permissão para editar
                        if (!$user->hasPermission('servico.voluntarios.cadastro.edit') && 
                            !$user->hasPermission('servico.voluntarios.cadastro.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar voluntários.');
                        }
                    } elseif ($routeName === 'voluntarios.cadastro.destroy' || $action === 'destroy') {
                        // Excluir - precisa de permissão para excluir
                        if (!$user->hasPermission('servico.voluntarios.cadastro.delete') && 
                            !$user->hasPermission('servico.voluntarios.cadastro.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para excluir voluntários.');
                        }
                    }
                } elseif (str_starts_with($routeName ?? '', 'voluntarios.areas')) {
                    // Áreas de Serviço
                    if ($routeName === 'voluntarios.areas.index' || $action === 'index' || 
                        $routeName === 'voluntarios.areas.show' || $action === 'show') {
                        // Ver - precisa de permissão para ver
                        if (!$user->hasPermission('servico.voluntarios.areas.view') && 
                            !$user->hasPermission('servico.voluntarios.areas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar áreas de serviço.');
                        }
                    } elseif ($routeName === 'voluntarios.areas.create' || $action === 'create' || 
                              $routeName === 'voluntarios.areas.store' || $action === 'store') {
                        // Criar - precisa de permissão para criar
                        if (!$user->hasPermission('servico.voluntarios.areas.create') && 
                            !$user->hasPermission('servico.voluntarios.areas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para criar áreas de serviço.');
                        }
                    } elseif ($routeName === 'voluntarios.areas.edit' || $action === 'edit' || 
                              $routeName === 'voluntarios.areas.update' || $action === 'update') {
                        // Editar - precisa de permissão para editar
                        if (!$user->hasPermission('servico.voluntarios.areas.edit') && 
                            !$user->hasPermission('servico.voluntarios.areas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar áreas de serviço.');
                        }
                    } elseif ($routeName === 'voluntarios.areas.destroy' || $action === 'destroy') {
                        // Excluir - precisa de permissão para excluir
                        if (!$user->hasPermission('servico.voluntarios.areas.delete') && 
                            !$user->hasPermission('servico.voluntarios.areas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para excluir áreas de serviço.');
                        }
                    }
                } elseif (str_starts_with($routeName ?? '', 'voluntarios.disponibilidade')) {
                    // Disponibilidade
                    if ($routeName === 'voluntarios.disponibilidade.index' || $action === 'index' || 
                        $routeName === 'voluntarios.disponibilidade.show' || $action === 'show') {
                        // Ver - precisa de permissão para ver
                        if (!$user->hasPermission('servico.voluntarios.disponibilidade.view') && 
                            !$user->hasPermission('servico.voluntarios.disponibilidade.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar disponibilidade.');
                        }
                    } elseif ($routeName === 'voluntarios.disponibilidade.create' || $action === 'create' || 
                              $routeName === 'voluntarios.disponibilidade.store' || $action === 'store') {
                        // Criar - precisa de permissão para criar
                        if (!$user->hasPermission('servico.voluntarios.disponibilidade.create') && 
                            !$user->hasPermission('servico.voluntarios.disponibilidade.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para criar disponibilidade.');
                        }
                    } elseif ($routeName === 'voluntarios.disponibilidade.edit' || $action === 'edit' || 
                              $routeName === 'voluntarios.disponibilidade.update' || $action === 'update') {
                        // Editar - precisa de permissão para editar
                        if (!$user->hasPermission('servico.voluntarios.disponibilidade.edit') && 
                            !$user->hasPermission('servico.voluntarios.disponibilidade.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar disponibilidade.');
                        }
                    } elseif ($routeName === 'voluntarios.disponibilidade.destroy' || $action === 'destroy') {
                        // Excluir - precisa de permissão para excluir
                        if (!$user->hasPermission('servico.voluntarios.disponibilidade.delete') && 
                            !$user->hasPermission('servico.voluntarios.disponibilidade.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para excluir disponibilidade.');
                        }
                    }
                } elseif (str_starts_with($routeName ?? '', 'voluntarios.escalas')) {
                    // Escalas
                    if ($routeName === 'voluntarios.escalas.index' || $action === 'index' || 
                        $routeName === 'voluntarios.escalas.show' || $action === 'show') {
                        // Ver - precisa de permissão para ver
                        if (!$user->hasPermission('servico.voluntarios.escalas.view') && 
                            !$user->hasPermission('servico.voluntarios.escalas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar escalas.');
                        }
                    } elseif ($routeName === 'voluntarios.escalas.create' || $action === 'create' || 
                              $routeName === 'voluntarios.escalas.store' || $action === 'store' ||
                              $routeName === 'voluntarios.escalas.store.step1' || 
                              $routeName === 'voluntarios.escalas.store.step2' || 
                              $routeName === 'voluntarios.escalas.store.step3') {
                        // Criar - precisa de permissão para criar
                        if (!$user->hasPermission('servico.voluntarios.escalas.create') && 
                            !$user->hasPermission('servico.voluntarios.escalas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para criar escalas.');
                        }
                    } elseif ($routeName === 'voluntarios.escalas.edit' || $action === 'edit' || 
                              $routeName === 'voluntarios.escalas.update' || $action === 'update') {
                        // Editar - precisa de permissão para editar
                        if (!$user->hasPermission('servico.voluntarios.escalas.edit') && 
                            !$user->hasPermission('servico.voluntarios.escalas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para editar escalas.');
                        }
                    } elseif ($routeName === 'voluntarios.escalas.destroy' || $action === 'destroy') {
                        // Excluir - precisa de permissão para excluir
                        if (!$user->hasPermission('servico.voluntarios.escalas.delete') && 
                            !$user->hasPermission('servico.voluntarios.escalas.manage')) {
                            return $denyAccess( 'Acesso negado. Você não tem permissão para excluir escalas.');
                        }
                    }
                } elseif (str_starts_with($routeName ?? '', 'voluntarios.historico')) {
                    // Histórico de Serviço - apenas visualização
                    if (!$user->hasPermission('servico.voluntarios.historico.view') && 
                        !$user->hasPermission('servico.voluntarios.historico.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar histórico de serviço.');
                    }
                } elseif (str_starts_with($routeName ?? '', 'voluntarios.relatorios')) {
                    // Relatórios - apenas visualização
                    if (!$user->hasPermission('servico.voluntarios.relatorios.view') && 
                        !$user->hasPermission('servico.voluntarios.relatorios.manage')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para visualizar relatórios.');
                    }
                } else {
                    // Outras rotas de serviço - verificar permissão geral
                    if (!$user->hasPermission('servico.departments.view') && 
                        !$user->hasPermission('servico.voluntarios.cadastro.view') &&
                        !$user->hasPermission('servico.voluntarios.areas.view') &&
                        !$user->hasPermission('servico.voluntarios.disponibilidade.view') &&
                        !$user->hasPermission('servico.voluntarios.escalas.view') &&
                        !$user->hasPermission('servico.voluntarios.historico.view') &&
                        !$user->hasPermission('servico.voluntarios.relatorios.view')) {
                        return $denyAccess( 'Acesso negado. Você não tem permissão para acessar este módulo.');
                    }
                }
                break;

            default:
                return $denyAccess( 'Acesso negado.');
        }

        return $next($request);
    }
}
