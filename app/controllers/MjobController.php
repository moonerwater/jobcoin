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
