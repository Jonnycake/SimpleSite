function class2tag(tagname,classname)
{
	var elements=document.getElementsByTagName(tagname);
	for(var x=0;x<elements.length;x++)
	{
		var element=elements[x];
		element.setAttribute("class",element.getAttribute("class")+" "+classname);
	}
}