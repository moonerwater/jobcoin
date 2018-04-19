<?php

use Phalcon\Mvc\Model;

class Jobseeker extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "jobseeker";
    }
}
