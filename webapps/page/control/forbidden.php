<?php $data=array('title' => "アクセスエラー");?>
<!-- login_box.php start -->
<?php view("/control/template/meta/start.php", $data);?>
<body class="hold-transition lockscreen">
<!-- Automatic element centering -->
<div class="lockscreen-wrapper">
	<div class="lockscreen-logo">
		<b class="text-xl text-danger">403</b>エラー
	</div>
	<!-- User name -->
	<div class="lockscreen-name">
		<p>Access denied</p>
		<a href="/top"><button class="btn btn-primary"><i class="text-muted"></i> TOPに戻る</button></a>
</div>

	<!-- /.lockscreen-item -->
	<div class="help-block text-center mt-2">
		このページへのアクセスはできません
	</div>
	<div class="text-center">

	</div>
	<div class="lockscreen-footer text-center">
	</div>
</div>
<!-- /.center -->

<!-- /.login-box -->
<?php view("/control/template/end.php", $data);?>
<!-- login_box.php end -->
