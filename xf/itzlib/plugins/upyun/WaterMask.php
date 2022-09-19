<?php 

/**  
 * 加水印类，支持文字、图片水印以及对透明度的设置、水印图片背景透明。  
 */ 
class WaterMask 
{ 
    /**
     * 水印类型
     * @var int $waterType 0为文字水印 ；1为图片水印   
     */ 
    public $waterType = 1; 
     
    /**
     * 水印位置 类型
     * @var int $pos  默认为9(右下角)
     */ 
    public $pos = 9;  
    
    /**
     * 水印透明度 
     * @var int  $transparent  水印透明度(值越小越透明)
     */ 
    public $transparent = 20;  
    
    /**
     * 水印图片   
     * @var string $waterImg
     */ 
    public $waterImg ;  
    
    /**
     * 需要添加水印的图片   
     * @var string $srcImg
     */ 
    public $srcImg = '';  
    
    /**
     * 图片句柄   
     * @var string $im
     */ 
    public $im = '';  
    
    /**
     * 水印图片句柄   
     * @var string $water_im  
     */ 
    public $water_im = '';  
    
    /**
     * 图片信息   
     * @var array  $srcImg_info
     * 本函数可用来取得 GIF、JPEG 及 PNG 三种 WWW 上图片的高与宽，不需要安装 GD library 就可以使用本函数。返回的数组有四个元素。
     * 第一个元素 (索引值 0) 是图片的宽度，单位是像素 (pixel)。
     * 第二个元素 (索引值 1) 是图片的高度。
     * 第三个元素 (索引值 2) 是图片的文件格式，其值 1 为 GIF 格式、 2 为 JPEG/JPG 格式、3 为 PNG 格式。
     * 第四个元素 (索引值 3) 为图片的高与宽字符串
     */ 
    public $srcImg_info = '';  
    
    /**
     * 水印图片信息   
     * @var array $waterImg_info  
     */ 
    public $waterImg_info = '';  
    
    
    
    /**
     * 如果是文字水印，则需要加的水印文字
     * @var string $waterStr  默认值  
     */ 
    public $waterStr = 'itouzi'; 
        
    /**
     * 文字字体大小   
     * @var int $fontSize  字体大小
     */ 
    public $fontSize = 14;  
    
    /**
     * 水印文字颜色（RGB）   
     * @var array $fontColor  水印文字颜色（RGB）   
     */ 
    public $fontColor = array ( 255, 255, 255 );  
    
    /**
     * 字体文件   
     * @var unknown_type
     */ 
    public $fontFile = 'Duality.ttf';  
    /**
     * 水印文字宽度   
     * @var int $str_w  
     */ 
    public $str_w = '';  

    /**
     * 水印文字高度   
     * @var int $str_h  
     */ 
    public $str_h = '';  

    /**
     * 水印X坐标   
     * @var int $x
     */ 
    public $x = '';  

    /**
     * 水印y坐标   
     * @var int   $y
     */ 
    public $y = ''; 
    
    /*
     * 多个水印文字数组
     * */
    public $more_str_array = array( );

    /**
     * 构造函数，通过传入需要加水印的源图片初始化源图片
     * @param string $img  需要加水印的源图片
     * @param string water_img 水印图片
     */ 
    public function __construct ($img,$water_img="") 
    {
        if($water_img){
            $this->waterImg = $water_img;
        }else{
            $this->waterImg = dirname(__FILE__)."/water.gif";
        }
        if(file_exists($img)){//源文件存在 
            $this -> srcImg = $img ; 
        }else{
            Yii::log('源文件'.$img.'不存在，请检查看文件路径是否正确',"error");
        } 
    } 

    /**
     * 获取需要添加水印的图片的信息，并载入图片
     */ 
    public  function imginfo () 
    {    
        $this -> srcImg_info = getimagesize($this -> srcImg); 
        switch ($this -> srcImg_info[2]) { 
            case 3 ://png 
                $this -> im = imagecreatefrompng($this -> srcImg); 
                break ; 
            case 2 :  //  jpeg/jpg 
                $this -> im = imagecreatefromjpeg($this -> srcImg); 
                break ; 
            case 1 :  //gif 
                $this -> im = imagecreatefromgif($this -> srcImg); 
                break ; 
            default : 
                Yii::log('源文件'.$img.'不存在，请检查看文件路径是否正确',"error");
                return;
                beeak;
        } 
    } 

