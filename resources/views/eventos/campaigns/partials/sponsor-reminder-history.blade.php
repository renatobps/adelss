@php
    use App\Models\CampaignReminderLog;

    // Rastreabilidade antes de uma abordagem pessoal: quem já foi cobrado,
    // quantas vezes, e por que alguém deixou de receber.
    $rmLogs = CampaignReminderLog::where('campaign_sponsor_id', $sponsor->id)
        ->latest('id')
        ->limit(10)
        ->get();
    $rmSentCount = $rmLogs->where('status', CampaignReminderLog::STATUS_ENVIADO)->count();
    $rmLast = $rmLogs->firstWhere('status', CampaignReminderLog::STATUS_ENVIADO);
@endphp

@if($rmLogs->isNotEmpty() || ! $sponsor->reminders_enabled)
    <div class="d-flex align-items-center flex-wrap gap-2 mb-2 small">
        <i class="bx bx-bell text-muted"></i>
        @if($rmLast)
            <span class="text-muted">
                Último lembrete: <strong>{{ $rmLast->sent_at?->format('d/m/Y') }}</strong>
                · {{ $rmSentCount }} {{ Str::plural('enviado', $rmSentCount) }}
            </span>
        @else
            <span class="text-muted">Nenhum lembrete enviado.</span>
        @endif

        @unless($sponsor->reminders_enabled)
            <span class="badge bg-secondary">Lembretes desativados</span>
        @endunless

        @if($rmLogs->isNotEmpty())
            <button class="btn btn-link btn-sm p-0 text-decoration-none" type="button"
                    data-bs-toggle="collapse" data-bs-target="#rmHistory-{{ $sponsor->id }}">
                ver histórico
            </button>
        @endif
    </div>

    @if($rmLogs->isNotEmpty())
        <div class="collapse mb-3" id="rmHistory-{{ $sponsor->id }}">
            <div class="table-responsive">
                <table class="table table-sm table-borderless mb-0 small">
                    <tbody>
                        @foreach($rmLogs as $log)
                            <tr>
                                <td class="text-muted" style="width: 130px;">
                                    {{ ($log->sent_at ?? $log->created_at)->format('d/m/Y H:i') }}
                                </td>
                                <td style="width: 90px;">
                                    <span class="badge bg-{{ match($log->status) {
                                        'enviado' => 'success',
                                        'falhou' => 'danger',
                                        default => 'secondary',
                                    } }}">{{ $log->statusLabel() }}</span>
                                </td>
                                <td style="width: 90px;" class="text-muted">{{ $log->typeLabel() }}</td>
                                <td class="text-muted">
                                    {{ $log->reason ?: ($log->pdf_attached ? 'Mensagem + carnê em PDF' : 'Mensagem de texto') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endif
