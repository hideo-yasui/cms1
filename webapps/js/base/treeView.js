
;(function($, undefined){

	/*
		* Constructor
		*/
	function TreeView(element, options) {
		var treeview = this;
		this.element = element;
		this.options = options;
		this.id = element.attr('id') || util.nextUUID('treeView');
		this.clickEnabled = true;
		this.contextEnabled = true;
		this.dataList = this.options.dataList || [];
		this.openParam = {};
		if(this.dataList!=null){
			this.render(this.dataList);
		}
		else {
			this._init();
		}
	}
	TreeView.prototype.render = function(dt){
		this.nodeData = [];
		var subClass = "";
		var _openNode = null;
		for(var i=0,n=dt.length;i<n;i++){
			var node = dt[i]["NODE"];
			var pnode = dt[i]["PNODE"];
			var name = dt[i]["NAME"];
			var lv = dt[i]["LV"];
			lv = lv|0;
			data = dt[i];
			var isOpen = false;

			data["child"] = [];
			data["childCount"] = 0;
			data["LV"] = lv;
			data["set"] = false;
			data["isopen"] = isOpen;
			data["key"] = i;
			if(data["OPTION_STRING"] && data["OPTION_STRING"]!="" && data["OPTION_STRING"].indexOf(":")>=0){
				var p = $("<p></p>");
				p.html(data["OPTION_STRING"]);
				data["OPTION_STRING"] = JSON.parse("{"+p.html()+"}");
			}

			this.nodeData[node] = data;
			if(typeof this.nodeData[pnode]=="object"){
				this.nodeData[pnode]["child"].push(node);
				this.nodeData[pnode]["childCount"]++;
			}
			if(isOpen){
				_openNode = node;
			}
		}
		var str = this._nodeHtml();
		var selecter = "#"+this.id;
		$(selecter).html(str);
		this._init();
	};


	TreeView.prototype._nodeHtml = function() {
		var str = "";
		var c;
		for(c in this.nodeData){
			if(!this.nodeData[c]["PNODE"] || this.nodeData[c]["PNODE"]==null || this.nodeData[c]["PNODE"]==""  || this.nodeData[c]["PNODE"]==""){
				str+=this._nodeHtmlSet(this.nodeData[c]["key"], this.nodeData[c]);
			}
		}
		for(c in this.nodeData){
			if(!this.nodeData[c]["PNODE"] || this.nodeData[c]["PNODE"]==null || this.nodeData[c]["PNODE"]==""  || this.nodeData[c]["PNODE"]=="") continue
			str+=this._nodeHtmlSet(this.nodeData[c]["key"],  this.nodeData[c]);
		}
		str = '<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">'+str+'</ul>';
		return str;
	};
	TreeView.prototype.getNodeData = function(node) {
		var _result = this.nodeData[node];
		return _result;
	};

	TreeView.prototype._nodeHtmlSet = function(key, nodeData) {
		if(nodeData["set"]) return "";
		if(key==null || key==undefined) return ""
		if(typeof key != "number") return "";
		var nodeId = "__node"+key;
		var name = nodeData["NAME"];
		var childCount= nodeData["childCount"];
		var pnode = nodeData["PNODE"];
		var lv = nodeData["LV"];
		var style = nodeData["STYLE"];
		var node = "";
		if(!style) style = "circle";
		var sub_class = "nav-item";
		if(childCount>0) {
			sub_class +=' has-treeview';
			name += '<i class="right fa fa-angle-left"></i>';
		}
		var node = '<li class="'+sub_class+'"><a id="#1#" class="nav-link"><i class="nav-icon fa fa-'+style+'"></i><p>#2#</p></a>';
		var nodewk = node.replace("#1#", nodeId);
		var ret = "";
		ret = nodewk.replace("#2#", name);
		nodeData["set"] = true;
		if(childCount>0){
			var childStr = '<ul class="nav nav-treeview"';
			//if(style=="closed") childStr += ' style="display:none;"';
			childStr += ">";
			for(var i=0;i<childCount;i++){
				var childNode = nodeData["child"][i];
				var childNodeData = this.nodeData[childNode];
				childStr += this._nodeHtmlSet(childNodeData["key"], childNodeData);
			}
			childStr += '</ul>';
			ret += childStr;

		}
		ret += '</li>\n';
		return ret;
	};

	TreeView.prototype._init = function() {
		var treeview = this;
		var _id = this.id;
		$('#'+_id+' .nav-link > .nav-icon').unbind('click')
		$('#'+_id+' .nav-link > .nav-icon').on('click', function(event){
			var ul = $(this).parent().next('ul');
			var node_id = $(this).parent().attr("id");
			$('#'+node_id+' .nav-link').removeClass('active');
			var style = $(this).attr("class");
			$(this).parent().addClass('active');
			if (ul.length > 0) {
				if($(this).parent().parent().hasClass("menu-open")){
					$(this).parent().parent().removeClass("menu-open")
				}
				else {
					$(this).parent().parent().addClass("menu-open")
				}
				/*
				if (ul.css('display') == 'block') {
					ul.slideUp('fast');
				}
				else {
					ul.slideDown('fast');
				}
				*/
			}
			switch("xx"+style){
				case "opened":
					$(this).parent().removeClass(style);
					style = "closed";
					$(this).parent().addClass(style);
					break;
				case "closed":
					$(this).parent().removeClass(style);
					style = "opened";
					$(this).parent().addClass(style);
					break;
				case "checked":
					$(this).parent().removeClass(style);
					style = "check";
					$(this).parent().addClass(style);
					break;
				case "check":
					$(this).parent().removeClass(style);
					style = "checked";
					$(this).parent().addClass(style);
					break;
			}
			var key = node_id.replace("__node", "");
			treeview.dataList[key]["STYLE"] = style;
		});


		$('#'+_id+' .nav-link > p').unbind('click')
		$('#'+_id+' .nav-link > p').on('click', function(event){
			//event.preventDefault();
			treeview._nodeClick($(this).parent(), 0);
		});
		if (this.options.defaultNode) {
			if(this.nodeData[this.options.defaultNode]){
				treeview._nodeClick($('#'+_id+' #__node'+this.nodeData[this.options.defaultNode]["key"]));
			}
		}
	};
	TreeView.prototype._nodeClick = function(node) {
		$('#'+this.id+' .nav-link').removeClass('active');
		$(node).parent().addClass('active');
		var nodeId = $(node).attr("id");
		var key = nodeId.replace("__node", "");
		if (this.options.onNodeClick) {
			this.options.onNodeClick(key, this.dataList[key]);
		}
	};
	TreeView.prototype.nodeClickLoad = function(nodeId) {
		//$("#"+this.id+" "+"#__node"+nodeId+'>.text').click();
	};
	TreeView.prototype.currentNodekey = function() {
		var nodeId = $('#'+this.id+' .nav_link .active').attr("id");
		var key = nodeId.replace("__node", "");
		return key;
	};
	TreeView.prototype.getNodeList = function(key) {
		if(key) return this.dataList[key];
		return this.dataList;
	};

	TreeView.DEFAULTS = {
		appendTo: "body"
	};

	// treeview plugin
	$.fn.treeview = function(option) {
		var args = Array.prototype.slice.call(arguments, 1);
		var results;
		this.each(function(){
			var $this = $(this),
				data = $this.data('util_treeview'),
				options;

			if (!data) {
				options = $.extend({}, TreeView.DEFAULTS, typeof option === 'object' && option);
				data = new TreeView($this, options);
				$this.data('util_treeview', data);
			} else {
				if (typeof option === 'string' && option.charAt(0) !== '_' && typeof data[option] === 'function') {
					results = data[option].apply(data, args);
				} else {
					$.error('Method ' + option + ' does not exist on treeview.');
				}
			}
		});
		return (results != undefined ? results : this);
	};
})(jQuery);
