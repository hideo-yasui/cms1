<html>
<head>
	<title>ログイン | システム管理</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="target-densitydpi=device-dpi, width=768, maximum-scale=1.0, user-scalable=yes">
	<meta charset="utf-8">
	<!-- Font Awesome -->
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">

	<!-- Ionicons -->
	<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
	<!-- Theme style -->
	<link rel="stylesheet" href="../../dist/css/adminlte.css">
	<!-- Google Font: Source Sans Pro -->
	<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
	<!-- jQuery -->
	<script src="../../plugins/jquery/jquery.min.js"></script>
	<!-- Bootstrap 4 -->
	<script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
	<!-- Slimscroll -->
	<script src="../../plugins/slimScroll/jquery.slimscroll.min.js"></script>
	<!-- FastClick -->
	<script src="../../plugins/fastclick/fastclick.js"></script>
	<!-- AdminLTE App -->
	<script src="../../dist/js/adminlte.min.js"></script>

<script>
/*
$(function(){
	$("input[name=login_id]").focus();
	$("form").submit(function(e){
		util.clearLocalData();
	});
});
*/
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
				<form action="" method="post">
					<div class="row mb-3">
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fa fa-envelope"></i></span>
							</div>
							<input type="email" class="form-control" placeholder="メールアドレス">
						</div>
					</div>
					<div class="row">
						<p class="small text-danger">
							メールアドレスが入力されていません
						</p>
					</div>
					<div class="row mb-3">
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fa fa-key"></i></span>
							</div>
							<input type="password" class="form-control" placeholder="パスワード">
						</div>
					</div>
					<div class="row">
						<p class="small text-danger">
							パスワードが入力されていません
						</p>
					</div>
					<div class="row mb-3">
						<!-- /.col -->
						<button type="submit" class="btn btn-primary btn-block">ログイン</button>
						<!-- /.col -->
					</div>
					<div class="row">
						<p class="small text-danger">
							メールアドレスまたはパスワードが間違っています
						</p>
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
