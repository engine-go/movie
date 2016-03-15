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


class ForumController extends Controller {

    public $sid="";

    public function __construct(){
        parent::__construct();
        $this->sid = I('id'); //版块id
        $forum_sections = D('ForumSection')->select();
        foreach($forum_sections as $key=>$section) {
            $forum_sections[$key]['class'] = ($section['id'] == $this->sid) ? "active" : "";
        }

        $this->assign('sectionid', $this->sid);
        $this->assign('sections',$forum_sections);
    }

    public function index(){


        $map['sid'] = I('id'); //版块id
        $count      = D('ForumTopic')->where($map)->count();//查询满足要求的总记录数
        $Page       = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $show       = trim($Page->show());// 分页显示输出


        $list = D('ForumTopic')->where($map)->limit($Page->firstRow.','.$Page->listRows)
        ->order('create_time desc')->select();

        foreach( $list as $key=>$val){
            $list[$key]['friendlyDate'] =  friendlyDate($val['create_time']);
        }


        //主题列表
        $this->assign('list',$list);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('totalPages',$Page->totalPages);
        $this->display();
    }

    /**
     * 主题详情
     */
    public function topicDetail(){

        $map['tid'] = I('tid'); //主题id
        $topicData = D('ForumTopic')->where($map)->find();
        $postData = D('ForumPost')->where($map)->select();




        //获取主题帖
        $allPost = array();
        foreach($postData as $key=> $row){

            $row['content'] = trim($row['content']);
            $row['content'] = html_entity_decode($row['content']);
            $row['friendlyTime'] = friendlyDate($row['create_time']);


                    if( $row['is_first'] ){
                        $firstPost = $row;
                    }else{
                        $allPost[$key]=$row;
                    }
        }

        $topicData['friendlyTime'] = friendlyDate($topicData['create_time']);
        $this->assign('firstPost',$firstPost); //首贴


        //统计浏览次数
        $viewKey = "VIEW_TOPIC_".I('tid');
        if(!isset($_COOKIE[$viewKey])){
            setcookie($viewKey,1,strtotime('tomorrow'));
            D('ForumTopic')->where("tid=".I('tid'))->setInc("views");
        }

        $allPost=array_values($allPost);
        $this->assign('allPosts',$allPost); //所有帖子
        $this->assign('replyCount',count($allPost)); //所有帖子
        $this->assign('topic',$topicData); //主题
        $this->display();
    }

    public function post(){
        $user = D('User');
        $ret = $user->isLogin();
        if(!$ret){
            $jumpUrl = U('/Passport/login');
            redirect($jumpUrl);
        }


        $this->display();
    }

    public function upload(){



        if(!D('User')->isLogin()){
            echo "error|请先登录";exit;
        }

        //本地上传到
        $upload = new Upload();
        //$upload->rootPath= dirname($_SERVER['SCRIPT_FILENAME'])."/";
        $upload->rootPath=ROOT_PATH;
        $upload->savePath='Public/upload/';
        $upload->exts=array('jpg','jpeg','gif','png');
        $ret =  $upload->upload();

        if(!$ret){
            echo "error|{$upload->getError()}";
        }else{
            $imgInfo = $ret['wangEditorH5File'];
            $filename = $upload->rootPath.$imgInfo['savepath'].$imgInfo['savename'];

//            $image = new Image(1,$filename);
//            $water_file = ROOT_PATH."public/data/common/water.png";
//
//
//            $image->water($water_file,Image::IMAGE_WATER_SOUTHEAST,70);
//            $image->save($filename);

            $uploadImgUrl = $this->upload2Qiniu($filename);

            if($uploadImgUrl){
                echo $uploadImgUrl;
            }else{
                echo "error|上传失败哟！";
            }

        }

    }


    /*上传到七牛云存储对象中*/
    /**
     * @param $origin_filename 原图片地址
     * @return string 如果失败，返回空字符串；如果成功，返回图片地址
     * @throws \Exception
     */
    private function upload2Qiniu($origin_filename){


        require 'vendor/autoload.php';

        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = 'ml7Vji4z-pfZVV8wI-6jTaZmrkBCm7BIiKx7ucWu';
        $secretKey = 'h4Fa_BE06b1oZvFW4ixqeR8mymtNC6r_4vDx58b4';


        // 初始化签权对象
        $auth = new Auth($accessKey, $secretKey);
        $domain = "http://7xrl3c.com1.z0.glb.clouddn.com";//测试域名

        // 要上传的空间
        $bucket = 'movie';

        // 生成上传 Token
        $token = $auth->uploadToken($bucket);


        // 要上传文件的本地路径
        $filePath = $origin_filename;

        // key为上传到七牛后保存的文件名
        $pathArr = explode("/",$origin_filename);
        $key = end($pathArr);

        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();

        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);

        if ($err !== null) {
            return  "";
        } else {
            return $domain.DIRECTORY_SEPARATOR.$key;
        }

    }


    //发帖
    public function doTopic(){

        $user = D('User');
        $ret = $user->isLogin();
        if(!$ret){
            $jumpUrl = U('/Passport/login');
            $this->ajaxReturn(array('status'=>1,'info'=>'发帖请先登录','url'=>$jumpUrl));
        }

        //创建主题
        $data  = array();
        $data['title'] = I('title');

        if( mb_strlen($data['title'],'UTF-8') < 10 ){
            $this->ajaxReturn(array('status'=>-1,'info'=>'标题不得小于10个字符'));
        }

        $data['create_time']=time();
        $data['author'] = $this->user['username'];
        $data['lastposter'] = $this->user['username'];
        $data['sid'] = I('sid');

        $topic_id  = D('ForumTopic')->add($data);
        if (!$topic_id){
            $this->ajaxReturn(array('status'=>-3,'info'=>'发布帖子失败'));
        }


        //发布帖子
        $detailData= array();
       // $content = I('content');
        $detailData['content'] = $_POST['content'];
        $detailData['is_first'] = 1;
        $detailData['tid'] = $topic_id;
        $detailData['author'] = $this->user['username'];
        $detailData['authorid'] = $this->user['uid'];
        $detailData['create_time']=time();


        $ret  = D('ForumPost')->add($detailData);


        if($ret){
            $jumpUrl = U('/Forum/index',array('id'=>$data['sid']));
            $this->ajaxReturn(array('status'=>1,'info'=>'发布成功','url'=>$jumpUrl));
        }else{
            $this->ajaxReturn(array('status'=>1,'info'=>'发布失败'));
        }




    }


    public function doReply(){

        $this->checkLogin();

        $data=array();
        $data['tid'] = I('tid');
        $data['content'] = I('replay');
        $data['author'] = $this->user['username'];
        $data['authorid'] = $this->user['uid'];
        $data['create_time'] = time();


        if(!D('ForumPost')->add($data)){
            $this->ajaxReturn(array('status'=>-2,'info'=>'回复失败'));
        }

        //主题的回复+1
        D('ForumTopic')->where("tid=".$data['tid'])->setInc("replies");

        $update_data=array();
        $update_data['lastposter']=$this->user['username'];
        D('ForumTopic')->where("tid=".$data['tid'])->save($update_data);

        $section_id =I('id');

        $jumpUrl = U('/Forum/topicDetail',array('tid'=>$data['tid'],'id'=>$section_id));
        $this->ajaxReturn(array('status'=>1,'info'=>'发布成功','url'=>$jumpUrl));

    }



}
