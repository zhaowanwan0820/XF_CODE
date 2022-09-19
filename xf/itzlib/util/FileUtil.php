<?
/**
 * FileUtil 
 * 
 * 生成文件的函数
 * 
 */
class FileUtil{

    /***************************************************************************
     * $table:表名
     * $cons：条件
     * return：XML格式文件
     **************************************************************************/
    function toXml($result) {
        header("Content-Type: text/xml");
        $xml='<?xml version="1.0"  encoding="utf-8" ?>';
        $xml.="<xml>";
        $xml.="<totalCount>".$totalNum."</totalCount>";
        $xml.="<items>";
        for($i=0;$i<$resultNum;$i++) {
            $xml.="<item>";
            foreach($result[$i] as $key=>$val)
                $xml.="<".$key.">".$val."</".$key.">";
            $xml.="</item>";
        }
       
        $xml.="</items>";
        $xml.="</xml>";
        return $xml;
    }

    /***************************************************************************
     * $table:表名
     * $mapping：数组格式头信息$map=array('No','Name','Email','Age');
     * $fileName：WORD文件名称
     * return：WORD格式文件
     **************************************************************************/
    function toWord($table,$mapping,$fileName) {
        header('Content-type: application/doc');
        header('Content-Disposition: attachment; filename="'.$fileName.'.doc"');
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"
       xmlns:w="urn:schemas-microsoft-com:office:word"
       xmlns="[url=http://www.w3.org/TR/REC-html40]http://www.w3.org/TR/REC-html40[/url]">
                <head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <title>'.$fileName.'</title>
                </head>
                <body>';
        echo'<table border=1><tr>';
        if(is_array($mapping)) {
            foreach($mapping as $key=>$val)
                echo'<td>'.$val.'</td>';
        }
        echo'</tr>';
        $results=$this->find('select * from '.$table);
        foreach($results as $result) {
            echo'<tr>';
            foreach($result as $key=>$val)
                echo'<td>'.$val.'</td>';
            echo'</tr>';
        }
        echo'</table>';
        echo'</body>';
        echo'</html>';
    }
    
	/***************************************************************************
     * $table:表名
     * $mapping：数组格式头信息$map=array('No','Name','Email','Age');
     * $fileName：Excel文件名称
     * $preAllData: 前置需输出的总数据统计
     * return：Excel格式文件
     **************************************************************************/
    static function toExcel($fileName,$results,$mapping=array(),$preAllData=array()) {
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:filename=".$fileName.".xls");
        echo'<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:x="urn:schemas-microsoft-com:office:excel"
        xmlns="[url=http://www.w3.org/TR/REC-html40]http://www.w3.org/TR/REC-html40[/url]">
        <head>
        <meta http-equiv="expires" content="Mon, 06 Jan 1999 00:00:01 GMT">
        <meta http-equiv=Content-Type content="text/html; charset=utf-8">
        <!--[if gte mso 9]><xml>
        <x:ExcelWorkbook>
        <x:ExcelWorksheets>
        <x:ExcelWorksheet>
        <x:Name></x:Name>
        <x:WorksheetOptions>
        <x:DisplayGridlines/>
        </x:WorksheetOptions>
        </x:ExcelWorksheet>
        </x:ExcelWorksheets>
        </x:ExcelWorkbook>
        </xml><![endif]-->
        </head>
        <body link=blue vlink=purple leftmargin=0 topmargin=0>';
        echo'<table width="100%" border="0" cellspacing="0" cellpadding="0">';
        if(!empty($preAllData)) {
            foreach($preAllData as $val) {
                 echo'<tr>';
                 echo'<td>'.$val['name'].'</td>';
                 echo'<td>'.$val['value'].'</td>';
                 echo'</tr>';
            }
        }
        echo'<tr>';
        if(is_array($mapping)) {
            foreach($mapping as $key=>$val){
                echo'<td>'.$val.'</td>';
            }     
        }
        echo'</tr>';
        foreach($results as $result) {
            echo'<tr>';
            foreach($result as $key=>$val){
                echo'<td>'.$val.'</td>';
            }
            echo'</tr>';
        }
        echo'</table>';
        echo'</body>';
        echo'</html>';
    }
    
    function toTxt($table,$mapping,$fileName ,$sql_where){
        header("Content-Type: application/force-download");//关键之一，提示下载（如:header("Content-Type:text/html");可能直接打开?)
        header("Content-Disposition: attachment; filename=".$fileName.".txt");
        
        if(is_array($mapping)) {
            foreach($mapping as $key=>$val)
                echo $val."\t";
        }
        echo "\r\n";
        $results=$this->find('select * from '.$table .$sql_where);
        foreach($results as $result) {
            foreach($result as $key=>$val)
                echo $val."\t";
            echo "\r\n";
        }
    }
    
}
