<script type="text/javascript">
<!--
//gets a DOM object by it's name
function getObjectByName(sName){				
	if(document.getElementsByName){
		oElements = document.getElementsByName(sName);

		if(oElements.length > 0)
			return oElements[0];
		else
			return null;
	}
	else if(document.all)
		return document.all[sName][0];
	else if(document.layers)
		return document.layers[sName][0];
	else
		return null;
}

function popupPrintWindow() {
	window.open("<?php echo html_entity_decode("$Location&print=yes"); ?>", "PopupPrintWindow", "location=0,status=no,menubar=no,resizable=1,width=800,height=450");
	return;
}

function reload2Export(theForm) {
	var browserName=navigator.appName; 
	if (browserName=="Microsoft Internet Explorer")
		window.open("<?php echo "$Location&export_excel=1"; ?>", "PopupExportWindow", "location=0,status=no,menubar=no,resizable=1,width=800,height=450");
	else {
		var s = getObjectByName('export_excel');
		s.value='1';
		theForm.submit();
	}
	return;
}
// vim:ai:ts=4:sw=4:filetype=javascript
//-->
</script>
