"use strict";
class CardTable {
	//data-formt : ID, value, title, imgurl, limit_ratio, limit_date
	constructor(element, options) {
		this.parent_dom = 'ul';
		this._publish_dom = '<ul class="mailbox-attachments clearfix"></ul>';
		this._row_template = [
			'<li #li_class#>',
				'<input type="hidden" value="#__index__#" name="__index__" id="__index__" />',
				'<input type="hidden" value="#ID# name="ID" />',
//				'<div class="panel-badge">',
//					'<span class="right badge badge-danger float-right mx-1 text-lg">#value#</span>',
//				'</div>',
				'<div class="mailbox-attachment-icon has-img">',
					'<h5>#title#</h5>',
					'<img src="#imgurl#" alt="Attachment">',
					'<h4 class="icon-label hide">',
						'<div class="text-xl"><i class="fa fa-check-circle"></i></div>',
					'</h4>',
				'</div>',
				'<div class="mailbox-attachment-info">',
					'<div class="">#value#',
					'</div>',
					'<div class="progress my-1">',
						'<div class="progress-bar #limit_ratio_color#" role="progressbar" aria-valuenow="#limit_ratio#" aria-valuemin="0" aria-valuemax="100" style="width: #limit_ratio#%">',
							'<span class="sr-only"></span>',
						'</div>',
					'</div>',
					'<span class="mailbox-attachment-size">',
					'	#limit_date#',
						'#button#',
					'</span>',
				'</div>',
			'</li>',
		].join('');
		this._edit = '<button href="javascript:void(0);" alt="dialog" class="btn btn-default btn-sm float-right" accesskey="rowedit"><i class="fa fa-edit"></i></button>';
		this._copy = '<button href="javascript:void(0);" class="btn btn-default btn-sm float-right" accesskey=""><i class="fa fa-utensils"></i></button>';
		this._delete = '<button href="javascript:void(0);" class="btn btn-default btn-sm float-right" accesskey="rowdelete"><i class="fa fa-trash-alt"></i></button>';
		this._table = null;
		this.element = element;
		this.publish(options);
	}
	publish(options) {
		this.options = options;
		this.display = options.display;
		this.data = options.data;
		this.tempdata = null;
		this.filterVal = options.filterVal;
		this.sortField = options.sortField;
		this.maxPageSize = options.maxPageSize;
		this.currentSort = {};
		this.currentSortOrder = [];
		this.sortFieldLength = 0;
		this.element.html(this._publish_dom);
	};
	refresh(data) {
		this.tempdata = null;

		$(this.element).show();
		if(!data) this.tempdata = this._dataCopy(this.data);
		else this.tempdata = this._dataCopy(data);

		if(!util.isEmpty(this.filterVal) && util.isFunction(this.options.onFilter)) this.tempdata = this.options.onFilter(this.tempdata, this.filterVal);
		if(util.isFunction(this.options.onSort)) this.tempdata = this.options.onSort(this.tempdata, this.getSortField());
		if(!this.tempdata || this.tempdata==null) return;
		this._table = this.element.find(this.parent_dom);
		var _html = '';
		_html += ''+this.__getHeaderHtml()+this.__getBodyHtml()+'';
		this._table.html(_html);
		this.__eventSetting();
	};

	__getHeaderHtml() {
		return "";
	};

