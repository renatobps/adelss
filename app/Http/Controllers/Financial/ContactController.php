<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialContact;
use App\Models\FinancialContactCategory;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = FinancialContact::with('category')->orderBy('name');
        
        // Filtro por categoria
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        
        $contacts = $query->get();
        $categories = FinancialContactCategory::orderBy('name')->get();
        $total = FinancialContact::count();
        
        return view('financial.contacts.index', compact('contacts', 'categories', 'total'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'type' => 'required|in:pessoa_fisica,pessoa_juridica',
            'cpf' => 'nullable|string|max:14',
            'cnpj' => 'nullable|string|max:18',
            'phone_1' => 'nullable|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'category_id' => 'nullable|exists:financial_contact_categories,id',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'O campo nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.email' => 'O e-mail deve ser um endereço válido.',
            'type.required' => 'O campo tipo é obrigatório.',
            'type.in' => 'O tipo deve ser Pessoa física ou Pessoa jurídica.',
            'category_id.exists' => 'A categoria selecionada não existe.',
        ]);

        FinancialContact::create($validated);

        return redirect()->route('financial.contacts.index')
            ->with('success', 'Contato criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FinancialContact $contact)
    {
        return response()->json($contact->load('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FinancialContact $contact)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'type' => 'required|in:pessoa_fisica,pessoa_juridica',
            'cpf' => 'nullable|string|max:14',
            'cnpj' => 'nullable|string|max:18',
            'phone_1' => 'nullable|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'category_id' => 'nullable|exists:financial_contact_categories,id',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'O campo nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.email' => 'O e-mail deve ser um endereço válido.',
            'type.required' => 'O campo tipo é obrigatório.',
            'type.in' => 'O tipo deve ser Pessoa física ou Pessoa jurídica.',
            'category_id.exists' => 'A categoria selecionada não existe.',
        ]);

        $contact->update($validated);

        return redirect()->route('financial.contacts.index')
            ->with('success', 'Contato atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FinancialContact $contact)
    {
        try {
            $contact->delete();
            return redirect()->route('financial.contacts.index')
                ->with('success', 'Contato removido com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('financial.contacts.index')
                ->with('error', 'Erro ao remover contato. Por favor, tente novamente.');
        }
    }

    /**
     * Store a new category
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ], [
            'name.required' => 'O campo nome da categoria é obrigatório.',
            'name.max' => 'O nome da categoria não pode ter mais de 255 caracteres.',
        ]);

        FinancialContactCategory::create($validated);

        return redirect()->route('financial.contacts.index')
            ->with('success', 'Categoria criada com sucesso!');
    }
}
