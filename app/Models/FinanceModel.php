<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceModel extends Model
{
    protected $table = 'player_finances';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'cash', 'total_income', 'total_expenses', 'reputation', 'daily_visitors', 'difficulty', 'allow_tours', 'profile_completed', 'resort_map', 'resort_open', 'units', 'last_active'];
    protected $useTimestamps = true;
    protected $createdField = '';
    protected $returnType = 'array';
}
