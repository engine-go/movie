<?php
namespace Home\Controller;
use Think\Controller;
use Home\Model;
use Org\Net\Http;
use Org\Net\Snoopy;
use Think\Page;
use Org\Net\simple_html_dom;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;
use Think\Upload;
use Think\Image;


class UserDetailController extends Controller {

   public function index(){
       $this->display();
   }


    public function updateInfo(){

        $return_data =array();

        $data['username'] = I('username');
        $data['word']= I('word');
        $userModel = D('User');

        $id =$userModel->where('uid='.$this->user['uid'])->save($data);
        $userinfo = $userModel->where('uid='.$this->user['uid'])->find();

        $userModel->setLoginCookie($userinfo);

        if( $id ){
            $return_data['msg'] = '修改成功';
            $return_data['info'] = '1';
            $return_data['url']=U('UserDetail/index');
        }else{
            $return_data['msg'] = '更新失败';
            $return_data['info']=-1;
        }

        $this->ajaxReturn($return_data);

    }


}
