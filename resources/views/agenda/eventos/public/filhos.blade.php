<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta name="description" content="{{ $event->description ?: $event->subtitle ?: $event->title }}">
  <meta property="og:title" content="{{ $event->title }}">
  <meta property="og:description" content="{{ $event->description ?: $event->subtitle ?: $event->title }}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#111111">



  <meta property="og:type" content="website">
  <title>{{ $event->title }} — ADEL</title>
  <link rel="stylesheet" href="{{ asset('vendor/vendor/bootstrap/css/bootstrap.css') }}">
  <link rel="stylesheet" href="{{ asset('events/templates/filhos/filhos.css') }}">
  <link rel="stylesheet" href="{{ asset('events/templates/filhos/integration.css') }}">
</head>
@php
    $bannerUrl = $event->bannerImagePublicUrl();
    $spotsLeft = null;
    if ($event->max_spots !== null && $event->max_spots > 0) {
        $spotsLeft = max(0, $event->max_spots - (int) ($event->registrations_em_vaga_count ?? 0));
    }
    $locationPhotoUrls = $event->locationPhotoPublicUrls();
    $pixPayment = session('pix_payment');
    $eventPriceJs = number_format((float) ($event->price ?? 0), 2, '.', '');
    $useConfiguredPayer = !$event->mercado_pago_account_id && !empty(config('mercadopago.test_payer_email')) && !empty(config('mercadopago.test_payer_document'));
@endphp
<body id="topo">
  <a class="skip" href="#conteudo">Pular para o conteúdo</a>
  <div class="wrap">
    <header>
      <a class="brand" href="#topo" aria-label="FILHOS — início"><span class="logo-frame"><img src="{{ asset('events/templates/filhos/juventude-jmais.png') }}" alt="Juventude J+ São Sebastião" width="500" height="500"></span><span class="brand-x" aria-hidden="true">×</span><span class="logo-frame church"><img src="{{ asset('events/templates/filhos/igreja-adel.png') }}" alt="Igreja ADEL São Sebastião" width="1000" height="1000"></span><span class="brand-sub">Uma geração.<br>Uma identidade.</span></a>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="navegacao">Menu +</button>
      <nav class="nav" id="navegacao" aria-label="Navegação principal">
        <a href="#sobre">A imersão</a><a href="#programacao">Programação</a><a href="#duvidas">Dúvidas</a><a class="nav-cta" href="#inscricao">Eu quero estar lá <span aria-hidden="true">↗</span></a>
      </nav>
    </header>
    <main id="conteudo">
      <div class="hero">
        <div class="hero-copy">
          <div class="eyebrow hero-label"><span class="dot" aria-hidden="true"></span> IMERSÃO DE JOVENS / PATERNIDADE</div>
          <h1>{{ $event->title }}</h1>
@if($event->subtitle)<p class="event-subtitle">{{ $event->subtitle }}</p>@endif
          <p class="hero-description">@if($event->description){{ $event->description }}@elseAntes de qualquer rótulo, uma identidade.<br><strong>Você tem um lugar. Você tem um Pai.</strong><br>Um dia para conhecer o coração de Deus e descobrir o que significa pertencer.@endif</p>
          <div class="hero-actions"><a href="#inscricao" class="button">Eu quero viver isso <span aria-hidden="true">↗</span></a><a class="text-link" href="#sobre">Conheça o encontro ↓</a></div>
          <div class="hero-meta">
            <span><svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3" y="5" width="14" height="12" rx="1"/><path d="M6 2v6m8-6v6M3 9h14"/></svg>{{ $event->start_date->translatedFormat('d \d\e F \d\e Y') }}</span>
            <span><svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="M10 5v5l3 2"/></svg>{{ $event->all_day ? "Dia inteiro" : $event->start_date->format("H:i") }}</span>
          </div>
        </div>
        <div class="hero-art">
  @if($bannerUrl)
  <img class="event-banner" src="{{ $bannerUrl }}" alt="{{ $event->title }}">
  @else
  <div class="poster" role="img" aria-label="Cartaz ABA, PAI. Chamados para pertencer. Romanos 8:15–16.">
    <svg class="poster-graphic" viewBox="0 0 500 560" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
      <defs><pattern id="poster-grid" width="35" height="35" patternUnits="userSpaceOnUse"><path d="M35 0H0v35" fill="none" stroke="#fff" stroke-opacity=".055" stroke-width=".7"/></pattern></defs>
      <path fill="#191919" d="M0 0h500v560H0z"/><path fill="url(#poster-grid)" d="M0 0h500v560H0z"/>
      <g fill="none" stroke="#f20850"><path d="M176 560V202a163 163 0 0 1 326 0v358" stroke-width="48"/><path d="M103 560V202a236 236 0 0 1 472 0v358"/><path d="M139 560V202a200 200 0 0 1 400 0v358"/><path d="M214 560V202a125 125 0 0 1 250 0v358"/></g>
      <g stroke="#f2f0e9" opacity=".5"><path d="M22 90h18m-9-9v18M444 438h18m-9-9v18"/></g>
    </svg>
    <div class="poster-top"><span>J+ × ADEL / Imersão</span><span>{{ $event->start_date->format('d.m.y') }} ↗</span></div>
    <div class="poster-title" aria-hidden="true">ABA.<br><span>PAI.</span></div>
    <div class="poster-sticker">CHAMADOS PARA PERTENCER</div>
    <div class="poster-bottom"><span>Paternidade de Deus<br>Identidade em Cristo</span><span>Romanos<br>8:15–16</span></div>
  </div>
  @endif
