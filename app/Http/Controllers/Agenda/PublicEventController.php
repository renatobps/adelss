<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PublicEventController extends Controller
{
    public function show(string $slug)
    {
        $event = Event::query()
            ->where('public_slug', $slug)
            ->where('visibility', 'public')
            ->whereIn('status', ['agendado', 'concluido'])
            ->with(['scheduleItems', 'registrationFields', 'speakers', 'category'])
            ->withCount([
                'registrations as registrations_em_vaga_count' => function ($q) {
                    $q->whereIn('status', [
                        EventRegistration::STATUS_PENDENTE,
                        EventRegistration::STATUS_CONFIRMADO,
                    ]);
                },
            ])
            ->firstOrFail();

        $customFields = $event->registrationFields
            ->filter(fn ($field) => ! $this->isDefaultRegistrationFieldName((string) $field->name))
            ->values();

        return view('agenda.eventos.public.show', compact('event', 'customFields'));
    }

    public function register(Request $request, string $slug)
    {
        $event = Event::query()
            ->where('public_slug', $slug)
            ->where('visibility', 'public')
            ->where('status', 'agendado')
            ->with('registrationFields')
            ->firstOrFail();

        if (!$event->registration_enabled) {
            return back()->with('error', 'As inscrições estão encerradas para este evento.');
        }

        if ($event->max_spots !== null && $event->max_spots > 0) {
            $count = $event->registrations()
                ->whereIn('status', [EventRegistration::STATUS_PENDENTE, EventRegistration::STATUS_CONFIRMADO])
                ->count();
            if ($count >= $event->max_spots) {
                return back()->with('error', 'Não há mais vagas disponíveis para este evento.');
            }
        }

        $rules = [
            'name' => 'required|string|max:255',
        ];

        if ($event->email_required) {
            $rules['email'] = 'required|email|max:255';
        } else {
            $rules['email'] = 'nullable|email|max:255';
        }

        if ($event->phone_required) {
            $rules['phone'] = ['required', 'regex:/^\(\d{2}\)\s\d{5}-\d{4}$/'];
        } else {
            $rules['phone'] = ['nullable', 'regex:/^\(\d{2}\)\s\d{5}-\d{4}$/'];
        }

        if ($event->address_required) {
            $rules['address'] = 'required|string|max:500';
        } else {
            $rules['address'] = 'nullable|string|max:500';
        }

        $custom = [];
        foreach ($event->registrationFields as $field) {
            if ($this->isDefaultRegistrationFieldName((string) $field->name)) {
                continue;
            }
            $key = 'custom.'.$field->id;
            $rule = ['nullable', 'string', 'max:2000'];
            if ($this->isAgeRegistrationFieldName((string) $field->name)) {
                $rule = ['nullable', 'integer', 'min:0', 'max:130'];
            }
            if ($field->required) {
                if ($this->isAgeRegistrationFieldName((string) $field->name)) {
                    $rule = ['required', 'integer', 'min:0', 'max:130'];
                } else {
                    $rule = ['required', 'string', 'max:2000'];
                }
            }
            $rules[$key] = $rule;
        }

        $validated = $request->validate($rules);

        $customAnswers = [];
        foreach ($event->registrationFields as $field) {
            if ($this->isDefaultRegistrationFieldName((string) $field->name)) {
                continue;
            }
            $val = $request->input('custom.'.$field->id);
            if ($val !== null && $val !== '') {
                $customAnswers[$field->id] = $val;
            }
        }

        $registration = EventRegistration::create([
            'event_id' => $event->id,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'custom_answers' => $customAnswers ?: null,
            'status' => EventRegistration::STATUS_PENDENTE,
        ]);

        if (!empty($event->notify_emails)) {
            $emails = array_filter(array_map('trim', explode(',', $event->notify_emails)));
            foreach ($emails as $to) {
                if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    try {
                        Mail::raw(
                            "Nova inscrição no evento \"{$event->title}\".\nNome: {$registration->name}\nE-mail: ".($registration->email ?? '-'),
                            function ($message) use ($to, $event) {
                                $message->to($to)->subject('Nova inscrição: '.$event->title);
                            }
                        );
                    } catch (\Throwable $e) {
                        // não interrompe o fluxo se o envio falhar
                    }
                }
            }
        }

        return back()->with('success', 'Inscrição realizada com sucesso!');
    }

    private function isDefaultRegistrationFieldName(string $name): bool
    {
        $normalized = Str::of($name)
            ->lower()
            ->ascii()
            ->replace('-', ' ')
            ->replace('_', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        $reserved = [
            'nome',
            'nome completo',
            'telefone',
            'endereco',
            'e mail',
            'email',
        ];

        return in_array($normalized, $reserved, true);
    }

    private function isAgeRegistrationFieldName(string $name): bool
    {
        $normalized = Str::of($name)
            ->lower()
            ->ascii()
            ->replace('-', ' ')
            ->replace('_', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        return in_array($normalized, ['idade', 'age'], true);
    }
}
