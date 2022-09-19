<?php
/** php Export CSV  class,根据总记录数与每批次记录数,计算总批次,循环导出。
 *
 *  Func:
 *  public setPageSize   设置每批次导出的记录条数
 *  public setExportName  设置导出的文件名
 *  public setSeparator   设置分隔符
 *  public setDelimiter   设置定界符
 *  public export      执行导出
 *  private getPageCount   计算导出总批次
 *  private setHeader    设置导出文件header
 *  private formatCSV    将数据格式化为csv格式
 *  private escape      转义字符串
 */
namespace libs\utils;

class ExportCsv {

    private $_dataType = 2;
    private $_title = null;
    private $_data = null;

    // 定义类属性
    protected $total = 0;         // 总记录数
    protected $pagesize = 500;      // 每批次导出的记录数
    protected $exportName = 'export.csv'; // 导出的文件名
    protected $separator = ',';      // 设置分隔符
    protected $delimiter = '"';      // 设置定界符

    /**
     * setDataType
     * 设置导入的数据组类型
     * @param int $type 1带key值的,2不带key值的
     * @access public
     * @return void
     */
    public function setDataType($type = 1){
        $this->_dataType = $type;
    }
    /**
     * setExportData
     * 设置导出数据
     * @param Array $data 要导出的数据列表
     * @access public
     * @return void
     */
    public function setExportData(Array $data){
        $this->_data = $data;
    }

    /**
     * setExportTitle
     * 设置导出的标题
     * @param Array $title 导出的数据列标题
     * @access public
     * @return void
     */
    public function setExportTitle(Array $title){
        $this->_title= $title;
    }

    /**
     * 返回总导出记录数
     * @return int
     */
    public function getExportTotal(){
        return count($this->_data);
    }

    /**
     * 获取导出的列名
     * @return Array
     */
    public function getExportTitle(){
        return $this->_title;
    }

    /**
     * 获取每批次数据
     * @param int $offset 偏移量
     * @param int $limit 获取的记录条数
     * @return Array
     */
    public function getExportData($offset, $limit){
        return array_slice($this->_data, $offset, $limit);
    }


    /**
     * 设置每次导出的记录条数
     * @param int $pagesize 每次导出的记录条数
     */
    public function setPageSize($pagesize=0){
        if(is_numeric($pagesize) && $pagesize>0){
            $this->pagesize = $pagesize;
        }
    }

    /**
     * 设置导出的文件名
     * @param String $filename 导出的文件名
     */
    public function setExportName($filename){
        if($filename!=''){
            $this->exportName = $filename;
        }
    }

    /**
     * 设置分隔符
     * @param String $separator 分隔符
     */
    public function setSeparator($separator){
        if($separator!=''){
            $this->separator = $separator;
        }
    }

    /**
     * 设置定界符
     * @param String $delimiter 定界符
     */
    public function setDelimiter($delimiter){
        if($delimiter!=''){
            $this->delimiter = $delimiter;
        }
    }

    /** 导出csv */
    public function export(){

        // 获取总记录数
        $this->total = $this->getExportTotal();

        // 没有记录
        if(!$this->total){
            return false;
        }

        // 计算导出总批次
        $pagecount = $this->getPageCount();

        // 获取导出的列名
        $title = $this->getExportTitle();
        if($this->_dataType == 1){
            if(empty($title) && !empty($this->_data)){
                $title = array_keys($this->_data[0]);
            }
        }
        if(empty($title)){
            return false;
        }

        // 设置导出文件header
        $this->setHeader();

        // 循环导出
        for($i=0; $i<$pagecount; $i++){

            $exportData = '';

            if($i==0){ // 第一条记录前先导出列名
                $exportData .= $this->formatCSV($title);
            }

            // 设置偏移值
            $offset = $i*$this->pagesize;

            // 获取每页数据
            $data = $this->getExportData($offset, $this->pagesize);

            // 将每页数据转换为csv格式
            if($data){
                if($this->_dataType == 1){
                    foreach($data as $row){
                        $exportData .= $this->formatCSV(array_values($row));
                    }
                }else{
                    foreach($data as $row){
                        $exportData .= $this->formatCSV($row);
                    }
                }
            }

            // 导出数据
            echo iconv('utf-8','gb2312//ignore',$exportData);
        }
        exit;
    }

    /** 计算总批次 */
    private function getPageCount(){
        $pagecount = (int)(($this->total-1)/$this->pagesize)+1;
        return $pagecount;
    }

    /** 设置导出文件header */
    private function setHeader() {
        //header('content-type:application/x-msexcel');
        header("Content-type:text/csv");

        $ua = $_SERVER['HTTP_USER_AGENT'];

        if(preg_match("/MSIE/", $ua)){
            header('content-disposition:attachment; filename="'.rawurlencode($this->exportName).'"');
        }elseif(preg_match("/Firefox/", $ua)){
            header("content-disposition:attachment; filename*=\"utf8''".$this->exportName.'"');
        }else{
            header('content-disposition:attachment; filename="'.$this->exportName.'"');
        }

        ob_end_flush();
        ob_implicit_flush(true);
    }

    /** 格式化为csv格式数据
     * @param Array $data 要转换为csv格式的数组
     */
    private function formatCSV($data=array()) {
        // 对数组每个元素进行转义
        $data = array_map(array($this,'escape'), $data);
        return $this->delimiter.implode($this->delimiter.$this->separator.$this->delimiter, $data).$this->delimiter."\r\n";
    }

    /** 转义字符串
     * @param String $str
     * @return String
     */
    private function escape($str){
        return str_replace($this->delimiter, $this->delimiter.$this->delimiter, $str);
    }

    /**
     * 设置请求头和写文件列头
     */
    public static function writeHeader($filename, array $headers) {
        //header('content-type:application/x-msexcel');
        header("Content-type:text/csv");
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match("/MSIE/", $ua)) {
            header('content-disposition:attachment; filename="'.rawurlencode($filename).'"');
        } elseif(preg_match("/Firefox/", $ua)) {
            header("content-disposition:attachment; filename*=\"utf8''".$filename.'"');
        } else {
            header('content-disposition:attachment; filename="'.$filename.'"');
        }

        ob_end_flush();
        ob_implicit_flush(true);
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        // 写文件列头
        foreach ($headers as $i=>$v) {
            // csv的Excel支持GBK编码，一定要转码，否则乱码
            $headers[$i] = iconv('utf-8', 'gbk//ignore', $v);
        }
        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $headers);
        fclose($fp);
    }

    /**
     * 设置内容
     */
    public static function writeContent(array $data, $exit) {
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        // 计数器
        $cnt = 0;
        // 每隔$pageSize行，刷新一下输出buffer，不要太大，也不要太小
        $pageSize = 10000;
        $total = count($data);
        foreach ($data as $item) {
            $cnt++;
            if ($cnt == $pageSize) {
                ob_flush();
                flush();
                $cnt = 0;
            }

            foreach ($item as $i => $v) {
                $item[$i] = iconv('utf-8', 'gbk//ignore', $v);
            }
            fputcsv($fp, $item);
        }
        fclose($fp);

        if ($exit) {
            exit;
        }
    }
}