	__getBodyHtml() {
		var body = '';
		var n= this.tempdata.length;
		var m = (n+"").length;

		if(m > this.options.zeroPaddingSize) this.options.zeroPaddingSize = m;
		if(!util.isEmpty(this.maxPageSize) && n>this.maxPageSize) n = this.maxPageSize;
		for(var i=0;i<n;i++){
			var _tr_class = "";
			var _row = this._row_template;
			_row = _row.replace('#__index__#', i);
			for(var key in this.options.header){
				if(!this.options.header[key]["field"]) this.options.header[key]["field"]=null;
				var fields = this.options.header[key]["field"];
				var text = this.options.header[key]["text"];
				if(fields == null) fields = [null];
				if(typeof fields!= "object") fields = [ fields ];
				var vals = "";
				var title = this.options.header[key]["title"];
				var settitle = false;
				var attribute = "";
				var cl = this.options.header[key]["class"];
				var _class = "";
				if(cl && cl!=null){
					_class = cl;
					if(this.tempdata[i][cl]) _class = this.tempdata[i][cl];
				}
				if(title && title!=null && this.tempdata[i][title]){
					attribute += ' title="'+this.tempdata[i][title]+'"';
					settitle = true;
				}
				if(this.__isVisible(this.tempdata[i], this.options.header[key]["visible"])===false) continue;
				for(var j=0,m=fields.length;j<m;j++){
					var field = fields[j];
					var val=null;
					if(field==null) val = i+1;
					else if(!(field in this.tempdata[i])) val = "";
					else if( this.tempdata[i][field]===0) val = "0";
					else if(util.isEmpty(this.tempdata[i][field])) val = "";
					else val = this.tempdata[i][field];

					if(!this.options.header[key]["type"]) this.options.header[key]["type"]=null;
					var type = this.options.header[key]["type"];

					switch(type){
						case "button":
							/*
							edit_copy_deleteを再現したければ、
							headerに90 : edit, 91 : copy, 92 : deleteの定義を設定
							buttonの定義のfieldを配列にし[90,91,92]とする
							1. field=90のデータは存在しないのでval=空
							2. keyのtype=button / fieldsの数だけbuttonフォームが、valsに追加される
							3. text=buttonで指定しているので、そちらに全部追加する
							*/
							var _class = this.options.header[field]["class"];
							let _alt = this.options.header[field]["alt"];
							let _accesskey = this.options.header[field]["accesskey"];
							let _target = this.options.header[field]["target"];
							if(this.__isVisible(this.tempdata[i], this.options.header[field]["visible"])===true){
								val = [
										'<button href="javascript:void(0);" alt="'+_alt+'" target="'+_target+'" class="btn btn-default btn-sm float-right" accesskey="'+_accesskey+'">',
											'<i class="fa fa-'+_class+'"></i>',
										'</button>'
									].join('');
							}
							break;
						case "edit_copy_delete" :
							val = this._edit+this._copy+this._delete;
							break;
						case "edit_delete" :
							val = this._edit+this._delete;
							break;
						case "edit" :
							val = this._edit;
							break;
						case "delete" :
							val = this._delete;
							break;
						case "check_icon" :
							val = ' class="check_icon"';
							val += ' accesskey="'+this.options.header[key]["accesskey"]+'"';
							val += ' target="'+this.options.header[key]["target"]+'"';
							break;
					}
					vals += val;
				}
				if(text==="imgurl" && util.isEmpty(vals)){
					vals = "/img/no_img.png";
				}
				_row = _row.replace_all('#'+text+'#', vals);
			}
			var bg_color = this.tempdata[i]["limit_ratio"];
			if(bg_color > 40){
				_row = _row.replace_all('#limit_ratio_color#', "bg-success");
			}
			else if(bg_color > 20){
				_row = _row.replace_all('#limit_ratio_color#', "bg-warning");
			}
			else {
				_row = _row.replace_all('#limit_ratio_color#', "bg-danger");
			}
			_row = _row.replace_all('#button#', "");
			body += _row;
		}
		return body;
	};
	__isVisible(data, _visible){
		var _isVisible = true;
		//if field have multiple outputs then visible propety setting
		//usage visible setting is visible : {field : exist of this.tempdata[i][field] , value : equal this.tempdata[i][field]
		if(_visible && _visible != null && _visible["field"] != null &&  _visible["value"] != null ){
			_isVisible = false;
			if(!data[_visible["field"]]){
				if(typeof data[_visible["field"]] == "number"){
					data[_visible["field"]] = 0;
				}
				else {
					data[_visible["field"]] = "";
				}
			}
			if(!_visible["fomula"]) _visible["fomula"] = "equal";
			switch(_visible["fomula"]){
				case "equal":
					if(data[_visible["field"]] == _visible["value"]) _isVisible = true;
					break;
				case "not":
					if(data[_visible["field"]] != _visible["value"]) _isVisible = true;
					break;
				case "greater":
					if(util.diffVal(data[_visible["field"]], _visible["value"]) > 0) _isVisible = true;
					break;
				case "less":
					if(util.diffVal(data[_visible["field"]], _visible["value"]) < 0) _isVisible = true;
					break;
			}
		}
		return _isVisible;
	};
	__eventSetting() {
		this._table.find('button').unbind("click");
		//this._table.find('button.btn[accesskey*=row]').unbind("click");
		this._table.find('input[type=checkbox]').unbind("change");
		this._table.find('._rowsort').unbind("click");

		var _self = this;
		if(this._table.find('a:not(.btn)').length){
			this._table.find('a:not(.btn)').on("click", function(event){
				event.preventDefault();
				var idx = $("#__index__", $(this).parent().parent().parent()).val();
				if (idx >= 0) {
					_self.options.onLinkClick.call(undefined, $(this), _self.tempdata[idx]);
				}
			});
		}
			$('li.check_icon img', this._table).click(function(event){
				event.preventDefault();
				var idx = $("#__index__", $(this).parent().parent()).val();
				if (idx >= 0) {
					_self.options.onLinkClick.call(undefined, $(this).parent().parent(), _self.tempdata[idx]);
				}
			});
		if(this._table.find('button.btn').length){
			this._table.find('button.btn').on("click", function(event){
				event.preventDefault();
				var idx = $("#__index__", $(this).parent().parent().parent()).val();
				if (idx >= 0) {
					_self.options.onButtonClick.call(undefined, $(this), _self.tempdata[idx]);
				}
			});
		}
		if(this._table.find('li:not(.check_icon) div.mailbox-attachment-icon').length){
			this._table.find('li:not(.check_icon) div.mailbox-attachment-icon').on("click", function(event){
				event.preventDefault();
				var obj = $("#__index__", $(this).parent());
				if (obj.val() >= 0) {
					_self._checkboxChange(obj.parent());
				}
			});
		}
	};
	_checkboxChange(obj){
		if($(obj).hasClass("active")){
			$(obj).removeClass("active");
			$(".icon-label", $(obj)).addClass("hide");
		}
		else {
			$(obj).addClass("active");
			$(".icon-label", $(obj)).removeClass("hide");
		}
	}

