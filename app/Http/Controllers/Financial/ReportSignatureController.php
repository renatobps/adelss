<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Services\Financial\PdfSignatureService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportSignatureController extends Controller
{
    public function edit(PdfSignatureService $signatures)
    {
        $this->authorize('financial.view-reports');

        return view('financial.reports.signatures', [
            'tesoureiroNome' => $signatures->tesoureiroNome(),
            'tesoureiroAssinaturaSrc' => $signatures->imageSrc(PdfSignatureService::ROLE_TESOUREIRO),
        ]);
    }

    public function update(Request $request, PdfSignatureService $signatures)
    {
        $this->authorize('financial.view-reports');

        $validated = $request->validate([
            'role' => ['required', Rule::in([PdfSignatureService::ROLE_TESOUREIRO])],
            'assinatura' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ], [
            'assinatura.required' => 'Selecione a imagem da assinatura.',
            'assinatura.image' => 'Envie um arquivo de imagem (PNG, JPG ou WEBP).',
            'assinatura.max' => 'A imagem não pode ter mais de 2 MB.',
        ]);

        $signatures->store($validated['role'], $request->file('assinatura'));

        return redirect()
            ->route('financial.reports.signatures')
            ->with('success', 'Assinatura do 1º tesoureiro salva. Os próximos PDFs já saem assinados.');
    }

    public function destroy(Request $request, PdfSignatureService $signatures)
    {
        $this->authorize('financial.view-reports');

        $validated = $request->validate([
            'role' => ['required', Rule::in([PdfSignatureService::ROLE_TESOUREIRO])],
        ]);

        $signatures->delete($validated['role']);

        return redirect()
            ->route('financial.reports.signatures')
            ->with('success', 'Imagem da assinatura removida. O PDF volta a mostrar a linha para assinar à mão.');
    }
}
