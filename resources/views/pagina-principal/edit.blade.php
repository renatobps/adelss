@extends('layouts.porto')

@section('title', 'Página Principal')
@section('page-title')
    <i class="bx bx-globe me-2"></i> Página Principal
@endsection
@section('breadcrumbs')
    <li><span>Página Principal</span></li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-primary">
        <i class="bx bx-link-external me-1"></i> Ver página principal
    </a>
</div>

<form action="{{ route('pagina-principal.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-12 mb-4">
            <section class="card">
                <header class="card-header">
                    <h5 class="mb-0"><i class="bx bx-image me-2"></i> Hero e Banner</h5>
                </header>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="hero_eyebrow" class="form-label">Eyebrow do hero</label>
                            <input type="text" class="form-control @error('hero_eyebrow') is-invalid @enderror"
                                   id="hero_eyebrow" name="hero_eyebrow" value="{{ old('hero_eyebrow', $settings->hero_eyebrow) }}">
                            @error('hero_eyebrow')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="hero_title" class="form-label">Título do hero</label>
                            <input type="text" class="form-control @error('hero_title') is-invalid @enderror"
                                   id="hero_title" name="hero_title" value="{{ old('hero_title', $settings->hero_title) }}">
                            @error('hero_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="banner_image" class="form-label">Imagem de banner</label>
                            <input type="file" class="form-control @error('banner_image') is-invalid @enderror"
                                   id="banner_image" name="banner_image" accept="image/*">
                            @if($settings->banner_image)
                                <small class="text-muted">Banner atual configurado. Envie um novo arquivo para substituir.</small>
                            @endif
                            @error('banner_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 mb-3">
                            <label for="hero_subtitle" class="form-label">Subtítulo</label>
                            <textarea class="form-control @error('hero_subtitle') is-invalid @enderror"
                                      id="hero_subtitle" name="hero_subtitle" rows="3">{{ old('hero_subtitle', $settings->hero_subtitle) }}</textarea>
                            @error('hero_subtitle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="hero_cta_primary_label" class="form-label">CTA primário — texto</label>
                            <input type="text" class="form-control" id="hero_cta_primary_label" name="hero_cta_primary_label"
                                   value="{{ old('hero_cta_primary_label', $settings->hero_cta_primary_label) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="hero_cta_primary_url" class="form-label">CTA primário — URL</label>
                            <input type="text" class="form-control" id="hero_cta_primary_url" name="hero_cta_primary_url"
                                   value="{{ old('hero_cta_primary_url', $settings->hero_cta_primary_url) }}" placeholder="#contato ou https://...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="hero_cta_secondary_label" class="form-label">CTA secundário — texto</label>
                            <input type="text" class="form-control" id="hero_cta_secondary_label" name="hero_cta_secondary_label"
                                   value="{{ old('hero_cta_secondary_label', $settings->hero_cta_secondary_label) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="hero_cta_secondary_url" class="form-label">CTA secundário — URL</label>
                            <input type="text" class="form-control" id="hero_cta_secondary_url" name="hero_cta_secondary_url"
                                   value="{{ old('hero_cta_secondary_url', $settings->hero_cta_secondary_url) }}" placeholder="#assista ou https://...">
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 mb-4">
            <section class="card">
                <header class="card-header">
                    <h5 class="mb-0"><i class="bx bx-book-open me-2"></i> Sobre Nós</h5>
                </header>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="about_eyebrow" class="form-label">Eyebrow</label>
                            <input type="text" class="form-control @error('about_eyebrow') is-invalid @enderror"
                                   id="about_eyebrow" name="about_eyebrow" value="{{ old('about_eyebrow', $settings->about_eyebrow) }}">
                            @error('about_eyebrow')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="about_title" class="form-label">Título</label>
                            <input type="text" class="form-control @error('about_title') is-invalid @enderror"
                                   id="about_title" name="about_title" value="{{ old('about_title', $settings->about_title) }}">
                            @error('about_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 mb-3">
                            <label for="about_text" class="form-label">Texto</label>
                            <textarea class="form-control @error('about_text') is-invalid @enderror"
                                      id="about_text" name="about_text" rows="5">{{ old('about_text', $settings->about_text) }}</textarea>
                            @error('about_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="about_highlight_word" class="form-label">Palavra de destaque</label>
                            <input type="text" class="form-control @error('about_highlight_word') is-invalid @enderror"
                                   id="about_highlight_word" name="about_highlight_word" value="{{ old('about_highlight_word', $settings->about_highlight_word) }}">
                            @error('about_highlight_word')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="about_link_label" class="form-label">Texto do link</label>
                            <input type="text" class="form-control @error('about_link_label') is-invalid @enderror"
                                   id="about_link_label" name="about_link_label" value="{{ old('about_link_label', $settings->about_link_label) }}">
                            @error('about_link_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="about_link_url" class="form-label">URL do link</label>
                            <input type="text" class="form-control @error('about_link_url') is-invalid @enderror"
                                   id="about_link_url" name="about_link_url" value="{{ old('about_link_url', $settings->about_link_url) }}" placeholder="#contato ou https://...">
                            @error('about_link_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-0">
                            <label for="about_bible_reference" class="form-label">Referência bíblica (opcional)</label>
                            <input type="text" class="form-control @error('about_bible_reference') is-invalid @enderror"
                                   id="about_bible_reference" name="about_bible_reference" value="{{ old('about_bible_reference', $settings->about_bible_reference) }}" placeholder="Mateus 28:19">
                            @error('about_bible_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 mb-4">
            <section class="card">
                <header class="card-header">
                    <h5 class="mb-0"><i class="bx bx-phone me-2"></i> Contato e Horários</h5>
                </header>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="service_times_text" class="form-label">Horários de culto</label>
                            <input type="text" class="form-control" id="service_times_text" name="service_times_text"
                                   value="{{ old('service_times_text', $settings->service_times_text) }}" placeholder="Domingo, 9h e 19h">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="phone" name="phone"
                                   value="{{ old('phone', $settings->phone) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="address_text" class="form-label">Endereço (linha 1)</label>
                            <input type="text" class="form-control" id="address_text" name="address_text"
                                   value="{{ old('address_text', $settings->address_text) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="address_line2" class="form-label">Endereço (linha 2)</label>
                            <input type="text" class="form-control" id="address_line2" name="address_line2"
                                   value="{{ old('address_line2', $settings->address_line2) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_email" class="form-label">E-mail de contato</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email"
                                   value="{{ old('contact_email', $settings->contact_email) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="whatsapp_number" class="form-label">WhatsApp</label>
                            <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number"
                                   value="{{ old('whatsapp_number', $settings->whatsapp_number) }}" placeholder="5511999999999">
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-md-6 mb-4">
            <section class="card h-100">
                <header class="card-header">
                    <h5 class="mb-0"><i class="bx bx-building me-2"></i> Departamentos exibidos</h5>
                </header>
                <div class="card-body">
                    <p class="text-muted">Os cards de departamentos na home são controlados em cada departamento, pelo toggle <strong>Exibir na Página Principal</strong>.</p>
                    <a href="{{ route('departments.index') }}" class="btn btn-default">
                        <i class="bx bx-link-external me-1"></i> Gerenciar departamentos
                    </a>
                </div>
            </section>
        </div>

        <div class="col-md-6 mb-4">
            <section class="card h-100">
                <header class="card-header">
                    <h5 class="mb-0"><i class="bx bx-group me-2"></i> Card de Pequenos Grupos</h5>
                </header>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="pgi_card_show" name="pgi_card_show" value="1"
                               {{ old('pgi_card_show', $settings->pgi_card_show) ? 'checked' : '' }}>
                        <label class="form-check-label" for="pgi_card_show">Exibir card de PGIs</label>
                    </div>
                    <div class="mb-3">
                        <label for="pgi_card_title" class="form-label">Título</label>
                        <input type="text" class="form-control" id="pgi_card_title" name="pgi_card_title"
                               value="{{ old('pgi_card_title', $settings->pgi_card_title) }}">
                    </div>
                    <div class="mb-3">
                        <label for="pgi_card_description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="pgi_card_description" name="pgi_card_description" rows="3">{{ old('pgi_card_description', $settings->pgi_card_description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="pgi_card_image" class="form-label">Imagem do card</label>
                        <input type="file" class="form-control @error('pgi_card_image') is-invalid @enderror"
                               id="pgi_card_image" name="pgi_card_image" accept="image/*">
                        @if($settings->pgi_card_image)
                            <small class="text-muted d-block mt-1">
                                Imagem atual:
                                <a href="{{ $settings->pgiCardImageUrl() }}" target="_blank" rel="noopener">ver</a>
                            </small>
                        @endif
                        @error('pgi_card_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label for="pgi_card_url" class="form-label">Link do card</label>
                        <input type="text" class="form-control" id="pgi_card_url" name="pgi_card_url"
                               value="{{ old('pgi_card_url', $settings->pgi_card_url) }}" placeholder="Deixe vazio para usar o Portal do Membro">
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 mb-4">
            <section class="card">
                <header class="card-header">
                    <h5 class="mb-0"><i class="bx bx-directions me-2"></i> Próximos Passos</h5>
                </header>
                <div class="card-body">
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="show_next_steps_section" name="show_next_steps_section" value="1"
                               {{ old('show_next_steps_section', $settings->show_next_steps_section ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="show_next_steps_section">Exibir seção Próximos Passos</label>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label for="next_steps_eyebrow" class="form-label">Eyebrow</label>
                            <input type="text" class="form-control" id="next_steps_eyebrow" name="next_steps_eyebrow"
                                   value="{{ old('next_steps_eyebrow', $settings->next_steps_eyebrow) }}">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="next_steps_title" class="form-label">Título da seção</label>
                            <input type="text" class="form-control" id="next_steps_title" name="next_steps_title"
                                   value="{{ old('next_steps_title', $settings->next_steps_title) }}">
                        </div>
                        <div class="col-12 mb-0">
                            <label for="next_steps_intro" class="form-label">Texto introdutório</label>
                            <textarea class="form-control" id="next_steps_intro" name="next_steps_intro" rows="3">{{ old('next_steps_intro', $settings->next_steps_intro) }}</textarea>
                        </div>
                    </div>

                    @php
                        $nextStepsCards = old('next_steps_cards', $settings->nextStepsCardsForForm());
                    @endphp

                    <div class="row g-3">
                        @foreach($nextStepsCards as $index => $card)
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">Card {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</h6>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox"
                                                   id="next_steps_cards_{{ $index }}_enabled"
                                                   name="next_steps_cards[{{ $index }}][enabled]" value="1"
                                                   {{ !empty($card['enabled']) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="next_steps_cards_{{ $index }}_enabled">Ativo</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="next_steps_cards_{{ $index }}_title">Título</label>
                                        <input type="text" class="form-control"
                                               id="next_steps_cards_{{ $index }}_title"
                                               name="next_steps_cards[{{ $index }}][title]"
                                               value="{{ $card['title'] ?? '' }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="next_steps_cards_{{ $index }}_text">Texto</label>
                                        <textarea class="form-control" rows="3"
                                                  id="next_steps_cards_{{ $index }}_text"
                                                  name="next_steps_cards[{{ $index }}][text]">{{ $card['text'] ?? '' }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="next_steps_cards_{{ $index }}_link_url">Link (URL)</label>
                                        <input type="text" class="form-control"
                                               id="next_steps_cards_{{ $index }}_link_url"
                                               name="next_steps_cards[{{ $index }}][link_url]"
                                               value="{{ $card['link_url'] ?? '' }}"
                                               placeholder="#contato, https://... ou @pgi / @portal">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="next_steps_cards_{{ $index }}_link_label">Texto do link</label>
                                        <input type="text" class="form-control"
                                               id="next_steps_cards_{{ $index }}_link_label"
                                               name="next_steps_cards[{{ $index }}][link_label]"
                                               value="{{ $card['link_label'] ?? '' }}">
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox"
                                               id="next_steps_cards_{{ $index }}_show_link"
                                               name="next_steps_cards[{{ $index }}][show_link]" value="1"
                                               {{ !empty($card['show_link']) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="next_steps_cards_{{ $index }}_show_link">Exibir link no card</label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mt-3">
                        Atalhos de link: <code>@whatsapp</code> abre o WhatsApp da igreja; <code>@pgi</code> usa o link do card de Pequenos Grupos; <code>@portal</code> usa o Portal do Membro.
                    </small>
                </div>
            </section>
        </div>

        <div class="col-md-6 mb-4">
            <section class="card h-100">
                <header class="card-header">
                    <h5 class="mb-0"><i class="bx bx-calendar me-2"></i> Eventos</h5>
                </header>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="show_events_section" name="show_events_section" value="1"
                               {{ old('show_events_section', $settings->show_events_section) ? 'checked' : '' }}>
                        <label class="form-check-label" for="show_events_section">Exibir seção de eventos</label>
                    </div>
                    <div class="mb-0">
                        <label for="events_count" class="form-label">Quantidade de eventos</label>
                        <input type="number" class="form-control" id="events_count" name="events_count" min="1" max="12"
                               value="{{ old('events_count', $settings->events_count) }}">
                    </div>
                </div>
            </section>
        </div>

        <div class="col-md-6 mb-4">
            <section class="card h-100">
                <header class="card-header">
                    <h5 class="mb-0"><i class="bx bx-video me-2"></i> Seção Assista</h5>
                </header>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="show_watch_section" name="show_watch_section" value="1"
                               {{ old('show_watch_section', $settings->show_watch_section) ? 'checked' : '' }}>
                        <label class="form-check-label" for="show_watch_section">Exibir seção Assista</label>
                    </div>
                    <div class="mb-0">
                        <label for="watch_video_url" class="form-label">URL do vídeo / transmissão</label>
                        <input type="text" class="form-control" id="watch_video_url" name="watch_video_url"
                               value="{{ old('watch_video_url', $settings->watch_video_url) }}" placeholder="https://youtube.com/...">
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 mb-4">
            <section class="card">
                <header class="card-header">
                    <h5 class="mb-0"><i class="bx bx-share-alt me-2"></i> Redes Sociais e Rodapé</h5>
                </header>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="social_facebook" class="form-label">Facebook</label>
                            <input type="url" class="form-control" id="social_facebook" name="social_facebook"
                                   value="{{ old('social_facebook', $settings->social_facebook) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="social_instagram" class="form-label">Instagram</label>
                            <input type="url" class="form-control" id="social_instagram" name="social_instagram"
                                   value="{{ old('social_instagram', $settings->social_instagram) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="social_youtube" class="form-label">YouTube</label>
                            <input type="url" class="form-control" id="social_youtube" name="social_youtube"
                                   value="{{ old('social_youtube', $settings->social_youtube) }}">
                        </div>
                        <div class="col-12 mb-0">
                            <label for="footer_text" class="form-label">Texto do rodapé</label>
                            <textarea class="form-control" id="footer_text" name="footer_text" rows="3">{{ old('footer_text', $settings->footer_text) }}</textarea>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bx bx-save me-1"></i> Salvar configurações
        </button>
    </div>
</form>
@endsection
