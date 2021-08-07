/*global $ */
'use strict';

$(function () {
  $('#rosetta-new-cancel').on('click', function () {
    window.close();
  });

  $('#rosetta-new-ok').on('click', function (e) {
    e.preventDefault();
    const parent_doc = window.opener.document;
    const rosetta_title = parent_doc.getElementById('rosetta_title');
    rosetta_title.value = document.getElementById('title').value;
    const rosetta_lang = parent_doc.getElementById('rosetta_lang');
    rosetta_lang.value = document.getElementById('lang').value;
    window.close();
  });

  $('input[name="title"]').get(0).focus();
});
