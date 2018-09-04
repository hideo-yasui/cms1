"use strict";
class ListTable extends CardTable{
	//data-formt : ID, value, title, imgurl, limit_ratio, limit_date

	constructor(element, options) {
		super(element, options);

		this.parent_dom = 'table';
		this._publish_dom = '<table class="table table-bordered table-striped"></table>';
		this._edit = '<button type="button" class="btn btn-outline-success btn-sm" accesskey="rowedit"><i class="fa fa-edit"></i></button>';
		this._copy = '<button type="button" class="btn btn-outline-primary btn-sm" accesskey="rowcopy"><i class="fa fa-clone"></i></button>';
		this._delete = '<button type="button" class="btn btn-outline-danger btn-sm" accesskey="rowdelete"><i class="fa fa-minus-circle"></i></button>';
		this._table = null;
		this.element = element;
		this.publish(options);
	}
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
		_html += '<tbody>'+this.__getHeaderHtml()+this.__getBodyHtml()+'</tbody>';
		this._table.html(_html);
		this.__eventSetting();
	};

	__getHeaderHtml() {
		var header = '';
		//var classArray = [];
		header+='<tr>';
		for(var key in this.options.header){
			var type = this.options.header[key]["type"];
			if(!this.options.header[key]["text"] || this.options.header[key]["text"] == "") continue;
			if(type=="hidden") continue;
			if(type=="checkbox") {
				header += '<th class="'+this.options.header[key]["class"]+'"><input type="checkbox" class="_allcheck"/></th>';
			}
			else {
				header += '<th ';
				//if(!util.isEmpty(this.options.header[key]["class"])) header += 'class="'+this.options.header[key]["class"]+'"';
				header += '>';
				if(!util.isEmpty(this.options.header[key]["sort"])){
					header += '<a href="javascript:void(0);" class="_rowsort" field="'+this.options.header[key]["sort"]+'">';
					if(!this.currentSort[this.options.header[key]["sort"]]) this.currentSort[this.options.header[key]["sort"]] = "";
					this.sortFieldLength++;
				}
				header +=this.options.header[key]["text"];
				if(!util.isEmpty(this.options.header[key]["sort"])){
					switch(this.currentSort[this.options.header[key]["sort"]]){
						case "desc" :
							header += '　<i class="fa fa-sort-down float-right"></a>';
							break;
						case "asc" :
							header += '　<i class="fa fa-sort-up float-right"></a>';
							break;
						default:
							header += '　<i class="fa fa-sort float-right"></a>';
					}
				}
				header += '</th>';
			}
			//classArray.push(key);
		}
		header+='</tr>';
		return header;
	};

	__getBodyHtml() {
		var body = '';
		var n= this.tempdata.length;
		var m = (n+"").length;

		if(m > this.options.zeroPaddingSize) this.options.zeroPaddingSize = m;
		if(!util.isEmpty(this.maxPageSize) && n>this.maxPageSize) n = this.maxPageSize;
		for(var i=0;i<n;i++){
			var _tr_class = "";
			var _row = "";
			for(var key in this.options.header){
				if(!this.options.header[key]["field"]) this.options.header[key]["field"]=null;
				var fields = this.options.header[key]["field"];
				//if(isEmpty(fields)) continue;
				if(fields == null) fields = [null];
				if(typeof fields!= "object") fields = [ fields ];
				var vals = "";
				//fields is multiple then data(string) is concatenated to display
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
					else if(!(field in this.tempdata[i])) val = "-";
					else if( this.tempdata[i][field]===0) val = "0";
					else if(util.isEmpty(this.tempdata[i][field])) val = "-";
					else val = this.tempdata[i][field];

					if(!this.options.header[key]["type"]) this.options.header[key]["type"]=null;
					var type = this.options.header[key]["type"];

					if(type=="checkbox"){
						var err = "";
						if(this.options.header[key]["error"]) err = this.options.header[key]["error"];
						if(err && err!="" && this.tempdata[i][err] && this.tempdata[i][err]!="") {
							type="link";
							val = "エラー";
						}
					}
					switch(type){
						case "link" :
							var _link = "";
							_link = '<a href="javascript:void(0);"';
							for(var attr in this.options.header[key]){
								if(attr=="type") continue;
								if(attr=="title" && settitle) continue;
								if(!this.options.header[key][attr] || this.options.header[key][attr]=="") continue;
								var attrVal = this.options.header[key][attr];
								if(attr=="field") attr = "name";
								_link += " "+attr+'="'+attrVal+'"';
							}
							_link +='>'+val+'</a>';
							val = _link;
							break;
						case "hidden" :
						case "checkbox" :
							var _link = "";
							_link = '<input type="'+type+'"';
							//type is checkbox or radio then attribute must have name
							for(var attr in this.options.header[key]){
								if(attr=="type") continue;
								if(attr=="title" && settitle) continue;
								if(!this.options.header[key][attr] || this.options.header[key][attr]=="") continue;
								var attrVal = this.options.header[key][attr];
								_link += " "+attr+'="'+attrVal+'"';
							}
							_link +=' value="'+val+'"/>';
							val = _link;
							break;
						case "number" :
							val = this.zeroPadding(val);
							break;
						case "percent" :
							val = (((val*100000)|0)/1000+"%");
							break;
						case "rowstyle":
							_tr_class = _class;
							_class="";
							break;
						case "filesize" :
							val = util.setFileUnit(val);
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
					}
					vals += val;
				}
				//if(_class!="") attribute += ' class="'+_class+'"';
				if(type!="hidden") _row += '<td'+attribute+'>'+vals+'</td>';
				else  _row += vals;
			}
			body += '<tr ';
			if(_tr_class!="") body += 'class="'+_tr_class+'"';
			body += '><input type="hidden" value="'+i+'" id="__index__" />';
			body += _row;
			body += '</tr>';
		}
		return body;
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
				var idx = $("#__index__", $(this).parent().parent()).val();
				if (idx >= 0) {
					_self.options.onLinkClick.call(undefined, $(this), _self.tempdata[idx]);
				}
			});
		}
		if(this._table.find('button.btn').length){
			this._table.find('button.btn').on("click", function(event){
				event.preventDefault();
				var idx = $("#__index__", $(this).parent().parent()).val();
				if (idx >= 0) {
					_self.options.onButtonClick.call(undefined, $(this), _self.tempdata[idx]);
				}
			});
		}
		if(this._table.find('input[type=checkbox]').length){
			this._table.find('input[type=checkbox]').on("change", function(event){
				event.preventDefault();
				_checkboxChange();
			});
			this._table.find('._allcheck').unbind("change");
			this._table.find('._allcheck').on("change", function(event){
				event.preventDefault();
				var checked = $(this).prop('checked');
				_self.selectAll(!checked);
			});

		}
		if(this.sortFieldLength>0){
			this._table.find('a._rowsort').on("click", function(event){
				event.preventDefault();
				var field = $(this).attr("field");
				var sort = _self.currentSort[field];
				console.log("field["+field+"]sort["+sort+"]");
				switch(sort){
					case "desc" :
						sort = "asc";
						break;
					default:
						sort = "desc";
				}
				_self.currentSort[field] = sort;
				_self.currentSortOrder.unshift(field);
				if(_self.currentSortOrder.length > this.sortFieldLength) _self.currentSortOrder.splice(_self.currentSortOrder.length-1, 1);
				_self.refresh();
			});
		}
	};
	_checkboxChange(obj){
		$("tr", this._table).removeClass("selected");
		$("tr:has(input:checked)", this._table).addClass("selected");
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
var	_listTable = new ListTable($("#listTable"), _listParam);
_listTable.refresh(data);
*/
