<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>ログイン | システム管理</title>
<!-- Tell the browser to be responsive to screen width -->
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">

<!-- Ionicons -->
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
<!-- Theme style -->
<link rel="stylesheet" href="/dist/css/adminlte.css">
<!-- Google Font: Source Sans Pro -->
<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
<!-- jQuery -->
<script src="/js/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="/js/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Slimscroll -->
<script src="/js/plugins/slimScroll/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="/js/plugins/fastclick/fastclick.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.min.js"></script>
<script src="/js/lib/utf.js"></script>
<script src="/js/lib/base64.js"></script>
<script src="/js/lib/inflate.js"></script>
<script src="/js/lib/deflate.js"></script>
<script src="/js/common/util.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/common/treeView.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/common/listTable.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/base/fileUI.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/base/dom.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/base/service.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/base/front.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script src="/js/base.js<?php echo "?v=".$GLOBALS['gEnvList']['version']; ?>"></script>
<script>
$(function(){
	$("form button").click(function(e){
		e.preventDefault();
		util.clearLocalData();
		var isSuccess = front.validateFormValue("login_form");
		if(isSuccess){
			var loginForm = front.getFormValue("login_form");
			service.postAjax("auth", loginForm,
				function(result, st, xhr) {
					if(result["status"]==="success"){
						var token = result["token"];
						util.setLocalData("auth", result);
						location.href = "top";
					}
					else {
						front.showValidateError($(e.target), result["message"], result["description"]);
					}
				},
				function(xhr, st, err) {
					console.log(xhr);
				}
			);
		}
	});
	$("input[name=login_id]").focus();
});

</script>

</head>
<body class="hold-transition login-page">
<div class="login-box">
	<div class="login-logo">
	<a href="./"><b>#service.name#</b></a>
	</div>
	<!-- /.login-logo -->
	<div class="card">
		<div class="card-header">
			<h3 class="card-title">ログイン</h3>
		</div>
		<div class="card-body login-card-body">
			<form id="login_form" action="" method="post" novalidate>
				<div class="row mb-3">
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text"><i class="fa fa-envelope"></i></span>
						</div>
						<input type="email" name="login_id" class="form-control" placeholder="メールアドレス" minlength="3" maxlength="32" inputtype="email" required="true">
					</div>
				</div>
				<div class="row mb-3">
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text"><i class="fa fa-key"></i></span>
						</div>
						<input type="password" name="password" class="form-control" placeholder="パスワード" accesskey="enter" minlength="8" maxlength="32" inputtype="hankaku" required="true">
					</div>
				</div>
				<div class="row mb-3">
					<!-- /.col -->
					<button type="submit" class="btn btn-primary btn-block">ログイン</button>
					<!-- /.col -->
				</div>
			</form>
			<h6 class="my-2">
				<a href="#" class="small">パスワード忘れた方</a>
			</h6>
			<hr class="my-3">
			<p class="my-2">
				<button type="button" class="btn btn-outline-success btn-block">新規登録する</button>
			</p>
		</div>
		<!-- /.login-card-body -->
	</div>
</div>
<!-- /.login-box -->

</body>
</html>
