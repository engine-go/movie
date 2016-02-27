<?php
namespace Home\Controller;
use Think\Controller;
use Org\Net\Http;
use Org\Net\Snoopy;
use Think\Page;
use Org\Net\simple_html_dom;
class IndexController extends Controller {

    public $movieModel=null;

    public function __construct(){
        parent::__construct();
        $this->movieModel = M("MovieData");
    }

    public function index(){

        $count      = $this->movieModel->count();//查询满足要求的总记录数
        $Page       = new Page($count,8);// 实例化分页类 传入总记录数和每页显示的记录数
        $show       = $Page->show();// 分页显示输出

        //进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = $this->movieModel->order('rank desc')
                ->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display(); // 输出模板

    }

    public function watchway(){


        $this->display();
    }


}