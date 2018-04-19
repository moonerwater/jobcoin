<?php

use Phalcon\Mvc\Controller;

class ControllerBase extends Controller
{

    protected function initialize()
    {

    }

    protected function encryptPassword($salt, $password)
    {
        return md5($salt . $password);
    }

    protected function queryPage(&$queryBuilder, $pageCurrent, $limit)
    {
        if (!$pageCurrent) $pageCurrent = 0;

        $paginator = new \Phalcon\Paginator\Adapter\QueryBuilder(array(
            "builder" => $queryBuilder,
            "limit" => $limit,
            "page" => (int)$pageCurrent
        ));

        return $paginator->getPaginate();
    }

    protected function buildInvitationCode($type = '') {
        $mycode = $this->randomString('upper,number', 6);
        $user = \User::findFirst(" code_system = '$mycode' ");
        if ($user) {
            return $this->buildInvitationCode($type);
        }
        return $mycode;
    }

    protected function randomString($type="number,upper,lower",$length){
        $valid_type = array('number','upper','lower');
        $case = explode(",",$type);
        $count = count($case);
        //根据交集判断参数是否合法
        if($count !== count(array_intersect($case,$valid_type))){
            return false;
        }
        $lower = "abcdefghijklmnopqrstuvwxyz";
        $upper = strtoupper($lower);
        $number = "0123456789";
        $str_list = "";
        for($i=0;$i<$count;++$i){
            $str_list .= $$case[$i];
        }
        return substr(str_shuffle($str_list),0,$length);
    }

    protected function buildNO()
    {
        return sprintf('%s%d', date("ymdHis", time()), rand(100000, 999999));
    }


    protected function reply($success, $totalCount, $data)
    {
        $obj = new stdClass();
        $obj->success = $success;
        $obj->total = $totalCount;
        $obj->data = $data;

        $cb = $this->request->get('callback');

        $this->response->setContent($cb ? $cb . "(" . json_encode($obj) . ");" : json_encode($obj));
        return $this->response->send();
    }

    protected function replyFailure($reason = "")
    {
        return $this->reply(false, 0, array('message' => $reason));
    }



    /**
     * 手机验证
     * @param string $mobile
     * @return bool
     */
    protected function isMobile($mobile) {
        $pattern = '/^1[3-9][0-9]{9}$/';
        return preg_match ( $pattern, $mobile ) ? true : false;
    }

    /**
     * 邮箱地址格式验证
     * @param string $email
     * @return boolean
     */
    protected function isEmail($email) {
        $pattern = '/^([a-zA-Z0-9_-])+(\\.[a-zA-Z0-9]+)?@([a-zA-Z0-9_-])+((\\.[a-zA-Z0-9_-]{2,3}){1,2})$/i';
        $patter = '/^([a-zA-Z0-9_-])+(\\.[a-zA-Z0-9]+)?@[a-zA-Z0-9_-]+(\\.(com|cn|net|org|cc|edu|de|gov|me|info|tv|mobi|asia|co|cc|biz|tel){1}){1,2}/i';
        return preg_match ( $patter, $email ) ? true : false;
    }

    /**
     * 验证密码格式
     * @param string $password
     * @return boolean
     */
    protected function isPassword($password) {
        $pattern = '/^[a-zA-Z0-9]{6,16}$/';
        return preg_match ( $pattern, $password ) ? true : false;
    }



    /**
     * 是否爬虫蜘蛛
     * @return bool
     */
    protected function isCrawler() {
        $agent= strtolower($_SERVER['HTTP_USER_AGENT']);
        if (!empty($agent)) {
            $spiderSite= array(
                "TencentTraveler",
                "Baiduspider+",
                "BaiduGame",
                "Googlebot",
                "msnbot",
                "Sosospider+",
                "Sogou web spider",
                "ia_archiver",
                "Yahoo! Slurp",
                "YoudaoBot",
                "Yahoo Slurp",
                "MSNBot",
                "Java (Often spam bot)",
                "BaiDuSpider",
                "Voila",
                "Yandex bot",
                "BSpider",
                "twiceler",
                "Sogou Spider",
                "Speedy Spider",
                "Google AdSense",
                "Heritrix",
                "Python-urllib",
                "Alexa (IA Archiver)",
                "Ask",
                "Exabot",
                "Custo",
                "OutfoxBot/YodaoBot",
                "yacy",
                "SurveyBot",
                "legs",
                "lwp-trivial",
                "Nutch",
                "StackRambler",
                "The web archive (IA Archiver)",
                "Perl tool",
                "MJ12bot",
                "Netcraft",
                "MSIECrawler",
                "WGet tools",
                "larbin",
                "Fish search",
            );
            foreach($spiderSite as $val) {
                $str = strtolower($val);
                if (strpos($agent, $str) !== false) {
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * 得到客戶端IP
     *
     * @return string
     */
    protected function getClientIP() {
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ClientIP = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ClientIP = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ClientIP = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ClientIP = $_SERVER['REMOTE_ADDR'];
        } else {
            $ClientIP = '0.0.0.0';
        }
        return $ClientIP;
    }

    protected function sort_array($array, $keyid, $order='asc', $type='number') {
        if(is_array($array)) {
            foreach($array as $val) {
                $order_arr[] = $val[$keyid];
            }

            $order = ($order == 'asc') ? SORT_ASC: SORT_DESC;
            $type  = ($type == 'number') ? SORT_NUMERIC: SORT_STRING;

            array_multisort($order_arr, $order, $type, $array);
        }
        return $array;
    }

}
