<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;

/**
 * @property int $id
 */
#[Fillable(['name', 'project_id', 'active'])]
class Epic extends Model
{
    use SoftDeletes;
}
