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
                $result = new stdClass();
                $result->user_type = 'old';
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

    public function checktodayloginAction(){
        $this->checkNoUserGoLogin();
        $userid = $this->userinfo['id'];

        $user = \User::findFirstById($userid);

        $login_score = 0;
        if(date('Ymd') != date('Ymd', $user->last_time)){
            $login_score = 1;
            $user->score += 1;

            $userscore = new \UserScore();
            $userscore->user_id = $user->id;
            $userscore->type = 'login';
            $userscore->score = 1;
            $userscore->create_time = time();
            $userscore->last_time = time();
            $userscore->save();
        }
        $user->last_time = time();
        $user->save();


        $result = new stdClass();
        $result->login_score = $login_score;

        $this->reply('success', 0, $result);
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

    public function checkchsiAction() {
        $this->checkNoUserGoLogin();
        $userid = $this->userinfo['id'];
        //
        $username = $this->request->get('username', 'string');
        $password = $this->request->get('password', 'string');

        if (!$username) {
            $this->replyFailure('没有用户名');
            return '';
        }

        if (!$password) {
            $this->replyFailure('没有密码');
            return '';
        }

        //putenv("PHANTOMJS_EXECUTABLE=/usr/local/bin/phantomjs");//引入phantomjs
        //exec("/usr/local/bin/casperjs /var/www/html/casperjs/chsi.js $username $password $userid");//此处我使用的都是绝对路径

        $file_path = '/var/www/html/casperjs/chsi/result_'.$userid.'.txt';
        if(file_exists($file_path)){
            $str = file_get_contents($file_path);
            $str = str_replace("\r\n","<br />",$str);
            $str = json_decode($str);
            $str->imgbase64 = $this->fileToBase64($str->data_url);
            $this->reply('success', 0, $str);
        }
        else{
            $this->replyFailure('error');
            return '';
        }

    }

    public function loginAction() {
        $this->checkUserGoMain();
    }

    public function mainAction() {
        $this->checkNoUserGoLogin();
        //
        //$this->view->disable();
        $userid = $this->userinfo['id'];

        //
        $data = array();
        $data['score'] = $this->userinfo['score'];
        //
        $user = \User::find(array('', 'order' => 'score desc, id asc'));
        foreach($user as $k => $v){
            if($v->id == $userid){
                $data['scorerank'] = $k+1;
            }
        }
        //
        $todaycount = \UserScoreList::find("is_get = 'Y' and last_time >= ".strtotime(date("Y-m-d")))->count();
        $totalcount = \UserScoreList::find("is_get = 'Y'")->count();
        $data['todaycount'] = $todaycount;
        $data['totalcount'] = $totalcount;
        //
        $data['time'] = date("Y-m-d H:i");
        //
        $user = \User::find(array('', 'order' => 'score desc, id asc', 'limit'=>5));
        $data['userlist'] = $user->toArray();
        //
        $totaluser = \User::find('')->count();
        $data['totaluser'] = $totaluser;
        $data['totalvip'] = $data['totaluser'] <=1000 ? $data['totaluser'] : 1000;
        //
        $user = \User::find(array('', 'order' => 'id asc'));
        foreach($user as $k => $v){
            if($v->id == $userid){
                $data['userrank'] = $k+1;
            }
        }
        //
        $data['username'] = $this->userinfo['name'];
        $data['username'] = $data['username'] ? $data['username'] : $this->userinfo['phone'];

        //print_r($data);
        $this->view->setVar('data', $data);
    }

    public function assetsAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        //
        $data = array();
        $data['jobcoin'] = $this->userinfo['jobcoin'];
        //
        $this->view->setVar('data', $data);
    }

    public function iaeAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        //
        $userjobcoin = \UserJobcoin::find(array('user_id ='.$userid, 'order' => 'create_time desc'));
        $userjobcoin = $userjobcoin->toArray();
        foreach($userjobcoin as $k => $v){
            $userjobcoin[$k]['time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        $data['userjobcoin'] = $userjobcoin;
        //
        $this->view->setVar('data', $data);
    }

    public function promoteAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        $data = array();
        $data['score'] = $this->userinfo['score'];
        //
        $user = \User::find(array('', 'order' => 'score desc, id asc'));
        foreach($user as $k => $v){
            if($v->id == $userid){
                $data['scorerank'] = $k+1;
            }
        }
        $data['user'] = $this->userinfo;
        //
        $this->view->setVar('data', $data);
    }

    public function strategyAction() {
        $this->checkNoUserGoLogin();
    }
    
    public function recordAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        $data = array();
        $data['score'] = $this->userinfo['score'];
        //
        $userscore = \UserScore::find(array('user_id ='.$userid, 'order' => 'create_time desc'));
        $userscore = $userscore->toArray();
        foreach($userscore as $k => $v){
            $userscore[$k]['time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        $data['userscore'] = $userscore;
        //
        $this->view->setVar('data', $data);
    }

    public function inviteAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        $data = array();
        $data['code_system'] = $this->userinfo['code_system'];
        //
        $user = \User::find("code_user = '".$data['code_system']."'");
        $user = $user->toArray();
        $data['count1'] = count($user);
        $data['count2'] = 0;
        foreach($user as $k => $v){
            $usercount = \User::find("code_user = '".$v['code_system']."'")->count();
            $data['count2'] += $usercount;
        }
        //
        $data['jobcoin'] = 0;
        $userjobcoin = \UserJobcoin::find(array('user_id ='.$userid." and (type = 'regfor1' or type = 'regfor2')", 'order' => 'create_time desc'));
        $userjobcoin = $userjobcoin->toArray();
        foreach($userjobcoin as $k => $v){
            $data['jobcoin'] += $v['jobcoin'];
        }

        $data['candy'] = 0;
        $usercandy = \UserCandy::find(array('user_id ='.$userid." and (type = 'regfor1' or type = 'regfor2')", 'order' => 'create_time desc'));
        $usercandy = $usercandy->toArray();
        foreach($usercandy as $k => $v){
            $data['candy'] += $v['candy'];
        }

        //
        $this->view->setVar('data', $data);
    }

    public function invitecardAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        $data = array();
        $data['code_system'] = $this->userinfo['code_system'];
        //
        $user = \User::find(array('', 'order' => 'id asc'));
        foreach($user as $k => $v){
            if($v->id == $userid){
                $data['userrank'] = $k+1;
            }
        }
        //
        $data['username'] = $this->userinfo['name'];
        $data['username'] = $data['username'] ? $data['username'] : $this->userinfo['phone'];

        //
        $this->view->setVar('data', $data);
    }

    public function followwechatAction()
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

    public function dataplateAction()
    {
        
    }

    public function chsiAction()
    {
        
    }

}
