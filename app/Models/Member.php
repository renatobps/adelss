<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'marital_status',
        'birth_date',
        'photo_url',
        'status',
        'cpf',
        'rg',
        'address',
        'city',
        'state',
        'zip_code',
        'membership_date',
        'notes',
        'department_id',
        'pgi_id',
        'role_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'membership_date' => 'date',
    ];

    /**
     * Relacionamento com Departamento (direto - departamento principal)
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relacionamento muitos-para-muitos com Departamentos
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_members', 'member_id', 'department_id')
                    ->withPivot('id', 'department_role_id', 'created_at', 'updated_at')
                    ->withTimestamps();
    }

    /**
     * Relacionamento com PGI
     */
    public function pgi()
    {
        return $this->belongsTo(Pgi::class);
    }

    /**
     * Relacionamento com Cargo
     */
    public function role()
    {
        return $this->belongsTo(MemberRole::class, 'role_id');
    }

    /**
     * Relacionamento com Turmas (como aluno)
     */
    public function turmas()
    {
        return $this->belongsToMany(\App\Models\Turma::class, 'class_students', 'member_id', 'class_id')
                    ->withTimestamps();
    }

    /**
     * Relacionamento com Transações Financeiras
     */
    public function financialTransactions()
    {
        return $this->hasMany(\App\Models\FinancialTransaction::class);
    }


    /**
     * Retorna a idade do membro
     */
    public function getAgeAttribute()
    {
        if (!$this->birth_date) {
            return null;
        }
        return $this->birth_date->age;
    }

    /**
     * Retorna a URL completa da foto do membro
     */
    public function getPhotoUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        // Se já for uma URL completa (começa com http:// ou https://), extrair apenas o caminho
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            // Extrair o caminho da URL completa
            $parsedUrl = parse_url($value);
            $path = $parsedUrl['path'] ?? '';
            
            // Remover /storage/ do início se existir
            $path = preg_replace('#^/storage/#', '', $path);
            $path = ltrim($path, '/');
            
            // Verificar se o arquivo existe antes de retornar
            if ($path && Storage::disk('public')->exists($path)) {
                return asset('storage/' . $path);
            }
            
            return null;
        }
        
        // Normalizar o caminho (remover barras duplicadas e no início)
        $path = ltrim($value, '/');
        
        // Verificar se o arquivo existe no storage
        if (Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }
        
        // Se não existir, retornar null para não quebrar a página
        return null;
    }

    /**
     * Scope para filtrar por status
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ativo');
    }

    /**
     * Scope para filtrar por gênero
     */
    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope para busca
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('cpf', 'like', "%{$term}%");
        });
    }
}

