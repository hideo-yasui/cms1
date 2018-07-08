$(function(){
	setUserInfo();
	pageSetting(base.pageCode, menuLoad);
});
function menuLoad(){
	getPermission(function(data){
		$(".menu").css("display", "none");
//		if(data[base.pageCode] && data[base.pageCode]["menu"] && data[base.pageCode]["menu"]["open"] &&
//			data[base.pageCode]["menu"]["open"]["control"] && data[base.pageCode]["menu"]["open"]["control"]=="enabled"){
			for(var key in data){
				if(key==base.pageCode) continue;
				var control = data[key]["menu"]["open"]["control"];
				if(control=="hidden") continue;
				$(".menu."+key).show();
				if(control=="disabled"){
					$("a", $(".menu."+key)).prop("disabled", true);
				}
			}
//		}
	});
}
