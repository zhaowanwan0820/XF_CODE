<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo conf("APP_NAME");?><?php echo l("ADMIN_PLATFORM");?></title>
<script type="text/javascript" src="__TMPL__Common/js/jquery.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/style.css" />
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/footer.css" />
<script type="text/javascript">
    var VAR_MODULE = "<?php echo conf("VAR_MODULE");?>";
    var VAR_ACTION = "<?php echo conf("VAR_ACTION");?>";
    var ROOT = '__APP__';
</script>
</head>

<body>
    <div class="footer">
        <?php if(!$is_cn): ?><?php echo conf("APP_NAME");?>
        <?php else: ?>
            网信普惠<?php endif; ?>
        <?php echo l("ADMIN_PLATFORM");?> <?php echo L("APP_VERSION");?>:<?php echo conf("DB_VERSION");?><?php if(app_conf("APP_SUB_VER")){ ?>.<?php echo app_conf("APP_SUB_VER");?><?php } ?>
    </div>
    <div class="ct_footer_tip" style="display:none;">
        <div class="box"></div>
        <a href="#" class="close">×</a>
    </div>
</body>
</html>