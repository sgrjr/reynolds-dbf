<?php namespace Sreynoldsjr\ReynoldsDbf\Presenters;

use Sreynolds\ReynoldsDbf\Presenters\Presenter;

class PasswordPresenter extends Presenter {

    public function email()
    {
        return $this->entity->EMAIL;
    }

    public function id()
    {
        return $this->entity->KEY;
    }

}