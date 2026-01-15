<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\EventCategory;
use Illuminate\Http\Request;

class EventCategoryController extends Controller
{
    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $category = EventCategory::create($validated);

        return response()->json([
            'success' => true,
            'category' => $category,
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy(EventCategory $category)
    {
        // Verificar se há eventos usando esta categoria
        $eventsCount = \App\Models\Event::where('category_id', $category->id)->count();
        
        if ($eventsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Não é possível remover esta categoria. Ela está sendo usada por {$eventsCount} evento(s).",
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoria removida com sucesso!',
        ]);
    }
}
