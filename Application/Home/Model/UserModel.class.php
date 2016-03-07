<?php
namespace Home\Model;
use Think\Model;
class UserModel extends Model{

    //自动验证
    protected $_validate = array(
        array("email", "require", "邮箱必须填写"),
        array("email", "email", "邮箱不正确"),
        array("password", "require", "密码必须！"),
        array("username", "require", "昵称必须！"),
        array('repassword','password','两次输入的密码不一样',0,'confirm'),
    );

    /*
     * 注册用户
     * */
    public function addUser($info){

        if(!$info['email']){
            return array('status'=>-2,'info'=>'邮箱不能为空');
        }
        $map['email'] =$info['email'];


        if($this->where($map)->find()){
            return array('status'=>-2,'info'=>'该邮箱已经注册');
        }
        $info['create_time']=time();
        $salt = mt_rand(1000,9999);
        $info['password'] = md5($info['password'].$salt);
        $info['salt']     = $salt;
        $this->add($info);
        $ret = $this->where($map)->find();
        return array('status'>1,'info'=>$ret);
    }


    /**
     * @return bool
     */
    public function isLogin(){


        if( $_COOKIE['token']  ){
            return true;
        }elseif( $_SESSION['uid'] ){
            
            if( !isset($_SESSION['last_access'])
                || (time() - $_SESSION['last_access']) > 1800 ){
                return false;
            }

            $map['uid'] = $_SESSION['uid'];
            $info = $this->where($map)->find();
            if( !$info['uid'] ){
                return false;
            }

            $this->setLoginCookie($info,86400);
            return true;
        }else{
            return false;
        }

    }


    /**
     * @param $info
     * @param int $livetime
     * @return bool
     */
    public function setLogin($info,$livetime=86400){

        //设置cookie
        $this->setLoginCookie($info,$livetime);

        //设置session
        $_SESSION['uid'] = isset($info['uid'])?$info['uid']:0;
        $_SESSION['last_access'] = time();
        return true;

    }


    /**
     * @param $ret
     * @param int $livetime
     * @return bool
     */
    private function setLoginCookie($info,$livetime=86400){
        $ret = json_encode($info);
        $encrypt_str = Crypt::encrypt($ret,'thisismovieapp');
        cookie('token',$encrypt_str,$livetime);
        return true;
    }


}