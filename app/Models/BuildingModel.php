<?php

namespace App\Models;

use CodeIgniter\Model;

class BuildingModel extends Model
{
    protected $table = 'buildings';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'building_type', 'name', 'level', 'capacity', 'revenue_per_day', 'upkeep_per_day', 'condition_pct', 'status'];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
