var isPlaying = false;
var player;
var srcArr = [];
var next, prev;

function getImgLink(pLink)
{
	let pa = pLink.split('/');
	let l = '';
	for(var i = 0; i < pa.length - 2; i++){
		l = l + pa[i] + '/';
	}
	//alert(l + pa[pn-1]);
	return l + pa[pa.length - 1];
}

function viewPDF(fileLink)
{
	var viewParams = {
		pdfOpenParams: {
			view: "FitV",
			pagemode: "thumbs"
		}
	}
	PDFObject.embed(fileLink, document.getElementById('viewer_show'), viewParams);
	document.getElementById('viewer').style.display = 'block';
	return true;
}

function viewImg(fileLink)
{
	let sImg = document.getElementById('hidden_img');
	sImg.src = fileLink;
	document.getElementById('viewer_imgs').style.display = 'block';
	if(nextPic(sImg)) document.getElementById('next_pic').style.display = 'block';
	if(prevPic(sImg)) document.getElementById('prev_pic').style.display = 'block';
	return true;
}

function viewVideo(fileLink)
{
	let vSize = checkVideoSize();
	let ih = '<iframe id="video_show" width="' + vSize[0] + '" height="' + vSize[1] + '" src="' + fileLink + '?rel=0&enablejsapi=1" frameborder="0" allow="accelerometer; encrypted-media; gyroscope; autoplay; picture-in-picture" allowfullscreen></iframe>';
	document.getElementById('viewer_show').innerHTML = ih;
	player = new YT.Player('video_show', {events: {'onReady':  function() {player.playVideo();}}});
	document.getElementById('viewer').style.display = 'block';
	isPlaying = true;
	return true;
}
	
function checkVideoSize()
{
	let res_x = 0.7*real_x > 800 ? 800 : 640;
	let res_y = 0.7*real_y > 600 ? 600 : 480;
	let vr = document.getElementById('viewer');
	hDivSize(vr, res_x, res_y); 
	vr.style.marginLeft = (0.13*real_x) + 'px';
	if(res_y == 600) vr.style.marginTop = (0.13*real_y) + 'px';	
	return [res_x, res_y];
}



function hDivSize(hdiv, dw, dh)
{
	hdiv.style.width = dw + 'px';
	hdiv.style.height = `${dh + 45}px`;
}
	
function hideDiv(h_id)
{
	if(isPlaying){
		player.stopVideo();
		isPlaying = false;
	}
	if(h_id == 'viewer_imgs'){
		document.getElementById('next_pic').style.display = 'none';
		document.getElementById('prev_pic').style.display = 'none';
		document.getElementById('hidden_img').src = '';
	}
	document.getElementById(h_id).style.display = 'none';
	return true;	
}

function nextPic(hp)
{
	for(var i=0;i<srcArr.length;i++){
		if(hp.src==srcArr[i] && (i+1)<srcArr.length) return srcArr[i+1];
	}
	return false;
}

function prevPic(hp)
{
	for(var i=0;i<srcArr.length;i++){
		if(hp.src==srcArr[i] && (i-1)>=0) return srcArr[i-1];
	}
	return false;
}

function changePic(isNext=false)
{
	let viewer = document.getElementById('viewer_imgs');
	let tImg = document.getElementById('hidden_img');
	viewer.style.display = 'none';
	tImg.src = isNext ? nextPic(tImg) : prevPic(tImg);
	viewer.style.display = 'block';
	document.getElementById('next_pic').style.display = nextPic(tImg) ? 'block' : 'none';
	document.getElementById('prev_pic').style.display = prevPic(tImg) ? 'block' : 'none';
}

function prepViewer()
{
	var vwOn = 0;
	let hPic = document.getElementById('hidden_img');
	let hdViewer = hPic ? 'viewer_imgs' : 'viewer';
	let hdCloser = hPic ? 'viewer_close_img' : 'viewer_close';
	if(hPic){
		var imgsArr = document.getElementsByTagName('img');
		for(var i=0;i<imgsArr.length;i++){
			if(imgsArr[i].src.length>0) srcArr.push(getImgLink(imgsArr[i].src));
		}
	}
	let vwDiv = document.getElementById(hdViewer);
	addEventListener("keyup", function(event){if(event.key === 'Escape' && vwDiv.style.display == 'block'){ hideDiv(hdViewer); vwOn = 0;}});
	addEventListener("click", function(event){if((!vwDiv.contains(event.target)) && vwDiv.style.display == 'block'){if(vwOn>0){hideDiv(hdViewer); vwOn = 0;} else vwOn++;}});
	document.getElementById(hdCloser).onclick = function(event){hideDiv(hdViewer); vwOn = 0;};
}
