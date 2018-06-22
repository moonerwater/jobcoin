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

    protected function buildCode($type, $length=6) {
        $mycode = $this->randomString($length);
        if($type == 'user'){
            $obj = \User::findFirst(" code_system = '$mycode' ");
        }
        if ($obj) {
            return $this->buildCode($type, $length);
        }
        return $mycode;
    }

    protected function randomString($length = 10){
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnpqrstuvwxyz123456789';
        $password = '';
        for ( $i = 0; $i < $length; $i++ )
        {
            $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $password;
    }

    protected function buildNO()
    {
        return sprintf('%s%d', date("ymdHis", time()), rand(100000, 999999));
    }


    protected function reply($reason, $error_code = 0, $result)
    {
        $obj = new stdClass();
        $obj->reason = $reason;
        $obj->error_code = $error_code;
        $obj->result = $result;

        $this->response->setContent(json_encode($obj));
        return $this->response->send();
    }

    protected function replyFailure($reason = "", $error_code = 404, $result = array())
    {
        return $this->reply($reason, $error_code, $result);
    }



    /**
     * 手机验证
     * @param string $mobile
     * @return bool
     */
    protected function isPhone($mobile) {
        $pattern = '/^1[0-9][0-9]{9}$/';
        return preg_match ( $pattern, $mobile ) ? true : false;
    }

    /**
     * 身份证验证
     * @param string $idcard
     * @return bool
     */
    protected function isIdCard($idcard) {
        $pattern ='/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/';
        return preg_match ( $pattern, $idcard ) ? true : false;
    }

    /**
     * 邮箱地址格式验证
     * @param string $email
     * @return boolean
     */
    protected function isEmail($email) {
        $pattern = '/^([a-zA-Z0-9_-])+(\\.[a-zA-Z0-9]+)?@([a-zA-Z0-9_-])+((\\.[a-zA-Z0-9_-]{2,3}){1,2})$/i';
        $pattern = '/^([a-zA-Z0-9_-])+(\\.[a-zA-Z0-9]+)?@[a-zA-Z0-9_-]+(\\.(com|cn|net|org|cc|edu|de|gov|me|info|tv|mobi|asia|co|cc|biz|tel){1}){1,2}/i';
        return preg_match ( $pattern, $email ) ? true : false;
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

    protected function fileToBase64($file){
        $base64_file = '';
        if(file_exists($file)){
            $mime_type= mime_content_type($file);
            $base64_data = base64_encode(file_get_contents($file));
            $base64_file = 'data:'.$mime_type.';base64,'.$base64_data;
        }
        return $base64_file;
    }

}
