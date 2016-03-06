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
        $this->display();
    }

    public function post(){
        $this->display();
    }

    public function upload(){

        //资源存放路径
        $upload_path = APP_PATH."Public".DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR;

       // dump($_FILES);exit;
        //本地上传到
        $upload = new Upload();
        $upload->rootPath='/data/wwwroot/movie/';
        $upload->savePath='Public/';
        $upload->exts=array('jpg','jpeg','gif','png');
        if(!$upload->upload()){
           dump( $upload->getError());
        }else{
            echo 'success!';
        }
        //dump($ret);
        exit;
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
        $filePath = $upload_path."test.jpg";




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



}
