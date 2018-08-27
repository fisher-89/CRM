<?php

namespace App\Services\Admin\Traits;

Trait GetInfo
{
    /**
     * 获取手机
     *
     * @return bool|null|string|string[]
     */
    public function getOperation()
    {
        if (isset($_SERVER['HTTP_X_NETWORK_INFO'])){
            $str1 = $_SERVER['HTTP_X_NETWORK_INFO'];
            $getstr1 = preg_replace('/(.*,)(11[d])(,.*)/i','\2',$str1);
            Return $getstr1;
        }elseif (isset($_SERVER['HTTP_X_UP_CALLING_LINE_ID'])){
            $getstr2 = $_SERVER['HTTP_X_UP_CALLING_LINE_ID'];
            Return $getstr2;
        }elseif (isset($_SERVER['HTTP_X_UP_SUBNO'])){
            $str3 = $_SERVER['HTTP_X_UP_SUBNO'];
            $getstr3 = preg_replace('/(.*)(11[d])(.*)/i','\2',$str3);
            Return $getstr3;
        }elseif (isset($_SERVER['DEVICEID'])){
            Return $_SERVER['DEVICEID'];
        }else{
            Return false;
        }
    }

    /**
     * 获取头部信息
     *
     * @return string
     */
    public function getHeader()
    {
        $str = '';
        foreach ($_SERVER as $key => $val) {
            $gstr = str_replace("&", "&", $val);
            $str .= "$key -> " . $gstr . "\r\n";
        }
        Return $str;
    }

    /**
     * 获取UA
     *
     * @return bool
     */
    public function getUA(){
        if (isset($_SERVER['HTTP_USER_AGENT'])){
            Return $_SERVER['HTTP_USER_AGENT'];
        }else{
            Return false;
        }
    }

    /**
     * 获取手机类型
     *
     * @return bool
     */
    public function getPhoneType()
    {
        $ua = $this->getUA();
        if($ua!=false){
            $str = explode(' ',$ua);
            Return $str[0];
        }else{
            Return false;
        }
    }

    /**
     * 判断是否是opera
     *
     * @return bool
     */
    public function isOpera(){
        $uainfo = $this->getUA();
        if (preg_match('/.*Opera.*/i',$uainfo)){
            Return true;
        }else{
            Return false;
        }
    }

    /**
     * 判断是否是m3gate
     *
     * @return bool
     */
    public function isM3gate(){
        $uainfo = $this->getUA();
        if (preg_match('/M3Gate/i',$uainfo)){
            Return true;
        }else{
            Return false;
        }
    }

    /**
     * 取得HA
     *
     * @return bool
     */
    public function getHttpAccept(){
        if (isset($_SERVER['HTTP_ACCEPT'])){
            Return $_SERVER['HTTP_ACCEPT'];
        }else{
            Return false;
        }
    }

    /**
     * 取得手机IP
     *
     * @return array|false|string
     */
    public function getIp()
    {
        global $ip;
        if (getenv("HTTP_CLIENT_IP")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } else if (getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("REMOTE_ADDR")) {
            $ip = getenv("REMOTE_ADDR");
        } else {
            $ip = "NULL";
        }
        return $ip;
    }
}