<?php

namespace App\Providers;

use App\Models\Department;
use App\Models\Discipleship\DiscipleshipCycle;
use App\Models\Discipleship\DiscipleshipFeedback;
use App\Models\Discipleship\DiscipleshipGoal;
use App\Models\Discipleship\DiscipleshipIndicator;
use App\Models\Discipleship\DiscipleshipMeeting;
use App\Models\Discipleship\DiscipleshipMember;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\FinancialContact;
use App\Models\FinancialCostCenter;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Pgi;
use App\Models\Rifa;
use App\Models\School;
use App\Models\ServiceArea;
use App\Models\ServiceHistory;
use App\Models\ServiceSchedule;
use App\Models\Study;
use App\Models\Turma;
use App\Models\Volunteer;
use App\Models\VolunteerAvailability;
use App\Policies\Agenda\EventCategoryPolicy;
use App\Policies\Agenda\EventPolicy;
use App\Policies\Discipleship\DiscipleshipCyclePolicy;
use App\Policies\Discipleship\DiscipleshipFeedbackPolicy;
use App\Policies\Discipleship\DiscipleshipGoalPolicy;
use App\Policies\Discipleship\DiscipleshipIndicatorPolicy;
use App\Policies\Discipleship\DiscipleshipMeetingPolicy;
use App\Policies\Discipleship\DiscipleshipMemberPolicy;
use App\Policies\Discipleship\DiscipleshipModulePolicy;
use App\Policies\Ensino\EscolaPolicy;
use App\Policies\Ensino\EstudoPolicy;
use App\Policies\Ensino\TurmaPolicy;
use App\Policies\Financial\FinancialAccountPolicy;
use App\Policies\Financial\FinancialCategoryPolicy;
use App\Policies\Financial\FinancialContactPolicy;
use App\Policies\Financial\FinancialCostCenterPolicy;
use App\Policies\Financial\FinancialModulePolicy;
use App\Policies\Financial\FinancialTransactionPolicy;
use App\Policies\Members\MemberPolicy;
use App\Policies\Members\MemberRolePolicy;
use App\Policies\Moriah\MoriahPolicy;
use App\Policies\Notificacoes\NotificacaoPolicy;
use App\Policies\Pgis\PgiPolicy;
use App\Policies\RifaPolicy;
use App\Policies\Rifas\RifaReportPolicy;
use App\Policies\Servico\DepartmentPolicy;
use App\Policies\Servico\ServiceAreaPolicy;
use App\Policies\Servico\ServiceHistoryPolicy;
use App\Policies\Servico\ServiceSchedulePolicy;
use App\Policies\Servico\VolunteerAvailabilityPolicy;
use App\Policies\Servico\VolunteerPolicy;
use App\Policies\Servico\VolunteerReportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Gate::policy(Rifa::class, RifaPolicy::class);
        Gate::policy(FinancialTransaction::class, FinancialTransactionPolicy::class);
        Gate::policy(FinancialCategory::class, FinancialCategoryPolicy::class);
        Gate::policy(FinancialAccount::class, FinancialAccountPolicy::class);
        Gate::policy(FinancialContact::class, FinancialContactPolicy::class);
        Gate::policy(FinancialCostCenter::class, FinancialCostCenterPolicy::class);
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(MemberRole::class, MemberRolePolicy::class);
        Gate::policy(Pgi::class, PgiPolicy::class);

        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Volunteer::class, VolunteerPolicy::class);
        Gate::policy(ServiceArea::class, ServiceAreaPolicy::class);
        Gate::policy(VolunteerAvailability::class, VolunteerAvailabilityPolicy::class);
        Gate::policy(ServiceSchedule::class, ServiceSchedulePolicy::class);
        Gate::policy(ServiceHistory::class, ServiceHistoryPolicy::class);

        Gate::policy(Study::class, EstudoPolicy::class);
        Gate::policy(School::class, EscolaPolicy::class);
        Gate::policy(Turma::class, TurmaPolicy::class);

        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(EventCategory::class, EventCategoryPolicy::class);

        Gate::policy(DiscipleshipCycle::class, DiscipleshipCyclePolicy::class);
        Gate::policy(DiscipleshipMember::class, DiscipleshipMemberPolicy::class);
        Gate::policy(DiscipleshipMeeting::class, DiscipleshipMeetingPolicy::class);
        Gate::policy(DiscipleshipIndicator::class, DiscipleshipIndicatorPolicy::class);
        Gate::policy(DiscipleshipGoal::class, DiscipleshipGoalPolicy::class);
        Gate::policy(DiscipleshipFeedback::class, DiscipleshipFeedbackPolicy::class);

        $financialModule = FinancialModulePolicy::class;
        Gate::define('financial.view-summary', [$financialModule, 'viewSummary']);
        Gate::define('financial.view-reports', [$financialModule, 'viewReports']);

        $notificacaoPolicy = NotificacaoPolicy::class;
        Gate::define('notificacoes.view', [$notificacaoPolicy, 'viewAny']);
        Gate::define('notificacoes.manage', [$notificacaoPolicy, 'manage']);

        $moriahPolicy = MoriahPolicy::class;
        Gate::define('moriah.view', [$moriahPolicy, 'viewAny']);
        Gate::define('moriah.manage', [$moriahPolicy, 'manage']);

        $discipleshipModule = DiscipleshipModulePolicy::class;
        Gate::define('discipleship.view', [$discipleshipModule, 'viewAny']);
        Gate::define('discipleship.manage', [$discipleshipModule, 'manage']);

        $rifaReportPolicy = RifaReportPolicy::class;
        Gate::define('rifas.view-reports', [$rifaReportPolicy, 'viewAny']);
        Gate::define('servico.view-reports', [VolunteerReportPolicy::class, 'viewAny']);
    }
}
