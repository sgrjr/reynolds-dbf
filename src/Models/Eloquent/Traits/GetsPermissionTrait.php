<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits;

use App\Helpers\PermissionRequested;

Trait GetsPermissionTrait
{

    public function can($request, $options = false)
    {
      $response = new PermissionRequested($this, $request, $options);
      return $response->can;
    }

    public function isSuperAdmin(){
    	return $this->EMAIL === "sgrjr@deliverance.me";
    }

    public function isAdmin(){
    	foreach($this->roles AS $role){
        if($role->name === "SUPER" || $role->name === "ADMIN")
        return true;
      }
      return false;
    }

    public function isEmployee(){
      foreach($this->roles AS $role){
        if($role->name === "SUPER" || $role->name === "ADMIN" || $role->name === "EMPLOYEE")
        return true;
      }
      return false;
    }

    public function isVendor(){
      return !$this->isEmployee();
    }
}
