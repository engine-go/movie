<?php
namespace Home\Controller;
use Think\Controller;
use Home\Model;
use Org\Net\Http;
use Org\Net\Snoopy;
use Think\Page;
use Org\Net\simple_html_dom;
class TechnologyController extends Controller {



    public function __construct(){
        parent::__construct();
    }

    public function index(){
        $forum_sections = D('ForumSection')->select();
        $this->assign('sections',$forum_sections);
        $this->display();
    }




}
