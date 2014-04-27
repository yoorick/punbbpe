/**
* Support routines for PunBB forum
* Based on PHPXref code (c) Gareth Watts 2003-2004
*/


/**
** Simple dynamic HTML popup handler
** (c) Gareth Watts, August 2003
*/
var gwActivePopup=null; // global
var gwTimeoutId=0;
var Popped=null;

function gwPopup(e,layerid, noautoclose) {
    var isEvent=true;
    var x=null; var y=null;


    gwCloseActive();
    try { e.type } catch (e) { isEvent=false; }
    if (isEvent) {
        if (e.pageX||e.pageY) {
            x=e.pageX; y=e.pageY;
        } else if (e.clientX||e.clientY) {
            if (document.documentElement && document.documentElement.scrollTop) {
                x=e.clientX+document.documentElement.scrollLeft; y=e.clientY+document.documentElement.scrollTop;
            } else {
                x=e.clientX+document.body.scrollLeft; y=e.clientY+document.body.scrollTop;
            }
        } else {
            return;
        }
    } else if (e != null) { /* assume it's an element */
        x=elementX(e);
        y=elementY(e);
    }
    layer=document.getElementById(layerid);
    if (x != null) {
        layer.style.left=x+'px';
        layer.style.top=y+'px';
    }
    layer.style.visibility='Visible';
    gwActivePopup=layer;
    clearTimeout(gwTimeoutId); gwTimeoutId=0;
    if (!noautoclose) {
        gwTimeoutId=setTimeout("gwCloseActive()", 2000);
        layer.onmouseout=function() { clearTimeout(gwTimeoutId); gwTimeoutId=setTimeout("gwCloseActive()", 350); }
        layer.onmouseover=function() { clearTimeout(gwTimeoutId); gwTimeoutId=0;}
    }
}

/**
* Close the active popup
*/
function gwCloseActive() {
    if (gwActivePopup) {
        gwActivePopup.style.visibility='Hidden';
        gwActivePopup=null;
    }

    Popped=null;
}

/**
* Display the popup for attachment
*/
function downloadPopup(e, aid) {
    if (Popped == aid)
	return;

    gwCloseActive();
    var title=document.getElementById('pun-title');
    var body=document.getElementById('pun-body');
    var desc=document.getElementById('pun-desc');
    var funcdata=ATTACH_DATA[aid];
    var atime=funcdata[0];
    var adescr=funcdata[1];
    var acmt=funcdata[2];
    var athumb=funcdata[3];
    var can_download=funcdata[4];

    if (athumb != '') adescr += '<br /><img src="'+O_BASE_URL+'/'+athumb+'" alt="" border="0"><br/><br/>';
    if (can_download) adescr = '<a href="'+O_BASE_URL+'/download.php?aid='+aid+'" title="click to download" class="att_filename">'+adescr+'</a>';
    if (athumb != '') adescr += '<b>BBcode</b>: <input type="text" onclick="this.select()" value="::thumb'+aid+'::" /><br/>';

    title.innerHTML = '#'+aid+': '+atime;
    desc.innerHTML = adescr;
    body.innerHTML = acmt;

    gwPopup(e, 'pun-popup');
    Popped = aid;
}


/**
* Calculate the absolute X offset of an html element
*/
function elementX(el) {
    var x = el.offsetLeft;
    var parent = el.offsetParent;
    while (parent) {
        x += parent.offsetLeft;
        parent = parent.offsetParent;
    }
    return x;
}

/**
* Calculate the absolute Y offset of an html element
*/
function elementY(el) {
    var y = el.offsetTop;
    var parent = el.offsetParent;
    while (parent != null) {
        y += parent.offsetTop;
        parent = parent.offsetParent;
    }
    return y;
}

