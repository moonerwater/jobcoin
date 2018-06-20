<?php

use Phalcon\Mvc\Model;

class UserScoreList extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "user_score_list";
    }
}
