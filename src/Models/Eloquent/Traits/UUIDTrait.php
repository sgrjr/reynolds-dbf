<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits;
//Instructions:
//be sure to add "uui" to $appends array


trait UUIDTrait {

  public function getUUIDAttribute()
    {
	return base64_encode(get_class ($this) . "_" . $this->id);
    }
		
}
