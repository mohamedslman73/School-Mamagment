<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = ['to_id','from_id','content'];

    public function from()
    {
    	return $this->belongsTo(User::class,'from_id');
    }
    
}
