<?php

namespace Osoobe\Laravel\Settings\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Osoobe\Laravel\Settings\Traits\HasMetas;
use Osoobe\Laravel\Settings\Traits\History;

class Post extends Model
{
    use HasMetas, History;

    protected $table = 'posts';
    protected $fillable = ['title', 'status'];

    public function metaTrack(): array
    {
        return ['status'];
    }
}
