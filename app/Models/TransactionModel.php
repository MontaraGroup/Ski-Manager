<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table = 'financial_transactions';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'game_day', 'category', 'description', 'amount', 'type'];
    protected $useTimestamps = true;
    protected $updatedField = '';
    protected $returnType = 'array';
}
