<?php
namespace Home\Controller;
use Think\Controller;
use Org\Net\Http;
use Org\Net\Snoopy;
use Org\Net\simple_html_dom;
class IndexController extends Controller {

    public $movieModel=null;

    public function __construct(){
        parent::__construct();
        $this->movieModel = M("MovieData");
    }

    public function index(){

        $sql = "select * from movie_data order by rank desc limit 20";
        $data = $this->movieModel->query($sql);
        $this->assign('list',$data);
        $this->display();
    }


}