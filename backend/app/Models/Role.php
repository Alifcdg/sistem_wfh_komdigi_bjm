<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $primaryKey = 'id_role';

    protected $fillable = [
        'nama_role',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'id_role');
    }
}
