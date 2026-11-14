@php $nonce = $nonce ?? bin2hex(random_bytes(24)); @endphp
{!! app(\App\Services\EventLandingHtmlRenderer::class)->render($event, compact('customFields', 'mercadoPagoPublicKey', 'errors', 'nonce')) !!}
