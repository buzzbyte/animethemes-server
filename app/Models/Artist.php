<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Artist extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'artist';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'artist_id';

    /**
     * Get the songs the artist has performed in
     */
    public function songs() {
        return $this->belongsToMany('App\Models\Song');
    }

    /**
     * Get the resources for the artist
     */
    public function resources() {
        return $this->belongsToMany('App\Models\Resource');
    }
}
