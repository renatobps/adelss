<?php

namespace App\Http\Controllers;

use App\Models\HomePageSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HomePageSettingController extends Controller
{
    public function edit(): View
    {
        $settings = HomePageSetting::current();

        return view('pagina-principal.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = HomePageSetting::current();

        $validated = $request->validate([
            'hero_title' => 'nullable|string|max:255',
            'hero_eyebrow' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:2000',
            'hero_cta_primary_label' => 'nullable|string|max:120',
            'hero_cta_primary_url' => 'nullable|string|max:500',
            'hero_cta_secondary_label' => 'nullable|string|max:120',
            'hero_cta_secondary_url' => 'nullable|string|max:500',
            'about_eyebrow' => 'nullable|string|max:120',
            'about_title' => 'nullable|string|max:255',
            'about_text' => 'nullable|string|max:4000',
            'about_highlight_word' => 'nullable|string|max:120',
            'about_link_label' => 'nullable|string|max:120',
            'about_link_url' => 'nullable|string|max:500',
            'about_bible_reference' => 'nullable|string|max:120',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'service_times_text' => 'nullable|string|max:255',
            'address_text' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'contact_email' => 'nullable|email|max:255',
            'whatsapp_number' => 'nullable|string|max:30',
            'show_watch_section' => 'nullable|boolean',
            'watch_video_url' => 'nullable|string|max:500',
            'show_events_section' => 'nullable|boolean',
            'events_count' => 'nullable|integer|min:1|max:12',
            'pgi_card_show' => 'nullable|boolean',
            'pgi_card_title' => 'nullable|string|max:120',
            'pgi_card_description' => 'nullable|string|max:1000',
            'pgi_card_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'pgi_card_url' => 'nullable|string|max:500',
            'show_next_steps_section' => 'nullable|boolean',
            'next_steps_eyebrow' => 'nullable|string|max:120',
            'next_steps_title' => 'nullable|string|max:255',
            'next_steps_intro' => 'nullable|string|max:2000',
            'next_steps_cards' => 'nullable|array|max:4',
            'next_steps_cards.*.enabled' => 'nullable|boolean',
            'next_steps_cards.*.title' => 'nullable|string|max:120',
            'next_steps_cards.*.text' => 'nullable|string|max:1000',
            'next_steps_cards.*.link_url' => 'nullable|string|max:500',
            'next_steps_cards.*.link_label' => 'nullable|string|max:120',
            'next_steps_cards.*.show_link' => 'nullable|boolean',
            'social_facebook' => 'nullable|string|max:500',
            'social_instagram' => 'nullable|string|max:500',
            'social_youtube' => 'nullable|string|max:500',
            'footer_text' => 'nullable|string|max:2000',
        ]);

        $validated['show_watch_section'] = $request->boolean('show_watch_section');
        $validated['show_events_section'] = $request->boolean('show_events_section');
        $validated['pgi_card_show'] = $request->boolean('pgi_card_show');
        $validated['show_next_steps_section'] = $request->boolean('show_next_steps_section');
        $validated['hero_cta_primary_label'] = $validated['hero_cta_primary_label'] ?? 'Planeje sua visita';
        $validated['hero_cta_secondary_label'] = $validated['hero_cta_secondary_label'] ?? 'Assista uma mensagem';
        $validated['pgi_card_title'] = $validated['pgi_card_title'] ?? 'Pequenos Grupos';
        $validated['next_steps_cards'] = $this->normalizeNextStepsCards($request->input('next_steps_cards', []));

        if ($request->hasFile('banner_image')) {
            if ($settings->banner_image) {
                Storage::disk('public')->delete($settings->banner_image);
            }

            $validated['banner_image'] = $request->file('banner_image')->store('home-page', 'public');
        } else {
            unset($validated['banner_image']);
        }

        if ($request->hasFile('pgi_card_image')) {
            if ($settings->pgi_card_image) {
                Storage::disk('public')->delete($settings->pgi_card_image);
            }

            $validated['pgi_card_image'] = $request->file('pgi_card_image')->store('home-page/pgi', 'public');
        } else {
            unset($validated['pgi_card_image']);
        }

        $settings->update($validated);

        return redirect()
            ->route('pagina-principal.edit')
            ->with('success', 'Configurações da página principal salvas com sucesso!');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array{enabled: bool, title: string, text: string, link_url: string, link_label: string, show_link: bool}>
     */
    private function normalizeNextStepsCards(array $rows): array
    {
        $cards = [];

        foreach (array_slice($rows, 0, 4) as $row) {
            $cards[] = [
                'enabled' => !empty($row['enabled']),
                'title' => trim((string) ($row['title'] ?? '')),
                'text' => trim((string) ($row['text'] ?? '')),
                'link_url' => trim((string) ($row['link_url'] ?? '')),
                'link_label' => trim((string) ($row['link_label'] ?? '')),
                'show_link' => !empty($row['show_link']),
            ];
        }

        return $cards;
    }
}
