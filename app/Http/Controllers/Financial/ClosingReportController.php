<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\CashClosing;
use App\Models\Event;
use App\Models\FinancialTransaction;
use App\Services\Financial\CultoOfferingReportService;
use App\Services\Financial\MatrixFinancialReportService;
use App\Services\Financial\WeeklyCashClosingService;
use App\Services\FinancialNotificationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClosingReportController extends Controller
{
    public function cultos(Request $request, CultoOfferingReportService $service)
    {
        $this->authorize('financial.fechamento.view');

        $cultos = Event::query()
            ->paraRelatorioFinanceiro()
            ->get();
        $culto = $request->filled('culto_id')
            ? Event::query()->paraRelatorioFinanceiro()->find($request->integer('culto_id'))
            : $cultos->first();

        $report = $culto ? $service->build($culto) : null;

        return view('financial.reports.culto-offerings', [
            'cultos' => $cultos,
            'culto' => $culto,
            'report' => $report,
            'canGenerate' => auth()->user()?->is_admin || auth()->user()?->can('financial.fechamento.generate'),
        ]);
    }

    public function attachToCulto(Request $request, Event $event, CultoOfferingReportService $service)
    {
        $this->authorize('financial.fechamento.generate');

        abort_unless($event->isCultoDaAgenda(), 404);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:financial_transactions,id'],
        ], [
            'ids.required' => 'Selecione ao menos um dízimo ou oferta para vincular.',
        ]);

        $count = $service->attachToCulto($event, $validated['ids']);

        return redirect()
            ->route('financial.reports.cultos', ['culto_id' => $event->id])
            ->with('success', $count === 1
                ? '1 lançamento vinculado a este culto.'
                : $count.' lançamentos vinculados a este culto.');
    }

    public function detachFromCulto(Event $event, FinancialTransaction $transaction, CultoOfferingReportService $service)
    {
        $this->authorize('financial.fechamento.generate');

        abort_unless($event->isCultoDaAgenda(), 404);

        if (! $service->detachFromCulto($event, $transaction)) {
            return redirect()
                ->route('financial.reports.cultos', ['culto_id' => $event->id])
                ->with('error', 'Este lançamento não está vinculado a este culto.');
        }

        return redirect()
            ->route('financial.reports.cultos', ['culto_id' => $event->id])
            ->with('success', 'Lançamento desvinculado deste culto.');
    }

    public function cultosPdf(Event $event, CultoOfferingReportService $service): Response
    {
        $this->authorize('financial.fechamento.view');

        return $service->download($event);
    }

    public function matrixDemonstrativo(Request $request, MatrixFinancialReportService $service)
    {
        $this->authorize('financial.fechamento.view');

        $year = (int) $request->input('year', now()->year);
        if ($year < 2000 || $year > now()->year + 1) {
            $year = (int) now()->year;
        }

        $report = $service->buildYear($year);
        $years = range(now()->year, now()->year - 10);

        return view('financial.reports.matrix-demonstrativo', [
            'year' => $year,
            'years' => $years,
            'months' => $report['months'],
        ]);
    }

    public function matrixDemonstrativoPdf(Request $request, MatrixFinancialReportService $service): Response
    {
        $this->authorize('financial.fechamento.view');

        $year = (int) $request->input('year', now()->year);
        if ($year < 2000 || $year > now()->year + 1) {
            $year = (int) now()->year;
        }

        return $service->download($year);
    }

    public function weekly(Request $request, WeeklyCashClosingService $service)
    {
        $this->authorize('financial.fechamento.view');

        $week = $service->weekFor($request->input('week_date', now()->toDateString()));
        $live = $service->liveTotals($week['start'], $week['end']);
        $diverged = $service->snapshotHasDiverged($live['latest_closing'], $live);
        $recentClosings = CashClosing::query()->latest('generated_at')->limit(12)->get();

        return view('financial.reports.weekly-closing', [
            'weekStart' => $week['start'],
            'weekEnd' => $week['end'],
            'live' => $live,
            'diverged' => $diverged,
            'recentClosings' => $recentClosings,
            'canGenerate' => auth()->user()?->is_admin || auth()->user()?->can('financial.fechamento.generate'),
        ]);
    }

    public function weeklyGenerate(Request $request, WeeklyCashClosingService $service, FinancialNotificationService $notifications)
    {
        $this->authorize('financial.fechamento.generate');

        $week = $service->weekFor($request->input('week_date', now()->toDateString()));
        $closing = $service->generate($week['start'], $week['end']);
        $whatsapp = $notifications->notificarFechamentoSemanal($closing, auth()->id());

        $message = 'Fechamento da semana '.$week['start']->format('d/m').' a '.$week['end']->format('d/m/Y').' gerado.';
        if ($whatsapp['success'] ?? false) {
            $message .= ' Enviado ao grupo dos tesoureiros.';
        }

        $redirect = redirect()
            ->route('financial.reports.weekly-closing', ['week_date' => $week['start']->toDateString()])
            ->with('success', $message)
            ->with('download_closing_id', $closing->id);

        if (! ($whatsapp['success'] ?? false) && filled($whatsapp['error'] ?? null)) {
            $redirect->with('warning', 'WhatsApp: '.$whatsapp['error']);
        }

        return $redirect;
    }

    public function weeklyPdf(CashClosing $cashClosing, WeeklyCashClosingService $service): Response
    {
        $this->authorize('financial.fechamento.view');

        $filename = 'fechamento-semanal-'.$cashClosing->period_start->format('Y-m-d').'.pdf';

        return response($service->pdfBinary($cashClosing), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
