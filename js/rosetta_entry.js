$(function() {
	$('#edit-entry').onetabload(function() {
		$('#rosetta-area label').toggleWithLegend($('#rosetta-area').children().not('label'),{
			user_pref: 'dcx_post_rosetta',
			legend_click: true,
			hide: false
		});
	});

	$('#rosetta-area a[name="delete"]').click(function() {
		return window.confirm(dotclear.msg.confirm_remove_rosetta);
	});
});
