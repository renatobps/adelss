@php
    $retryDestinations = $post->destinations->filter(fn ($d) => $d->canRetry());
    $canRetryAny = $retryDestinations->isNotEmpty()
        && in_array($post->status, ['erro', 'erro_parcial'], true);
    $menuId = 'ig-post-actions-' . $post->id . '-' . ($menuSuffix ?? 'main');
@endphp
<div class="dropdown midia-post-actions">
    <button type="button"
            class="btn btn-sm btn-light midia-post-actions-btn"
            id="{{ $menuId }}"
            data-bs-toggle="dropdown"
            data-bs-auto-close="true"
            data-bs-popper-config='{"strategy":"fixed"}'
            aria-expanded="false"
            title="Ações">
        <i class="bx bx-dots-vertical-rounded"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="{{ $menuId }}">
        <li>
            <button type="button"
                    class="dropdown-item"
                    data-bs-toggle="modal"
                    data-bs-target="#igPostDetailsModal"
                    data-post-id="{{ $post->id }}">
                <i class="bx bx-info-circle me-2"></i> Ver detalhes
            </button>
        </li>
        @if($canRetryAny)
            @can('midia.instagram.schedule')
                @if($retryDestinations->count() === 1)
                    @php $retryDest = $retryDestinations->first(); @endphp
                    <li>
                        <form method="POST" action="{{ route('midia.instagram.posts.destinations.retry', $retryDest) }}"
                              onsubmit="return confirm('Tentar publicar este destino novamente?')">
                            @csrf
                            <button type="submit" class="dropdown-item text-warning">
                                <i class="bx bx-refresh me-2"></i> Tentar novamente
                            </button>
                        </form>
                    </li>
                @else
                    <li>
                        <button type="button"
                                class="dropdown-item text-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#igPostDetailsModal"
                                data-post-id="{{ $post->id }}">
                            <i class="bx bx-refresh me-2"></i> Tentar novamente
                        </button>
                    </li>
                @endif
            @endcan
        @endif
        @if($post->canDeleteLocally())
            @can('midia.instagram.schedule')
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('midia.instagram.posts.destroy', $post) }}"
                          onsubmit="return confirm(@json($post->status === 'agendado' || $post->status === 'erro' ? 'Excluir este registro?' : 'Remover só o registro do ADELSS? A publicação no Instagram (se existir) NÃO será apagada.'))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bx bx-trash me-2"></i>
                            {{ in_array($post->status, ['agendado', 'erro'], true) ? 'Excluir' : 'Remover registro' }}
                        </button>
                    </form>
                </li>
            @endcan
        @endif
    </ul>
</div>
