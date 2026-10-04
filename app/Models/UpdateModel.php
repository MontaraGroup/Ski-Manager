<?php

namespace App\Models;

use CodeIgniter\Model;

class UpdateModel extends Model
{
    protected $table            = 'updates';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['version', 'title', 'description', 'type', 'released_at'];
    protected $useTimestamps    = true;
}
