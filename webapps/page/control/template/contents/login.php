<!-- login.php start -->
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
<!-- login.php end -->
