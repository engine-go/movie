<?php
namespace Home\Controller;
use Think\Controller;
use Org\Net\Http;
use Org\Net\Snoopy;
use Org\Net\simple_html_dom;
class IndexController extends Controller {

    public function index(){

         $url  = "http://movie.douban.com/top250";
//        $snoopy  = new Snoopy();
//        $snoopy->fetch($url);
//
//        $document = $snoopy->results; //document结果
//        $pattern = '(^(\s*)<(\w+)\sclass="grid_view">.*^\\1</\\2>)ism';
//        preg_match_all($pattern,$document,$match);
//        dump($match);

        $html = new simple_html_dom();
        $html->load_file($url);
        $dom = $html->find('.grid_view');

//        $movie_names = array("大内密探零零发","少林足球","国产凌凌漆");
//        foreach($movie_names as $name)
//        var_dump($this->getMagnet($name));

       // $movie_data_model = M("MovieData");

        $res =array();
        foreach( $dom as $key=> $ol) {
            foreach($ol->find('li') as $key=> $li) {


                foreach ($li->find('.title') as $title) {
                    if (!isset($res[$key]['title'])) {
                        $res[$key]['title'] = trim($title->innertext);

                    } else {
                        $res[$key]['en_title'] = $title->innertext;
                    }

                }

                foreach( $li->find('.other') as $other) {
                    $res[$key]['other'] = $other->innertext;
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





        dump($res);

        $html->clear();
        $this->assign('list',$res);
        $this->display();

    }


    //获取磁力
    public function getMagnet($keyword){

        $source_url = "https://btdigg.org/search?info_hash=&q={$keyword}";
//        $snoopy  = new Snoopy();
//        $snoopy->fetch($source_url);
//        $dom = $snoopy->results;
//
        $html = new simple_html_dom();
        $html->load_file($source_url);

        foreach( $html->find(".ttth a") as $a ){
                if( strlen($a->href) > 0 ){
                    return $a->href;
                }
        }
    }


}