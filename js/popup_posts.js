/*global $ */
'use strict';

$(function() {
  $('#link-insert-cancel').on('click', function() {
    window.close();
  });

  $('#form-entries tr>td.maximal>a').on('click', function(e) {
    e.preventDefault();
    const parent_doc = window.opener.document;
    const rosetta_url = parent_doc.getElementById('rosetta_url');
    rosetta_url.value = $(this).attr('href');
    window.close();
  });
});
