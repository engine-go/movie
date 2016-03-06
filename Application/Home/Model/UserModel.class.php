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

}