<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'member_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    /**
     * Relacionamento opcional com Member (membro)
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Permissões diretamente atribuídas ao usuário.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    /**
     * Verificar se o usuário possui uma permissão.
     * Admin sempre tem acesso a tudo.
     */
    public function hasPermission(string $key): bool
    {
        if ($this->is_admin) {
            return true;
        }

        // Carregar permissões se ainda não foram carregadas
        if (!$this->relationLoaded('permissions')) {
            $this->load('permissions');
        }

        // Permissões diretas do usuário - verificar se a collection tem a permissão
        $userPermissions = $this->permissions;
        if ($userPermissions && $userPermissions->isNotEmpty()) {
            foreach ($userPermissions as $permission) {
                if ($permission->key === $key) {
                    return true;
                }
            }
        }

        // Permissões herdadas do cargo/função do membro
        if ($this->member) {
            if (!$this->member->relationLoaded('role')) {
                $this->member->load('role');
            }
            
            if ($this->member->role) {
                if (!$this->member->role->relationLoaded('permissions')) {
                    $this->member->role->load('permissions');
                }
                
                $rolePermissions = $this->member->role->permissions;
                if ($rolePermissions && $rolePermissions->isNotEmpty()) {
                    foreach ($rolePermissions as $permission) {
                        if ($permission->key === $key) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}

