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
	<h3 class="card-title">パスワードをお忘れの方</h3>
</div>
<div class="card-body">
	<form action="" method="post">
		<div class="row mb-3">
			<div class="input-group">
				<div class="input-group-prepend">
					<span class="input-group-text"><i class="fa fa-envelope"></i></span>
				</div>
				<input type="email" class="form-control" placeholder="登録メールアドレス">
			</div>
		</div>
		<div class="row mb-3">
			<p class="small text-muted">
				ご登録されたメールアドレスにパスワード再設定のご案内が送信されます。
			</p>
		</div>

		<div class="row">
			<!-- /.col -->
			<button type="submit" class="btn btn-primary btn-block">送信する</button>
			<!-- /.col -->
		</div>
	</form>
</div>
<!-- /.login-card-body -->
<!-- login.php end -->
