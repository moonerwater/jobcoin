<?php

class MjobController extends ControllerH5
{
    public function initialize()
    {
        parent::initialize();
    }

    public function indexAction() {

    }

    public function logoutAction() {
        $this->session->remove('userinfo');
        $this->response->redirect('mjob/index');
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

        $user = \User::findFirstByPhone($mobile);
        if ($user) {
            $result = new stdClass();
            $result->user_type = 'old';
        }
        else{
            $result = new stdClass();
            $result->user_type = 'new';
        }

        $this->reply(true, 0, $result);
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
                $login_score = 0;
                if(date('Ymd') != date('Ymd', $user->last_time)){
                    $login_score = 1;
                }
                $user->last_time = time();
                $user->save();

                $result = new stdClass();
                $result->user_type = 'old';
                $result->login_score = $login_score;
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

                $userscore = new \UserScore();
                $userscore->user_id = $user->id;
                $userscore->type = 'reg';
                $userscore->score = 30;
                $userscore->create_time = time();
                $userscore->last_time = time();
                $userscore->save();

                $userjobcoin = new \UserJobcoin();
                $userjobcoin->user_id = $user->id;
                $userjobcoin->type = 'reg';
                $userjobcoin->jobcoin = 100;
                $userjobcoin->create_time = time();
                $userjobcoin->last_time = time();
                $userjobcoin->save();

                $result = new stdClass();
                $result->user_type = 'new';
                $result->reg_score = 30;

                //邀请时的奖励
                if($code_user){
                    $user = \User::findFirst(" code_system = '$code_user'");
                    if($user){
                        //一级
                        $user->jobcoin += 20;
                        $user->candy += 10;
                        $user->save();

                        $userjobcoin = new \UserJobcoin();
                        $userjobcoin->user_id = $user->id;
                        $userjobcoin->type = 'regfor1';
                        $userjobcoin->jobcoin = 20;
                        $userjobcoin->create_time = time();
                        $userjobcoin->last_time = time();
                        $userjobcoin->save();

                        $usercandy = new \UserCandy();
                        $usercandy->user_id = $user->id;
                        $usercandy->type = 'regfor1';
                        $usercandy->candy = 10;
                        $usercandy->create_time = time();
                        $usercandy->last_time = time();
                        $usercandy->save();

                        //二级
                        $code_user = $user->code_user;
                        $user = \User::findFirst(" code_system = '$code_user'");
                        if($user){
                            $user->jobcoin += 10;
                            $user->candy += 5;
                            $user->save();

                            $userjobcoin = new \UserJobcoin();
                            $userjobcoin->user_id = $user->id;
                            $userjobcoin->type = 'regfor2';
                            $userjobcoin->jobcoin = 10;
                            $userjobcoin->create_time = time();
                            $userjobcoin->last_time = time();
                            $userjobcoin->save();

                            $usercandy = new \UserCandy();
                            $usercandy->user_id = $user->id;
                            $usercandy->type = 'regfor2';
                            $usercandy->candy = 5;
                            $usercandy->create_time = time();
                            $usercandy->last_time = time();
                            $usercandy->save();
                        }

                    }
                }

            }
            $userLogin = \User::findFirst(array(
                sprintf(" id = ".$user->id),
                "columns" => "id, name"
            ));
            $this->session->set('userinfo', $userLogin->toArray());

            $this->reply('success', 0, $result);
        }
    }

    public function getjobcoinnoAction() {
        $this->checkNoUserGoLogin();
        $userid = $this->userinfo['id'];
        $total_score = $this->getTotalScore();

        $canget = number_format(100*$this->userinfo['score']/$total_score, 2);
        $stime = strtotime('-3 days');
        $etime = time();
        $scorelist = \UserScoreList::query()->columns('create_time')->where(' user_id = '.$userid)->orderBy('create_time desc')->execute()->toArray();
        if($scorelist){
            if($scorelist[0]['create_time'] > $stime){
                $stime = $scorelist[0]['create_time'];
            }
        }
        if($this->userinfo['create_time'] > $stime){
            $stime = $this->userinfo['create_time'];
        }
        //echo $stime;

        //8小时放一次，每次放6个晶体，每8小时放出100个job币。
        for($mytime = $stime+3600*8; $mytime<=$etime; $mytime+=3600*8){
            //
            $money_total=($canget < 0.1) ? 0.1 : $canget;
            $personal_num=6;
            $min_money=0.01;
            $money_right=$money_total;
            $randMoney=[];
            for($i=1;$i<=$personal_num;$i++){
                if($i== $personal_num){
                    $money=$money_right;
                }else{
                    $max=$money_right*100 - ($personal_num - $i ) * $min_money *100;
                    $money= rand($min_money*100,$max) /100;
                    $money=sprintf("%.2f",$money);
                }
                $randMoney[]=$money;
                $money_right=$money_right - $money;
                $money_right=sprintf("%.2f",$money_right);
            }
            shuffle($randMoney);
            //
            foreach($randMoney as $k => $v){
                $userscorelist = new \UserScoreList();
                $userscorelist->user_id = $userid;
                $userscorelist->jobcoinno = uniqid().$this->randomString(4);
                $userscorelist->jobcoin = $v;
                $userscorelist->create_time = $mytime;
                $userscorelist->last_time = $mytime;
                $userscorelist->save();
            }
        }
        //
        $userscorelist = \UserScoreList::find(array( "user_id = :user_id: and is_get = 'N' ", 'bind'=>array('user_id'=>$userid), 'columns'=>'jobcoinno, jobcoin'));
        $result = $userscorelist->toArray();
        $this->reply('success', 0, $result);
    }

    public function editjobcoinnoAction() {
        $this->checkNoUserGoLogin();
        $userid = $this->userinfo['id'];
        $jobcoinno = trim($this->request->get('jobcoinno'));

        $userscorelist =\UserScoreList::findFirst([" user_id = ?1 and jobcoinno = ?2 and is_get = 'N' ", 'bind'=>[ 1=>$userid, 2=>$jobcoinno ] ]);
        if (!$userscorelist) {
            $this->replyFailure('no this jobcoinno');
            return '';
        }
        $userscorelist->is_get = 'Y';
        $userscorelist->last_time = time();
        if(!$userscorelist->save()){
            $this->replyFailure('save is error');
            return '';
        }
        else{
            $user = \User::findFirstById($userid);
            $user->jobcoin += $userscorelist->jobcoin;
            $user->last_time = time();
            $user->save();


            $userjobcoin = new \UserJobcoin();
            $userjobcoin->user_id = $userid;
            $userjobcoin->type = 'mining';
            $userjobcoin->jobcoin = $userscorelist->jobcoin;
            $userjobcoin->create_time = time();
            $userjobcoin->last_time = time();
            $userjobcoin->save();

            //
            $result = new stdClass();
            $result->jobcoinno = $jobcoinno;
            $this->reply('success', 0, $result);
        }
    }

    public function loginAction() {
        $this->checkUserGoMain();
    }

    public function mainAction() {
        $this->checkNoUserGoLogin();
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
