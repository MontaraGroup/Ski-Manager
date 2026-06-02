<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketSaleModel extends Model
{
    protected $table = 'ticket_sales';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'game_day', 'ticket_type', 'quantity', 'revenue'];
    protected $useTimestamps = true;
    protected $updatedField = '';
    protected $returnType = 'array';
}
