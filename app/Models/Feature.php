<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'enabled', 'description', 'icon', 'color', 'version'];

    public static function isEnabled($name)
    {
        return self::where('name', $name)->first()->enabled;
    }
}
