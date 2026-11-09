<section aria-label="Disciplinas do curso">
    <h3 class="h4 mb-3">Disciplinas e provas <span class="badge bg-primary">{{ $courseForms->count() }}</span></h3>
    <p class="text-muted">Estude pela apostila e faça a prova da disciplina. Ao finalizar, veja sua nota e consulte as respostas das questões que errou.</p>
    <div class="row g-3">
        @foreach($courseForms as $courseForm)
            <div class="col-md-6">
                <article class="border rounded p-3 h-100 d-flex flex-column">
                    <h4 class="h6">{{ $courseForm->title }}</h4>
                    <p class="small text-muted">{{ $courseForm->questions_count }} itens · Correção ao finalizar</p>
                    <div class="d-flex flex-wrap gap-2 mt-auto">
                        <a href="{{ $courseForm->materialUrl() }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">Ler apostila</a>
                        <a href="{{ $courseForm->examPdfUrl() }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">Prova PDF</a>
                        <a href="{{ $courseForm->publicUrl() }}" class="btn btn-success btn-sm">Fazer prova</a>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
</section>
