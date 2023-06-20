/*global $, dotclear */
'use strict';

dotclear.mergeDeep(dotclear, dotclear.getData('rosetta_entry'));
dotclear.mergeDeep(dotclear, dotclear.getData('rosetta_type'));

$(() => {
  $('#edit-entry').on('onetabload', () => {
    // Add toggle capability on Rosetta area
    $('#rosetta-area > label').toggleWithLegend($('#rosetta-area').children().not('label'), {
      user_pref: 'dcx_post_rosetta',
      legend_click: true,
      hide: false,
    });
  });

  function getURLParameter(url, name) {
    // Extract param value from URL
    const results = new RegExp(`[\?&]${name}=([^&#]*)`).exec(url);
    return results === null ? null : results[1] || 0;
  }

  function addTranslationRow(post_id, post_lang, rosetta_id, table) {
    $.get('services.php', {
      f: 'getTranslationRow',
      xd_check: dotclear.nonce,
      id: post_id,
      lang: post_lang,
      rosetta_id,
    })
      .done((data) => {
        if ($('rsp[status=failed]', data).length > 0) {
          // For debugging purpose only:
          // console.log($('rsp',data).attr('message'));
          window.console.log('Dotclear REST server error');
          return;
        }
        // ret -> status (true/false)
        // msg -> message to display
        const ret = Number($('rsp>rosetta', data).attr('ret'));
        if (!ret) {
          return;
        }
        // Append the new line at the end of the table
        const msg = $('rsp>rosetta', data).attr('msg');
        $(table).append(msg);
        // Bind removing translation function
        $(`${table} tr:last td:last a`).bind('click', function (e) {
          removeTranslation($(this));
          e.preventDefault();
        });
        return true;
      })
      .fail((jqXHR, textStatus, errorThrown) => {
        window.console.log(`AJAX ${textStatus} (status: ${jqXHR.status} ${errorThrown})`);
      })
      .always(() => {
        // Nothing here
      });
    return null;
  }

  function removeTranslation(link) {
    if (!window.confirm(dotclear.msg.confirm_remove_rosetta)) {
      return false;
    }
    const href = link.attr('href');
    const row = link.parent().parent();
    const post_id = getURLParameter(href, 'id');
    const post_lang = getURLParameter(href, 'lang');
    const rosetta_id = getURLParameter(href, 'rosetta_id');
    const rosetta_lang = getURLParameter(href, 'rosetta_lang');
    $.get('services.php', {
      f: 'removeTranslation',
      xd_check: dotclear.nonce,
      id: post_id,
      lang: post_lang,
      rosetta_id,
      rosetta_lang,
    })
      .done((data) => {
        if ($('rsp[status=failed]', data).length > 0) {
          // For debugging purpose only:
          // console.log($('rsp',data).attr('message'));
          window.console.log('Dotclear REST server error');
          return;
        }
        // ret -> status (true/false)
        // msg -> message to display
        const ret = Number($('rsp>rosetta', data).attr('ret'));
        const msg = $('rsp>rosetta', data).attr('msg');
        if (ret) {
          // Remove corresponding line in table
          row.remove();
        } else {
          // Display error message
          window.alert(msg);
        }
      })
      .fail((jqXHR, textStatus, errorThrown) => {
        window.console.log(`AJAX ${textStatus} (status: ${jqXHR.status} ${errorThrown})`);
      })
      .always(() => {
        // Nothing here
      });
  }

  // Switch to Ajax for removing translation link
  $('a.rosetta-remove').on('click', function (e) {
    removeTranslation($(this));
    e.preventDefault();
  });

  // Switch to Ajax for adding translation link
  $('a.rosetta-add').on('click', function (e) {
    const href = $(this).attr('href');
    const post_id = getURLParameter(href, 'id');
    const post_lang = getURLParameter(href, 'lang');
    const post_type = getURLParameter(href, 'type');
    const rosetta_hidden = document.getElementById('rosetta_url');
    // Call popup_posts.php in order to select entry (post/page)
    //    rosetta_hidden.value = '';
    const p_win = window.open(
      `${dotclear.rosetta.popup_posts_url}${post_type}`,
      'dc_popup',
      'alwaysRaised=yes,dependent=yes,toolbar=yes,height=500,width=760,menubar=no,resizable=yes,scrollbars=yes,status=no',
    );
    // Wait for popup close
    const timer = setInterval(() => {
      if (!p_win.closed) {
        return;
      }
      clearInterval(timer);
      // Get translation post/page id
      const rosetta_id = getURLParameter(rosetta_hidden.value, 'id');
      if (rosetta_id !== null && rosetta_id !== '') {
        // Reset hidden fields to prevent dirtying form
        rosetta_hidden.value = rosetta_hidden.defaultValue;

        $.get('services.php', {
          f: 'addTranslation',
          xd_check: dotclear.nonce,
          id: post_id,
          lang: post_lang,
          rosetta_id,
        })
          .done((data) => {
            if ($('rsp[status=failed]', data).length > 0) {
              // For debugging purpose only:
              // console.log($('rsp',data).attr('message'));
              window.console.log('Dotclear REST server error');
              return;
            }
            // ret -> status (true/false)
            // msg -> message to display
            const ret = Number($('rsp>rosetta', data).attr('ret'));
            const msg = $('rsp>rosetta', data).attr('msg');
            if (ret) {
              // Append new row at the end of translations list
              addTranslationRow(post_id, post_lang, rosetta_id, '#rosetta-list');
            } else {
              // Display error message
              window.alert(msg);
            }
          })
          .fail((jqXHR, textStatus, errorThrown) => {
            window.console.log(`AJAX ${textStatus} (status: ${jqXHR.status} ${errorThrown})`);
          })
          .always(() => {
            // Nothing here
          });
      }
    }, 500);
    e.preventDefault();
  });

  // Switch to Ajax for adding translation link
  $('a.rosetta-new').on('click', function (e) {
    const href = $(this).attr('href');
    const post_id = getURLParameter(href, 'id');
    const post_lang = getURLParameter(href, 'lang');
    const post_type = getURLParameter(href, 'type');
    const rosetta_title = document.getElementById('rosetta_title');
    const rosetta_lang = document.getElementById('rosetta_lang');
    const edit_new = Number(getURLParameter(href, 'edit'));
    const p_win = window.open(
      `${dotclear.rosetta.plugin_url}&type=${post_type}&id=${post_id}&lang=${post_lang}`,
      'dc_popup',
      'alwaysRaised=yes,dependent=yes,toolbar=yes,height=500,width=760,menubar=no,resizable=yes,scrollbars=yes,status=no',
    );
    // Wait for popup close
    const timer = setInterval(() => {
      if (p_win.closed) {
        clearInterval(timer);
        // Get translation post/page title and lang
        if (
          rosetta_title.value !== null &&
          rosetta_title.value !== '' &&
          rosetta_lang.value !== null &&
          rosetta_lang.value !== ''
        ) {
          $.get('services.php', {
            f: 'newTranslation',
            xd_check: dotclear.nonce,
            id: post_id,
            lang: post_lang,
            type: post_type,
            rosetta_title: rosetta_title.value,
            rosetta_lang: rosetta_lang.value,
          })
            .done((data) => {
              if ($('rsp[status=failed]', data).length > 0) {
                // For debugging purpose only:
                // console.log($('rsp',data).attr('message'));
                window.console.log('Dotclear REST server error');
                return;
              }
              // ret -> status (true/false)
              // msg -> message to display
              const ret = Number($('rsp>rosetta', data).attr('ret'));
              const msg = $('rsp>rosetta', data).attr('msg');
              if (ret) {
                // Append new row at the end of translations list
                const rosetta_id = Number($('rsp>rosetta', data).attr('id'));
                addTranslationRow(post_id, post_lang, rosetta_id, '#rosetta-list');
                if (edit_new) {
                  // Redirect to new entry edition if requested
                  const edit = $('rsp>rosetta', data).attr('edit');
                  window.location.href = edit;
                }
                return;
              }
              // Display error message
              window.alert(msg);
            })
            .fail((jqXHR, textStatus, errorThrown) => {
              window.console.log(`AJAX ${textStatus} (status: ${jqXHR.status} ${errorThrown})`);
            })
            .always(() => {
              // Reset hidden fields to prevent dirtying form
              rosetta_title.value = rosetta_title.defaultValue;
              rosetta_lang.value = rosetta_lang.defaultValue;
            });
        }
      }
    }, 500);
    e.preventDefault();
  });
});
