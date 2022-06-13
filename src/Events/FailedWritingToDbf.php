<?php namespace Sreynoldsjr\ReynoldsDbf\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FailedWritingToDbf implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

     /**
     * The Title instance added to the cart
     * 
     * 
     * @var \App\Models\Title;
    */

    public $item;
    public $userId;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Title $item;
     * @param Int $userId;
     * @return void
     */
    public function __construct($item, $userId)
    {
        $this->item = $item;
        $this->userId = $userId;
        $this->message = 'Failed writing ' . get_class($item) . ' entry was updated with id ' . $item->id . ' and INDEX ' . $item->INDEX . ' by user: ' . $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        //return new Channel('store_activity);
    }
}
