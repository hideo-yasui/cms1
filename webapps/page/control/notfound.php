<?php $data=array('title' => "ページがみつかりません");?>
<!-- login_box.php start -->
<?php view("/control/template/meta/start.php", $data);?>
<body class="hold-transition lockscreen">
<!-- Automatic element centering -->
<div class="lockscreen-wrapper">
	<div class="lockscreen-logo">
		<b class="text-xl text-warning">404</b>エラー
	</div>
	<!-- User name -->
	<div class="lockscreen-name">
		<p>Not Found</p>
		<a ><button class="btn btn-primary"><i class="text-muted"></i> TOPに戻る</button></a>
</div>

	<!-- /.lockscreen-item -->
	<div class="help-block text-center mt-2">
		お探しのページは見つかりませんでした
	</div>
	<div class="text-center">
		あと<span id="timer">10</span>秒後にTOPページに戻ります
	</div>
	<div class="lockscreen-footer text-center">
	</div>
</div>
<!-- /.center -->
<script>
var n=11;
var t=null;
function window_return(){
	n--;
	document.getElementById("timer").innerHTML=n+"";
	clearTimeout(t);
	if(n>0){
		t = setTimeout("window_return();", 1000);
	}
	else {
		location.href="/";
	}
}
window.onload = window_return;
</script>

<!-- /.login-box -->
<?php view("/control/template/end.php", $data);?>
<!-- login_box.php end -->
