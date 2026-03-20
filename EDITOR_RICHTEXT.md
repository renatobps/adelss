# Editor Rich Text - TinyMCE 6

## Escolha técnica: TinyMCE 6

**Justificativa:**
- **Paste do Word/Google Docs**: TinyMCE tem filtro nativo excelente para colar do Word e Google Docs, mantendo formatação útil e removendo lixo.
- **Paste sem formatação**: Botão `pastetext` na toolbar (Ctrl+Shift+V).
- **HTML limpo**: Produz HTML semântico (p, h1–h4, ul, ol, strong, em).
- **Compatível com Laravel**: Integrado via Blade, sem build npm.
- **Licença**: GPL-2.0, adequada para uso interno.
- **Templates/blocos**: Plugin `template` para blocos prontos (Dia de propósito, listas, citações).
- **Manutenção**: Projeto ativo e amplamente usado (incl. WordPress).

## Instalação

O editor é carregado via CDN (jsDelivr). Não é necessário `npm` ou `composer` para o editor.

## Segurança - Sanitização

O helper `App\Helpers\HtmlHelper::sanitize()` trata o HTML antes de salvar:

1. **Com HTMLPurifier** (recomendado): `composer require mews/purifier` e `php artisan vendor:publish --tag=purifier-config`
2. **Sem HTMLPurifier**: usa fallback interno (strip_tags + remoção de scripts e atributos perigosos)

## Uso do componente

```blade
<x-rich-text-editor
    name="observacao"
    id="observacao"
    :value="old('observacao', $model->observacao ?? '')"
    placeholder="Digite o conteúdo..."
    :minHeight="320"
/>
```

## Funcionalidades da toolbar

- Desfazer/Refazer
- Blocos: Parágrafo, H1–H4, Citação
- Negrito, itálico, sublinhado, riscado
- Alinhamento (esquerda, centro, direita, justificado)
- Listas (ordenada e não ordenada)
- Link, emojis
- Remover formatação
- Colar como texto puro (Ctrl+Shift+V)
- Caracteres especiais
- Preview e tela cheia
- Templates prontos

## Renderização

O HTML salvo é exibido com `{!! $goal->observacao !!}` nas views `show.blade.php` e `pdf.blade.php`. Os estilos em `.observacao-content` garantem boa apresentação (parágrafos, listas, títulos, alinhamento).
