<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberCustomField;
use App\Models\MemberCustomFieldValue;
use App\Models\MemberSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicMemberRegistrationController extends Controller
{
    public function create(string $token)
    {
        abort_unless(MemberSetting::publicRegistrationEnabled(), 404);
        abort_unless(hash_equals(MemberSetting::publicRegistrationToken(), $token), 404);

        $customFields = MemberCustomField::active()->get();

        return view('members.public.create', compact('token', 'customFields'));
    }

    public function store(Request $request, string $token)
    {
        abort_unless(MemberSetting::publicRegistrationEnabled(), 404);
        abort_unless(hash_equals(MemberSetting::publicRegistrationToken(), $token), 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:members,email',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:M,F',
            'marital_status' => 'nullable|in:solteiro,casado,divorciado,viuvo,uniao_estavel',
            'birth_date' => 'nullable|date',
            'marriage_date' => 'nullable|date',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip_code' => 'nullable|string|max:10',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string|max:2000',
            'custom_fields' => 'nullable|array',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('members/photos', 'public');
        }

        $member = Member::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'marital_status' => $validated['marital_status'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'marriage_date' => $validated['marriage_date'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'photo_url' => $photoPath,
            'notes' => $validated['notes'] ?? null,
            'status' => Member::STATUS_PENDENTE,
        ]);

        $fields = MemberCustomField::active()->get()->keyBy('id');
        foreach ($request->input('custom_fields', []) as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            if (!$fields->has($fieldId)) {
                continue;
            }
            MemberCustomFieldValue::create([
                'member_id' => $member->id,
                'member_custom_field_id' => $fieldId,
                'value' => is_array($value) ? json_encode($value) : (string) $value,
            ]);
        }

        return redirect()
            ->route('members.public.create', ['token' => $token])
            ->with('success', 'Cadastro enviado! Um administrador irá revisar seus dados antes de ativar o registro.');
    }
}
