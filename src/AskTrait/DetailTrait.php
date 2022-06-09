<?php namespace App\Ask\AskTrait;

trait DetailTrait {

	public function getQtyAttribute(){
		if(isset($this->attributes["REQUESTED"])){
			return $this->attributes["REQUESTED"];
		}

		return null;
	}
	public function getQuantityAttribute(){
		if(isset($this->attributes["REQUESTED"])){
			return $this->attributes["REQUESTED"];
		}

		return null;
	}

	public function getDescriptionAttribute(){
		$a = $this->attributes;
		$description = "";

		if(isset($this->attributes["TITLE"])){
			$description = "\"" . $a["TITLE"] . "\" by " . $a["AUTHOR"];
		}

		return $description;
	}

	public function getCostAttribute(){
		return \App\Helpers\Misc::figureCost($this);
	}

	public function getIsbnAttribute(){
		if(isset($this->attributes["PROD_NO"])){
			return $this->attributes["PROD_NO"];
		}

		return null;
	}

	public function getImageAttribute($atts){return $this->getImgAttribute($atts);}

	public function getUrlAttribute(){
		return "/isbn/" . $this->getIsbnAttribute();
	}
	
	public function getSmallImageAttribute(){
		$atts = $this->attributes;
		return "http://localhost/img/small/" . $atts["PROD_NO"] . ".JPG";
	  }
	
	  public function getDefaultImageAttribute(){
		return $this->getSmallImageAttribute();
	  }

}

