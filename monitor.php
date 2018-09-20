<?php
/**
 * 微信公众号信息处理
 */
class wxapi {
    
    //corpid
    public $corpid = 'wxe20e1a873ad6ccc1';
    //sercret
    public $corpsecret = 'UTKh0pIFWDFyaU6nr_aK5uHPWELeUwjUHaYE5fjmxHc';
    
    //微信发消息api
    public $weixinSendApi = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=';
    
    /**
     * 请求微信Api，获取AccessToken
     */
    public function getAccessToken()
    {
        error_reporting(E_ALL);
        //临时存放 并不安全
        $filePath = './weixinToken.txt';
        $tokenInfo = array();
        if(is_file($filePath)){
            $tokenInfo = json_decode(file_get_contents($filePath),TRUE);
        }
        if(!isset($tokenInfo['access_token']) || time()>$tokenInfo['expires_in']){
            //更新access_token
            $getAccessTokenApi = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$this->corpid}&corpsecret={$this->corpsecret}";
            
            $jsonString = $this->curlGet($getAccessTokenApi);
            $jsonInfo = json_decode($jsonString,true);
            if(isset($jsonInfo['access_token'])) {
                $jsonInfo['expires_in'] = time() + 7100;
                file_put_contents($filePath, json_encode($jsonInfo));
            }            
            $tokenInfo = $jsonInfo;             
        }
        
        if(isset($tokenInfo['access_token']) && $tokenInfo['expires_in']>time()){
            return $tokenInfo['access_token'];
        } else {
            return FALSE;
        }
    }
    
    /**
     * 发信息接口
     *      
     * @author wanghan
     * @param $content 发送内容
     * @param $touser 接收的用户 @all全部 多个用 | 隔开
     * @param $toparty 接收的群组 @all全部 多个用 | 隔开
     * @param $totag 标签组 @all全部 多个用 | 隔开
     * @param $agentid 应用id
     * @param $msgtype 信息类型 text=简单文本
     */
    public function send($content,$urlalert,$totag='34',$agentid=1000021,$msgtype='textcard')
    {   
        $api = $this->weixinSendApi.$this->getAccessToken();
        $postData = array(
#            'touser' => "KD008604",
#            'toparty' => $toparty,
            'totag' => $totag,
            'msgtype' => $msgtype,
            'agentid' => $agentid,
            'textcard' => array(
                'title' => "系统告警",
		'description' => $content,
		'url' => $urlalert,
		'btntxt' => "详细信息"
            )
        );
        
        $postString = urldecode(json_encode($postData));
        $ret = $this->curlPost($api,$postString);
        $retArr = json_decode($ret,TRUE);
        if(isset($retArr['errcode']) && $retArr['errcode'] == 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Curl Post数据
     * @param string $url 接收数据的api
     * @param string $vars 提交的数据
     * @param int $second 要求程序必须在$second秒内完成,负责到$second秒后放到后台执行
     * @return string or boolean 成功且对方有返回值则返回
     */
    function curlPost($url, $vars, $second=30)
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
//            'Content-Type: application/json; charset=utf-8',  
//            'Content-Length: ' . strlen($vars))  
//        ); 
        $data = curl_exec($ch);
        curl_close($ch);
        if($data)
            return $data;
        return false;
    }
    
    /**
     * CURL get方式提交数据
     * 通过curl的get方式提交获取api数据
     * @param string $url api地址
     * @param int $second 超时时间,单位为秒
     * @param string $log_path 日志存放路径,如果没有就不保存日志,还有存放路径要有读写权限
     * @return true or false
     */
    function curlGet($url,$second=30,$log_path='', $host='', $port='')
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        if(!empty($host)){
            curl_setopt($ch,CURLOPT_HTTPHEADER,$host);
        }
        if(!empty($port)){
            curl_setopt($ch,CURLOPT_PORT,$port);
        }
        $data = curl_exec($ch);
        $return_ch = curl_errno($ch);
        curl_close($ch);
        if($return_ch!=0)
        {
            if(!empty($log_path))
                file_put_contents($log_path,curl_error($ch)."\n\r\n\r",FILE_APPEND);
            return false;
        }
        else
        {
            return $data;
        }
    }
}
$wxapi = new wxapi;
$map['Memo2'] = $argv[1];
$map['Memo']=$argv[2];
$map['UnclearAmount']=$argv[3];
$map['$DeptUnclearAmount']=$argv[4];
$memo = "<div class=\"gray\">设备名称：</div>".$map['Memo2']."\n";
$memo.= "<div class=\"gray\">告警消息：</div>".$map['Memo']."\n";
$memo.= "<div class=\"gray\">错误状况：</div>".$map['UnclearAmount']."\n";
$memo.= "<div class=\"gray\">生成时间：</div>".$map['$DeptUnclearAmount']."\n";
$urlal = "http://10.2.2.33:8086/fault/AlarmActions.do?methodCall=alarmProperties&entity=".$argv[5];
#echo $memo;
#echo $urlal;
#echo $argv[0];
$wxapi->send($memo,$urlal);

