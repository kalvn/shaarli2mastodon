<?php
class Utils {

  /**
   * Determines whether the configuration is valid or not.
   *
   * @param  ConfigManager    $conf   Configuration instance.
   *
   * @return boolean                  Whether the config is valid or not.
   */
  public static function isConfigValid ($conf) {
    $mandatory = array(
      'MASTODON_INSTANCE',
      'MASTODON_APPTOKEN',
    );
    foreach ($mandatory as $value) {
      $setting = $conf->get('plugins.'. $value);
      if (empty($setting)) {
        return false;
      }
    }
    return true;
  }

  /**
   * Determines if the link is a note.
   * @param  array  $link The link to check.
   * @return boolean      Whether the link is a note or not.
   */
  public static function isLinkNote ($link) {
    return !preg_match('/^http[s]?:/', $link['url']);
  }
}
