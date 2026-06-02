<?php

namespace App\Models;

use CodeIgniter\Model;

class WeatherModel extends Model
{
    protected $table = 'weather';
    protected $primaryKey = 'id';
    protected $allowedFields = ['game_day', 'temp', 'condition_name', 'wind', 'snowfall', 'visibility', 'humidity', 'snow_base', 'forecast'];
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
