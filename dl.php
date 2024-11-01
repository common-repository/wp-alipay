<?php 
require_once("alipay_config.php");
$post_id   = $_GET['id'];
$trade_no  = $_GET['no'];

if(!empty($post_id)&&!empty($trade_no)){
	$time      = mysql_query("SELECT alipay_time FROM ".$wpdb->alipay." WHERE alipay_num = '".$trade_no."'");
	$row       = mysql_fetch_row($time);
	$old_time  = $row[0];
	$new       = mktime();
	$old       = strtotime($old_time);
	if($new - $old > 900) mysql_query("UPDATE ".$wpdb->alipay." SET alipay_dl = 'false' WHERE alipay_num = '".$trade_no."'");
	$postid    = mysql_query("SELECT alipay_post FROM ".$wpdb->alipay." WHERE alipay_num = '".$trade_no."'");
	$row1      = mysql_fetch_row($postid);
	$id        = $row1[0];
	$dl        = mysql_query("SELECT alipay_dl FROM ".$wpdb->alipay." WHERE alipay_num = '".$trade_no."'");
	$row2      = mysql_fetch_row($dl);
	$allow_dl  = $row2[0];
	
	if($post_id == $id && $allow_dl=='true'){
		$fileurl   = get_post_meta($id, 'ali_dl', true);
	}else{
        $false = false;
    }
}else{
    $err =  '非法请求！';
}
?>
<!DOCTYPE html>
<html>
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>下载页面</title>
    <?php if($fileurl){echo '<meta http-equiv="Refresh" content="0; url='.$fileurl.'"> '; }?>
</head>
    <body>
    <?php if(isset($false)){
        echo '下载验证失败，请联系站长！';
    }elseif(isset($err)){
        echo $err;
    }
    ?>
    </body>
</html>