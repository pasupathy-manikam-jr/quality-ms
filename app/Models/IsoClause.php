<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An ISO 9001:2015 clause that quality records can be tagged with.
 *
 * @property int $id
 * @property string $number
 * @property string $title
 */
class IsoClause extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];
}
