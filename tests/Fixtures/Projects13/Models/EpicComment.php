<?php

namespace Projects13\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['epic_id', 'user_id', 'body'])]
class EpicComment extends Model {}
