<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use Home\Model\OrderModel;
use OT\DataDictionary;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class UcenterController extends HomeController {
    //个人中心
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

        //获取登录消费者信息
        $customerUid = session('customerUid');
        $uidWhere = array(
            'uid' => $customerUid,
            'status' => 1
        );
        $customerInfo = M('user') -> where($uidWhere) -> find();
        if($customerInfo){
            $loginTitle = $customerInfo['nickname'];
            $loginTitleArr = array(
                'loginStatus' => 1,
                'loginNickname' => $loginTitle,
                'loginHeaderUrl' => '/Public/Home/images/logo.png'
            );
        }else{
            $loginTitle = '请登录';
            $loginTitleArr = array(
                'loginStatus' => 0,
                'loginNickname' => $loginTitle,
                'loginHeaderUrl' => '/Public/Home/images/touxiang.png'
            );
        }

        $this -> assign('wxShareInfo',$wxShareInfo);
        $this -> assign('loginTitleArr',$loginTitleArr);
        $this->display();
    }

    /*
     * 退出成功
     *      方法
     */
    public function exitCustomerFun(){
        if(IS_POST){
            $postArr = I('post.');

            if($postArr['exitStatus'] == 1){
                session('customerUid',null);           // 删除当前登录的session
                $ajax['status'] = 1;
                $ajax['msg'] = '退出成功';
                $this -> ajaxReturn($ajax);
            }else{
                $ajax['status'] = 0;
                $ajax['msg'] = '退出失败';
                $this -> ajaxReturn($ajax);
            }
        }
    }


    //个人信息
    public function personal(){

        $customerUid = session('customerUid');
        if($customerUid){
            //查询对应的消费者信息
            $whereLoginUser = array(
                'uid' => $customerUid,
                'status' => 1
            );
            $loginUser = M('user') -> where($whereLoginUser) -> find();
            if($loginUser){

                $this -> assign('loginUser',$loginUser);
                $this ->display();
            }else{
                $this -> error('该用户已被禁用，请重新登录!');
            }
        }else{
            $this -> error('请登录以后，进行此操作!');
        }


    }

    /*
     * 修改个人信息
     */
    public function alterPasswordFun(){
        if(IS_POST){
            $postArr = I("post.");

            if($postArr['uid'] == ''){
                $ajax['status'] = 0;
                $ajax['msg'] = '请登录以后，再进行此操作！';
                $this -> ajaxReturn($ajax);
            }

            //修改条件
            $whereUcenter = array(
                'uid' => $postArr['uid'],
                'status' => 1
            );

            //修改内容
            $updateUcenter = array(
                'nickname' => $postArr['nickname'],
                'email' => $postArr['email'],
                'sex' => $postArr['sex'],
                'qq' => $postArr['qq'],
            );
            $resUser = M('user') -> where($whereUcenter) -> save($updateUcenter);
            if($resUser){
                $ajax['status'] = 1;
                $ajax['msg'] = '修改成功！';
                $this -> ajaxReturn($ajax);
            }else{
                $ajax['status'] = 2;
                $ajax['msg'] = '您没有修改任何信息！';
                $this -> ajaxReturn($ajax);
            }
        }
    }

    //收藏
    public function collect(){
        $whereCollect = array(
            'uid' => session('customerUid'),
            'is_delete' => 0
        );
        $collectList = M('collect') -> where($whereCollect) -> select();
        if($collectList){
            //遍历得到运营中心数据
            foreach($collectList as $k => $v){
                $whereMember = array(
                    'uid' => $v['mid']
                );
                $memberData = M('member') -> where($whereMember) -> find();
                $collectList[$k]['nickname'] = $memberData['nickname'];
                $collectList[$k]['mobile'] = $memberData['mobile'];
                $whereCenter = array(
                    'id' => $memberData['center_id'],
                    'status' => 1
                );
                $centerData = M('center') -> where($whereCenter) -> find();
                if($centerData['company_name']){
                    $collectList[$k]['centerName'] = $centerData['company_name'];
                    $collectList[$k]['centerStatus'] = 1;
                }else{
                    $collectList[$k]['centerName'] = '此运营中心已被禁用或不存在';
                    $collectList[$k]['centerStatus'] = 0;
                }
            }

            $collectData['status'] = 1;
            $collectData['list'] = $collectList;
        }else{
            $collectData['status'] = 0;
        }


        $this -> assign('collectData',$collectData);
        $this ->display();
    }

    //消费者订单
    public function orderlist(){

        //判断消费者是否登录
        $customerUid = session('customerUid');
        if($customerUid){
            //全部订单信息
            $whereCusOrder = array(
                'customer_id' => $customerUid,
                'is_delete' => 0
            );
            $orderListAll = M('order') -> where($whereCusOrder) -> select();
            if($orderListAll){
                //排除status为0的状态
                $orderList = array();
                foreach($orderListAll as $k => $v){
                    //查询业务员信息与手机
                    $whereMember = array(
                        'uid'  => $v['business_id']
                    );
                    $businessData = M('member') -> where($whereMember) -> find();
                    $orderListAll[$k]['business_name'] = $businessData['nickname'];
                    $orderListAll[$k]['business_mobile'] = $businessData['mobile'];
                    //查询运营中心信息
                    $whereCenter = array(
                        'id' => $v['center_id']
                    );
                    $centerData = M('center') -> where($whereCenter) -> find();
                    $orderListAll[$k]['company_name'] = $centerData['company_name'];
                    //查询物流信息数据
                    $whereLogistics = array(
                        'oid' => $v['order_id'],
                        'is_delete' => 0
                    );
                    $logisticsArr = M('logistics') -> where($whereLogistics) -> select();
                    $orderListAll[$k]['logisticsArr'] = $logisticsArr;

                    //筛选有效订单
                    if($v['status'] > 0){
                        array_push($orderList,$orderListAll[$k]);
                    }
                }
                $orderData['status'] = 1;
                $orderData['data'] = $orderList;
            }else{
                $orderData['status'] = 0;
            }
        }else{
            $orderData['status'] = 0;
        }

        $this -> assign('orderData',$orderData);
        $this ->display();
    }

    //微信分享
    public function wxShareFun(){



    }
}