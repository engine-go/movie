<?php
/*
 * @author EngineMa
 * 用于抓取豆瓣信息
 *
 *
 * */
namespace Home\Controller;
use Think\Controller;
use Org\Net\Http;
use Org\Net\Snoopy;
use Org\Net\simple_html_dom;



class SpiderController extends Controller {

    public $movie_data_model =null;

    public function __construct(){

        $this->movie_data_model = M("MovieData");
    }

    public function index(){

        $start = intval($_GET['start']);
        $url  = "http://movie.douban.com/top250?start={$start}&filter=";
        $html = new simple_html_dom();
        $html->load_file($url);
        $dom = $html->find('.grid_view');


        $res =array();
        foreach( $dom as $key=> $ol) {
            foreach($ol->find('li') as $key=> $li) {

                foreach ($li->find('.title') as $title) {

                    //dump($title->innertext);
                    $title = $this->strips_title($title->innertext);
                    $map['title']=$title;
                    $ret = $this->movie_data_model->where($map)->find();
                    if( $ret ){
                        continue 2;
                    }


                    if (!isset($res[$key]['title'])) {
                        $res[$key]['title'] = trim($title);

                    } else {
                        $res[$key]['en_title'] =$title;
                    }

                }

                foreach( $li->find('.other') as $other) {
                    $other_title = $this->strips_title($other->innertext);
                    $res[$key]['other_title'] = $other_title;
                }

                foreach( $li->find('a') as $a) {
                    $res[$key]['href'] = $a->href;
                }

                foreach( $li->find('.rating_num') as $rank) {
                    $res[$key]['rank'] = $rank->innertext;
                }

                foreach( $li->find('.inq') as $quote) {
                    $res[$key]['quote'] = $quote->innertext;
                }

                foreach( $li->find('img') as $img){
                    $res[$key]['img'] = $img->src;
                }

                foreach( $li->find('.star') as $star){
                    $res[$key]['comment'] = (int)$star->lastchild()->innertext;
                }


            }

        }


        foreach( $res as $key=>$val ){
                unset($res[$key]['href']);
                unset($res[$key]['comment']);
                unset($res[$key]['is_valid']);
                $this->movie_data_model->add($val);
        }


        $html->clear();

//        //获得磁力链接
//        $this->spiderMagnet();
    }

    //爬虫抓取磁力链接
    public function spiderMagnet(){

        $map['magnet'] = '';
        $ret  = $this->movie_data_model->where($map)->limit(25)->select();
        $data = array();
       // dump($ret);
        if( !empty($ret) ) {
            $num =0;
            foreach ($ret as $key => $movie) {
                $data['id'] = $movie['id'];
                $data['magnet'] = $this->getMagnet($movie['title']);
                if( !empty($data['magnet']) && strlen($data['magnet']) > 0 ){
                    $this->movie_data_model->save($data);
                    $num++;
                }else{
                    $data['magnet']="0";
                    $this->movie_data_model->save($data);
                }
            }

            echo $num;exit;
        }

        echo 0;

    }

    public function test(){
        echo 123;
    }


    //处理豆瓣电影标题的一些特殊字符
    public function strips_title($text){

        $text = trim($text);
        $text = preg_replace('/\&nbsp;/', "", strip_tags($text) );
        $text = ltrim($text,"/");
        $text = preg_replace('/\&#39;/','\'',$text);

        return $text;
    }


    //获取磁力
    public function getMagnet($keyword){

        $source_url = "https://btdigg.org/search?info_hash=&q={$keyword}";




        $snoopy  = new Snoopy();
        $snoopy->read_timeout=10;
        $snoopy->agent="Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:30.0) Gecko/20100101 Firefox/30.0";
        $snoopy->fetch($source_url);

        $status =   Http::get_http_response_code($source_url);
//        if ( $status!=200 ){
//                return $status;
//        }
        var_dump($status);

        $document = $snoopy->results; //document结果

        //$document = Http::requestGet($source_url);


        $html = new simple_html_dom();
       // $html->load_file($source_url);
        $html->load($document);


        foreach( $html->find(".ttth a") as $a ){
                if( strlen($a->href) > 0 ){
                    return $a->href;
                }
        }
    }





}