	//usage:if cardTable hiden & dispose
	remove() {
		this.element.find(this.parent_dom).empty();
	};
	//usage:if cardTable visible change
	visible(f) {
		if(this.display != f){
			if(!f) {
				$(this.element).hide();
				this.remove();
			}
			else {
				$(this.element).show();
				this.refresh();
			}
		}
		this.display = f;
	};
	filter(r) {
		this.filterVal = r;
		if(this.display) this.refresh();
	};
	//if you want to get data of this
	getData(index, field) {
		if(index && index>=0 && index<this.tempdata.length){
			if(field && typeof field =="string") this.tempdata[index][field];
			else if(field && typeof field =="object") {
				//this case is field's type is Array
				var result = {};
				for(var f=0,m= field.length;f<m;f++){
					result[field[f]] = this.tempdata[index][field[f]];
				}
				return result;
			}
		}
		else {
			if(field && typeof field =="string") {
				var result = [];
				for(var i=0,n=this.tempdata.length;i<n;i++){
					result[result.length] = this.tempdata[i][field];
				}
				return result;
			}
			else if(field && typeof field =="object") {
				//this case is field's type is Array
				var result = [];
				for(var i=0,n=this.tempdata.length;i<n;i++){
					var row = {};
					for(var f=0,m= field.length;f<m;f++){
						row[field[f]] = this.tempdata[i][field[f]];
					}
					result[result.length] = row;
				}
				return result;
			}
		}
		return this.tempdata;
	};
	//if you want to search data of this
	existData(index, field, value) {
		if(!value) return -1;
		if(field && typeof field =="string" && typeof value =="string") {
			var target = this.getData(index, field);
			for(var i=0;i<target.length;i++){
				if(target[i]==value){
					return i;
				}
			}
		}
		else {
			//field is associative array
			var _field = [];
			for(var key in value){
				_field.push(key);
			}
			var target = this.getData(index, _field);
			for(var i=0;i<target.length;i++){
				var _exist = true;
				for(var key in value){
					if(!target[i][key]) return -1;
					if(value[key]!=target[i][key]) _exist = false;
				}
				if(_exist) return i;
			}
		}
		return -1;
	};
	//if you want to all select or all no select
	selectAll(unchecked) {
		var checked = true;
		if(unchecked) checked = false;
		/*
		this._table.find('._allcheck').prop('checked', checked);
		this._table.find('input:checkbox').prop('checked', checked);
		*/
		this._table.find('li').removeClass("active");
		if(checked){
			this._table.find('li').addClass("active");
		}
		this._checkboxChange();
	};
	//if you want to select of this searched data
	selectData(index, field, value, unchecked) {
		var rowNo = this.existData(index, field, value);
		if(rowNo>=0) {
			var check = true;
			if(unchecked) check = false;
			$("li:eq("+rowNo+")", this._table).removeClass("active");
			if(checked){
				$("li:eq("+rowNo+")", this._table).addClass("active");
			}
			this._checkboxChange();
			return true;
		}
		return false;
	};
	//if you want to get data of this.selected or checked
	getSelectData(field) {
		var selectRow = [];
		var result = [];
		$("li.active", this._table).each(function(i){
			var rowNo = $("#__index__", $(this)).val();
			if(util.isInteger(rowNo)){
				selectRow.push(rowNo);
			}
		});
		if(!field){
			var result = [];
			for(var i=0,n=selectRow.length;i<n;i++){
				var idx = selectRow[i];
				result[result.length] = this.tempdata[idx];
			}
		}
		else if(field && typeof field =="string") {
			var result = [];
			for(var i=0,n=selectRow.length;i<n;i++){
				var idx = selectRow[i];
				result[result.length] = this.tempdata[idx][field];
			}
		}
		else if(field && typeof field =="object") {
			//this case is field's type is Array
			var result = [];
			for(var i=0,n=selectRow.length;i<n;i++){
				var idx = selectRow[i];
				var row = {};
				for(var f=0,m= field.length;f<m;f++){
					row[field[f]] = this.tempdata[idx][field[f]];
				}
				result[result.length] = row;
			}
		}
		return result;
	};
	getSortField() {
		var ret = {};
		for(var i=0;i<this.currentSortOrder.length;i++){
			if(ret[this.currentSortOrder[i]]) continue;
			ret[this.currentSortOrder[i]] = this.currentSort[this.currentSortOrder[i]];
		}
		return ret;
	};
	_dataCopy(data){
		var _tempdata = new Array(data.length);
		for(var i=0,n=data.length;i<n;i++){
			_tempdata[i] = {};
			for(var key in data[i]){
				_tempdata[i][key] = data[i][key];
			}
		}
		return _tempdata;
	}
}
/*
var data =[
	{"ID" : 1, "value" : 3, "title" : "商品名", "imgurl" : "/img/reizo/meat/100.png", "limit_ratio": 40, "limit_date" : "2018/08/02"},
	{"ID" : 2, "value" : 3, "title" : "商品名", "imgurl" : "/img/reizo/meat/100.png", "limit_ratio": 40, "limit_date" : "2018/08/02"}
];
var _listParam = {
	"data" : data,
	"header" : {
		"0" : {"field" : "ID", "text" : "ID", "title" : "", "class" : "", "type" : ""},
		"1" : {"field" : "value", "text" : "value", "title" : "", "class" : "", "type" : ""},
		"2" : {"field" : "title", "text" : "title", "title" : "", "class" : "", "type" : ""},
		"3" : {"field" : "imgurl", "text" : "imgurl", "title" : "", "class" : "", "type" : ""},
		"4" : {"field" : "limit_ratio", "text" : "limit_ratio", "title" : "", "class" : "", "type" : ""},
		"5" : {"field" : "limit_date", "text" : "limit_date", "title" : "", "class" : "", "type" : ""}
	},
	"zeroPaddingSize" : 3,
	"maxPageSize" : 20,
	"sortField" : "",
	"filterVal" : null,
	"onFilter" : function(){
		alert("onfileter");
	},
	"onButtonClick" : function(){
		alert("button click");
	},
	"onLinkClick" : function(){
		alert("link click");
	},
};
var	_listTable = new CardTable($("#listTable"), _listParam);
_listTable.refresh(data);
*/
