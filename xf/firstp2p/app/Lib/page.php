<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------


class Page  {
    // 起始行数
    public $firstRow    ;
    // 列表每页显示行数
    public $listRows    ;
    // 页数跳转时要带的参数
    public $parameter  ;
    // 分页总页面数
    protected $totalPages  ;
    // 总行数
    protected $totalRows  ;
    // 当前页数
    protected $nowPage    ;
    // 分页的栏的总页数
    protected $coolPages   ;
    // 分页栏每页显示的页数
    protected $rollPage   ;
    // 分页显示定制
    protected $config  =    array('header'=>'条记录','prev'=>'上一页','next'=>'下一页','first'=>'第一页','last'=>'最后一页','theme'=>' %totalRow% %header% %nowPage%/%totalPage% 页 %upPage% %downPage% %first%  %prePage%  %linkPage%  %nextPage% %end%');

    // currentPageSize
    protected $currentPageSize;

    protected $configNoCount  = array('prev'=>'上一页','next'=>'下一页','first'=>'第一页','theme'=>' %upPage% %downPage% %first%  %prePage%  %linkPage%  %nextPage%');
    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $totalRows  总的记录数
     * @param array $listRows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     +----------------------------------------------------------
     */
    public function __construct($totalRows,$listRows,$parameter='', $currentPageSize = 0) {
        $this->totalRows = $totalRows;
        $this->parameter = $parameter;
        $this->rollPage = 5;
        if (!function_exists("C")) {
            $default_list_rows = app_conf("DEAL_PAGE_SIZE");
        } else {
            $default_list_rows = C("PAGE_LISTROWS");
        }
        $this->listRows = !empty($listRows)?$listRows:$default_list_rows;
        $this->totalPages = ceil($this->totalRows/$this->listRows);     //总页数
        $this->coolPages  = ceil($this->totalPages/$this->rollPage);
        $this->nowPage  = isset($_GET['p']) && intval($_GET['p']) ? intval($_GET['p']) : 1;
        if(!empty($this->totalPages) && $this->nowPage>$this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        $this->firstRow = $this->listRows*($this->nowPage-1);
        $this->currentPageSize = $currentPageSize;
    }

    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name]    =   $value;
        }
    }

   private function get_page_link($url,$page,$query) {
        if(substr($url,-1)=="?")
        $url.="p=".$page;
        elseif(strpos($url,'?')&&substr($url,-1)!="&")
        $url.="&p=".$page;
        elseif(strpos($url,'?')&&substr($url,-1)!="?")
        $url.="p=".$page;
        else {
            unset ($query['p']);
            $url.="?p=".$page.$this->get_query($query,"&");
        }

        // 过滤url中的XSS注入字符，单引号双引号
        $url = str_replace(array('\'', '"'), array('', ''), $url);
        return $url;
    }
    /**
     +----------------------------------------------------------
     * 分页显示输出
     * @query 翻页要显示的参数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function show($query=array()) {
        if(0 == $this->totalRows) return '';
        $p = 'p';
        $nowCoolPage      = ceil($this->nowPage/$this->rollPage);
        $url = $_SERVER['REQUEST_URI'];

        $parse = parse_url($url);
        if(isset($parse['query'])) {
            parse_str($parse['query'],$params);
            $query =  array_merge($params,$query);
            unset($params[$p]);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }
        // 过滤url中的XSS注入字符，单引号双引号
        $url = str_replace(array('\'', '"'), array('', ''), $url);
        if(app_conf("URL_MODEL")==1)
        {
            /* $url = $GLOBALS['current_url'];
            $url = htmlspecialchars($url, ENT_QUOTES);
            $url = trim($url,"-");//去掉 翻页多余的 - */
        }
        //上下翻页字符串
        $upRow   = $this->nowPage-1;
        $downRow = $this->nowPage+1;
        if ($upRow>0){
            $upPage="<a href='".$this->get_page_link($url,$upRow,$query)."'>".$this->config['prev']."</a>";
        }else{
            $upPage="";
        }

        if ($downRow <= $this->totalPages){
            $downPage="<a href='".$this->get_page_link($url,$downRow,$query)."'>".$this->config['next']."</a>";
        }else{
            $downPage="";
        }
        // << < > >>
        if($nowCoolPage == 1){
            $theFirst = "";
            $prePage = "";
        }else{
            $preRow =  $this->nowPage-$this->rollPage;
            $prePage = "<a href='".$this->get_page_link($url,$preRow,$query)."' >上".$this->rollPage."页</a>";
            $theFirst = "<a href='".$this->get_page_link($url,1,$query)."' >".$this->config['first']."</a>";
        }
        if($nowCoolPage == $this->coolPages){
            $nextPage = "";
            $theEnd="";
        }else{
            $nextRow = $this->nowPage+$this->rollPage;
            if($nextRow>$this->totalPages)$nextRow = $this->totalPages;
            $theEndRow = $this->totalPages;
            $nextPage = "<a href='".$this->get_page_link($url,$nextRow,$query)."' >下".$this->rollPage."页</a>";
            $theEnd = "<a href='".$this->get_page_link($url,$theEndRow,$query)."' >".$this->config['last']."</a>";
        }
        // 1 2 3 4 5
        $linkPage = "";
        for($i=1;$i<=$this->rollPage;$i++){
            $page=($nowCoolPage-1)*$this->rollPage+$i;
            if($page!=$this->nowPage){
                if($page<=$this->totalPages){
                    $linkPage .= "&nbsp;<a href='".$this->get_page_link($url,$page,$query)."'>&nbsp;".$page."&nbsp;</a>";
                }else{
                    break;
                }
            }else{
                if($this->totalPages != 1){
                    $linkPage .= "&nbsp;<span class='current'>".$page."</span>";
                }
            }
        }
        $pageStr     =     str_replace(
            array('%header%','%nowPage%','%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%'),
            array($this->config['header'],$this->nowPage,$this->totalRows,$this->totalPages,$theFirst,$upPage,$prePage,$linkPage,$nextPage,$downPage,$theEnd),$this->config['theme']);
        return $pageStr;
    }

    /**
     +----------------------------------------------------------
     * 分页显示输出 (假,不带总页数)
     * @query 翻页要显示的参数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function showWithNoCount($query=array()) {

        $p = 'p';
        $nowCoolPage      = ceil($this->nowPage/$this->rollPage);
        $url = $_SERVER['REQUEST_URI'];

        $parse = parse_url($url);
        if(isset($parse['query'])) {
            parse_str($parse['query'],$params);
            $query =  array_merge($params,$query);
            unset($params[$p]);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }
        // 过滤url中的XSS注入字符，单引号双引号
        $url = str_replace(array('\'', '"'), array('', ''), $url);
        //上下翻页字符串
        $upRow   = $this->nowPage-1;
        $downRow = $this->nowPage+1;
        if ($upRow>0){
            $upPage="<a href='".$this->get_page_link($url,$upRow,$query)."'>".$this->config['prev']."</a>";
        }else{
            $upPage="";
        }

        if ($this->currentPageSize >= $this->listRows){
            $downPage="<a href='".$this->get_page_link($url,$downRow,$query)."'>".$this->config['next']."</a>";
        }else{
            $downPage="";
        }
        // << < > >>
        $nowCoolPage = ceil($this->nowPage/$this->rollPage);
        if($nowCoolPage <= 1){
            $theFirst = "";
            $prePage = "";
        }else{
            $preRow =  $this->nowPage-$this->rollPage;
            $prePage = "<a href='".$this->get_page_link($url,$preRow,$query)."' >上".$this->rollPage."页</a>";
            $theFirst = "<a href='".$this->get_page_link($url,1,$query)."' >".$this->config['first']."</a>";
        }

        $theEnd="";
        if($this->currentPageSize < $this->listRows){
            $nextPage = "";
        }else{
            $nextRow = $this->nowPage+$this->rollPage;
            $nextPage = "<a href='".$this->get_page_link($url,$nextRow,$query)."' >下".$this->rollPage."页</a>";
        }
        // 1 2 3 4 5
        $linkPage = "";
        for($i=1;$i<=$this->rollPage;$i++){
            $page=($nowCoolPage-1)*$this->rollPage+$i;
            if($page!=$this->nowPage){
                if($this->currentPageSize >= $this->listRows){
                    $linkPage .= "&nbsp;<a href='".$this->get_page_link($url,$page,$query)."'>&nbsp;".$page."&nbsp;</a>";
                }else{
                    break;
                }
            }else{
                $linkPage .= "&nbsp;<span class='current'>".$page."</span>";
            }
        }
        $pageStr     =     str_replace(
            array('%nowPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%'),
            array($this->nowPage,$theFirst,$upPage,$prePage,$linkPage,$nextPage,$downPage),$this->configNoCount['theme']);
        return $pageStr;
    }


    /**
     * * 获取翻页url 的参数
     * @param unknown $query 查询参数
     * @param string 链接符号 ? 或者 &
     * @return string
     */
    private function get_query($query=array(),$link=''){
        $str = http_build_query($query);
        if($str){
            return $link.$str;
        }
    }

}
?>
