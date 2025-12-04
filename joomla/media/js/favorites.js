//add-on to jquery
$.expr[':'].textEquals = function(a, i, m) {
    return $(a).text().match(("^" + RegExp.escape(m[3]) + "$"));
};

function changebackground(item, iconUrl, exists=false) {
	if (iconUrl && iconUrl != "") {
		//console.log("icon:" + iconUrl);
		var img = $('<img>');
		img.attr('text',$(item).text());
		img.attr('src', iconUrl);
		img.on('error',
			function() {
				console.log("item.text:" + $(this).attr('text') + ":" + $(this).attr('src') + ":not loaded!!!");
			}
		);
		img.on('load',
			function() {
				//console.log("item.text:" + $(this).attr('text'));
				if ( this.width >= 5 ) {
					var alink = $("a:textEquals(\""+$(this).attr('text')+"\")");
					alink.css('background',"transparent url("+ $(this).attr('src') +") center left no-repeat");
					alink.css('background-position', "0 0");
					alink.css('background-size', "16px");
				} else {
					console.log("item.width is too small:" + $(this).attr('text') +":" + this.width);
				}
			}
		);
	}
 }


$(document).ready(function() {
	$('.css-treeview').each(function () {
		$(this).find('.htmlfile').each(function () {
			var item = this;
			var url = new Url(this.href);
			if (0) {//$(this).attr('icon') && $(this).attr('icon')!="") {
				//console.log("icon set for:" +  url.host + ":" +$(item).text());
				changebackground(item, $(this).attr('icon'), false);
			}else if ((url.protocol == 'https') ||(url.protocol == 'http')){
				//console.log("retrieving:" + url + " for " +  url.host +":" + $(item).text());
				var url = uriroot + "index.php?option=com_ajax&plugin=jofavorites&" + 
						"format=json&group=content&method=grabicon&url64=" +  btoa(url.protocol +"://" + url.host);
				//console.log("retrieving:" + url);
				$.ajax({
					url : url,
					type : 'GET',
					dataType : 'html', // On désire recevoir du HTML
					success : $.proxy(function(code_html, statut){ // code_html contient le HTML renvoyé
									//console.log(code_html);
									result = JSON.parse(code_html);
									if (result.success) {
										var icon = result.data[0];
										//console.log("change background for " + $(item).text() +":with:" + icon);
										changebackground(item, icon, true);
									}
								}, item)
				});
			}
		});
	});
});
