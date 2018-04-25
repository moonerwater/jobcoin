<?php

use Phalcon\Mvc\Model;

class Resume extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "resume";
    }
}
