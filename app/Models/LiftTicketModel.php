<?php

namespace App\Models;

use CodeIgniter\Model;

class LiftTicketModel extends Model
{
    protected $table = 'lift_tickets';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'ticket_type', 'price', 'active'];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
