<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignSponsor;
use App\Models\Member;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CampaignSponsorController extends Controller
{
    /**
     * Autocomplete de membros ativos para o modal de patrocinador.
     */
    public function searchMembers(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        $members = Member::query()
            ->where('status', 'ativo')
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'phone']);

        return response()->json(['data' => $members]);
    }

    public function store(Request $request, Campaign $campaign)
    {
        $data = $request->validate(
            [
                'type' => 'required|in:membro,avulso',
                'member_ids' => 'required_if:type,membro|nullable|array|min:1',
                'member_ids.*' => 'integer|exists:members,id',
                'name' => 'required_if:type,avulso|nullable|string|max:255',
                'phone' => 'nullable|string|max:30',
                'notes' => 'nullable|string|max:1000',
            ],
            [
                'member_ids.required_if' => 'Selecione ao menos um membro patrocinador.',
                'member_ids.min' => 'Selecione ao menos um membro patrocinador.',
                'name.required_if' => 'Informe o nome do patrocinador.',
            ]
        );

        // Avulso: um único patrocinador digitado à mão.
        if ($data['type'] === 'avulso') {
            DB::transaction(function () use ($campaign, $data) {
                $sponsor = $campaign->sponsors()->create([
                    'member_id' => null,
                    'name' => $data['name'],
                    'phone' => $data['phone'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                $sponsor->generateInstallments();
            });

            return redirect()
                ->route('financial.campaigns.show', $campaign)
                ->with('success', "Patrocinador \"{$data['name']}\" adicionado com {$campaign->installments_count} parcela(s).");
        }

        // Membros: vários de uma vez. Nome/telefone são copiados do cadastro
        // no momento da inclusão, para o histórico da campanha não mudar depois.
        $members = Member::whereIn('id', $data['member_ids'])->get();

        $existingMemberIds = $campaign->sponsors()
            ->whereNotNull('member_id')
            ->pluck('member_id')
            ->all();

        $added = 0;
        $skipped = 0;

        DB::transaction(function () use ($campaign, $members, $existingMemberIds, $data, &$added, &$skipped) {
            foreach ($members as $member) {
                if (in_array($member->id, $existingMemberIds, true)) {
                    $skipped++;
                    continue;
                }

                $sponsor = $campaign->sponsors()->create([
                    'member_id' => $member->id,
                    'name' => $member->name,
                    'phone' => $member->phone,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                $sponsor->generateInstallments();
                $added++;
            }
        });

        if ($added === 0) {
            return redirect()
                ->route('financial.campaigns.show', $campaign)
                ->with('warning', 'Nenhum patrocinador adicionado — os membros selecionados já participam desta campanha.');
        }

        $message = "{$added} patrocinador(es) adicionado(s), cada um com {$campaign->installments_count} parcela(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} ignorado(s) por já participarem da campanha.";
        }

        return redirect()
            ->route('financial.campaigns.show', $campaign)
            ->with('success', $message);
    }

    public function update(Request $request, CampaignSponsor $sponsor)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:1000',
        ]);

        $sponsor->update($data);

        return redirect()
            ->route('financial.campaigns.show', $sponsor->campaign_id)
            ->with('success', 'Patrocinador atualizado com sucesso.');
    }

    public function destroy(CampaignSponsor $sponsor)
    {
        $campaignId = $sponsor->campaign_id;

        if ($sponsor->paidInstallments()->exists()) {
            return redirect()
                ->route('financial.campaigns.show', $campaignId)
                ->with('error', 'Este patrocinador possui parcelas pagas e não pode ser removido. Estorne os pagamentos primeiro.');
        }

        $sponsor->delete();

        return redirect()
            ->route('financial.campaigns.show', $campaignId)
            ->with('success', 'Patrocinador removido com sucesso.');
    }

    /**
     * Carnê completo do patrocinador (todas as parcelas) em PDF.
     */
    public function carne(CampaignSponsor $sponsor)
    {
        $sponsor->load(['campaign.department', 'installments']);

        $pdf = Pdf::loadView('financial.campaigns.pdf.carne', [
            'campaign' => $sponsor->campaign,
            'groups' => [
                ['sponsor' => $sponsor, 'installments' => $sponsor->installments],
            ],
        ])->setPaper('a4');

        return $pdf->download('carne-' . Str::slug($sponsor->name) . '.pdf');
    }
}
