<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerItemModel extends Model
{
    protected $table = 'player_items';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'segment_id', 'item_type', 'subtype', 'name', 'level', 'length_meters', 'condition_pct', 'capacity', 'difficulty', 'snow_depth', 'snow_quality', 'status', 'sector'];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
