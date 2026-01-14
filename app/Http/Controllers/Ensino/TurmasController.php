<?php

namespace App\Http\Controllers\Ensino;

use App\Http\Controllers\Controller;
use App\Models\Turma;
use App\Models\School;
use App\Models\Member;
use App\Models\Discipline;
use App\Models\Lesson;
use App\Models\LessonAttendance;
use App\Models\ClassFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TurmasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Turma::with('school');

        // Busca
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filtro por status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filtro por escola
        if ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        // Ordenação
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $turmas = $query->paginate(20)->withQueryString();

        // Lista de escolas para o formulário
        $schools = School::orderBy('name')->get();

        return view('ensino.turmas.index', compact('turmas', 'schools'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $schools = School::orderBy('name')->get();
        return view('ensino.turmas.create', compact('schools'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'school_id' => 'required|exists:schools,id',
            'schedule' => 'nullable|in:manhã,tarde,noite',
            'status' => 'required|in:preparando turma,em andamento,pausada,finalizada',
            'description' => 'nullable|string',
        ]);

        Turma::create($validated);

        return redirect()->route('ensino.turmas.index')
            ->with('success', 'Turma criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Turma $turma)
    {
        try {
            $turma->load([
                'school',
                'students',
                'disciplines.teachers',
                'lessons.discipline',
                'lessons.attendances.member',
                'files.discipline'
            ]);

            // Contadores para as abas
            $studentsCount = $turma->students->count();
            $disciplinesCount = $turma->disciplines->count();
            $lessonsCount = $turma->lessons->count();
            $filesCount = $turma->files->count();

            // Lista de membros para adicionar como alunos
            $members = \App\Models\Member::orderBy('name')->get();

            // Lista de escolas para o formulário
            $schools = School::orderBy('name')->get();

            return view('ensino.turmas.show', compact(
                'turma',
                'studentsCount',
                'disciplinesCount',
                'lessonsCount',
                'filesCount',
                'members',
                'schools'
            ));
        } catch (\Exception $e) {
            return redirect()->route('ensino.turmas.index')
                ->with('error', 'Erro ao carregar detalhes da turma: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Turma $turma)
    {
        $schools = School::orderBy('name')->get();
        return view('ensino.turmas.edit', compact('turma', 'schools'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Turma $turma)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'school_id' => 'required|exists:schools,id',
            'schedule' => 'nullable|in:manhã,tarde,noite',
            'status' => 'required|in:preparando turma,em andamento,pausada,finalizada',
            'description' => 'nullable|string',
        ]);

        $turma->update($validated);

        return redirect()->route('ensino.turmas.show', $turma)
            ->with('success', 'Turma atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Turma $turma)
    {
        $turma->delete();

        return redirect()->route('ensino.turmas.index')
            ->with('success', 'Turma removida com sucesso!');
    }

    /**
     * Adicionar alunos à turma
     */
    public function storeStudents(Request $request, Turma $turma)
    {
        $validated = $request->validate([
            'member_ids' => 'required|array',
            'member_ids.*' => 'exists:members,id',
        ]);

        $turma->students()->sync($validated['member_ids']);

        return redirect()->route('ensino.turmas.show', $turma)
            ->with('success', 'Alunos atualizados com sucesso!');
    }

    /**
     * Remover aluno da turma
     */
    public function removeStudent(Turma $turma, Member $member)
    {
        $turma->students()->detach($member->id);

        return redirect()->route('ensino.turmas.show', $turma)
            ->with('success', 'Aluno removido com sucesso!');
    }

    /**
     * Criar disciplina na turma
     */
    public function storeDiscipline(Request $request, Turma $turma)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:members,id',
        ]);

        $discipline = Discipline::create([
            'name' => $validated['name'],
            'class_id' => $turma->id,
        ]);

        if (isset($validated['teacher_ids'])) {
            $discipline->teachers()->sync($validated['teacher_ids']);
        }

        return redirect()->route('ensino.turmas.show', $turma)
            ->with('success', 'Disciplina criada com sucesso!');
    }

    /**
     * Atualizar disciplina
     */
    public function updateDiscipline(Request $request, Turma $turma, Discipline $discipline)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:members,id',
        ]);

        $discipline->update(['name' => $validated['name']]);

        if (isset($validated['teacher_ids'])) {
            $discipline->teachers()->sync($validated['teacher_ids']);
        } else {
            $discipline->teachers()->detach();
        }

        return redirect()->route('ensino.turmas.show', $turma)
            ->with('success', 'Disciplina atualizada com sucesso!');
    }

    /**
     * Remover disciplina
     */
    public function destroyDiscipline(Turma $turma, Discipline $discipline)
    {
        $discipline->delete();

        return redirect()->route('ensino.turmas.show', $turma)
            ->with('success', 'Disciplina removida com sucesso!');
    }

    /**
     * Criar aula
     */
    public function storeLesson(Request $request, Turma $turma)
    {
        $validated = $request->validate([
            'lesson_date' => 'required|date',
            'discipline_id' => 'nullable|exists:disciplines,id',
            'subject' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:members,id',
        ]);

        $lesson = Lesson::create([
            'class_id' => $turma->id,
            'discipline_id' => $validated['discipline_id'] ?? null,
            'lesson_date' => $validated['lesson_date'],
            'subject' => $validated['subject'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Registrar presenças
        if (isset($validated['member_ids'])) {
            foreach ($validated['member_ids'] as $memberId) {
                LessonAttendance::create([
                    'lesson_id' => $lesson->id,
                    'member_id' => $memberId,
                    'present' => true,
                ]);
            }
        }

        return redirect()->route('ensino.turmas.show', $turma)
            ->with('success', 'Aula registrada com sucesso!');
    }

    /**
     * Visualizar/Editar aula
     */
    public function showLesson(Turma $turma, Lesson $lesson)
    {
        // Implementar visualização/edição da aula
        return redirect()->route('ensino.turmas.show', $turma);
    }

    /**
     * Atualizar aula
     */
    public function updateLesson(Request $request, Turma $turma, Lesson $lesson)
    {
        // Implementar atualização da aula
        return redirect()->route('ensino.turmas.show', $turma);
    }

    /**
     * Remover aula
     */
    public function destroyLesson(Turma $turma, Lesson $lesson)
    {
        $lesson->delete();

        return redirect()->route('ensino.turmas.show', $turma)
            ->with('success', 'Aula removida com sucesso!');
    }

    /**
     * Criar arquivo
     */
    public function storeFile(Request $request, Turma $turma)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:file,text,external_link',
            'discipline_id' => 'nullable|exists:disciplines,id',
            'file' => 'required_if:type,file|file|max:10240',
            'content' => 'required_if:type,text|nullable|string',
            'external_url' => 'required_if:type,external_link|nullable|url',
            'description' => 'nullable|string',
        ]);

        $fileData = [
            'class_id' => $turma->id,
            'discipline_id' => $validated['discipline_id'] ?? null,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
        ];

        if ($validated['type'] == 'file' && $request->hasFile('file')) {
            $fileData['file_path'] = $request->file('file')->store('class-files', 'public');
        } elseif ($validated['type'] == 'text') {
            $fileData['content'] = $validated['content'];
        } elseif ($validated['type'] == 'external_link') {
            $fileData['external_url'] = $validated['external_url'];
        }

        ClassFile::create($fileData);

        return redirect()->route('ensino.turmas.show', $turma)
            ->with('success', 'Arquivo adicionado com sucesso!');
    }

    /**
     * Remover arquivo
     */
    public function destroyFile(Turma $turma, ClassFile $file)
    {
        if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        return redirect()->route('ensino.turmas.show', $turma)
            ->with('success', 'Arquivo removido com sucesso!');
    }

    /**
     * Relatório de frequência mensal
     */
    public function frequencyMonthly(Request $request, Turma $turma)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $disciplineId = $request->input('discipline_id');

        // Buscar aulas do período
        $lessons = Lesson::where('class_id', $turma->id)
            ->whereYear('lesson_date', $year)
            ->whereMonth('lesson_date', $month)
            ->when($disciplineId, function ($query) use ($disciplineId) {
                return $query->where('discipline_id', $disciplineId);
            })
            ->with(['attendances.member'])
            ->get();

        // Buscar todos os alunos da turma
        $students = $turma->students()->orderBy('name')->get();

        // Contar dias do mês
        $daysInMonth = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;

        if ($request->ajax() || $request->wantsJson()) {
            // Retornar HTML para AJAX
            $html = view('ensino.turmas.partials.frequency-table', compact('students', 'lessons', 'daysInMonth', 'month', 'year'))->render();
            return response()->json(['html' => $html]);
        }

        // Retornar view completa se não for AJAX
        return view('ensino.turmas.modals.frequency-monthly', compact('turma', 'month', 'year', 'disciplineId', 'students', 'lessons', 'daysInMonth'));
    }
}