    /**
     * 获取水印图片的信息，并载入图片
     */ 
    private function waterimginfo () 
    {  
        $this -> waterImg_info = getimagesize($this -> waterImg); 
        switch ($this -> waterImg_info[2]) { 
            case 3 : 
                $this -> water_im = imagecreatefrompng($this -> waterImg); 
                break; 
            case 2 : 
                $this -> water_im = imagecreatefromjpeg($this -> waterImg); 
                break; 
            case 1 : 
                $this -> water_im = imagecreatefromgif($this -> waterImg); 
                break; 
            default :
                Yii::log('源图片文件'. $this -> srcImg .'格式不正确，目前本函数只支持PNG、JPEG、GIF图片水印功能',"error");
                return;
                beeak;
        } 
    } 

    /**
     * 水印位置算法   
     */ 
    private function waterpos () 
    {  
        switch ($this -> pos) { 
            case 0 : //随机位置    
                $this -> x = rand(0, $this -> srcImg_info[0] - $this -> waterImg_info[0]); 
                $this -> y = rand(0, $this -> srcImg_info[1] - $this -> waterImg_info[1]); 
                break 1; 
            case 1 : //上左    
                $this -> x = 20; 
                $this -> y = 20; 
                break 1; 
            case 2 : //上中    
                $this -> x = ($this -> srcImg_info[0] - $this -> waterImg_info[0]) / 2; 
                $this -> y = 20; 
                break 1; 
            case 3 : //上右    
                $this -> x = $this -> srcImg_info[0] - $this -> waterImg_info[0]; 
                $this -> y = 20; 
                break 1; 
            case 4 : //中左    
                $this -> x = 20; 
                $this -> y = ($this -> srcImg_info[1] - $this -> waterImg_info[1]) / 2; 
                break 1; 
            case 5 : //中中    
                $this -> x = ($this -> srcImg_info[0] - $this -> waterImg_info[0]) / 2; 
                $this -> y = ($this -> srcImg_info[1] - $this -> waterImg_info[1]) / 2; 
                break 1; 
            case 6 : //中右    
                $this -> x = $this -> srcImg_info[0] - $this -> waterImg_info[0] - 20; 
                $this -> y = ($this -> srcImg_info[1] - $this -> waterImg_info[1]) / 2; 
                break 1; 
            case 7 : //下左    
                $this -> x = 20; 
                $this -> y = $this -> srcImg_info[1] - $this -> waterImg_info[1] - 20; 
                break 1; 
            case 8 : //下中    www.2cto.com
                $this -> x = ($this -> srcImg_info[0] - $this -> waterImg_info[0]) / 2; 
                $this -> y = $this -> srcImg_info[1] - $this -> waterImg_info[1] - 20; 
                break 1; 
            case 9 : //下右    
                $this -> x = $this -> srcImg_info[0] - $this -> waterImg_info[0] - 20; 
                $this -> y = $this -> srcImg_info[1] - $this -> waterImg_info[1] - 20; 
                break 1; 
            default : //下右    
                //$this -> x = $this -> srcImg_info[0] - $this -> waterImg_info[0] - 20; 
                //$this -> y = $this -> srcImg_info[1] - $this -> waterImg_info[1] - 20; 
                break 1; 
        } 
    } 

    /** 
     * 加图片水印  原有的方法
     */  
    private function waterimg_s ()  
    {
        if ($this -> srcImg_info[0] <= $this -> waterImg_info[0] || $this -> srcImg_info[1] <= $this -> waterImg_info[1]) {  
            Yii::log('图片尺寸太小，无法加水印，请上传一张大图片',"error"); 
            exit();  
        } 
        //计算水印位置  
        $this->waterpos(); 
        $cut = imagecreatetruecolor($this -> waterImg_info[0], $this -> waterImg_info[1]);  
        imagecopy($cut, $this -> im, 0, 0, $this -> x, $this -> y, $this -> waterImg_info[0],   
        $this -> waterImg_info[1]);  
        $pct = $this -> transparent;  
        imagecopy($cut, $this -> water_im, 0, 0, 0, 0, $this -> waterImg_info[0],   
        $this -> waterImg_info[1]);  
        //将图片与水印图片合成  
        imagecopymerge($this -> im, $cut, $this -> x, $this -> y, 0, 0, $this -> waterImg_info[0], $this -> waterImg_info[1], $pct);  
    } 

