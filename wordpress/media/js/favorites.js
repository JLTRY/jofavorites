//add-on to jquery
jQuery.expr[':'].textEquals = function(a, i, m) {
    return jQuery(a).text().match(("^" + RegExp.escape(m[3]) + "$"));
};

function changebackground($, item, iconUrl, exists=false) {
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

jQuery(document).ready(function($) {
    var items = $('.css-treeview .htmlfile').toArray();

    function processNextItem() {

        var item = items.shift();
        if (!item) return;
        var url = new Url(item.href);
        await new Promise(r => setTimeout(r, 500));
        if ((url.protocol == 'https') || (url.protocol == 'http')) {
            var requestUrl = uriroot + "index.php?option=com_ajax&plugin=jofavorites&" +
                "format=json&group=content&method=grabicon&url64=" + btoa(url.protocol + "://" + url.host);

            $.ajax({
                url: requestUrl,
                type: 'GET',
                dataType: 'html',
                success: function(code_html) {
                    try {
                        var result = JSON.parse(code_html);
                        if (result && result.success) {
                            changebackground($, item, result.data, true);
                        }
                    } catch (e) {
                        console.log('JSON parse error for favorites:', e);
                    }
                    processNextItem(); // next item only after this finishes
                },
                error: function(xhr, status, error) {
                    console.log('failure for favorites', status, error);
                    processNextItem(); // continue even if one fails
                }
            });
        } else {
            processNextItem(); // skip invalid URLs
        }
    }

    processNextItem();
});
