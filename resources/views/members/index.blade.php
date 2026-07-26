@extends('layouts.porto')

@section('title', 'Membros')
@section('page-title', 'Membros')

@section('breadcrumbs')
    <li><span>Membros</span></li>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canCreateMembers = $isAdmin || ($user && ($user->hasPermission('members.index.create') || $user->hasPermission('members.create') || $user->hasPermission('members.index.manage')));
    $canEditMembers = $isAdmin || ($user && ($user->hasPermission('members.index.edit') || $user->hasPermission('members.edit') || $user->hasPermission('members.index.manage')));
    $canDeleteMembers = $isAdmin || ($user && ($user->hasPermission('members.index.delete') || $user->hasPermission('members.delete') || $user->hasPermission('members.index.manage')));
    $canManageRoles = $isAdmin
        || ($user && (
            $user->hasPermission('members.roles.view')
            || $user->hasPermission('members.roles.manage')
            || $user->hasPermission('members.roles.create')
            || $user->hasPermission('members.roles.edit')
        ));
    $canManagePermissions = $isAdmin;
    $canManageFields = $isAdmin || ($user && ($user->hasPermission('members.index.manage') || $user->hasPermission('members.index.edit')));
    $formatPhone = function (?string $phone): string {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 11) {
            return sprintf('(%s) %s %s-%s', substr($digits, 0, 2), substr($digits, 2, 1), substr($digits, 3, 4), substr($digits, 7, 4));
        }
        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4));
        }
        return $phone ?: '—';
    };
    $bdayDate = \Carbon\Carbon::createFromDate($birthdaysYear, $birthdaysMonth, 1);
    $prevMonth = $bdayDate->copy()->subMonth();
    $nextMonth = $bdayDate->copy()->addMonth();
    $kpis = $kpis ?? [];
    $mapPoints = collect($mapMembers ?? [])->map(function ($m) {
        return [
            'lat' => (float) $m->latitude,
            'lng' => (float) $m->longitude,
            'name' => $m->name,
            'city' => $m->city,
        ];
    })->values();
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@php $publicLinkToShow = session('public_registration_url') ?: ($publicRegistrationUrl ?? null); @endphp
@if($publicLinkToShow)
    <div class="alert alert-info members-public-link-alert d-flex flex-wrap align-items-center gap-3">
        <div class="flex-grow-1 min-w-0">
            <strong class="d-block mb-1">Link público de cadastro</strong>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ $publicLinkToShow }}" target="_blank" rel="noopener" class="text-break" id="membersPublicLinkUrl">{{ $publicLinkToShow }}</a>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="membersCopyPublicLink" data-url="{{ $publicLinkToShow }}" title="Copiar link">
                    <i class="bx bx-copy"></i> <span class="members-copy-label">Copiar link</span>
                </button>
                @if($canCreateMembers)
                    <form method="POST" action="{{ route('members.public-link') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="regenerate" value="1">
                        <button class="btn btn-sm btn-outline-secondary" type="submit" title="Gerar novo token">
                            <i class="bx bx-refresh"></i> Novo link
                        </button>
                    </form>
                @endif
            </div>
            <small class="text-muted">Cadastros entram como <strong>pendente</strong> e precisam de aprovação.</small>
        </div>
        <div class="text-center">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=112x112&margin=8&data={{ urlencode($publicLinkToShow) }}"
                 alt="QR Code do cadastro" width="112" height="112" class="rounded border bg-white p-1">
            <div class="small text-muted mt-1">QR Code</div>
        </div>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- KPIs --}}
