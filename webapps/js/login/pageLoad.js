$(function(){
	$("input[name=login_id]").focus();
	$("input[accesskey=enter]").unbind("keypress");

	$("input[accesskey=enter]").unbind("keyup");
	$("input[accesskey=enter]").on("keyup", function(e){
		if(e.keyCode==13){
			login();
		}
		if(!util.isEmpty($(this).attr("autologin"))){
			var len = $(this).attr("loginlength");
			if($(this).val().length==len) login();
		}
	});
	$(".form__error").hide();
	var err = service.getQueryParam("err");
	if(!util.isEmpty(err)){
		$("#err"+err).show();
	}
});

function login(){
	var _isSubmit = front.validateFormValue(formPageid);
	util.clearLocalData();
	if(_isSubmit) $("form[name=LoginForm]").submit();
}
