<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostPermition extends Model
{
	protected $fillable = ['post_id','parents','staff','class_id'];

	public function post()
	{
		return $this->belongsTo(Post::class,'post_id');
	}
	
}
