<?php view($data["_directory"]["meta"]."start.php", $data);?>
<style>
.check-box{
	font-size:1.8rem;
}
.checked-box{
	font-size:1.4rem;
}
.kana{
	font-size:.5rem;
	border-bottom:1px dashed #CCC;
	margin-bottom:8px;
}
@page {
	size:portrait;
}
@media print{
	html,body {
		height:100%;
		width:100%;
		font-size : 100%;
		-webkit-print-color-adjust: exact;
	}
	.print_none{
		display:none;
	}
}
.wrapper {
	margin:10px;
}
.doc .sub-title{
	font-size:0.8rem;
	width:20%;
	border-bottom:solid 1px #000;
	line-height:1rem;
}
.doc {
	font-size:20px;
	margin: 0 auto;
	margin-top:-2px;
	width:90%;
}
.row {
	border: solid 2px #666;
	margin-top:-2px;
	line-height:1rem;
}
.doc:firstchild{
	margin-top:0px;
}
.doc .title{
	margin-bottom:4px;
	font-size:.7rem;
}
.doc .body{
	padding-left:8px;
}
.doc .col-1,
.doc .col-2,
.doc .col-3,
.doc .col-4,
.doc .col-5,
.doc .col-6,
.doc .col-7,
.doc .col-8,
.doc .col-9,
.doc .col-12 {
	border-right:solid 1px #666;
	padding:8px;
}

</style>
</head>
<body>
<div class="wrapper">
	<?php view($data['contents'], $data);?>
</div>
<?php view($data["_directory"]["meta"]."end.php", $data);?>
