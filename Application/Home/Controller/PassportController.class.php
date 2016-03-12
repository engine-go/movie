<?php
namespace Home\Controller;
use Think\Controller;
use Think\Crypt;
use Home\Model;
use Org\Net\Http;
use Org\Net\Snoopy;
use Think\Page;
use Org\Net\simple_html_dom;
class PassportController extends Controller {

    public $movieModel=null;

    public function __construct(){
        parent::__construct();
       // $this->passportModel = M("Passport");


        if( $this->isLogin() ){
            $url =U('Index/index');
            redirect($url);
        }

    }

    public function doRegister(){

        $user = new \Home\Model\UserModel();
        $result = $user->create();

        if(!$result){
            $error_msg = $user->getError();
            $this->error($error_msg,"",true);
        }

        $info=array();
        $info['email'] = I('email');
        $info['password']= I('password');
        $info['username']= I('username');


        $ret = $user->addUser($info);
        $ret['status']<0 && $this->ajaxReturn($ret);

        $this->setLogin($ret['info']);
        $data = array('status'=>1,'info'=>'注册成功');
        $this->ajaxReturn($data);

    }

    //


    protected function isLogin(){


        if( $_COOKIE['token']  ){
                return true;
        }elseif( $_SESSION['uid'] ){
            $map['uid'] = $_SESSION['uid'];
            $info = M('User')->where($map)->find();
            if( !$info['uid'] ){
                return false;
            }

            $this->setLoginCookie($info,86400);
            return true;
        }else{
            return false;
        }

    }




    public function doLogin(){

        $user = new \Home\Model\UserModel();
        $result = $user->create();

        if(!$result){
            $error_msg = $user->getError();
            $this->error($error_msg,"",true);
        }

        $email = I('email');
        $password = I('password');
        $liveTime = I('remember')==1?86400*7:86400;

        $map['email'] = trim($email);
        if( $info = $user->where($map)->find() ){
            if( $info['password']!=md5($password.$info['salt']) ) {
                $data = array('status' => -1, 'info' => '邮箱或密码错误');
            }else{
                $this->setLogin($info,$liveTime);
                $data = array('status' => 1, 'info' => '登录成功');
            }
        }else{
            $data = array('status'=>-2,'info'=>'邮箱或密码错误');
        }


        $this->ajaxReturn($data);
    }

    public function setLogin($info,$livetime=86400){

        //设置cookie
        $this->setLoginCookie($info,$livetime);

        //设置session
        $_SESSION['uid'] = isset($info['uid'])?$info['uid']:0;
        return true;

    }

    private function setLoginCookie($ret,$livetime=86400){
        $ret = json_encode($ret);
        $encrypt_str = Crypt::encrypt($ret,'thisismovieapp');
        cookie('token',$encrypt_str,$livetime);
        return true;
    }



}
