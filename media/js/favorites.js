function changebackground(item, code_html) {
	$(item).css('background',"transparent url("+code_html +") center left no-repeat");
	$(item).css('background-position', "0 0");
	$(item).css('background-size', "16px");
}

$(document).ready(function() {
	$('.css-treeview').each(function () {
		$(this).find('.htmlfile').each(function () {
			var item = this;
			var url = new Url(this.href);
			if ($(this).attr('icon')) {
				changebackground(item, $(this).attr('icon'));
			}else if ((url.protocol == 'https') ||(url.protocol == 'http')){
				$.ajax({
					url : uriroot + "/index.php?option=com_ajax&plugin=jofavorites&" + 
						"format=json&group=content&method=grabicon&url=" + url.protocol +"://" + url.host ,
					type : 'GET',
					url_host : url.host,
					item, item,
					dataType : 'html', // On désire recevoir du HTML
					success : function(code_html, statut){ // code_html contient le HTML renvoyé
						if (code_html != "") {
							changebackground(this.item, code_html);
						}
					}
				});
			};
		});
	});
});