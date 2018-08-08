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

    protected function getIp() {
        if(!empty($_SERVER["HTTP_CLIENT_IP"]))
        {
            $cip = $_SERVER["HTTP_CLIENT_IP"];
        }
        else if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
        {
            $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        else if(!empty($_SERVER["REMOTE_ADDR"]))
        {
            $cip = $_SERVER["REMOTE_ADDR"];
        }
        else
        {
            $cip = '';
        }
        preg_match("/[\d\.]{7,15}/", $cip, $cips);
        $cip = isset($cips[0]) ? $cips[0] : 'unknown';
        unset($cips);

        return $cip;
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

    protected function checkCanAdmin($userid) {
        if($userid == 25 || $userid == 27){
            return true;
        }
        else{
            return false;
        }

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

    /**
     * [将Base64图片转换为本地图片并保存]
     * @E-mial wuliqiang_aa@163.com
     * @TIME   2017-04-07
     * @WEB    http://blog.iinu.com.cn
     * @param  [Base64] $base64_image_content [要保存的Base64]
     * @param  [目录] $path [要保存的路径]
     */
    function base64_image_content($base64_image_content,$path) {
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
            $type = $result[2];
            $type = ($type=='jpeg'?'jpg':$type);
            $path1 = APP_PATH."public";
            $new_path = $path."/".date('Ymd',time())."/";
            if(!file_exists($path1.$new_path)){
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($path1.$new_path, 0700);
            }
            $new_file = uniqid().".{$type}";
            if (file_put_contents($path1.$new_path.$new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
                return $new_path.$new_file;
            }else{
                return false;
            }
        }else{
            return false;
        }

    }


}
