@if($canPay && $situacao === 'em_atraso' && $sponsor->reminders_enabled && $sponsor->phone)
    <li>
        <form method="POST" action="{{ route('financial.campaigns.reminders.send-now', $sponsor) }}"
              onsubmit="return confirm('Enviar o lembrete de cobrança para {{ $sponsor->name }} agora?');">
            @csrf
            <button type="submit" class="dropdown-item">
                <i class="bx bx-bell me-1"></i>Enviar lembrete agora
            </button>
        </form>
    </li>
@endif
@if($canEdit)
    <li>
        <form method="POST" action="{{ route('financial.campaigns.reminders.toggle-sponsor', $sponsor) }}">
            @csrf
            <button type="submit" class="dropdown-item">
                @if($sponsor->reminders_enabled)
                    <i class="bx bx-bell-off me-1"></i>Não enviar lembretes
                @else
                    <i class="bx bx-bell me-1"></i>Voltar a enviar lembretes
                @endif
            </button>
        </form>
    </li>
@endif
