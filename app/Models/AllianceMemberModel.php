<?php

namespace App\Models;

use CodeIgniter\Model;

class AllianceMemberModel extends Model
{
    protected $table = 'alliance_members';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'alliance_id', 'user_id', 'role', 'donated_cash', 'cross_skiers_generated', 'joined_at'
    ];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
