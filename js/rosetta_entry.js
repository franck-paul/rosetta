/*global $, dotclear */
'use strict';

dotclear.mergeDeep(dotclear, dotclear.getData('rosetta_entry'));
dotclear.mergeDeep(dotclear, dotclear.getData('rosetta_type'));

$(() => {
  // Use $.toglleWithDetails with Dotclear 2.27+
  $.fn.toggleWithDetailsRosetta = function (s) {
    const target = this;
    const defaults = {
      unfolded_sections: dotclear.unfolded_sections,
      hide: true, // Is section unfolded?
      fn: false, // A function called on first display,
      user_pref: false,
      reverse_user_pref: false, // Reverse user pref behavior
    };
    const p = $.extend(defaults, s);
    if (p.user_pref && p.unfolded_sections !== undefined && p.user_pref in p.unfolded_sections) {
      p.hide = p.reverse_user_pref;
    }
    const toggle = () => {
      if (!p.hide && p.fn) {
        p.fn.apply(target);
        p.fn = false;
      }
      p.hide = !p.hide;
    };
    return this.each(() => {
      $(target).on('toggle', (e) => {
        if (p.user_pref) {
          dotclear.jsonServicesPost('setSectionFold', () => {}, {
            section: p.user_pref,
            value: p.hide ^ p.reverse_user_pref ? 1 : 0,
          });
        }
        toggle();
        e.preventDefault();
        return false;
      });
      toggle();
    });
  };

  $('#edit-entry').on('onetabload', () => {
    // Add toggle capability on Rosetta area
    $('#rosetta-details').toggleWithDetailsRosetta({
      user_pref: 'dcx_post_rosetta',
      hide: $('#rosetta-list tbody').children().length === 0 ? false : true,
    });
  });

  function getURLParameter(url, name) {
    // Extract param value from URL
    const results = new RegExp(`[\?&]${name}=([^&#]*)`).exec(url);
    return results === null ? null : results[1] || 0;
  }

  function addTranslationRow(post_id, post_lang, rosetta_id, table) {
    dotclear.jsonServicesPost(
      'getTranslationRow',
      (data) => {
        // ret -> status (true/false)
        // msg -> line to add
        if (!data.ret) {
          return;
        }
        // Append the new line at the end of the table
        $(table).append(data.msg);
        // Bind removing translation function
        $(`${table} tr:last td:last a`).on('click', function (e) {
          removeTranslation($(this));
          e.preventDefault();
        });
        return true;
      },
      {
        id: post_id,
        lang: post_lang,
        rosetta_id,
      },
    );

    return null;
  }

  function removeTranslation(link) {
    if (!window.confirm(dotclear.msg.confirm_remove_rosetta)) {
      return false;
    }

    const href = link.attr('href');
    const row = link.parent().parent();

    dotclear.jsonServicesPost(
      'removeTranslation',
      (data) => {
        // ret -> status (true/false)
        // msg -> message to display
        if (data.ret) {
          // Remove corresponding line in table
          row.remove();
        } else {
          // Display error message
          window.alert(data.msg);
        }
      },
      {
        id: getURLParameter(href, 'id'),
        lang: getURLParameter(href, 'lang'),
        rosetta_id: getURLParameter(href, 'rosetta_id'),
        rosetta_lang: getURLParameter(href, 'rosetta_lang'),
      },
    );
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

        dotclear.jsonServicesPost(
          'addTranslation',
          (data) => {
            // ret -> status (true/false)
            // msg -> message to display
            if (data.ret) {
              // Append new row at the end of translations list
              addTranslationRow(post_id, post_lang, rosetta_id, '#rosetta-list');
            } else {
              // Display error message
              window.alert(data.msg);
            }
          },
          {
            id: post_id,
            lang: post_lang,
            rosetta_id,
          },
        );
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
          dotclear.jsonServicesPost(
            'newTranslation',
            (data) => {
              // ret -> status (true/false)
              // msg -> message to display
              if (data.ret) {
                // Append new row at the end of translations list
                addTranslationRow(post_id, post_lang, data.id, '#rosetta-list');
                if (edit_new) {
                  // Redirect to new entry edition if requested
                  window.location.href = data.edit;
                }
                // Reset hidden fields to prevent dirtying form
                rosetta_title.value = rosetta_title.defaultValue;
                rosetta_lang.value = rosetta_lang.defaultValue;
                return;
              }
              // Reset hidden fields to prevent dirtying form
              rosetta_title.value = rosetta_title.defaultValue;
              rosetta_lang.value = rosetta_lang.defaultValue;
              // Display error message
              window.alert(data.msg);
            },
            {
              id: post_id,
              lang: post_lang,
              type: post_type,
              rosetta_title: rosetta_title.value,
              rosetta_lang: rosetta_lang.value,
            },
          );
        }
      }
    }, 500);
    e.preventDefault();
  });
});
