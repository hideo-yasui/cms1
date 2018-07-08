$(function() {
    if ( $('.js_selector_toggle').length ) {
        $('.js_selector_toggle').on("change",function(){
        	var togglename = $(this).find(":selected").attr("data-toggle")
	        var targetid = "#" + togglename;
        	if(togglename != undefined){
			    $(".input_other").attr("disabled",false);				      				      
	            $(".toggle_block").hide()
	            $(targetid).show()
        	}else{
  			    $(".input_other").attr("disabled",true);				      				      
        		$(".toggle_block").hide()
        	}
        }).trigger('change')
    }
    
	//追加input
	if($(".js_addcheck input[type=checkbox]").length){
		var addinputs = $('.js_addcheck input[type=checkbox]:checked');
		$('.js_addcheck input[type=checkbox]').on('change',function(){
			var target = $(this).parent().find(".js_inputadd")
			if($(this).prop("checked") && target.length){
				target.show()
                $(document).find(".input_other").each(function(){
                  if($(this).attr("style")!="" && $(this).attr("style")!="display: block;" && $(this).attr("style")!="display: inline-block;"){
    			      $(this).attr("disabled",true);				      				      
                  }				      				      
                });
			    $(target).attr("disabled",false);				      				      
			}else{
				target.hide()
                $(document).find(".input_other").each(function(){
                  if($(this).attr("style")!="" && $(this).attr("style")!="display: block;" && $(this).attr("style")!="display: inline-block;"){
    			      $(this).attr("disabled",true);				      				      
                  }				      				      
                });
			}
		})
        $(document).find(".input_other").each(function(){
          if($(this).attr("style")=="display:none;"){
    	    $(this).attr("disabled","true");	
          }				      				      
        });
		if(addinputs.length){
			addinputs.each(function(){
				var target = $(this).parent().find(".js_inputadd")
				if(target.length){
                    $(document).find(".input_other").each(function(){
                      if($(this).attr("style")=="display:none;"){
                   	      $(this).attr("disabled",true);	
                      }				      				      
                    });
					target.show()
    			    //$(target).attr("disabled",false);				      				      
				}else{
					target.hide()
                    $(document).find(".input_other").each(function(){
                      if($(this).attr("style")!="" && $(this).attr("style")!="display: block;" && $(this).attr("style")!="display: inline-block;"){
    	    		      $(this).attr("disabled",true);				      				      
                      }				      				      
                    });
				}
			})
		}
	}

	//checkbox parent check
	if($(".js_parentcheck").length){
		var parentwrp = $(".js_parentcheck")
		var parentcheck = parentwrp.data("parent")
		$('.js_parentcheck input[type=checkbox]').on('change',function(e){
			if($("#" + parentcheck).prop('checked') == true && parentcheck == $(this).attr("id")){
				$(this).prop("checked","checked")
				$(this).parents("li").addClass("selected")
				$(parentwrp).find("li:not(.selected) input[type=checkbox]").prop({"checked":"",disabled:"disabled"})
				$(parentwrp).find("li:not(.selected) .js_inputadd").hide()
				$(parentwrp).find("li:not(.selected)").addClass("disabled")
			}else if($("#" + parentcheck).prop('checked') == false && parentcheck == $(this).attr("id")){
				$(parentwrp).find("li input[type=checkbox]").prop({"checked":"",disabled:""})
				$(parentwrp).find("li").removeClass("selected").removeClass("disabled")
			}
		})
		if($("#" + parentcheck).prop('checked') == true){
			$("#" + parentcheck).parents("li").addClass("selected")
			$(parentwrp).find("li:not(.selected) input[type=checkbox]").prop({"checked":"",disabled:"disabled"})
			$(parentwrp).find("li:not(.selected)").addClass("disabled")
		}
	}

	//いいえclose
	if($(".js_opencheck").length){
		$('.js_opencheck input[type=radio]').on('change',function(e){
			var close = $(this),
				closetarget = close.data("open"),
				closechk = close.attr("data-open")
			$(".js_opencontent").hide()
			if(closechk){
			    $(".js_inputaddwrp").find("input").each(function(){
                  $(this).attr("disabled",true);				      				      
                });
			    $(".js_inputaddwrp").find("select").each(function(){
                  $(this).attr("disabled",true);				      				      
                });
			    $(closetarget).find("input").each(function(){
                  $(this).attr("disabled",false);				      				      
                });
			    $(closetarget).find("select").each(function(){
                  $(this).attr("disabled",false);				      				      
                });
                $(document).find(".input_other").each(function(){
                  if($(this).attr("style")=="display:none;"){
               	      $(this).attr("disabled",true);	
                  }				      				      
                });
				$(closetarget).show()
			}else{
			    $(".js_inputaddwrp").find("input").each(function(){
                  $(this).attr("disabled",true);				      				      
                });
			    $(".js_inputaddwrp").find("select").each(function(){
                  $(this).attr("disabled",true);				      				      
                });
				$(closetarget).hide()
			}
		})
		if($('.js_opencheck input[type=radio]:checked').data("open")){
			$( $('.js_opencheck input[type=radio]:checked').data("open")).show()

    	    $(".js_inputaddwrp").find("input").each(function(){
                $(this).attr("disabled",false);	
            });
    	    $(".js_inputaddwrp").find("select").each(function(){
              $(this).attr("disabled",false);	
            });
            $(document).find(".input_other").each(function(){
              if($(this).attr("style")=="display:none;"){
           	      $(this).attr("disabled",true);	
              }				      				      
            });
            $(".js_inputaddwrp").each(function(){
              if($(this).parents(".js_opencontent").attr("style")=="display:none;"){
           	      $(this).find("input").attr("disabled",true);	
              }				      				      
            });
		}else{
    	    $(".js_inputaddwrp").find("input").each(function(){
              $(this).attr("disabled",true);				      				      
            });
    	    $(".js_inputaddwrp").find("select").each(function(){
              $(this).attr("disabled",true);				      				      
            });
		}
	}

	//input追加処理
	if($(".js_inputadd").length){
		$(".js_inputadd").each(function(){
			var wrap = $(this),
				lis = $(this).find(".js_inputaddwrp"),
				btn = $(this).find(".js_inputaddbtn"),
				cnt = 1

			//独自表示件数取得
			if($(this).data("add")){
				cnt = $(this).data("add")
			}
			//非表示項目がないときボタン削除
			if(!lis.children(".js_hidden").length){
				btn.hide()
			}
			//独自表示件数取得
			$(btn).on("click",function(){
				lis.children(":hidden").each(function(i){
					if(i < cnt){
						$(this).show().removeClass("js_hidden")
					}
				})
				if(!lis.children(":hidden:first").length){
					btn.hide()
				}
			})
		})
	}
	
	$("input[inputtype=number]").on("blur", function(){
		//半角変換
		val = util.convHankaku(this.value);
		this.value = val;
	});

});
function frmsubmit(){
  //必須チェック
  var isSuccess = front.validateFormValue("content .inner");
  if(isSuccess){
    document.frm.submit();
  }
}

$(document).ready(function(){
  var disp_order = $("#disp_order").val();
  var max_order = $("#max_order").val();
  $("#progressbar").progressBar({
    percent: Math.floor((disp_order/max_order)*100),
    split: 0,
    width: "100%",
    height: "25px"
  });
});

	

