<?php

namespace App\Http\Controllers\Discipleship;

use App\Http\Controllers\Controller;
use App\Models\Discipleship\DiscipleshipCycle;
use App\Models\Discipleship\DiscipleshipMember;
use App\Models\Discipleship\DiscipleshipMeeting;
use App\Models\Discipleship\DiscipleshipIndicator;
use App\Models\Discipleship\DiscipleshipGoal;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DiscipleshipDashboardController extends Controller
{
    /**
     * Dashboard do discipulador
     */
    public function discipulador()
    {
        $user = auth()->user();
        
        // Buscar discípulos do usuário logado
        $disciples = DiscipleshipMember::with(['member', 'cycle', 'meetings' => function($query) {
            $query->recentes()->limit(1);
        }])
        ->where('discipulador_id', $user->id)
        ->ativos()
        ->get();
        
        // Calcular alertas
        $alerts = $this->getAlerts($user->id);
        
        // Últimos encontros
        $lastMeetings = DiscipleshipMeeting::whereHas('discipleshipMember', function($query) use ($user) {
            $query->where('discipulador_id', $user->id);
        })
        ->recentes()
        ->limit(5)
        ->get();
        
        return view('discipleship.dashboard.discipulador', compact('disciples', 'alerts', 'lastMeetings'));
    }

    /**
     * Dashboard da liderança
     */
    public function lideranca()
    {
        // Estatísticas gerais
        $totalEmDiscipulado = DiscipleshipMember::ativos()->count();
        $totalCiclosAtivos = DiscipleshipCycle::ativos()->count();
        $membrosSemAcompanhamento = $this->getMembrosSemAcompanhamento();
        
        // Indicadores críticos
        $indicadoresCriticos = $this->getIndicadoresCriticos();
        
        // Evolução por ciclo
        $evolucaoPorCiclo = $this->getEvolucaoPorCiclo();
        
        return view('discipleship.dashboard.lideranca', compact(
            'totalEmDiscipulado',
            'totalCiclosAtivos',
            'membrosSemAcompanhamento',
            'indicadoresCriticos',
            'evolucaoPorCiclo'
        ));
    }

    /**
     * Obter alertas para o discipulador
     */
    private function getAlerts($discipuladorId)
    {
        $alerts = [];
        
        // Sem encontro há X dias (padrão: 14 dias)
        $daysWithoutMeeting = 14;
        $membersWithoutMeeting = DiscipleshipMember::where('discipulador_id', $discipuladorId)
            ->ativos()
            ->whereDoesntHave('meetings', function($query) use ($daysWithoutMeeting) {
                $query->where('data', '>=', Carbon::now()->subDays($daysWithoutMeeting));
            })
            ->get();
        
        foreach ($membersWithoutMeeting as $member) {
            $alerts[] = [
                'type' => 'sem_encontro',
                'message' => "Sem encontro há mais de {$daysWithoutMeeting} dias: {$member->member->name}",
                'member_id' => $member->member_id,
            ];
        }
        
        // Propósitos vencidos
        $goalsVencidos = DiscipleshipGoal::whereHas('discipleshipMember', function($query) use ($discipuladorId) {
            $query->where('discipulador_id', $discipuladorId);
        })
        ->vencidos()
        ->get();
        
        foreach ($goalsVencidos as $goal) {
            $alerts[] = [
                'type' => 'proposito_vencido',
                'message' => "Propósito vencido: {$goal->descricao}",
                'member_id' => $goal->discipleshipMember->member_id,
            ];
        }
        
        // Ciclo próximo do fim (7 dias)
        $cyclesEnding = DiscipleshipCycle::whereHas('members', function($query) use ($discipuladorId) {
            $query->where('discipulador_id', $discipuladorId);
        })
        ->ativos()
        ->where('data_fim', '<=', Carbon::now()->addDays(7))
        ->where('data_fim', '>=', Carbon::now())
        ->get();
        
        foreach ($cyclesEnding as $cycle) {
            $alerts[] = [
                'type' => 'ciclo_terminando',
                'message' => "Ciclo '{$cycle->nome}' termina em breve",
                'cycle_id' => $cycle->id,
            ];
        }
        
        return $alerts;
    }

    /**
     * Obter membros sem acompanhamento
     */
    private function getMembrosSemAcompanhamento()
    {
        // Membros ativos que não estão em nenhum ciclo ativo
        return \App\Models\Member::active()
            ->whereDoesntHave('discipleshipMembers', function($query) {
                $query->where('status', 'ativo');
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Obter indicadores críticos
     */
    private function getIndicadoresCriticos()
    {
        // Indicadores com valores baixos (0 ou 1) nos últimos 30 dias
        return DiscipleshipIndicator::ativos()
            ->with(['values' => function($query) {
                $query->where('data_registro', '>=', Carbon::now()->subDays(30))
                    ->whereIn('valor', ['0', '1']);
            }])
            ->get()
            ->filter(function($indicator) {
                return $indicator->values->count() > 0;
            });
    }

    /**
     * Página de Ajuda — Tutorial do fluxo de discipulado
     */
    public function help()
    {
        return view('discipleship.help');
    }

    /**
     * Obter evolução por ciclo
     */
    private function getEvolucaoPorCiclo()
    {
        return DiscipleshipCycle::with(['members'])
            ->orderBy('data_inicio', 'desc')
            ->limit(10)
            ->get()
            ->map(function($cycle) {
                return [
                    'nome' => $cycle->nome,
                    'data_inicio' => $cycle->data_inicio,
                    'total_membros' => $cycle->members->count(),
                    'membros_ativos' => $cycle->members->where('status', 'ativo')->count(),
                ];
            });
    }
}
