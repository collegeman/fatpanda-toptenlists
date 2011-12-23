<?php
/*
Plugin Name: Top Ten Lists by Fat Panda
Description: Quickly assemble top ten list posts from your own content and content from around the Web.
Version: 1.0
Author: Aaron Collegeman, Fat Panda
Author URI: http://aaroncollegeman.com/fatpanda
Plugin URI: http://github.com/collegeman/fatpanda-toptenlists
*/

class FatPandaTopTenLists {

  private static $plugin;
  static function load() {
    $class = __CLASS__; 
    return ( self::$plugin ? self::$plugin : ( self::$plugin = new $class() ) );
  }

  private function __construct() {
    add_action( 'init', array( $this, 'init' ), 10 );
  }

  function init() {
    if ( is_admin() ) {
      add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }
  }

  function admin_menu() {  
    add_submenu_page( 'edit.php', 'Create List...', 'Create List...', 'level_1', __CLASS__, array( $this, 'form' ) ); 
  }

  function form() {
    if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
      if (!wp_verify_nonce($_POST['_wpnonce'], __CLASS__)) {
        die("Invalid nonce.");
      }

    }

    ?>  
      <div class="wrap">
        <?php screen_icon() ?>
        <h2>Create New Top Content List</h2>
        <form id="<?php echo __CLASS__ ?>" action="<?php echo admin_url('edit.php') ?>?page=<?php echo __CLASS__ ?>" method="post">
          <div class="list">
            <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce(__CLASS__) ?>" />
            
            <ul class="urls"></ul>

            <div class="template" style="display:none;">
              <span class="cnt"></span>
              <input type="text" class="url" name="url[]" value="" />
              <a href="#" class="rem button" onclick="TopTenLists.remove(this); return false;">remove</a>
            </div>

            <p>
              <a href="#" class="button" onclick="TopTenLists.addAnotherUrl('<?php bloginfo('siteurl') ?>/'); return false;">Add Another URL</a>
              &nbsp;<a href="#" class="button" onclick="TopTenLists.switchToPaste(); return false;">Paste Multiple...</a>
            </p>

            <p style="padding-top:15px;">
              <input type="submit" class="button-primary" value="<?php esc_attr_e('Create Post') ?>..." />
              &nbsp;&nbsp;<a href="<?php echo admin_url('edit.php') ?>" onclick="return confirm('Are you sure you want to cancel?');">Cancel</a>
            </p>
          </div>
          <div class="paste" style="display:none;">
            <p>Paste in one URL per line.</p>
            <textarea class="multiple"></textarea>
            <p>
              <input type="button" class="button-primary" value="<?php esc_attr_e('Paste') ?>" onclick="TopTenLists.paste(); return false;" />
              &nbsp;&nbsp;<a href="#" onclick="TopTenLists.cancelPaste(); return false;">Cancel</a>
            </p>
          </div>
        </form>
      </div>

