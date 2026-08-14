<?php

namespace App\Models;

use CodeIgniter\Model;

class UsersModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    
    // Kolom yang diizinkan untuk diisi secara manual (insert/update)
    protected $allowedFields    = ['name', 'username', 'password', 'role', 'level'];

    // Mengaktifkan fitur otomatis pengisian created_at dan updated_at
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}