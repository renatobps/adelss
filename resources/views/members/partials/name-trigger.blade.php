@php
    $deptNames = $member->departments->pluck('name');
    if ($deptNames->isEmpty() && $member->department) {
        $deptNames = collect([$member->department->name]);
    }
    $phoneFormatted = is_callable($formatPhone ?? null)
        ? $formatPhone($member->phone)
        : ($member->phone ?: '—');
    $canMessageMember = filled($member->phone)
        && (($isAdmin ?? false) || (($user ?? null) && (($user->can('notificacoes.view') ?? false) || $user->hasPermission('notificacoes.manage'))));
    $memberModalPayload = [
        'id' => $member->id,
        'name' => $member->name,
        'email' => $member->email,
        'phone' => $phoneFormatted,
        'status' => $member->status,
        'status_label' => $member->status_label,
        'role' => $member->role->name ?? null,
        'departments' => $deptNames->values()->all(),
        'pgi' => $member->pgi->name ?? null,
        'photo_url' => $member->photo_url,
        'initials' => $member->initials,
        'avatar_color' => $member->avatar_color,
        'show_url' => route('members.show', $member),
        'edit_url' => ($canEditMembers ?? false) ? route('members.edit', $member) : null,
        'message_url' => $canMessageMember ? route('notificacoes.painel.index', ['member_id' => $member->id]) : null,
        'delete_url' => ($canDeleteMembers ?? false) ? route('members.destroy', $member) : null,
    ];
    // json_encode padrão escapa acentos (\u00e9) — seguro para base64 + atob no browser
    $memberModalB64 = base64_encode(json_encode($memberModalPayload));
@endphp
<button type="button"
        class="btn btn-link p-0 align-baseline fw-semibold members-name-btn {{ $class ?? '' }}"
        style="{{ $style ?? 'color:inherit;text-decoration:none;' }}"
        data-member-b64="{{ $memberModalB64 }}">
    {{ $member->name }}
</button>
