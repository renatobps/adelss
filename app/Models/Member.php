<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ATIVO = 'ativo';
    public const STATUS_INATIVO = 'inativo';
    public const STATUS_VISITANTE = 'visitante';
    public const STATUS_TRANSFERIDO = 'membro_transferido';
    public const STATUS_PENDENTE = 'pendente';

    public const STATUSES = [
        self::STATUS_ATIVO => 'Ativo',
        self::STATUS_INATIVO => 'Inativo',
        self::STATUS_VISITANTE => 'Visitante',
        self::STATUS_TRANSFERIDO => 'Transferido',
        self::STATUS_PENDENTE => 'Pendente',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'marital_status',
        'marriage_date',
        'birth_date',
        'photo_url',
        'status',
        'cpf',
        'rg',
        'address',
        'city',
        'state',
        'zip_code',
        'latitude',
        'longitude',
        'geocoded_at',
        'membership_date',
        'notes',
        'department_id',
        'pgi_id',
        'role_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'membership_date' => 'date',
        'marriage_date' => 'date',
        'geocoded_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
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
     * Relacionamento com Disciplinas (como professor)
     */
    public function disciplines()
    {
        return $this->belongsToMany(\App\Models\Discipline::class, 'discipline_teachers', 'member_id', 'discipline_id')
                    ->withTimestamps();
    }

    /**
     * Relacionamento com Discipulado
     */
    public function discipleshipMembers()
    {
        return $this->hasMany(\App\Models\Discipleship\DiscipleshipMember::class, 'member_id');
    }

    /**
     * Verifica se o membro é professor de alguma turma
     */
    public function isTeacherOfAnyClass(): bool
    {
        return $this->disciplines()->exists();
    }

    /**
     * Relacionamento com Funções do Moriah (muitos para muitos)
     */
    public function moriahFunctions()
    {
        return $this->belongsToMany(MoriahFunction::class, 'member_moriah_functions', 'member_id', 'moriah_function_id')
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
     * Vendas de rifa realizadas pelo membro como vendedor.
     */
    public function rifaVendas()
    {
        return $this->hasMany(\App\Models\RifaVenda::class, 'vendedor_id');
    }

    /**
     * Números de rifa vendidos/reservados por este membro.
     */
    public function numerosRifaVendidos()
    {
        return $this->hasMany(\App\Models\NumeroRifa::class, 'vendedor_id');
    }

    /**
     * Relacionamento com o usuário do sistema (login)
     */
    public function user()
    {
        return $this->hasOne(User::class);
    }

    /**
     * Grupos de notificação (WhatsApp) aos quais o membro pertence
     */
    public function notificacaoGrupos()
    {
        return $this->belongsToMany(NotificacaoGrupo::class, 'grupo_member', 'member_id', 'grupo_id')->withTimestamps();
    }


    public function customFieldValues()
    {
        return $this->hasMany(MemberCustomFieldValue::class);
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

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    /** Iniciais determinísticas (ex: Millene Da Silva Mendes → MD). */
    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));
        if ($parts === []) {
            return '?';
        }
        $first = mb_substr($parts[0], 0, 1);
        $second = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

        return mb_strtoupper($first . $second);
    }

    /** Cor de fundo estável a partir do nome. */
    public function getAvatarColorAttribute(): string
    {
        $palette = ['#0088CC', '#14B8A6', '#8B5CF6', '#F59E0B', '#EF4444', '#EC4899', '#10B981', '#6366F1'];
        $hash = crc32(mb_strtolower(trim((string) $this->name)));

        return $palette[abs($hash) % count($palette)];
    }

    public function fullAddress(): string
    {
        return collect([$this->address, $this->city, $this->state, $this->zip_code])
            ->filter(fn ($v) => filled($v))
            ->implode(', ');
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
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

