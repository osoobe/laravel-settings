<?php

namespace Osoobe\Laravel\Settings\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Osoobe\Laravel\Settings\Traits\HasMetas;

class User extends Model
{
    use HasMetas;

    protected $table = 'users';
    protected $fillable = ['name'];
}
