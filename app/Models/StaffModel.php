<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffModel extends Model
{
    protected $table = 'staff';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'name', 'role', 'level', 'salary', 'morale', 'experience', 'assigned_to', 'status'];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
