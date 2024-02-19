<?php

namespace App\Models;

use App\Traits\ConstantExport;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\App;

class User extends Authenticatable
{
    protected $primaryKey = 'idUser';
    public $timestamps = false;
}