    /**
     * 加文字水印
     */ 
    private function waterstr_s () 
    {
        $this->fontFile = dirname(__FILE__)."/msyhbd.ttc"; //宋体
        $rect = imagettfbbox($this -> fontSize, 0, $this -> fontFile, $this -> waterStr); 
        $w = abs($rect[2] - $rect[6]); 
        $h = abs($rect[3] - $rect[7]); 
        $fontHeight = $this -> fontSize; 
        $this -> water_im = imagecreatetruecolor($w, $h); 
        imagealphablending($this -> water_im, false); 
        imagesavealpha($this -> water_im, true); 
        $white_alpha = imagecolorallocatealpha($this -> water_im, 255, 255, 255, 127); 
        imagefill($this -> water_im, 0, 0, $white_alpha); 
        $color = imagecolorallocate($this -> water_im, $this -> fontColor[0], $this -> fontColor[1],  
        $this -> fontColor[2]); 
        imagettftext($this -> water_im, $this -> fontSize, 0, 0, $this -> fontSize, $color,  
        $this -> fontFile, $this -> waterStr); 
        $this -> waterImg_info = array ( 
            0 => $w, 1 => $h 
        ); 
        $this->waterimg_s(); 
    } 
    
    /**
     * 加图片水印 全图加水印
     */ 
    private function waterimg () 
    { 
        if ($this -> srcImg_info[0] <= $this -> waterImg_info[0] || $this -> srcImg_info[1] <= $this -> waterImg_info[1]) { 
            Yii::log('图片尺寸太小，无法加水印，请上传一张大图片',"error"); 
            return ;
        } 
        $w_num = ($this -> srcImg_info[0]/$this -> waterImg_info[0]);
        $h_num = ($this -> srcImg_info[1]/$this -> waterImg_info[1]);
        for ($i = 0; $i < $w_num; $i++ ){
            for ($j = 0; $j < $h_num; $j++ ){
                $this->x = $this -> waterImg_info[0]*$i;
                $this->y = $this -> waterImg_info[1]*$j;
                //计算水印位置 
                //$this->waterpos(); 
                //返回一个图像标识符，代表了一幅大小为 x_size 和 y_size 的黑色图像
                $cut = imagecreatetruecolor($this -> waterImg_info[0], $this -> waterImg_info[1]); 
                
                //bool imagecopy( dst_im, src_im, int dst_x, int dst_y, int src_x, int src_y, int src_w, int src_h )
                //将 src_im 图像中坐标从 src_x，src_y 开始，宽度为 src_w，高度为 src_h 的一部分拷贝到 dst_im 图像中坐标为 dst_x 和 dst_y 的位置上。
                imagecopy($cut, $this -> im, 0, 0, $this -> x, $this -> y, $this -> waterImg_info[0],$this -> waterImg_info[1]); 
                $pct = $this -> transparent; 
                imagecopy($cut, $this -> water_im, 0, 0, 0, 0, $this -> waterImg_info[0], $this -> waterImg_info[1]); 
                //将图片与水印图片合成 
                imagecopymerge($this -> im, $cut, $this -> x, $this -> y, 0, 0, $this -> waterImg_info[0], $this -> waterImg_info[1], $pct);
            }
        }  
    } 

