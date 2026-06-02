<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceModel extends Model
{
    protected $table = 'player_finances';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'cash', 'total_income', 'total_expenses'];
    protected $useTimestamps = true;
    protected $createdField = '';
    protected $returnType = 'array';
}
