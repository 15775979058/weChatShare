<?php


class Jssdk {
  private $appId;
  private $appSecret;
  private $time1;
  private $url1;

  public function __construct($appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
  }

  public function getSignature(){
      $this ->time1  = time();
      //获取accessToken
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
      $res = json_decode($this->httpGet($url));
      $token = $res->access_token;

      //取出JS凭证
      $js = file_get_contents("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$token."&type=jsapi");
      $jss = json_decode($js,True);
      $jsapi_ticket = $jss['ticket'];//   取出JS凭证, 至于存储代码就不列举了

      //开始签名算法了
      $dataa['noncestr'] =  'ymsmsxt'; //随意字符串 一会要传到JS里去.要求一致
      $dataa['jsapi_ticket'] = $jsapi_ticket;
      $dataa['timestamp'] = $this ->time1;
      $this->url1 = $dataa['url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];//动态获取URL
      ksort($dataa);
      $signature = '';
      foreach($dataa as $k => $v){
        $signature .= $k.'='.$v.'&';
      }
      $signature = substr($signature, 0, strlen($signature)-1);
      $this->signature = sha1($signature);// 必填，签名

      $wxShareInfo = array(
        'timestamp' => $this ->time1,
        'nonceStr' => $dataa['noncestr'],
        'signature' => $this->signature,
        'shareUrl' => $this->url1
      );
      return $wxShareInfo;
  }

  public function getSignPackage() {

    $jsapiTicket = $this->getJsApiTicket();

    // 注意 URL 一定要动态获取，不能 hardcode.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    return $signPackage; 
  }

  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  private function getJsApiTicket() {
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = cache('jsapi_ticket');
    if ($data['expire_time'] < time()) {
      $accessToken = $this->getAccessToken();
      // 如果是企业号用以下 URL 获取 ticket
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      $res = json_decode($this->httpGet($url));
      
      $ticket = $res->ticket;
      if ($ticket) {
        $data['expire_time'] = time() + 7000;
        $data['jsapi_ticket'] = $ticket;
        cache('jsapi_ticket',$data);
      }
    } else {
      $ticket = $data['jsapi_ticket'];
    }

    return $ticket;
  }

  private function getAccessToken() {
    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = cache('access_token');
    if ($data['expire_time'] < time()) {
      // 如果是企业号用以下URL获取access_token
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
      $res = json_decode($this->httpGet($url));
      $access_token = $res->access_token;
      
      if ($access_token) {
        $data['expire_time'] = time() + 7000;
        $data['access_token'] = $access_token;
        cache('access_token',$data);
      }
    } else {
      $access_token = $data['access_token'];
    }
    return $access_token;
  }

  private function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl, CURLOPT_TIMEOUT, (int)500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }

}