/*global $, dotclear */
'use strict';

$(function() {
  $('#edit-entry').onetabload(function() {
    // Add toggle capability on Rosetta area
    $('#rosetta-area > label').toggleWithLegend($('#rosetta-area').children().not('label'), {
      user_pref: 'dcx_post_rosetta',
      legend_click: true,
      hide: false
    });
  });

  function getURLParameter(url, name) {
    // Extract param value from URL
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);
    if (results === null) {
      return null;
    } else {
      return results[1] || 0;
    }
  }

  function addTranslationRow(post_id, post_lang, rosetta_id, table) {
    var params = {
      f: 'getTranslationRow',
      xd_check: dotclear.nonce,
      id: post_id,
      lang: post_lang,
      rosetta_id: rosetta_id
    };
    $.get('services.php', params, function(data) {
      if ($('rsp[status=failed]', data).length > 0) {
        // For debugging purpose only:
        // console.log($('rsp',data).attr('message'));
        window.console.log('Dotclear REST server error');
      } else {
        // ret -> status (true/false)
        // msg -> message to display
        var ret = Number($('rsp>rosetta', data).attr('ret'));
        var msg = $('rsp>rosetta', data).attr('msg');
        if (ret) {
          // Append the new line at the end of the table
          $(table).append(msg);
          // Bind removing translation function
          $(table + ' tr:last td:last a').bind('click', function(e) {
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
    var post_id = getURLParameter(href, 'id');
    var post_lang = getURLParameter(href, 'lang');
    var rosetta_id = getURLParameter(href, 'rosetta_id');
    var rosetta_lang = getURLParameter(href, 'rosetta_lang');
    var params = {
      f: 'removeTranslation',
      xd_check: dotclear.nonce,
      id: post_id,
      lang: post_lang,
      rosetta_id: rosetta_id,
      rosetta_lang: rosetta_lang
    };
    $.get('services.php', params, function(data) {
      if ($('rsp[status=failed]', data).length > 0) {
        // For debugging purpose only:
        // console.log($('rsp',data).attr('message'));
        window.console.log('Dotclear REST server error');
      } else {
        // ret -> status (true/false)
        // msg -> message to display
        var ret = Number($('rsp>rosetta', data).attr('ret'));
        var msg = $('rsp>rosetta', data).attr('msg');
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
    var post_id = getURLParameter(href, 'id');
    var post_lang = getURLParameter(href, 'lang');
    var post_type = getURLParameter(href, 'type');
    var rosetta_hidden = document.getElementById('rosetta_url');
    // Call popup_posts.php in order to select entry (post/page)
    //    rosetta_hidden.value = '';
    var p_win = window.open(
      'popup_posts.php?popup=1&plugin_id=rosetta&type=' + post_type, 'dc_popup',
      'alwaysRaised=yes,dependent=yes,toolbar=yes,height=500,width=760,' +
      'menubar=no,resizable=yes,scrollbars=yes,status=no');
    // Wait for popup close
    var timer = setInterval(function() {
      if (p_win.closed) {
        clearInterval(timer);
        // Get translation post/page id
        var rosetta_id = getURLParameter(rosetta_hidden.value, 'id');
        if (rosetta_id !== null && rosetta_id !== '') {

          // Reset hidden fields to prevent dirtying form
          rosetta_hidden.value = rosetta_hidden.defaultValue;

          var params = {
            f: 'addTranslation',
            xd_check: dotclear.nonce,
            id: post_id,
            lang: post_lang,
            rosetta_id: rosetta_id
          };
          $.get('services.php', params, function(data) {
            if ($('rsp[status=failed]', data).length > 0) {
              // For debugging purpose only:
              // console.log($('rsp',data).attr('message'));
              window.console.log('Dotclear REST server error');
            } else {
              // ret -> status (true/false)
              // msg -> message to display
              var ret = Number($('rsp>rosetta', data).attr('ret'));
              var msg = $('rsp>rosetta', data).attr('msg');
              if (ret) {
                // Append new row at the end of translations list
                addTranslationRow(post_id, post_lang, rosetta_id, '#rosetta-list');
              } else {
                // Display error message
                window.alert(msg);
              }
            }
          });
        }
      }
    }, 500);
    e.preventDefault();
  });

  // Switch to Ajax for adding translation link
  $('a.rosetta-new').click(function(e) {
    var href = $(this).attr('href');
    var post_id = getURLParameter(href, 'id');
    var post_lang = getURLParameter(href, 'lang');
    var post_type = getURLParameter(href, 'type');
    var rosetta_title = document.getElementById('rosetta_title');
    var rosetta_lang = document.getElementById('rosetta_lang');
    var edit_new = Number(getURLParameter(href, 'edit'));
    var p_win = window.open(
      'plugin.php?p=rosetta&popup_new=1&popup=1&plugin_id=rosetta' +
      '&type=' + post_type + '&id=' + post_id + '&lang=' + post_lang,
      'dc_popup',
      'alwaysRaised=yes,dependent=yes,toolbar=yes,height=500,width=760,' +
      'menubar=no,resizable=yes,scrollbars=yes,status=no');
    // Wait for popup close
    var timer = setInterval(function() {
      if (p_win.closed) {
        clearInterval(timer);
        // Get translation post/page title and lang
        if ((rosetta_title.value !== null && rosetta_title.value !== '') &&
          (rosetta_lang.value !== null && rosetta_lang.value !== '')) {

          var params = {
            f: 'newTranslation',
            xd_check: dotclear.nonce,
            id: post_id,
            lang: post_lang,
            type: post_type,
            rosetta_title: rosetta_title.value,
            rosetta_lang: rosetta_lang.value,
          };

          // Reset hidden fields to prevent dirtying form
          rosetta_title.value = rosetta_title.defaultValue;
          rosetta_lang.value = rosetta_lang.defaultValue;

          $.get('services.php', params, function(data) {
            if ($('rsp[status=failed]', data).length > 0) {
              // For debugging purpose only:
              // console.log($('rsp',data).attr('message'));
              window.console.log('Dotclear REST server error');
            } else {
              // ret -> status (true/false)
              // msg -> message to display
              var ret = Number($('rsp>rosetta', data).attr('ret'));
              var msg = $('rsp>rosetta', data).attr('msg');
              if (ret) {
                // Append new row at the end of translations list
                var rosetta_id = Number($('rsp>rosetta', data).attr('id'));
                addTranslationRow(post_id, post_lang, rosetta_id, '#rosetta-list');
                if (edit_new) {
                  // Redirect to new entry edition if requested
                  var edit = $('rsp>rosetta', data).attr('edit');
                  window.location.href = edit;
                }
              } else {
                // Display error message
                window.alert(msg);
              }
            }
          });
        }
      }
    }, 500);
    e.preventDefault();
  });

});