<div class="members-kpi-grid">
    <div class="members-kpi-card members-kpi-card--blue">
        <div class="members-kpi-card__icon"><i class="bx bx-user"></i></div>
        <div>
            <div class="members-kpi-card__value">{{ $kpis['ativos'] ?? 0 }}</div>
            <div class="members-kpi-card__label">Membros</div>
            <div class="members-kpi-card__hint">ativos</div>
        </div>
    </div>
    <div class="members-kpi-card members-kpi-card--teal">
        <div class="members-kpi-card__icon"><i class="bx bx-walk"></i></div>
        <div>
            <div class="members-kpi-card__value">{{ $kpis['visitantes'] ?? 0 }}</div>
            <div class="members-kpi-card__label">Visitantes</div>
            <div class="members-kpi-card__hint">{{ $kpis['visitantes_mes'] ?? 0 }} este mês</div>
        </div>
    </div>
    <div class="members-kpi-card members-kpi-card--amber">
        <div class="members-kpi-card__icon"><i class="bx bx-user-plus"></i></div>
        <div>
            <div class="members-kpi-card__value">{{ $kpis['novos_mes'] ?? 0 }}</div>
            <div class="members-kpi-card__label">Novos este mês</div>
        </div>
    </div>
    <div class="members-kpi-card members-kpi-card--violet">
        <div class="members-kpi-card__icon"><i class="bx bx-cake"></i></div>
        <div>
            <div class="members-kpi-card__value">{{ $kpis['aniversariantes'] ?? 0 }}</div>
            <div class="members-kpi-card__label">Aniversariantes</div>
            <div class="members-kpi-card__hint">no mês atual</div>
        </div>
    </div>
    <div class="members-kpi-card members-kpi-card--rose">
        <div class="members-kpi-card__icon"><i class="bx bx-crown"></i></div>
        <div>
            <div class="members-kpi-card__value">{{ $kpis['lideres'] ?? 0 }}</div>
            <div class="members-kpi-card__label">Líderes</div>
            <div class="members-kpi-card__hint">com cargo</div>
        </div>
    </div>
    <div class="members-kpi-card members-kpi-card--slate">
        <div class="members-kpi-card__icon"><i class="bx bx-time-five"></i></div>
        <div>
            <div class="members-kpi-card__value">{{ $kpis['pendentes'] ?? 0 }}</div>
            <div class="members-kpi-card__label">Pendentes</div>
            <div class="members-kpi-card__hint">aguardando revisão</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="members-tools">
                        @if($canCreateMembers)
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bx bx-upload"></i> Importar CSV
                            </button>
                            <a href="{{ route('members.export.pdf', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-file"></i> PDF
                            </a>
                            <a href="{{ route('members.export.excel', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-spreadsheet"></i> Excel
                            </a>
                            <a href="{{ route('members.export.blank') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-printer"></i> Ficha em branco
                            </a>
                            <form method="POST" action="{{ route('members.public-link') }}" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary" type="submit">
                                    <i class="bx bx-link"></i> Link público
                                </button>
                            </form>
                        @endif
                        @if($canManageRoles)
                            <a href="{{ route('member-roles.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-id-card"></i> Cargos
                            </a>
                        @endif
                        @if($canManagePermissions)
                            <a href="{{ route('permissions.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-lock-alt"></i> Permissões
                            </a>
                        @endif
                        @if($canManageFields)
                            <a href="{{ route('members.custom-fields.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-slider-alt"></i> Campos
                            </a>
                        @endif
                        @if($canCreateMembers)
                            <a href="{{ route('members.create') }}" class="btn btn-sm btn-primary">
                                <i class="bx bx-plus"></i> Novo membro
                            </a>
                        @endif
                    </div>
                    <div class="btn-group members-view-toggle d-none d-md-inline-flex" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-members-view="lista" title="Tabela"><i class="bx bx-list-ul"></i></button>
                        <button type="button" class="btn btn-sm btn-primary" data-members-view="cards" title="Cards"><i class="bx bx-grid-alt"></i></button>
                    </div>
                </div>

                <form method="GET" action="{{ route('members.index') }}" class="row g-2 mb-3" id="membersFilterForm">
                    <input type="hidden" name="bday_month" value="{{ $birthdaysMonth }}">
                    <input type="hidden" name="bday_year" value="{{ $birthdaysYear }}">
                    <input type="hidden" name="page" value="1">
                    <input type="hidden" name="per_page" value="{{ (int) ($perPage ?? 10) }}">
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="Buscar nome, email, telefone...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">Status</option>
                            @foreach(\App\Models\Member::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="gender">
                            <option value="">Gênero</option>
                            <option value="M" @selected(request('gender') === 'M')>Masculino</option>
                            <option value="F" @selected(request('gender') === 'F')>Feminino</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="pgi_vinculo">
                            <option value="">PGI</option>
                            <option value="com_pgi" @selected(request('pgi_vinculo') === 'com_pgi')>Com PGI</option>
                            <option value="sem_pgi" @selected(request('pgi_vinculo') === 'sem_pgi')>Sem PGI</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-primary w-100" type="submit"><i class="bx bx-filter"></i> Filtrar</button>
                    </div>
                </form>

                @if($members->total() === 0)
                    <div class="text-center text-muted py-5">Nenhum membro encontrado.</div>
                @else
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div class="small text-muted">
                            Mostrando {{ $members->firstItem() }}–{{ $members->lastItem() }} de {{ $members->total() }}
                        </div>
                        <form method="GET" action="{{ route('members.index') }}" class="d-flex align-items-center gap-2">
                            @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $v)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <label class="small text-muted mb-0" for="membersPerPage">Mostrar</label>
                            <select name="per_page" id="membersPerPage" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                                @foreach([10, 25, 50, 100] as $opt)
                                    <option value="{{ $opt }}" @selected((int) ($perPage ?? 10) === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                            <span class="small text-muted">registros</span>
                        </form>
                    </div>

                    <div id="membersCards" class="members-card-grid">
                        @foreach($members as $member)
                            @php
                                $deptNames = $member->departments->pluck('name');
                                if ($deptNames->isEmpty() && $member->department) {
                                    $deptNames = collect([$member->department->name]);
                                }
                            @endphp
                            <div class="members-person-card">
                                <div class="members-person-card__menu">
                                    @include('members.partials.actions-menu', ['member' => $member, 'suffix' => 'card'])
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    @include('members.partials.avatar', ['member' => $member, 'size' => 56])
                                    <div class="min-w-0 pe-4">
                                        <div class="members-person-card__name text-truncate">
                                            <span class="members-status-dot members-status-dot--{{ $member->status }}" title="{{ $member->status_label }}"></span>
                                            @include('members.partials.name-trigger', ['member' => $member])
                                        </div>
                                        <div class="members-person-card__meta">{{ $formatPhone($member->phone) }}</div>
                                        @if($member->email)
                                            <div class="members-person-card__meta text-truncate">{{ $member->email }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-2 d-flex flex-wrap gap-1">
                                    @foreach($deptNames->take(3) as $deptName)
                                        <span class="badge members-badge-dept">{{ $deptName }}</span>
                                    @endforeach
                                    @if($member->pgi)
                                        <span class="badge members-badge-pgi">{{ $member->pgi->name }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="membersTable" class="table-responsive d-none">
                        <table class="table members-table members-table--grouped align-middle mb-0">
                            <thead>
                                <tr class="members-table__group-row">
                                    <th class="text-center">Informações pessoais</th>
                                    <th class="text-center">Departamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($members as $member)
                                    @php
                                        $deptNames = $member->departments->pluck('name');
                                        if ($deptNames->isEmpty() && $member->department) {
                                            $deptNames = collect([$member->department->name]);
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-start gap-2">
                                                @include('members.partials.avatar', ['member' => $member, 'size' => 40])
                                                <div class="min-w-0">
                                                    <div class="d-flex align-items-center gap-1">
                                                        <span class="members-status-dot members-status-dot--{{ $member->status }}" title="{{ $member->status_label }}"></span>
                                                        @include('members.partials.name-trigger', ['member' => $member])
                                                    </div>
                                                    @if($member->email)
                                                        <div class="small text-muted text-truncate">{{ $member->email }}</div>
                                                    @endif
                                                    <div class="small text-muted">{{ $formatPhone($member->phone) }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($deptNames as $deptName)
                                                    <span class="badge members-badge-dept">{{ $deptName }}</span>
                                                @endforeach
                                                @if($member->pgi)
                                                    <span class="badge members-badge-pgi" title="PGI">{{ $member->pgi->name }}</span>
                                                @endif
                                                @if($deptNames->isEmpty() && !$member->pgi)
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-center">
                        {{ $members->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Aniversariantes --}}
        <div class="members-side-widget">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="members-side-widget__title"><i class="bx bx-cake me-1"></i> Aniversariantes</h3>
                <div class="btn-group btn-group-sm">
                    <a class="btn btn-outline-secondary" href="{{ route('members.index', array_merge(request()->query(), ['bday_month' => $prevMonth->month, 'bday_year' => $prevMonth->year])) }}">‹</a>
                    <span class="btn btn-light disabled">{{ $bdayDate->translatedFormat('M/Y') }}</span>
                    <a class="btn btn-outline-secondary" href="{{ route('members.index', array_merge(request()->query(), ['bday_month' => $nextMonth->month, 'bday_year' => $nextMonth->year])) }}">›</a>
                </div>
            </div>

            @forelse($birthdayMembers as $m)
                <div class="members-bday-item">
                    @include('members.partials.avatar', ['member' => $m, 'size' => 32])
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-truncate">{{ $m->name }}</div>
                        <div class="text-muted">{{ $m->birth_date->format('d/m') }}</div>
                    </div>
                </div>
            @empty
                <div class="members-bday-empty">Nenhum aniversariante em {{ $bdayDate->translatedFormat('F') }}.</div>
            @endforelse
        </div>

        {{-- Mapa --}}
        <div class="members-side-widget">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="members-side-widget__title"><i class="bx bx-map me-1"></i> Mapa de membros</h3>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#membersMapCollapse">Expandir</button>
            </div>
            <p class="small text-muted mb-2">{{ $mappedCount }} localizados de {{ $addressCount }} com endereço.</p>
            <div class="collapse show" id="membersMapCollapse">
                <div id="membersMap" class="members-map"></div>
            </div>
        </div>
    </div>
</div>

@include('members.partials.member-modal')

@if($canCreateMembers)
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Importar membros</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('members.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <p class="small text-muted">Use o template CSV. <a href="{{ route('members.import.template') }}">Baixar template</a></p>
                    <input type="file" name="import_file" class="form-control" accept=".csv,.txt" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Importar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    function decodeMemberPayload(btn) {
        var b64 = btn.getAttribute('data-member-b64');
        if (!b64) return null;
        try {
            return JSON.parse(atob(b64));
        } catch (e) {
            console.error('Falha ao ler dados do membro', e);
            return null;
        }
    }

    function escHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function getMemberModalEl() {
        var el = document.getElementById('memberQuickModal');
        if (el && el.parentElement !== document.body) {
            document.body.appendChild(el);
        }
        return el;
    }

    function openMemberModal(data) {
        var memberModalEl = getMemberModalEl();
        if (!memberModalEl || !data) return;

        document.getElementById('mqName').textContent = data.name || '—';
        document.getElementById('mqEmail').textContent = data.email || 'Sem e-mail';
        document.getElementById('mqPhone').textContent = data.phone || 'Sem telefone';
        document.getElementById('mqStatus').textContent = data.status_label || '—';
        document.getElementById('mqRole').textContent = data.role || '—';
        document.getElementById('mqDepartments').textContent = (data.departments && data.departments.length)
            ? data.departments.join(', ')
            : '—';
        document.getElementById('mqPgi').textContent = data.pgi || '—';

        var avatar = document.getElementById('mqAvatar');
        if (data.photo_url) {
            avatar.innerHTML = '<img src="' + escHtml(data.photo_url) + '" alt="" class="rounded-circle" width="72" height="72" style="object-fit:cover;width:72px;height:72px;">';
        } else {
            avatar.innerHTML = '<div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:72px;height:72px;background:' +
                escHtml(data.avatar_color || '#0088CC') + ';color:#fff;font-weight:700;font-size:1.25rem;">' +
                escHtml(data.initials || '?') + '</div>';
        }

        var badges = document.getElementById('mqBadges');
        var badgesHtml = '';
        if (data.role) {
            badgesHtml += '<span class="badge bg-primary">' + escHtml(data.role) + '</span>';
        }
        if (data.status_label) {
            var statusClass = data.status === 'ativo' ? 'bg-success'
                : (data.status === 'visitante' ? 'bg-warning text-dark'
                : (data.status === 'pendente' ? 'bg-info' : 'bg-secondary'));
            badgesHtml += '<span class="badge ' + statusClass + '">' + escHtml(data.status_label) + '</span>';
        }
        (data.departments || []).forEach(function (d) {
            badgesHtml += '<span class="badge members-badge-dept">' + escHtml(d) + '</span>';
        });
        if (data.pgi) {
            badgesHtml += '<span class="badge members-badge-pgi">' + escHtml(data.pgi) + '</span>';
        }
        badges.innerHTML = badgesHtml;

        document.getElementById('mqProfileBtn').href = data.show_url || '#';

        var editBtn = document.getElementById('mqEditBtn');
        if (data.edit_url) {
            editBtn.href = data.edit_url;
            editBtn.classList.remove('d-none');
        } else {
            editBtn.classList.add('d-none');
        }

        var msgBtn = document.getElementById('mqMessageBtn');
        if (data.message_url) {
            msgBtn.href = data.message_url;
            msgBtn.classList.remove('d-none');
        } else {
            msgBtn.classList.add('d-none');
        }

        var delForm = document.getElementById('mqDeleteForm');
        if (data.delete_url) {
            delForm.action = data.delete_url;
            delForm.classList.remove('d-none');
        } else {
            delForm.classList.add('d-none');
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(memberModalEl).show();
        } else if (window.jQuery) {
            window.jQuery(memberModalEl).modal('show');
        } else {
            memberModalEl.classList.add('show');
            memberModalEl.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('.members-name-btn');
        if (!btn) return;
        event.preventDefault();
        event.stopPropagation();
        var data = decodeMemberPayload(btn);
        if (data) {
            openMemberModal(data);
        }
    });

    var copyBtn = document.getElementById('membersCopyPublicLink');
    if (copyBtn) {
        copyBtn.addEventListener('click', async function () {
            var url = this.getAttribute('data-url') || '';
            var label = this.querySelector('.members-copy-label');
            try {
                await navigator.clipboard.writeText(url);
                if (label) label.textContent = 'Copiado!';
                this.classList.add('btn-success');
                this.classList.remove('btn-outline-secondary');
                setTimeout(function () {
                    if (label) label.textContent = 'Copiar link';
                    copyBtn.classList.remove('btn-success');
                    copyBtn.classList.add('btn-outline-secondary');
                }, 1600);
            } catch (e) {
                window.prompt('Copie o link:', url);
            }
        });
    }

    var STORAGE_KEY = 'members.index.view';
    var cardsEl = document.getElementById('membersCards');
    var tableEl = document.getElementById('membersTable');
    var btns = document.querySelectorAll('[data-members-view]');

    function isMobile() { return window.matchMedia('(max-width: 767.98px)').matches; }

    function applyView(view) {
        var mode = view === 'lista' ? 'lista' : 'cards';
        try { localStorage.setItem(STORAGE_KEY, mode); } catch (e) {}
        btns.forEach(function (b) {
            var active = b.getAttribute('data-members-view') === mode;
            b.classList.toggle('btn-primary', active);
            b.classList.toggle('btn-outline-secondary', !active);
        });
        if (!cardsEl || !tableEl) return;
        if (isMobile()) {
            cardsEl.classList.remove('d-none');
            tableEl.classList.add('d-none');
            return;
        }
        cardsEl.classList.toggle('d-none', mode !== 'cards');
        tableEl.classList.toggle('d-none', mode !== 'lista');
    }

    var saved = 'cards';
    try { saved = localStorage.getItem(STORAGE_KEY) || 'cards'; } catch (e) {}
    applyView(saved);
    btns.forEach(function (b) {
        b.addEventListener('click', function () {
            applyView(b.getAttribute('data-members-view'));
        });
    });
    window.addEventListener('resize', function () {
        applyView(localStorage.getItem(STORAGE_KEY) || 'cards');
    });
})();
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    var mapPoints = @json($mapPoints);
    var mapEl = document.getElementById('membersMap');
    if (!mapEl || typeof L === 'undefined') return;
    var map = L.map(mapEl).setView([-15.78, -47.93], 4);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 18,
    }).addTo(map);
    var bounds = [];
    mapPoints.forEach(function (p) {
        if (!p.lat || !p.lng) return;
        L.marker([p.lat, p.lng]).addTo(map).bindPopup('<strong>' + p.name + '</strong>' + (p.city ? '<br>' + p.city : ''));
        bounds.push([p.lat, p.lng]);
    });
    if (bounds.length) map.fitBounds(bounds, { padding: [24, 24], maxZoom: 13 });
    var collapse = document.getElementById('membersMapCollapse');
    if (collapse) {
        collapse.addEventListener('shown.bs.collapse', function () { map.invalidateSize(); });
    }
    setTimeout(function () { map.invalidateSize(); }, 300);
})();
</script>
@endpush
