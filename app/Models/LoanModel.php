<?php

namespace App\Models;

use CodeIgniter\Model;

class LoanModel extends Model
{
    protected $table = 'loans';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'loan_type', 'principal', 'interest_rate', 'remaining', 'daily_payment', 'days_total', 'days_remaining', 'status'];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
