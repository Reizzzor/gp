<script>
	var infoUrl = "<?=isset($url_info) ? $url_info : '' ?>";
	var tableId = "<?=$table_id ? $table_id : '' ?>";
	var uIndex = <?=$table_id == 'orders_table' ? 1 : 0 ?>;
	var slnFltr = <?=isset($s_fltr_str) && $s_fltr_str ? "JSON.parse($s_fltr_str)" : 'null' ?>;
	var kaFltr = <?=isset($k_fltr_str) && $k_fltr_str ? "JSON.parse($k_fltr_str)" : 'null' ?>;
	
	function fillFilterDD()
	{
		let fType = getSelectedValue('filter_type');
		let fltr = document.getElementById('filter');
		let fltrOpts = fType == 'salons' ? slnFltr : kaFltr;
		while(fltr.options.length > 1){
			fltr.options[1] = null;
		}
		for(var k in fltrOpts){
			fltr.options[fltr.options.length] = new Option(fltrOpts[k], k);
		}
	}
	
	function filterRows(tblID)
	{
		fTbl = document.getElementById(tblID);
		let kI;
		let kW = getSelectedValue('filter_type') == 'salons' ? 'Салон' : 'Контрагент';
		for(var i in fTbl.rows[0].cells){
			if(fTbl.rows[0].cells[i].innerHTML == kW){
				kI = i;
				break;
			}
		}
		let fltr = document.getElementById('filter');
		let fltrItem = fltr.selectedIndex > 0 ? fltr.options[fltr.selectedIndex].text : '';
		for(i=1;i<fTbl.rows.length;i++){
			if(!fltrItem) fTbl.rows[i].style.display = 'table-row';
			else fTbl.rows[i].style.display = fTbl.rows[i].cells[kI].innerHTML == fltrItem ? 'table-row' : 'none';
		}
		resizeRows(fTbl);
	}
	
	function extAPL()
	{	
		let t = document.getElementById(tableId);
		let ch = document.body.clientHeight;
		let rows = t.rows;
		if((ch - 60 ) < t.offsetHeight) t.tHead.style.position = 'sticky';
		if(slnFltr){
			rows[0].cells[4].style.minWidth = "120px";
			rows[0].cells[rows[0].cells.length-2].style.minWidth = "150px";
			rows[0].cells[rows[0].cells.length-1].style.minWidth = "120px";
		}
		if(!slnFltr && (tableId == 'orders_table')){
			rows[0].cells[2].style.minWidth = "200px";
			rows[0].cells[4].style.minWidth = "150px";
		}
		resizeRows(t);
		if(['preorders_table', 'new_store_table'].includes(tableId)) return true;
		for(var i=1;i<rows.length;i++){
			var rowId = rows[i].cells[uIndex].innerHTML;
			var goToInfo = function(dbId){
				return function(){
					location.href = infoUrl + dbId;
				};
			};
			if(!isNaN(rowId)) rows[i].onclick = goToInfo(rowId);
		}
		if(slnFltr) fillFilterDD();
		return true;
	}
</script>
