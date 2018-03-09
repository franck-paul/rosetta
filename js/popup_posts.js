/*global $ */
'use strict';

$(function() {
  $('#link-insert-cancel').click(function() {
    window.close();
  });

  $('#form-entries tr>td.maximal>a').click(function(e) {
    e.preventDefault();
    var parent_doc = window.opener.document;
    var rosetta_url = parent_doc.getElementById('rosetta_url');
    rosetta_url.value = $(this).attr('href');
    window.close();
  });
});
