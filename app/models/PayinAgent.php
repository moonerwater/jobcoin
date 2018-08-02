<?php

use Phalcon\Mvc\Model;

class PayinAgent extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "payin_agent";
    }
}
