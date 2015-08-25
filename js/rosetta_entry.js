$(function() {
	$('#edit-entry').onetabload(function() {
		// Add toggle capability on Rosetta area
		$('#rosetta-area label').toggleWithLegend($('#rosetta-area').children().not('label'),{
			user_pref: 'dcx_post_rosetta',
			legend_click: true,
			hide: false
		});
	});

	function getURLParameter(url,name) {
		// Extract param value from URL
	    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);
	    if (results === null) {
	       return null;
	    }
	    else{
	       return results[1] || 0;
	    }
	}

	// Switch to Ajax for removing translation link
	$('a.rosetta-remove').click(function(e) {
		if (!window.confirm(dotclear.msg.confirm_remove_rosetta)) {
			return false;
		}
		var href = $(this).attr('href');
		var row = $(this).parent().parent();
		var post_id = getURLParameter(href,'id');
		var post_lang = getURLParameter(href,'lang');
		var rosetta_id = getURLParameter(href,'rosetta_id');
		var rosetta_lang = getURLParameter(href,'rosetta_lang');
		var params = {
			f: 'removeTranslation',
			xd_check: dotclear.nonce,
			id: post_id,
			lang: post_lang,
			rosetta_id: rosetta_id,
			rosetta_lang: rosetta_lang
		};
		$.get('services.php',params,function(data) {
			if ($('rsp[status=failed]',data).length > 0) {
				// For debugging purpose only:
				// console.log($('rsp',data).attr('message'));
				console.log('Dotclear REST server error');
			} else {
				// ret -> status (true/false)
				// msg -> message to display
				var ret = Number($('rsp>rosetta',data).attr('ret'));
				var msg = $('rsp>rosetta',data).attr('msg');
				if (ret) {
					// Remove corresponding line in table
					row.remove();
					window.alert(msg);
				} else {
					// Display error message
					window.alert(msg);
				}
			}
		});
		e.preventDefault();
	});

	// Switch to Ajax for adding translation link
	$('a.rosetta-add').click(function(e) {
		var href = $(this).attr('href');
		var post_id = getURLParameter(href,'id');
		var post_lang = getURLParameter(href,'lang');
		// Call popup_posts.php in order to select entry (post/page)
		if (1 == 1) {
			var rosetta_id = getURLParameter(href,'rosetta_id');
			var rosetta_lang = getURLParameter(href,'rosetta_lang');
			var params = {
				f: 'addTranslation',
				xd_check: dotclear.nonce,
				id: post_id,
				lang: post_lang,
				rosetta_id: rosetta_id,
				rosetta_lang: rosetta_lang
			};
			$.get('services.php',params,function(data) {
				if ($('rsp[status=failed]',data).length > 0) {
					// For debugging purpose only:
					// console.log($('rsp',data).attr('message'));
					console.log('Dotclear REST server error');
				} else {
					// ret -> status (true/false)
					// msg -> message to display
					var ret = Number($('rsp>rosetta',data).attr('ret'));
					var msg = $('rsp>rosetta',data).attr('msg');
					if (ret) {
						// Add the new line at the end of the table
					} else {
						// Display error message
						window.alert(msg);
					}
				}
			});
		}
		e.preventDefault();
	});

});
