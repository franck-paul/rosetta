$(function() {
	$('#edit-entry').onetabload(function() {
		// Add toggle capability on Rosetta area
		$('#rosetta-area > label').toggleWithLegend($('#rosetta-area').children().not('label'),{
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

	function addTranslationRow(post_id,post_lang,rosetta_id,table) {
		var params = {
			f: 'getTranslationRow',
			xd_check: dotclear.nonce,
			id: post_id,
			lang: post_lang,
			rosetta_id: rosetta_id
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
					// Append the new line at the end of the table
					$(table).append(msg);
					// Bind removing translation function
					$(table+' tr:last td:last a').bind('click',function(e){
						removeTranslation($(this));
						e.preventDefault();
					});
					return true;
				}
			}
		});
		return null;
	}

	function removeTranslation(link) {
		if (!window.confirm(dotclear.msg.confirm_remove_rosetta)) {
			return false;
		}
		var href = link.attr('href');
		var row = link.parent().parent();
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
				} else {
					// Display error message
					window.alert(msg);
				}
			}
		});
	}

	// Switch to Ajax for removing translation link
	$('a.rosetta-remove').click(function(e) {
		removeTranslation($(this));
		e.preventDefault();
	});

	// Switch to Ajax for adding translation link
	$('a.rosetta-add').click(function(e) {
		var href = $(this).attr('href');
		var post_id = getURLParameter(href,'id');
		var post_lang = getURLParameter(href,'lang');
		var post_type = getURLParameter(href,'type');
		var rosetta_hidden = document.getElementById('rosetta_url');
		// Call popup_posts.php in order to select entry (post/page)
		rosetta_hidden.value = '';
		var p_win = window.open(
			'popup_posts.php?popup=1&plugin_id=rosetta&type='+post_type,'dc_popup',
			'alwaysRaised=yes,dependent=yes,toolbar=yes,height=500,width=760,'+
			'menubar=no,resizable=yes,scrollbars=yes,status=no');
		// Wait for popup close
		var timer = setInterval(function() {
		    if (p_win.closed) {
		        clearInterval(timer);
				// Get translation post/page id
			    var rosetta_id = getURLParameter(rosetta_hidden.value,'id');
				if (rosetta_id !== null && rosetta_id !== '') {
					var params = {
						f: 'addTranslation',
						xd_check: dotclear.nonce,
						id: post_id,
						lang: post_lang,
						rosetta_id: rosetta_id
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
								// Append new row at the end of translations list
								addTranslationRow(post_id,post_lang,rosetta_id,'#rosetta-list');
							} else {
								// Display error message
								window.alert(msg);
							}
						}
					});
				}
				// Reset hidden field
				rosetta_hidden.value = rosetta_hidden.defaultValue;
		    }
		}, 500);
		e.preventDefault();
	});

});
