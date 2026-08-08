@php
    $rmStats = $reminderStats[$sponsor->id] ?? ['total' => 0, 'last' => null, 'for_oldest' => 0];
    $rmLimit = $situacao === 'em_atraso' && $rmStats['for_oldest'] >= $reminderSettings->max_reminders;
@endphp

@if(! $sponsor->reminders_enabled)
    <span class="cs-badge cs-badge--muted" title="Este patrocinador não recebe lembretes automáticos">
        <i class="bx bx-bell-off"></i> Sem lembretes
    </span>
@elseif($rmLimit)
    <span class="cs-badge cs-badge--limit"
          title="Já foram enviados {{ $rmStats['for_oldest'] }} lembretes desta parcela — trate manualmente">
        <i class="bx bx-error"></i> Limite atingido
    </span>
@endif
