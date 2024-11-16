<?php

use Illuminate\Database\Eloquent\Model;

class GameStat extends Model
{
    protected $table = 'game_stats';
    protected $fillable = ['server_id', 'date', 'year', 'month', 'num_players', 'num_companies', 'timestamp'];
}
