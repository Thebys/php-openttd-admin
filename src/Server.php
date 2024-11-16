<?php

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    protected $table = 'servers';
    protected $fillable = ['ip', 'admin_port', 'name', 'last_updated'];
}
