<?php

namespace App\Models;

use CodeIgniter\Model;

class UpdateModel extends Model
{
    protected $table            = 'updates';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['version', 'title', 'description', 'release_date', 'type', 'is_latest', 'content_json'];
    protected $useTimestamps    = true;
}