      <style>
        ul.urls span.cnt { display:inline-block; font-size: 2em; width: 1.2em; text-align:right; margin: 0 10px 0 0; }  
        ul.urls li { list-style-type: none; margin: 0; padding: 0 0 10px 0; }
        ul.urls .url { width: 50%; font-size: 2em; color: #ddd; display: inline-block; }
        ul.urls .url:focus { color: black; }
        ul.urls .rem { display: inline-block; position:relative; top:-4px; left: 5px; }
        .paste .multiple { width: 50%; height: 300px; }
      </style>

      <script>
        (function($) {
          var form = $('#<?php echo __CLASS__ ?>');

          var self = window.TopTenLists = {

            paste: function() {
              var list = [];
              var raw = $.trim(form.find('.multiple').val());

              if (raw.length) {
                list = $.trim(form.find('.multiple').val()).split("\n").map(function(url) {
                  return $.trim(url);
                });
              }

              if (list.length) {
                // remove all of the empty URL fields
                var urls = form.find('.urls .url').filter(function() {
                  var val = $.trim($(this).val());
                  return val == '' || val == '<?php bloginfo('siteurl') ?>/';
                });
                urls.closest('li').remove();
                $.each(list, function(i, url) {
                  if (url.length) {
                    self.addAnotherUrl(url);
                  }
                });
              }

              self.cancelPaste();
            },
            
            cancelPaste: function() {
              form.find('.paste').hide();
              form.find('.multiple').val('');
              form.find('.list').show();
            },

            switchToPaste: function() {
              form.find('.list').hide();
              form.find('.paste').show();
              form.find('.multiple').focus();
            },

            remove: function (a) {
              $(a).closest('li').remove();
              self.resetNumbering();
            },

            resetNumbering: function() {
              for (var i=0; i<form.find('.url').size(); i++) {
                form.find('.urls li:eq('+i+') .cnt').text(i+1);
              }
            },

            addAnotherUrl: function(url) {
              var li = $('<li></li>');
              li.html(form.find('.template').clone().html());
              li.appendTo(form.find('.urls'));
              var input = form.find('.urls .url:last');
              if (url != undefined) {
                input.val(url);
              }
              input.focus();
              self.resetNumbering();
            }

          };


          for (var i=0; i<10; i++) TopTenLists.addAnotherUrl('<?php bloginfo('siteurl') ?>/');
          form.find('.url:first').focus();
        })(jQuery);
      </script>
    <?php
  }

  // ===========================================================================
  // Helper functions - Provided to your plugin, courtesy of wp-kitchensink
  // http://github.com/collegeman/wp-kitchensink
  // ===========================================================================
  
  /**
   * This function provides a convenient way to access your plugin's settings.
   * The settings are serialized and stored in a single WP option. This function
   * opens that serialized array, looks for $name, and if it's found, returns
   * the value stored there. Otherwise, $default is returned.
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  function setting($name, $default = null) {
    $settings = get_option(sprintf('%s_settings', __CLASS__), array());
    return isset($settings[$name]) ? $settings[$name] : $default;
  }

  /**
   * Use this function in conjunction with Settings pattern #3 to generate the
   * HTML ID attribute values for anything on the page. This will help
   * to ensure that your field IDs are unique and scoped to your plugin.
   *
   * @see settings.php
   */
  function id($name, $echo = true) {
    $id = sprintf('%s_settings_%s', __CLASS__, $name);
    if ($echo) {
      echo $id;
    }
    return $id;
  }

  /**
   * Use this function in conjunction with Settings pattern #3 to generate the
   * HTML NAME attribute values for form input fields. This will help
   * to ensure that your field names are unique and scoped to your plugin, and
   * named in compliance with the setting storage pattern defined above.
   * 
   * @see settings.php
   */
  function field($name, $echo = true) {
    $field = sprintf('%s_settings[%s]', __CLASS__, $name);
    if ($echo) {
      echo $field;
    }
    return $field;
  }
  
  /**
   * A helper function. Prints 'checked="checked"' under two conditions:
   * 1. $field is a string, and $this->setting( $field ) == $value
   * 2. $field evaluates to true
   */
  function checked($field, $value = null) {
    if ( is_string($field) ) {
      if ( $this->setting($field) == $value ) {
        echo 'checked="checked"';
      }
    } else if ( (bool) $field ) {
      echo 'checked="checked"';
    }
  }

  /**
   * A helper function. Prints 'selected="selected"' under two conditions:
   * 1. $field is a string, and $this->setting( $field ) == $value
   * 2. $field evaluates to true
   */
  function selected($field, $value = null) {
    if ( is_string($field) ) {
      if ( $this->setting($field) == $value ) {
        echo 'selected="selected"';
      }
    } else if ( (bool) $field ) {
      echo 'selected="selected"';
    }
  }
  
}

#
# Initialize our plugin
#
FatPandaTopTenLists::load();
