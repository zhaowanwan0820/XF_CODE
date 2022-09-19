<?php if (!defined('THINK_PATH')) exit();?><div class="main">
<div class="main_title">文件共有<?php echo (count($errorData)); ?>条错误数据</div>
<form action="" method="post">
         <div class="button_row">
            <input type="hidden" name="a" value="download_csv_datas">
            <input type="hidden" name="m" value="CouponBind">
            <input type="hidden" name="error_data" value="<?php echo (arrayToCsvString($errorData)); ?>">
            <input type="submit" class="button" value="下载错误数据"/>
         </div>
      </form>
</div>