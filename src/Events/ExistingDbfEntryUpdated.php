<?php namespace Sreynoldsjr\ReynoldsDbf\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class ExistingDbfEntryUpdated
{
    use Dispatchable, SerializesModels;

     /**
     * The Items instance added to the dbf file
     * 
     * 
     * @var Sreynoldsjr\ReynoldsDbf\Models\*;
    */

    public $item;

    /*
    * The User instance associated with the change:
    * Sreynoldsjr\ReynoldsDbf\Models\User $user;
    */

    public $user;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Title $item;
     * @param Sreynoldsjr\ReynoldsDbf\Models\User $user;
     * @return void
     */
    public function __construct($item, $userId)
    {
        $this->item = $item;
        $this->userId = $userId;
        $this->message = 'Existing ' . get_class($item) . ' entry was updated with id ' . $item->id . ' and INDEX ' . $item->INDEX . ' by user: ' . $userId;
    }
}
