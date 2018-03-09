/*global $ */
'use strict';

$(function() {
  $('#rosetta-new-cancel').click(function() {
    window.close();
  });

  $('#rosetta-new-ok').click(function(e) {
    e.preventDefault();
    var parent_doc = window.opener.document;
    var rosetta_title = parent_doc.getElementById('rosetta_title');
    rosetta_title.value = document.getElementById('title').value;
    var rosetta_lang = parent_doc.getElementById('rosetta_lang');
    rosetta_lang.value = document.getElementById('lang').value;
    window.close();
  });
});