    /**
     * 加多个水印文字
     */ 
    public function waterStrMore () 
    {
        $this->imginfo(); 
        $this->fontFile = dirname(__FILE__)."/msyhbd.ttc"; //微软雅黑
        
        foreach($this->more_str_array as $key=>$row){
            $rect = imagettfbbox($row["fontSize"], 0, $this->fontFile, $row["waterStr"]); 
            
            $w = abs($rect[2] - $rect[6]); 
            $h = abs($rect[3] - $rect[7]); 
            $fontHeight = $row["fontSize"]; 
            $this->water_im = imagecreatetruecolor($w, $h); 
            imagealphablending($this->water_im, false); 
            imagesavealpha($this->water_im, true); 
            $white_alpha = imagecolorallocatealpha($this->water_im, 255, 255, 255, 127); 
            imagefill($this->water_im, 0, 0, $white_alpha); 
            $color = imagecolorallocate($this->water_im,$row["fontColor"][0],$row["fontColor"][1],$row["fontColor"][2]); 
            imagettftext($this->water_im,$row["fontSize"],0,0,$row["fontSize"],$color,$this->fontFile,$row["waterStr"]); 
            $this->waterImg_info = array (0 => $w, 1 => $h  ); 
            
            if ($this->srcImg_info[0] <= $this->waterImg_info[0] || $this -> srcImg_info[1] <= $this -> waterImg_info[1]) {  
                Yii::log('图片尺寸太小，无法加水印，请上传一张大图片',"error");   
                exit();  
            } 
            //计算水印位置  
            $this->x = $row["x"];
            $this->y = $row["y"];
            $cut = imagecreatetruecolor($this -> waterImg_info[0], $this -> waterImg_info[1]);  
            imagecopy($cut, $this -> im, 0, 0, $this -> x, $this -> y, $this -> waterImg_info[0],   
            $this -> waterImg_info[1]);  
            $pct = $this -> transparent;  
            imagecopy($cut, $this -> water_im, 0, 0, 0, 0, $this -> waterImg_info[0],   
            $this -> waterImg_info[1]);  
            //将图片与水印图片合成  
            imagecopymerge($this -> im, $cut, $this -> x, $this -> y, 0, 0, $this -> waterImg_info[0], $this -> waterImg_info[1], $pct);  
        }
    } 

    /**
     * 水印图片输出
     */ 
    public function output () 
    { 
        $this->imginfo(); 
        if ($this -> waterType == 0) { 
            $this->waterstr(); 
        } else { 
            $this->waterimginfo(); 
            $this->waterimg(); 
        } 
        switch ($this -> srcImg_info[2]) { 
            case 3 : 
                imagepng($this -> im, $this -> srcImg);  //im 为使用 ImageCreate() 所建立的图片代码
                break ; 
            case 2 : 
                imagejpeg($this -> im, $this -> srcImg); 
                break; 
            case 1 : 
                imagegif($this -> im, $this -> srcImg); 
                break; 
            default : 
                Yii::log('添加水印失败！',"error");
                break; 
        } 
        //图片合成后的后续销毁处理 
        imagedestroy($this -> im); 
        imagedestroy($this -> water_im); 
    } 

    /**
     * 输出到浏览器 
     */ 
    public function outputToWeb () 
    { 
        switch ($this -> srcImg_info[2]) { 
            case 3 : 
                header("Content-type: image/png");
                imagepng($this -> im);  //im 为使用 ImageCreate() 所建立的图片代码
                break ; 
            case 2 : 
                header("Content-type: image/jpeg");
                imagejpeg($this -> im); 
                break; 
            case 1 : 
                header("Content-type: image/gif");
                imagegif($this -> im); 
                break; 
            default : 
                Yii::log('添加水印失败！',"error");
                break; 
        } 
        //图片合成后的后续销毁处理 
        imagedestroy($this -> im); 
        imagedestroy($this -> water_im); 
    } 
    
    /**
     * 输出到文件，用于多个水印文字的输出
     */ 
    public function output_waterstr () 
    { 
        switch ($this -> srcImg_info[2]) { 
            case 3 : 
                imagepng($this -> im, $this -> srcImg);  //im 为使用 ImageCreate() 所建立的图片代码
                break ; 
            case 2 : 
                imagejpeg($this -> im, $this -> srcImg); 
                break; 
            case 1 : 
                imagegif($this -> im, $this -> srcImg); 
                break; 
            default : 
                Yii::log('添加水印失败！',"error");
                break; 
        } 
        //图片合成后的后续销毁处理 
        imagedestroy($this -> im); 
        imagedestroy($this -> water_im); 
    } 
} 
