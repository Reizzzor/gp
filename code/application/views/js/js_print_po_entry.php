<script>
	var chsnModules = []; //массив выбранных модулей, заполняется объектами вида {id:id, name:name, x:length, y:width}
	var savedCM = [];
	var exmv = [];
	<?php if(isset($cm_list)) echo "savedCM = JSON.parse('".$cm_list."');\n"; ?>
	if(savedCM.length>0 && !savedCM[0].id) savedCM = [];

	
	function extAPL()
	{
		if(savedCM && savedCM.length > 0){
			for(var i=0;i<savedCM.length;i++){
				savedCM[i].x = +(savedCM[i].x);
				savedCM[i].y = +(savedCM[i].y);
			}
			parseSavedCM();
			console.log(JSON.stringify(chsnMdules));
		}
	}
</script>