<?php

use Phalcon\Mvc\Model;

class PayinList extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "payin_list";
    }
}
