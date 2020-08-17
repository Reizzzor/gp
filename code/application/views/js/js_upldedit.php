<script>
	var delURL = "<?=$del_url?>";
	var updURL = "<?=$upd_url?>";
	var infoID = 'InfoUpldDial';
	var dseqs = <?=isset($dseqs_str) ? "JSON.parse($dseqs_str)" : '[]'?>;
	var startAggr = 'val0';
	var startPos = 0;
	
	function getDseq(agID)
	{
		for(var i=0; i<dseqs.length; i++){
			if(+(dseqs[i]['aggr_id']) == agID) return +(dseqs[i]['dseq']);
		}
		return 1;
	}
	
	function makeDseqDD(oq)
	{
		let ddd = document.getElementById('dspl_dd');
		if(ddd.options.length == oq) return true;
		while(ddd.options.length != oq){
			if(ddd.options.length < oq){
				var nextPos = +(ddd.options[ddd.options.length - 1].text) + 1;
				var npOption = new Option(nextPos, 'val' + nextPos);
				ddd.options[ddd.options.length] = npOption;
			}
			else ddd.options[oq] = null;
		}
	}	
	
	function editDsplDD()
	{
		let cDDVal = getSelectedValue('ctgr_dd');
		let dDD = document.getElementById('dspl_dd');
		let hv = document.getElementById('upldHidden').value;
		if(cDDVal == 'val0') dDD.selectedIndex = 0;
		else if(cDDVal == startAggr && hv != 'new'){
			makeDseqDD(getDseq(+(cDDVal.substring(3))) - 1);
			for(var i=0; i<dDD.options.length; i++){
				if(+(dDD.options[i].text) == startPos){
					dDD.selectedIndex = i;
					break;
				}
			}
		}
		else makeDseqDD(getDseq(+(cDDVal.substring(3))));
	}
	
	
	function ytbLink(dID)
	{
		let oa = String(document.getElementById(dID).firstChild.onclick).split("'")[1];
		let sa = oa.split("/");
		return 'https://www.youtube.com/watch?v=' + sa[sa.length-1];
	}
	
	function upldDelConfirm(docID)
	{
		if (confirm("Вы подтверждаете удаление?")) location.href = delURL + document.getElementById('upldHidden').value;
		else return false;
	}
	
	function newUpld()
	{
		if(document.getElementById('upld_dialog').style.display == 'block') return false;
		clearUpldInfo(infoID);
		startAggr = document.getElementById('ctgr_dd').value; 
		startPos = 1;
		document.getElementById('delUpld').disabled = true;
		document.getElementById('upldHidden').value = 'new';
		document.getElementById('upld_dialog').style.display = 'block';
		return true;
	}
	
	function editUpld(doc_id, ctgrDDVal)
	{
		if(document.getElementById('upld_dialog').style.display == 'block') return false;
		clearUpldInfo(infoID);
		document.getElementById('upld_file_name').value = document.getElementById('doc_' + doc_id).innerHTML;
		document.getElementById('hdn_form').action = updURL + doc_id;
		document.getElementById('ctgr_dd').value = ctgrDDVal; 
		makeDseqDD(getDseq(+(ctgrDDVal.substring(3))) - 1);
		let dsplDD = document.getElementById('dspl_dd');
		startPos = document.getElementById('dspl_' + doc_id).innerHTML;
		for(var i=0; i<dsplDD.options.length; i++){
			if(dsplDD.options[i].text == startPos){
				dsplDD.selectedIndex = i;
				break;
			}
		}
		document.getElementById('upldHidden').value = doc_id;
		if(delURL.indexOf("video") != -1) document.getElementById('fileUpload').value = ytbLink('link_' + doc_id);
		startAggr = ctgrDDVal;
		document.getElementById('upld_dialog').style.display = 'block';
		return true;
	}
	
	function checkUpldDial()
	{
		clearUpldInfo(infoID);
		let errChk = "";
		if(!document.getElementById('fileUpload').value && document.getElementById('upldHidden').value == "new") errChk = addUpldInfo(infoID, 'Не выбран файл'); 
		if(!document.getElementById('upld_file_name').value) errChk = addUpldInfo(infoID, 'Не введено название документа'); 
		if(getSelectedValue('ctgr_dd') == "val0") errChk = addUpldInfo(infoID, 'Не выбрана категория документа'); 
		return errChk ? true : false;
	}
	
	function clearUpldInfo(div_id)
	{
		document.getElementById(div_id).innerHTML = '';
	}
	
	function addUpldInfo(div_id, msg)
	{
		let divInfo = document.getElementById(div_id);
		if(msg) divInfo.innerHTML += "\n" + msg;
		return divInfo.innerHTML;
	}
	
	function extAPL()
	{
		prepViewer();
	}
	
</script>