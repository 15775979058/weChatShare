<h1>weChat share method</h1>

	本项目环境是thinkphp3.2+mysql+center os6.5
	<h2>文件简介</h2>
	<ul>
		<li>
			config.php:	主要用于配置微信appId与appSecret
		</li>
		<li>
			jssdk.class.php:	主要用于放在扩展第三方类进行access_token获取与签名生成功能
		</li>
		<li>
			UcenterController.class.php:	主要用于控制器获取第三方得到的数据，并assign到静态页面
		</li>
		<li>
			ucenter.html:	主要用于页面进行分享功能的实现与js代码
		</li>
	</ul>

	<h2>具体重点代码详解：</h2>
	<div>
		<h3>config.php:</h3><br/>
		    /* 微信分享配置 亿鼎达 */
		    'wechat' => [
		        'appId' => '你的appId',
		        'appSecret' => '你的appSecret'
		    ],
		    <p/>
	</div>

	<div>
		<h3>Jssdk.class.php:</h3><br/>
		    ```
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
		    ```
		    <p/>
	</div>

	<div>
		<h3>UcenterController.class.php:</h3><br/>
		    ```
		        public function ucenter(){
			       $options = C('wechat');
			        import('Vendor.wxShare.Jssdk');
			        $wxObj = new \Jssdk($options['appId'],$options['appSecret']);
			        //获取签名
			        $wxSignArr = $wxObj -> getSignature();

			        //微信分享jssdk数组
			        $wxShareInfo = array(
			            'appId' => $options['appId'],
			            'timestamp' => $wxSignArr['timestamp'],
			            'nonceStr' => $wxSignArr['nonceStr'],
			            'signature' => $wxSignArr['signature'],
			            'shareUrl' => $wxSignArr['shareUrl']
			        );
			        $this -> assign('wxShareInfo',$wxShareInfo);
			    }
		    ```
		    <p/>
	</div>

	<div>
		<h3>ucenter.html:</h3><br/>
		    ```
		     <script>
			    wx.config({
			        debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
			        appId: "{$wxShareInfo['appId']}", // 必填，公众号的唯一标识
			        timestamp:"{$wxShareInfo['timestamp']}" , // 必填，生成签名的时间戳
			        nonceStr: "{$wxShareInfo['nonceStr']}", // 必填，生成签名的随机串
			        signature: "{$wxShareInfo['signature']}",// 必填，签名，见附录1
			        jsApiList: [  'onMenuShareTimeline',
			            'checkJsApi',
			            'onMenuShareTimeline',
			            'onMenuShareAppMessage',
			            'onMenuShareQQ',
			            'onMenuShareWeibo',
			            'chooseWXPay'
			        ] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
			    });


			    wx.ready(function(){
			        wx.onMenuShareAppMessage({
			            title: '周亮', // 分享标题
			            desc: '嘻嘻哈哈程序猿', // 分享描述
			            link: "{$wxShareInfo['shareUrl']}", // 分享链接
			            imgUrl: 'http://<?php echo $_SERVER["SERVER_NAME"]; ?>__IMG__/uploads/self-img-collect.jpg', // 分享图标
			            type: 'link', // 分享类型,music、video或link，不填默认为link
			            dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
			            success: function () {
			                alert('分享成功');
			            },
			            cancel: function () {
			                alert('取消分享了');
			            },
			        });
			    });
			</script>
		    ```
		    <p/>
	</div>

