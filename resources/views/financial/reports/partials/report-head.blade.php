<div class="fr-report-head">
    <div class="d-flex align-items-start gap-3">
        <img src="{{ asset('img/logo.png') }}" alt="Logo" class="fr-logo" onerror="this.style.display='none'">
        <div>
            <h5 class="mb-0">{{ $title }}</h5>
            @if(!empty($subtitle))
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    <div class="fr-report-head__actions">
        @if(!empty($pdfUrl))
            <a href="{{ $pdfUrl }}" class="btn btn-outline-primary">
                <i class="bx bxs-file-pdf me-1"></i>{{ $pdfLabel ?? 'Gerar PDF' }}
            </a>
        @endif
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bx bx-printer me-1"></i>Imprimir
        </button>
    </div>
</div>
