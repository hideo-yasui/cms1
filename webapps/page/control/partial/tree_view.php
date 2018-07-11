<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
<!-- Brand Logo -->
<a href="#" class="brand-link">
	<img src="../../dist/img/AdminLTELogo.png"
			 alt="#service.title#"
			 class="brand-image img-circle elevation-3"
			 style="opacity: .8">
	<span class="brand-text font-weight-light">#service.title#</span>
</a>
<!-- Sidebar -->
<div class="sidebar">
	<!-- Sidebar Menu -->
	<nav class="mt-2">
		<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
<?php
$dt = $data["data"]["get_treemenu"];
$nodeDatas = array();
for($i=0,$n=count($dt);$i<$n;$i++){
	$node = $dt[$i]["NODE"];
	$pnode = $dt[$i]["PNODE"];
	$name = $dt[$i]["NAME"];
	$option = trim('{'.str_replace('&quot;', '"', $dt[$i]["OPTION_STRING"]).'}');
	$option = json_decode($option, true);
	$lv = $dt[$i]["LV"];
	$data = $dt[$i];

	$data["child"] = array();
	$data["childCount"] = 0;
	$data["set"] = false;
	$data["key"] = $i;
	$data["option"] = $option;

	$nodeDatas[$node] = $data;
	if(isset($nodeDatas[$pnode])){
		$nodeDatas[$pnode]["child"][]= $node;
		$nodeDatas[$pnode]["childCount"]++;
	}
}
?>
<?php
foreach ( $nodeDatas as $node=>$nodedata ){
	$pnode = $nodedata["PNODE"];
	$name = $nodedata["NAME"];
	$style = $nodedata["STYLE"];
	$child = $nodedata["child"];
	if(empty($pnode)){
echo <<<EOT
<li class="nav-item has-treeview">
	  <a href="javascript:void(0);" class="nav-link">
		<i class="nav-icon fa fa-$style"></i>
		<p>
		  $name
		  <i class="right fa fa-angle-left"></i>
		</p>
	  </a>
	  <ul class="nav nav-treeview">
EOT;
	foreach ( $child as $t=>$cnode ){
		$cnodedata = $nodeDatas[$cnode];
		$name = $cnodedata["NAME"];
		$style = $cnodedata["STYLE"];
		$option = $cnodedata["option"];
		$target = "";
		$accesskey = "";
		if(isset($option)){
			$target = $option["query_code"];
			$accesskey = $option["onclick"];
		}
echo <<<EOT
<li class="nav-item">
  <a href="javascript:void(0);" class="nav-link" onClick="base.linkProc(this, {})" accesskey="$accesskey" target="$target">
	<i class="fa fa-$style nav-icon"></i>
	<p>
		$name
		<span class="badge badge-info right">2</span>
	</p>
  </a>
</li>
EOT;

	}
echo <<<EOT
	  </ul>
</li>
EOT;
	}
}
?>
</ul>
</nav>
<!-- /.sidebar-menu -->
</div>
<!-- /.sidebar -->
</aside>