</div>
      </div>
      <div class="youth-strip" aria-label="Paternidade, identidade e pertencimento"><span>PATERNIDADE</span><b aria-hidden="true">✳</b><span>IDENTIDADE</span><b aria-hidden="true">✳</b><span>PERTENCIMENTO</span></div>
      <div class="date-strip" aria-label="Informações do encontro">
        <div class="date-item"><span class="eyebrow">Reserve a data</span><strong>{{ $event->start_date->format('d.m.Y') }}</strong></div>
        <div class="date-item"><span class="eyebrow">Um dia inteiro juntos</span><strong>{{ $event->all_day ? "Dia inteiro" : $event->start_date->format("H:i") . ($event->end_date ? " às " . $event->end_date->format("H:i") : "") }}</strong></div>
        <div class="date-item"><span class="eyebrow">Para viver em comunhão</span><strong>Imersão de jovens</strong></div>
        <div class="date-item"><span class="eyebrow">Nossa base</span><strong>Romanos 8:15–16</strong></div>
      </div>
      <section class="intro" id="sobre" aria-labelledby="sobre-titulo">
        <div><div class="section-kicker"><span class="num">01</span><span class="eyebrow">A imersão</span></div><h2>SUA HISTÓRIA.<br>SUA IDENTIDADE.<br><em>SEU PAI.</em></h2></div>
        <div class="intro-copy">@if($event->about_html){!! clean($event->about_html) !!}@else<p>Tem muita voz tentando dizer quem você é. E se você separasse um dia para ouvir o que Deus diz sobre você?</p><p><strong>FILHOS é uma imersão da juventude J+ e da ADEL São Sebastião sobre a paternidade de Deus.</strong> Um dia de Palavra, louvor, conversa e convivência, com espaço para ouvir, fazer perguntas e caminhar juntos.</p><p>Traga suas perguntas, sua história e sua vontade de caminhar. Tem espaço para você.</p><div class="intro-note">Conhecer o Pai. Descobrir-se filho. Viver em família.</div>@endif</div>
      </section>
      <section class="journey" aria-label="Os três momentos da imersão">
        <div class="journey-grid">
          <article class="journey-card"><div class="journey-head"><span class="journey-number">I.</span><svg class="journey-symbol" viewBox="0 0 48 48" aria-hidden="true"><path d="M11 40V20a13 13 0 0 1 26 0v20M6 40h36M19 40V22a5 5 0 0 1 10 0v18"/></svg></div><h3>Conhecer o Pai.</h3><p>Olhar para o caráter de Deus e descobrir seu acolhimento, sua graça e seu amor.</p><span class="reference">Lucas 15:11–32</span></article>
          <article class="journey-card"><div class="journey-head"><span class="journey-number">II.</span><svg class="journey-symbol" viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="16"/><circle cx="24" cy="19" r="5"/><path d="M14 36v-3a10 10 0 0 1 20 0v3M24 3v4m0 34v4M3 24h4m34 0h4"/></svg></div><h3>Reconhecer-se filho.</h3><p>Refletir sobre identidade e aprender a viver com a confiança de quem pertence à família de Deus.</p><span class="reference">Romanos 8:15–16</span></article>
          <article class="journey-card"><div class="journey-head"><span class="journey-number">III.</span><svg class="journey-symbol" viewBox="0 0 48 48" aria-hidden="true"><ellipse cx="24" cy="20" rx="18" ry="6"/><path d="M9 23v16m30-16v16M20 26v12m8-12v12M16 10V6m16 4V6M7 40h8m18 0h8"/></svg></div><h3>Viver como família.</h3><p>Levar o pertencimento para a vida: ouvir, acolher e cuidar de quem caminha ao nosso lado.</p><span class="reference">Efésios 2:19</span></article>
        </div>
      </section>
    </main>
  </div>
  <aside class="quote-band" aria-label="Mensagem central">
    <div class="wrap quote-layout"><span class="quote-mark" aria-hidden="true">“</span><blockquote>PERTENCER MUDA<br><em>COMO VOCÊ VIVE.</em></blockquote><cite>Filhos por adoção<small>À luz de Romanos 8:15–16</small></cite></div>
  </aside>
  <div class="wrap">
    <section id="programacao">
      <div class="schedule-head"><div><span class="eyebrow">O que vai rolar</span><h2>NOSSA<br><em>PROGRAMAÇÃO.</em></h2></div></div>
      @forelse($event->scheduleItems->groupBy('day') as $day => $items)
      <h3 class="event-day">Dia {{ $day ?: 1 }}</h3>
      @foreach($items as $item)
      <div class="schedule-row">
        <time>{{ $item->time_hh !== null ? sprintf('%02d:%02d', $item->time_hh, $item->time_mm ?? 0) : 'A confirmar' }}</time>
        <div><h3>{{ $item->title }}</h3><p>{{ $item->detail }}</p>
        @if($item->responsible_name)<p>{{ $item->responsible_name }}</p>@endif</div>
      </div>
      @endforeach
      @empty
      <p>A programação será divulgada em breve.</p>
      @endforelse
    </section>
    @if($event->speakers->isNotEmpty())
    <section id="palestrantes">
      <span class="eyebrow">Quem vai estar com a gente</span><h2>PALESTRANTES.</h2>
      <div class="event-speakers">
      @foreach($event->speakers as $speaker)
      <article>
        @if($speaker->photo_path)<img src="{{ \App\Models\Event::publicStorageUrl($speaker->photo_path) }}" alt="{{ $speaker->name }}" loading="lazy">@endif
        <h3>{{ $speaker->name }}</h3><p>{{ $speaker->description }}</p>
      </article>
      @endforeach
      </div>
    </section>
    @endif
    <section class="invitation" id="participar" aria-labelledby="participar-titulo">
      <div><div class="eyebrow">O convite está feito</div><h2>SEU LUGAR<br><em>É AQUI.</em></h2><p>Reserve o dia. Chame a galera.<br>Venha viver essa imersão com a gente.</p></div>
      <div class="invitation-info">
        <div class="info-row"><span class="eyebrow">Quando</span><p>{{ $event->start_date->translatedFormat('d \d\e F \d\e Y') }}<br>{{ $event->all_day ? "Dia inteiro" : $event->start_date->format("H:i") . ($event->end_date ? " às " . $event->end_date->format("H:i") : "") }}</p></div>
        <div class="info-row"><span class="eyebrow">Onde</span><p>{{ $event->location ?: 'Local a confirmar' }}</p></div>
        <div class="info-row"><span class="eyebrow">Inscrições</span><p>{{ $event->is_paid ? 'R$ ' . number_format((float) $event->price, 2, ',', '.') : 'Entrada gratuita' }}</p><p class="status">{{ $event->registration_enabled && $event->status === 'agendado' ? 'Confira a disponibilidade no formulário abaixo.' : 'Inscrições encerradas.' }}</p><a class="button" href="#inscricao">Ver inscrição ↗</a></div>
        <button type="button" class="button share-button" id="compartilhar">Compartilhar com a galera <span aria-hidden="true">↗</span></button><p class="share-status" id="share-status" role="status" aria-live="polite"></p>
      </div>
    </section>
    @if(count($locationPhotoUrls))
    <section id="local"><h2>CONHEÇA O LOCAL.</h2><div class="event-speakers">@foreach($locationPhotoUrls as $photoUrl)<img src="{{ $photoUrl }}" alt="Local do evento" loading="lazy">@endforeach</div></section>
    @endif
    <div class="filhos-registration">@include('agenda.eventos.public.partials.registration')</div>
    <section class="faq" id="duvidas" aria-labelledby="duvidas-titulo">
      <div><div class="section-kicker"><span class="num">03</span><span class="eyebrow">Ficou alguma dúvida?</span></div><h2>CHEGA MAIS.<br><em>A GENTE EXPLICA.</em></h2></div>
      <div>
        <details><summary>Para quem é a imersão?</summary><p>O encontro está sendo preparado para os jovens da igreja. A faixa etária e as orientações para convidados serão informadas pela organização.</p></details>
        <details><summary>Como faço minha inscrição?</summary><p>Use o formulário de inscrição nesta página. Após o envio, acompanhe a confirmação e as orientações apresentadas pelo sistema.</p></details>
        <details><summary>O que significa o tema “paternidade”?</summary><p>Vamos falar sobre a paternidade de Deus, nossa identidade como filhos e o pertencimento à sua família. Os textos centrais incluem Romanos 8:15–16 e Lucas 15:11–32.</p></details>
        <details><summary>Preciso compartilhar minha história pessoal?</summary><p>Não. As conversas e orações individuais são voluntárias. Cada participante pode acompanhar as atividades no seu ritmo, com respeito à sua história.</p></details>
        <details><summary>Haverá alimentação?</summary><p>A programação prevê café da manhã e almoço. Cardápio, condições de participação e orientações sobre restrições alimentares serão confirmados pela organização.</p></details>
      </div>
    </section>
