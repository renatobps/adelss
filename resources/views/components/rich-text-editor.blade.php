@props([
    'name' => 'observacao',
    'id' => 'observacao',
    'value' => '',
    'placeholder' => 'Digite o conteúdo aqui...',
    'minHeight' => 280,
])

<div class="rich-text-editor-wrapper">
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        {{ $attributes->merge(['class' => 'form-control']) }}
        rows="4"
    >{{ $value }}</textarea>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('{{ $id }}');
    if (!textarea) return;

    tinymce.init({
        selector: '#{{ $id }}',
        base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6',
        suffix: '.min',
        language: 'pt',
        branding: false,
        promotion: false,
        height: {{ $minHeight }},
        menubar: false,
        statusbar: true,
        resize: true,
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333; }',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'charmap', 'emoticons',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'preview', 'help', 'wordcount',
            'paste', 'template'
        ],
        toolbar: 'undo redo | blocks | template | ' +
            'bold italic underline strikethrough | ' +
            'forecolor backcolor | ' +
            'alignleft aligncenter alignright alignjustify | ' +
            'bullist numlist outdent indent | ' +
            'link emoticons | table | removeformat | pastetext | ' +
            'charmap | preview fullscreen',
        block_formats: 'Parágrafo=p; Título 1=h1; Título 2=h2; Título 3=h3; Título 4=h4; Citação=blockquote',
        paste_as_text_shortcut: true,
        paste_data_images: false,
        paste_remove_styles: true,
        paste_remove_styles_if_webkit: true,
        paste_merge_formats: false,
        paste_enable_default_filters: true,
        paste_word_valid_elements: 'p,b,strong,i,em,u,h1,h2,h3,h4,h5,h6,ol,ul,li,a[href],span,br,table,thead,tbody,tr,th,td',
        paste_retain_attrs: [],
        paste_strip_class_attributes: 'all',
        templates: [
            { title: 'Caixa de texto', description: 'Caixa com borda para destacar texto', content: '<div style="border: 1px solid #cbd5e1; background-color: #f8fafc; padding: 12px 16px; margin: 10px 0; border-radius: 6px;"><p>Digite o texto aqui...</p></div>' },
            { title: 'Caixa de destaque', description: 'Caixa colorida para ênfase', content: '<div style="border-left: 4px solid #1a365d; background-color: #eff6ff; padding: 12px 16px; margin: 10px 0;"><p>Digite o texto destacado aqui...</p></div>' },
            { title: 'Dia de Propósito', description: 'Estrutura para um dia', content: '<h3>DIA 1 - Título</h3><p><strong>Leitura:</strong> </p><p><strong>Reflexão:</strong> </p><p><strong>Oração:</strong> </p><p><strong>Jejum:</strong> </p>' },
            { title: 'Lista numerada', description: 'Lista ordenada', content: '<ol><li>Item 1</li><li>Item 2</li><li>Item 3</li></ol>' },
            { title: 'Bloco de citação', description: 'Versículo ou citação', content: '<blockquote><p>Citação ou versículo bíblico aqui.</p></blockquote>' }
        ],
        link_default_target: '_blank',
        link_assume_external_targets: 'https',
        placeholder: '{{ addslashes($placeholder) }}',
        setup: function(editor) {
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

    // Garantir que o conteúdo seja enviado no submit
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
