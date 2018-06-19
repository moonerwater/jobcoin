<?php

class ControllerH5 extends ControllerBase
{

    protected function initialize()
    {
        parent::initialize();
        $this->userinfo = $this->session->get('userinfo');

        /*if(!$this->session->get('auth-code') && $this->dispatcher->getActionName() != 'please' && $this->dispatcher->getActionName() != 'getCode'){
            $this->response->redirect('inspiration/list/please');
        }*/
        if($this->userinfo){
            $userinfo = \User::findFirst(" id = ".$this->userinfo['id'])->toArray();
            $this->userinfo = $userinfo;
        }
        $this->view->setVar('userinfo', $userinfo);
        $this->view->setVar('versionNum', date('Ymd'));
        $this->view->setVar('todaydetail', date('YmdHis'));
        $this->view->setVar('today', date('Y-m-d'));
    }

    protected function checkNoUserGoLogin(){
        if($this->userinfo){

        }
        else{
            $this->response->redirect('mjob/index');
        }
    }

    protected function checkUserGoMain(){
        if($this->userinfo){
            $this->response->redirect('mjob/main');
        }
        else{

        }
    }


}