<div class="organizers"><div><span class="eyebrow">Quem caminha junto</span><p>Uma imersão da juventude <strong>J+</strong><br>e da <strong>ADEL São Sebastião.</strong></p></div><div class="organizer-logos"><span class="logo-frame"><img src="{{ asset('events/templates/filhos/juventude-jmais.png') }}" alt="Juventude J+ São Sebastião" width="500" height="500"></span><span class="brand-x" aria-hidden="true">×</span><span class="logo-frame church"><img src="{{ asset('events/templates/filhos/igreja-adel.png') }}" alt="Igreja ADEL São Sebastião" width="1000" height="1000"></span></div></div>
    <footer><a class="brand" href="#topo" aria-label="FILHOS — voltar ao início"><span class="brand-name">FILHOS</span></a><span class="footer-tagline">Você tem um lugar. Você tem um Pai.</span><a class="back-top" href="#topo">Voltar ao início ↑</a><span class="footer-note">Imersão de jovens · 2026<br>Paternidade, identidade e pertencimento.</span></footer>
  </div>
  <script>
    const menuButton=document.querySelector('.menu-toggle');
    const navigation=document.querySelector('#navegacao');
    function closeMenu(){menuButton.setAttribute('aria-expanded','false');navigation.classList.remove('open');menuButton.textContent='Menu +';}
    menuButton.addEventListener('click',()=>{const open=menuButton.getAttribute('aria-expanded')!=='true';menuButton.setAttribute('aria-expanded',String(open));navigation.classList.toggle('open',open);menuButton.textContent=open?'Fechar −':'Menu +';});
    navigation.querySelectorAll('a').forEach(link=>link.addEventListener('click',closeMenu));
    document.addEventListener('keydown',event=>{if(event.key==='Escape'&&navigation.classList.contains('open')){closeMenu();menuButton.focus();}});
    const tabs=Array.from(document.querySelectorAll('[role="tab"]'));
    function selectTab(tab){tabs.forEach(item=>{const selected=item===tab;item.setAttribute('aria-selected',String(selected));item.tabIndex=selected?0:-1;document.getElementById(item.getAttribute('aria-controls')).hidden=!selected;});}
    tabs.forEach((tab,index)=>{tab.addEventListener('click',()=>selectTab(tab));tab.addEventListener('keydown',event=>{let next;if(event.key==='ArrowRight')next=(index+1)%tabs.length;else if(event.key==='ArrowLeft')next=(index-1+tabs.length)%tabs.length;else if(event.key==='Home')next=0;else if(event.key==='End')next=tabs.length-1;else return;event.preventDefault();selectTab(tabs[next]);tabs[next].focus();});});
    document.getElementById('compartilhar').addEventListener('click',async()=>{
      const status=document.getElementById('share-status');
      const text=@json($event->title . ' — ' . $event->start_date->format('d/m/Y') . ' — ' . ($event->location ?: 'Local a confirmar'));
      const isWeb=location.protocol==='https:'||location.protocol==='http:';
      const data={title:@json($event->title),text};
      if(isWeb)data.url=location.href.split('#')[0];
      try{if(navigator.share){await navigator.share(data);status.textContent='Convite compartilhado.';}else if(navigator.clipboard&&window.isSecureContext){await navigator.clipboard.writeText(text+(isWeb?'\n'+data.url:''));status.textContent='Convite copiado! Cole na sua conversa.';}else{window.prompt('Copie o convite para compartilhar:',text+(isWeb?'\n'+data.url:''));status.textContent='Use o texto do convite para compartilhar.';}}catch(error){if(error.name!=='AbortError'){window.prompt('Copie o convite para compartilhar:',text+(isWeb?'\n'+data.url:''));status.textContent='Use o texto do convite para compartilhar.';}}
    });
  </script>
@include('agenda.eventos.public.partials.registration-scripts')
</body>
</html>
