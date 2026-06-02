<?php

namespace App\Models;

use CodeIgniter\Model;

class SnowCannonModel extends Model
{
    protected $table = 'snow_cannons';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'cannon_name', 'level', 'output_per_day', 'energy_cost', 'water_usage', 'status', 'condition_pct', 'assigned_slope'];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
