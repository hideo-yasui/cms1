<?php
$systemdata = $data["data"]["get_systemdata"];
$treemenu = $data["data"]["get_treemenu"];
?>
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
<!-- Brand Logo -->
<a href="#" class="brand-link">
	<img src="../../dist/img/AdminLTELogo.png"
			 alt="<?php echo $systemdata[0]["NAME"]; ?>"
			 class="brand-image img-circle elevation-3"
			 style="opacity: .8">
	<span class="brand-text font-weight-light"><?php echo $systemdata[0]["NAME"]; ?></span>
</a>
<!-- Sidebar -->
<div class="sidebar">
	<!-- Sidebar Menu -->
	<nav class="mt-2">
		<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
<?php
$nodeDatas = array();

for($i=0,$n=count($treemenu);$i<$n;$i++){
	$node = $treemenu[$i]["NODE"];
	$pnode = $treemenu[$i]["PNODE"];
	$name = $treemenu[$i]["NAME"];
	$option = trim('{'.str_replace('&quot;', '"', $treemenu[$i]["OPTION_STRING"]).'}');
	$option = json_decode($option, true);
	$lv = $treemenu[$i]["LV"];
	$data = $treemenu[$i];

	$data["child"] = array();
	$data["childCount"] = 0;
	$data["set"] = false;
	$data["key"] = $i;
	$data["option"] = $option;
	$data["active"] = "";
	$data["open"] = "";

	if(isset($_GET["ta"]) && strpos(",".$_GET["ta"]."," , ",".$node.",") !== false){
		$data["active"] = "active";
	}
	if(isset($_GET["to"]) && strpos(",".$_GET["to"]."," , ",".$node.",") !== false){
		$data["open"] = "menu-open";
	}

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
	$active = $nodedata["active"];
	$open = $nodedata["open"];
	if(empty($pnode)){
echo <<<EOT
<li class="nav-item has-treeview $open">
	  <a id="$node" href="javascript:void(0);" class="nav-link $active">
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
  <a id="$cnode" href="javascript:void(0);" class="nav-link" onClick="base.linkProc(this, {})" accesskey="$accesskey" target="$target">
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
