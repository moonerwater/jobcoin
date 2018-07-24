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
            $userinfo = \User::findFirst(" id = ".$this->userinfo['id']);
            if($userinfo){
                $userinfo = $userinfo->toArray();
            }
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
            $this->response->redirect('/mjob/login?backurl='.urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));
        }
    }

    protected function getDisablePhone($phone){
        return substr($phone,0,3).'****'.substr($phone,7,4);
    }

    protected function getDisableIp($ip){
        $ip = explode('.', $ip);
        return $ip[0].'.***'.'.***.'.$ip[3];
    }

    protected function checkUserGoMain(){
        if($this->userinfo){
            $this->response->redirect('mjob/main');
        }
        else{

        }
    }

    protected function getTotalScore(){
        $user = \User::find();
        $score = 0;
        foreach($user as $k => $v){
            $score += $v->score;
        }
        return $score;
    }

    protected function request_post($url = '', $param = '') {
        if (empty($url) || empty($param)) {
            return false;
        }

        $postUrl = $url;
        $curlPost = $param;
        $curl = curl_init();//初始化curl
        curl_setopt($curl, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($curl, CURLOPT_HEADER, 0);//设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($curl);//运行curl
        curl_close($curl);

        return $data;
    }


}
