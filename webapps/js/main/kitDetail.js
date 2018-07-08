$(function(){
	setUserInfo();
	$("input[for],textarea[for]").each( function(e){
		var _name = $(this).attr("name");
		_editForm = $(this);
		_editLabel = $("span[id="+_name+"]");
		setFormAndLabel(_editForm, _editLabel, false, false);
	});
	//toggle設定
	$("*[accesskey=toggle]").on("click", function(e){
		var _id = $(this).attr("id");
		var _accesskey = $(this).attr("accesskey");
		var _isSave = false;
		var _editform=null, _editLabel = null;
		//for属性にて、制御対象を決定する
		$("*[for='"+_id+"']").each(function(){
			var _tag = ($(this).prop("tagName")).toUpperCase();
			var _name = $(this).attr("name");
			var _accesskey = $(this).attr("accesskey");
			if(_tag == "INPUT" || _tag == "TEXTAREA") {
				_editForm = $(this);
				_editLabel = $("span[id="+_name+"]");
			}

			//表示・非表示切り替え
			if($(this).is(':visible')){
 				$(this).hide();
				//保存処理押下時＝保存ボタンを非表示にするタイミング
				if(_accesskey == "save") _isSave = true;
			}
			else {
				$(this).show();
				//フォームを表示した場合、フォームが空の場合、ラベルの値を設定
				if(_tag == "INPUT" || _tag == "TEXTAREA") setFormAndLabel(_editForm, _editLabel, false, false);
			}
		});
		if(_isSave){
			kit_header_edit_save(function(){
				//保存完了時にラベルの値をフォームの値に設定する
				setFormAndLabel(_editForm, _editLabel, true, true);
			});
		}
		if($(this).parent().attr("class").indexOf("is-edit") < 0)  $(this).parent().addClass("is-edit");
		else $(this).parent().removeClass("is-edit");
	});

  // 検査キット詳細のアンケート表示をトグルで切り替える
  $('#toggle_survey_list').on("click",function(){
    var t = $(this)
    var wrp = t.attr("href");
    if($(wrp).is(":hidden")){
      $(wrp).velocity("slideDown",{
            duration: 300,
            easing: "ease-out"
      })
      t.addClass("is-selected")
    }else{
      $(wrp).velocity("slideUp",{
            duration: 300,
            easing: "ease-in"
      })
      t.removeClass("is-selected")
    }
    return false;
  })
  if ($(".js_target_toggle").length) {
	  $(".js_target_toggle").on("click", function() {
		  var t = $(this)
		  var wrp = t.attr("href");
		  if ($(wrp).is(":hidden")) {
			  $(wrp).addClass("is--active")
			  t.addClass("is--active")
		  } else {
			  t.removeClass("is--active")
			  $(wrp).removeClass("is--active")
		  }
		  return false;
	  })
  }
  if ($(".js_classtoggle").length) {
	  $(".js_classtoggle").on("click", function() {
		  $(this).toggleClass("is--active")
	  })
  }
  //tooltip
  if ($('.js_tooltip').length) {
	  $('.js_tooltip').on("click", function() {
		  $(".js_tooltip").not(this).removeClass("is--active");
		  $(this).toggleClass("is--active");
	  })
  }
  //モーダル
  $(".js_result_modal").on("click",function(e) {
	  e.preventDefault();
	  var url = $(this).attr("href")
	  modalOpen(url);
  })

  $(".block--modal__btn , .block--modal__bg").on("click", function(e) {
	  e.preventDefault();
	  var target = $(".block--modal");
	  modalClose(target)
  })

  function modalOpen(target, callback) {
	  $(target).addClass("is--active");
	  $("html,body").addClass("is--active");
  }

  function modalClose(target, callback) {
	  $(target).removeClass("is--active");
	  $("html,body").removeClass("is--active");
  }
});
//検査キットタイトル・メモ保存処理
function kit_header_edit_save(_callback){
	var page_id = "kit_header_form";
	var validate = front.validateFormValue(page_id);
	if(validate){
		var _req = front.getFormValue(page_id);
		service.postAjax("/save/upd/"+page_id, _req,
			function(data, st, xhr) {
				if(data["status"]=="success" && data["data"]["result"].length>0 &&
					data["data"]["result"][0]["count"]>0){
					_callback();
				}
			},
			function(xhr, st, err) {
				service.alert("ERROR","",  function(){});
			}
		);
	}
}
//フォームとラベルの値を一致させる処理
function setFormAndLabel(form, label, setLabel, setEnforce){
	var _labelVal = label.text();
	var _formVal = form.val();

	if(setLabel && !setEnforce){
		if(util.isEmpty(_labelVal) && !util.isEmpty(_formVal) ){
			//空チェックし、ラべルにフォームを設定
			label.text(_formVal);
		}
	}
	else if(setLabel && setEnforce){
		//空チェックせず、ラべルにフォームを設定
		label.text(_formVal);
	}
	else if(!setLabel && !setEnforce){
		if(util.isEmpty(_formVal) && !util.isEmpty(_labelVal) ){
			//空チェックし、フォームにラベルを設定
			form.val(_labelVal);
		}
	}
	else {
		//空チェックせず、フォームにラベルを設定
		form.val(_labelVal);
	}
}
