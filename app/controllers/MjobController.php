<?php

class MjobController extends ControllerH5
{
    public function initialize()
    {
        parent::initialize();
    }

    public function indexAction()
    {
        
    }

    public function getphonecodeAction() {
        $this->view->disable();
        $mobile = $this->request->get('mobile', 'string');
        $tempSms = $this->cache->get('sms_'.date("Ymd").'_'.$mobile);
        $tempSms = $tempSms ? $tempSms : array();
        $tempSmsLast = $tempSms[count($tempSms)-1];
        if (!$this->isPhone($mobile)) {
            $this->replyFailure('手机号码格式不正确');
            return '';
        }
        elseif (count($tempSms) >= 3) {
            $this->replyFailure('相同手机号码一天内只能发送三次短信');
            return '';
        }
        elseif ($tempSmsLast) {
            if (time() - $tempSmsLast['time'] < 60) {
                $this->replyFailure('60秒后才能发送');
                return '';
            }
        }
        $code = rand(1000,9999);
        $sms = new \Sms();
        if (!$sms->sendSms($mobile, 'userSign', array('code' => $code))) {
            $this->replyFailure('发送失败');
            return '';
        }
        $tempSms[] = array('code' => $code, 'time' => time());
        $this->cache->save('sms_'.date("Ymd").'_'.$mobile, $tempSms);

        $this->reply(true, 0, array('message' => 'success'));
    }

    public function checkphonecodeAction() {
        $this->view->disable();
        $mobile = $this->request->get('mobile', 'string');
        $vcode = $this->request->get('code', 'alphanum');
        $code_user = $this->request->get('code_user');
        $tempSms = $this->cache->get('sms_'.date("Ymd").'_'.$mobile);
        $tempSms = $tempSms ? $tempSms : array();
        $tempSmsLast = $tempSms[count($tempSms)-1];
        if (!$this->isPhone($mobile)) {
            $this->replyFailure('手机号码格式不正确');
            return '';
        }

        if (!($tempSmsLast['code'] && $tempSmsLast['code'] == $vcode)) {
            $this->replyFailure('验证码错误');
            return '';
        }
        else {
            $user = \User::findFirstByPhone($mobile);
            if ($user) {
                $user->last_time = time();
                $user->save();

                $result = new stdClass();
                $result->user_type = 'old';
                $this->reply('success', 0, $result);
                return '';
            }
            else{
                if($code_user){
                    $usercode = \User::findFirst(" code_system = '$code_user'");
                    if(!$usercode){
                        $usercode = \User::findFirst(" phone = '$code_user'");
                    }
                }
                if($usercode){
                    $code_user = $usercode->code_system;
                }
                else{
                    $code_user = null;
                }

                $user = new \User();
                $user->score = 30;
                $user->jobcoin = 100;
                $user->code_system = $this->buildCode('user', 6);
                $user->phone = $mobile;
                $user->code_user = $code_user;
                $user->create_time = time();
                $user->last_time = time();
                $user->save();

                $result = new stdClass();
                $result->user_type = 'new';
                $this->reply('success', 0, $result);
            }
            $userLogin = \User::findFirst(array(
                sprintf(" id = ".$user->id),
                "columns" => "id, name"
            ));
            $this->session->set('userinfo', $userLogin->toArray());

            $this->reply(true, 0, array('message' => 'success'));
        }
    }

    public function loginAction()
    {
        
    }

    public function mainAction()
    {
        
    }

    public function promoteAction()
    {
        
    }
    
    public function recordAction()
    {
        
    }

    public function followwechatAction()
    {
        
    }

    public function inviteAction()
    {
        
    }

    public function thirdpartyAction()
    {
        
    }

    public function settingAction()
    {
        
    }

    public function warrantAction()
    {
        
    }

    public function identityAction()
    {
        
    }

    public function strategyAction()
    {
        
    }

    public function dataplateAction()
    {
        
    }

    public function assetsAction()
    {
        
    }

    public function iaeAction()
    {
        
    }

    public function invitecardAction()
    {
        
    }

}
