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


class ForumController extends Controller {



    public function __construct(){
        parent::__construct();
        $forum_sections = D('ForumSection')->select();
        $this->assign('sections',$forum_sections);
    }

    public function index(){

        $map['sid'] = I('id'); //版块id
        $list = D('ForumTopic')->where($map)->select();
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 主题详情
     */
    public function topicDetail(){

        $map['tid'] = I('tid'); //主题id
        $topicData = D('ForumTopic')->where($map)->find();


        //$map['is_first'] = 1;
        $postData = D('ForumPost')->where($map)->select();
        $allPost = array();
        foreach($postData as $key=> $row){

            $row['content'] = trim($row['content']);
            $row['content'] = html_entity_decode($row['content']);

                    if( $row['is_first'] ){
                        $firstPost = $row;
                    }else{
                        $allPost[$key]=$row;
                    }
        }


        $this->assign('firstPost',$firstPost); //首贴
        $this->assign('allPost',$postData); //所有帖子
        $this->assign('topic',$topicData);
        $this->display();
    }

    public function post(){
        $this->display();
    }

    public function upload(){

        //本地上传到
        $upload = new Upload();
        $upload->rootPath='/data/wwwroot/movie/';
        $upload->savePath='Public/';
        $upload->exts=array('jpg','jpeg','gif','png');
        $ret =  $upload->upload();
        if(!$ret){
            echo "error|{$upload->getError()}";
        }else{
            $imgInfo = $ret['wangEditorH5File'];
            $host = $_SERVER['HTTP_HOST'];
            echo "http://".$host."/movie/".$imgInfo['savepath'].$imgInfo['savename'];
            exit;
            //echo  "http://7xrl3c.com1.z0.glb.clouddn.com/{$ret['savename']}";
        }

    }


    /*上传到七牛云存储对象中*/
    private function upload2Qiniu(){


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
        $filePath = $uploadFile;//$upload_path."test.jpg";




        // 上传到七牛后保存的文件名
        $key = 'my-php-logo1.png';

        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();

        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        echo "\n====> putFile result: \n";
        if ($err !== null) {
            dump($err);
        } else {
            dump($ret);
        }

    }


    //新增一个话题
    public function doTopic(){

        $user = D('User');
        $ret = $user->isLogin();
        if(!$ret){
            redirect('/Index/login');
        }

        //创建主题
        $data  = array();
        $data['title'] = I('title');
        $data['create_time']=time();
        $data['author'] = $this->userCookie['username'];
        $data['sid'] = 1;

        $topic_id  = D('ForumTopic')->add($data);
        if (!$topic_id){
          echo '发布帖子失败';exit;
        }

        //$topicData = D('ForumTopic')->where("tid=".$topic_id)->find();
        //发布帖子
        $detailData= array();
        $content = I('content');
        $detailData['content'] = $content;
        $detailData['is_first'] = 1;
        $detailData['tid'] = $topic_id;
        $detailData['author'] = $this->userCookie['username'];
        $detailData['authorid'] = $this->userCookie['uid'];
        $detailData['create_time']=time();
        $ret  = D('ForumPost')->add($detailData);
        if($ret){
            echo 'success|发布成功';
        }else{
            echo 'error|发布帖子失败';
        }




    }



}
