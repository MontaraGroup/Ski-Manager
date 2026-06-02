<?php

namespace App\Models;

use CodeIgniter\Model;

class NightSkiingModel extends Model
{
    protected $table = 'night_skiing';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'light_name', 'light_type', 'level', 'coverage', 'energy_cost', 'status', 'condition_pct', 'assigned_slope'];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
