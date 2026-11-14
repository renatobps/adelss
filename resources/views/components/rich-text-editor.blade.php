@props([
    'name' => 'observacao',
    'id' => 'observacao',
    'value' => '',
    'placeholder' => 'Digite o conteúdo aqui...',
    'minHeight' => 280,
    'uploadUrl' => null,
])

@php
    $plugins = [
        'advlist', 'autolink', 'lists', 'link', 'charmap', 'emoticons',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'preview', 'help', 'wordcount',
    ];
    if ($uploadUrl) {
        $plugins[] = 'image';
    }
    $toolbarLinkGroup = $uploadUrl
        ? 'link image emoticons | table | removeformat | pastetext | charmap | preview fullscreen'
        : 'link emoticons | table | removeformat | pastetext | charmap | preview fullscreen';
@endphp

<div class="rich-text-editor-wrapper">
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        {{ $attributes->merge(['class' => 'form-control']) }}
        rows="4"
    >{{ $value }}</textarea>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('{{ $id }}');
    if (!textarea) return;

    const editorTemplates = [
        { title: 'Caixa de texto', description: 'Caixa com borda para destacar texto', content: '<div style="border: 1px solid #cbd5e1; background-color: #f8fafc; padding: 12px 16px; margin: 10px 0; border-radius: 6px;"><p>Digite o texto aqui...</p></div>' },
        { title: 'Caixa de destaque', description: 'Caixa colorida para ênfase', content: '<div style="border-left: 4px solid #1a365d; background-color: #eff6ff; padding: 12px 16px; margin: 10px 0;"><p>Digite o texto destacado aqui...</p></div>' },
        { title: 'Dia de Propósito', description: 'Estrutura para um dia', content: '<h3>DIA 1 - Título</h3><p><strong>Leitura:</strong> </p><p><strong>Reflexão:</strong> </p><p><strong>Oração:</strong> </p><p><strong>Jejum:</strong> </p>' },
        { title: 'Lista numerada', description: 'Lista ordenada', content: '<ol><li>Item 1</li><li>Item 2</li><li>Item 3</li></ol>' },
        { title: 'Bloco de citação', description: 'Versículo ou citação', content: '<blockquote><p>Citação ou versículo bíblico aqui.</p></blockquote>' }
    ];

    tinymce.init({
        selector: '#{{ $id }}',
        base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.6',
        suffix: '.min',
        language: 'pt_BR',
        language_url: {{ \Illuminate\Support\Js::from(asset('vendor/tinymce/langs/pt_BR.js')) }},
        branding: false,
        promotion: false,
        height: {{ $minHeight }},
        menubar: false,
        statusbar: true,
        resize: true,
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333; }',
        plugins: @json($plugins),
        toolbar: 'undo redo | blocks | modelos | ' +
            'bold italic underline strikethrough | ' +
            'forecolor backcolor | ' +
            'alignleft aligncenter alignright alignjustify | ' +
            'bullist numlist outdent indent | ' +
            '{{ $toolbarLinkGroup }}',
        block_formats: 'Parágrafo=p; Título 1=h1; Título 2=h2; Título 3=h3; Título 4=h4; Citação=blockquote',

        paste_data_images: {{ $uploadUrl ? 'true' : 'false' }},

        paste_merge_formats: false,

        @if($uploadUrl)
        automatic_uploads: true,
        images_upload_handler: function (blobInfo, progress) {
            return new Promise(function (resolve, reject) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', {{ \Illuminate\Support\Js::from($uploadUrl) }});
                xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.upload.onprogress = function (e) {
                    if (e.lengthComputable) {
                        progress(e.loaded / e.total * 100);
                    }
                };
                xhr.onload = function () {
                    if (xhr.status === 422) {
                        try {
                            var err = JSON.parse(xhr.responseText);
                            reject(err.message || (err.errors && err.errors.file && err.errors.file[0]) || 'Arquivo inválido');
                        } catch (ex) {
                            reject('Arquivo inválido');
                        }
                        return;
                    }
                    if (xhr.status < 200 || xhr.status >= 300) {
                        reject('Erro HTTP ' + xhr.status);
                        return;
                    }
                    try {
                        var json = JSON.parse(xhr.responseText);
                        if (!json || typeof json.location !== 'string') {
                            reject('Resposta inválida do servidor');
                            return;
                        }
                        resolve(json.location);
                    } catch (ex) {
                        reject('Resposta inválida do servidor');
                    }
                };
                xhr.onerror = function () {
                    reject('Falha de rede ao enviar imagem');
                };
                var formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                xhr.send(formData);
            });
        },
        @endif
        link_default_target: '_blank',
        link_assume_external_targets: 'https',
        placeholder: '{{ addslashes($placeholder) }}',
        setup: function(editor) {
            editor.ui.registry.addMenuButton('modelos', {
                text: 'Modelos',
                fetch: function(callback) {
                    callback(editorTemplates.map(function(item) {
                        return {
                            type: 'menuitem',
                            text: item.title,
                            onAction: function() { editor.insertContent(item.content); }
                        };
                    }));
                }
            });
            editor.on('init', function() {
                if (textarea.value) {
                    editor.setContent(textarea.value);
                }
            });
            editor.on('change keyup', function() {
                textarea.value = editor.getContent();
            });
        },
        init_instance_callback: function(editor) {
            editor.on('submit', function() {
                textarea.value = editor.getContent();
            });
        }
    });

    const form = textarea.closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            if (tinymce.get('{{ $id }}')) {
                tinymce.get('{{ $id }}').save();
            }
        });
    }
});
</script>
@endpush
