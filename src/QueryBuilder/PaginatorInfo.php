<?php namespace Sreynoldsjr\ReynoldsDbf\QueryBuilder;

use Sreynoldsjr\ReynoldsDbf\Models\Traits\MagicFunctionsTrait;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;

class PaginatorInfo {

	use MagicFunctionsTrait,
		HasAttributes;

	public $incrementing = false;

	 /**
     * The loaded relationships for the model.
     *
     * @var array
     */
    protected $relations = [];
    protected $timestamps = false;

	public function __construct(array $attributes){

		$this->attributes = array_merge(
			["items"=>[],"perPage"=>10,"total"=>0,"count"=>0,"page"=>1,"firstItem"=>null,"lastItem"=>null,"hasMorePages"=>false, "pages"=>1],
			 $attributes
			);

		$this->attributes['pages'] = ceil($attributes['items']->count() / $this->attributes['perPage']);
	}

	public static function get(Array $attributes){
		$pi = new static($attributes);
		return $pi->result();
	}

	public function result(){
		$data = $this->items->forPage($this->page, $this->perPage);

		$this->total = $this->items->count();
		$this->count = $data->count();
		$this->firstItem = $data->first();
		$this->lastItem = $data->last();
		$this->hasMorePages = $this->page < $this->pages;

		$paginator = $this->getAttributes();
		unset($paginator['items']);

		return [
			"paginatorInfo"=> $paginator,
			"data"=> $data
		];
	}

	// returns collection
	public function getItemsAttribute(){
		return $this->attributes['items'];
	}


	// returns Int
	public function getPerPageAttribute(){
		return $this->attributes['perPage'];
	}

	// returns Int
	public function getTotalAttribute(){
		return $this->attributes['total'];
	}

	// returns Int
	public function getCountAttribute(){
		return $this->attributes['count'];
	}

	// returns Int
	public function getCurrentPageAttribute(){
		return $this->attributes['currentPage'];
	}

	// return INDEX of first item or null
	public function getFirstItemAttribute(){
		return $this->attributes['firstItem'];
	}

	// return INDEX of last item or null
	public function getLastItemAttribute(){
		return $this->attributes['lastItem'];
	}

	// returns Boolean
	public function getHasMorePagesttribute(){
		return $this->attributes['hasMorePages'];
	}

/**
 * Get the value indicating whether the IDs are incrementing.
 *
 * @return bool
 */
public function getIncrementing()
{
    return $this->incrementing;
}

	/**
	 * Determine if the given relation is loaded.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function relationLoaded($key)
	{
	    return array_key_exists($key, $this->relations);
	}

	 /**
     * Determine if the model uses timestamps.
     *
     * @return bool
     */
    public function usesTimestamps()
    {
        return $this->timestamps;
    }

}
           