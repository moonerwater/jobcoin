<?php

class ControllerApi extends ControllerBase
{

    protected function initialize()
    {
        parent::initialize();
    }

    protected function checkTokenAndGetPartner($token){
        if(!$token){
            $this->replyFailure('token is empty', 404, null);
            die();
        }
        else{
            $partner = \Partner::findFirstByToken($token);
            if(!$partner){
                $this->replyFailure('token is error', 404, null);
                die();
            }
            else{
                return $partner->id;
            }
        }
    }

}
