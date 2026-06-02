<?php

namespace App\Models;

use CodeIgniter\Model;

class MapSegmentModel extends Model
{
    protected $table = 'map_segments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['type', 'name', 'points', 'length_meters', 'sector', 'active'];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
