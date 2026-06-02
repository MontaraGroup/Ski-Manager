<?php

namespace App\Models;

use CodeIgniter\Model;

class MarketingModel extends Model
{
    protected $table = 'marketing_campaigns';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'campaign_type', 'name', 'daily_cost', 'visitor_boost', 'reputation_boost', 'days_remaining', 'status'];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
