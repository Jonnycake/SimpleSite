/*
 *    SimpleSite Editable v0.1: Functions required for live content editing.
 *    Copyright (C) 2013 Jon Stockton
 * 
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 var editing=[];
 var editContents=[];
 var editsOpen=0;
 var editViewing=[];
 var editArray='';
 var viewDict={};

 function saveEdit(eid)
 {
  	var isOpen=editing.indexOf(eid);
 	var isViewing=editViewing.indexOf(eid);
 	if(isViewing==-1)
 		var updatedContent=document.getElementById("origContent"+eid+"_textarea").value;
 	else
 		var updatedContent=document.getElementById("origContent"+eid).innerHTML;
 	var parameters='ajax=1&ajaxFunc=liveEdit&eid='+eid+'&updateContent='+encodeURIComponent(updatedContent)+'&editArray='+encodeURIComponent(editArray);
	var xmlhttp;
	if (window.XMLHttpRequest)
	{// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	}
	else
	{// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange=function()
	{
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
			editContents[isOpen]=updatedContent;
			editArray=xmlhttp.responseText;
		}
	}
	xmlhttp.open("POST","index.php",true);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.send(parameters);
 }
 function viewEdit(eid)
 {
 	var isOpen=editing.indexOf(eid);
	if(isOpen>-1)
	{
		editViewing[isOpen]=eid;
		var origContentUP=document.getElementById("origContent"+eid+"_textarea").value;
		viewDict["editable"+eid]=origContentUP;
		var parameters='ajax=1&ajaxFunc=parseText&content='+encodeURIComponent(origContentUP);
		var xmlhttp;
		if (window.XMLHttpRequest)
		{// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp=new XMLHttpRequest();
		}
		else
		{// code for IE6, IE5
			xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange=function()
		{
			if (xmlhttp.readyState==4 && xmlhttp.status==200)
			{
				document.getElementById("origContent"+eid).innerHTML=xmlhttp.responseText;
			}
		}
		xmlhttp.open("POST","index.php",true);
		xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xmlhttp.send(parameters);
 	}
 }
 function edit(eid)
 {
 	var unparsed='';
 	var isOpen=editing.indexOf(eid);
 	var isViewing=editViewing.indexOf(eid);
 	var tmpeditarr=eval(atob(editArray));
 	var origContent='';
 	if(isOpen>-1 && isViewing==-1)
 	{
 		editing.splice(isOpen,1);
 		editsOpen-=1;
 		origContent=editContents[isOpen];
 		editContents.splice(isOpen,1);
 		delete tmpeditarr[eid-1];
 		document.getElementById("origContent"+eid).innerHTML=origContent;
 	}
 	else
 	{
		origContent=document.getElementById("origContent"+eid);
		if(isViewing==-1)
		{
 			editContents[editsOpen]=origContent.innerHTML;
 			editing[editsOpen++]=eid;
		}
		else
		{
			unparsed=eval('viewDict.editable'+eid);
			editViewing.splice(isViewing,1);
		}
		if(unparsed=='')
			unparsed=tmpeditarr[eid-1].unparsed;
 		origContent.innerHTML="<textarea id=\"origContent"+eid+"_textarea\" rows=\"10\">"+unparsed+"</textarea><br/><input type=\"button\" value=\"Save\" onclick=\"saveEdit("+eid+");\"/><br/><input type=\"button\" value=\"Preview\" onclick=\"viewEdit("+eid+");\"/>";
 	}
 }