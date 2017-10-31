<?php

/**
 * shaarli2mastodon
 *
 * Automatically publishes your new Shaarli links to your Mastodon timeline.
 * Get Shaarli at https://github.com/shaarli/shaarli
 *
 * Uses TootoPHP - https://framagit.org/MaxKoder/TootoPHP
 * Largely inspired by ArthurHoaro's shaarli2twitter - https://github.com/ArthurHoaro/shaarli2twitter
 *
 * See README.md for instructions.
 *
 * @author kalvn <kalvnthereal@gmail.com>
 */

//require 'tootophp/autoload.php';
//require_once 'mastodonapi/Mastodon_api.php';
require_once 'Mastodon/Mastodon.php';

/**
 * Maximum length for a toot.
 */
const TOOT_LENGTH = 500;
/**
 * In Mastodon, URL count for 23 characters.
 * https://github.com/tootsuite/mastodon/pull/4427/commits
 */
const URL_LENGTH = 23;
/**
 * The default toot format if none is specified.
 */
const DEFAULT_FORMAT = '#Shaarli: ${title} ${url} ${tags}';
/**
 * Authorized placeholders.
 */
const ALLOWED_PLACEHOLDERS = array('url', 'permalink', 'title', 'tags', 'description');

/**
 * Init function: check settings, and set default format.
 *
 * @param ConfigManager $conf instance.
 *
 * @return array|void Error if config is not valid.
 */
function shaarli2mastodon_init($conf)
{
    $format = $conf->get('plugins.MASTODON_TOOT_FORMAT');
    if (empty($format)) {
        $conf->set('plugins.MASTODON_TOOT_FORMAT', DEFAULT_FORMAT);
    }

    if (!isConfigValid($conf)) {
        return array('Please set up your Mastodon parameters in plugin administration page.');
    }
}

/**
 * Add the JS file: disable the toot button if the link is set to private.
 *
 * @param array         $data New link values.
 * @param ConfigManager $conf instance.
 *
 * @return array $data with the JS file.
 */
function hook_shaarli2mastodon_render_footer($data, $conf)
{
    if ($data['_PAGE_'] == Router::$PAGE_EDITLINK) {
        $data['js_files'][] = PluginManager::$PLUGINS_PATH . '/shaarli2mastodon/shaarli2mastodon.js';
    }
    return $data;
}

/**
 * Hook save link: will automatically publish a tweet when a new public link is shaared.
 *
 * @param array         $data New link values.
 * @param ConfigManager $conf instance.
 *
 * @return array $data not altered.
 */
function hook_shaarli2mastodon_save_link($data, $conf)
{
    // No toot without config, for private links, or on edit.
    if (!isConfigValid($conf)
        || (isset($data['updated']) && $data['updated'] != false)
        || $data['private']
        || !isset($_POST['toot'])
    ) {
        return $data;
    }

    // We make sure not to alter data
    $link = $data;

    // We will use an array to generate hashtags, then restore original shaare tags.
    $data['tags'] = array_values(array_filter(explode(' ', $data['tags'])));
    for ($i = 0, $c = count($data['tags']); $i < $c; $i++) {
        $data['tags'][$i] = '#'. $data['tags'][$i];
    }

    $data['permalink'] = index_url($_SERVER) . '?' . $data['shorturl'];

    // If the link is a note, we use the permalink as the url.
    if(isLinkNote($data)){
        $data['url'] = $data['permalink'];
    }

    $format = $conf->get('plugins.MASTODON_TOOT_FORMAT', DEFAULT_FORMAT);
    $toot = formatToot($data, $format);
    $response = toot($conf, $toot);

    // If an error has occurred, not blocking: just log it.
    if (isset($response['error'])) {
        error_log('Mastodon API error: '. $response['error']);
    }

    return $link;
}

/**
 * Hook render_editlink: add a checkbox to toot the new link or not.
 *
 * @param array         $data New link values.
 * @param ConfigManager $conf instance.
 *
 * @return array $data with `edit_link_plugin` placeholder filled.
 */
function hook_shaarli2mastodon_render_editlink($data, $conf)
{
    if (! $data['link_is_new'] || ! isConfigValid($conf)) {
        return $data;
    }

    $private = $conf->get('privacy.default_private_links', false);

    $html = file_get_contents(PluginManager::$PLUGINS_PATH . '/shaarli2mastodon/edit_link.html');
    $html = sprintf($html, $private ? '' : 'checked="checked"');

    $data['edit_link_plugin'][] = $html;

    return $data;
}

/**
 * Posts the toot to Mastodon.
 * 
 * @param  ConfigManager    $conf Configuration instance.
 * @param  string           $toot The toot to post. It must respect Mastodon restrictions.
 * 
 * @return void
 */
function toot($conf, $toot){
    $mastodonInstance = $conf->get('plugins.MASTODON_INSTANCE', false);
    $appId = $conf->get('plugins.MASTODON_APPID', false);
    $appSecret = $conf->get('plugins.MASTODON_APPSECRET', false);
    $appToken = $conf->get('plugins.MASTODON_APPTOKEN', false);

    $mastodonApi = new Mastodon($mastodonInstance, array(
        'bearer' => $appToken
    ));

    $mastodonApi->authentify($appId, $appSecret);

    return $mastodonApi->postStatus($toot);
}

/**
 * Format a string according the the given template.
 * 
 * @param  array    $link   The link to format, in an array format.
 * @param  string   $format The template.
 * @return string           The input string formatted with the given template.
 */
function formatToot($link, $format){
    $priorities = ALLOWED_PLACEHOLDERS;

    $toot = $format;
    foreach ($priorities as $priority) {
        if (strlen($toot) >= TOOT_LENGTH) {
            return removeRemainingPlaceholders($toot);
        }

        $toot = replacePlaceholder($toot, $priority, $link[$priority]);
    }

    $toot = str_replace('\n', "\n", $toot);

    return $toot;
}

/**
 * Replaces a single placeholder with its value.
 * 
 * @param  string $toot        The toot.
 * @param  string $placeholder The placeholder id.
 * @param  string $value       The value to replace the placeholder with.
 * 
 * @return string              The input string with placeholder replaced with value.
 */
function replacePlaceholder($toot, $placeholder, $value){
    if (is_array($value)) {
        return replacePlaceholderArray($toot, $placeholder, $value);
    }

    $currentLength = getTootLength($toot);
    $valueLength = $placeholder === 'permalink' || $placeholder === 'url' ? URL_LENGTH : strlen($value);

    if($currentLength + $valueLength > TOOT_LENGTH){
        $value = mb_strcut($value, 0, TOOT_LENGTH - $currentLength - 4) . '…';
    }

    return str_replace('${' . $placeholder . '}', $value, $toot);
}

/**
 * Replaces a single placeholder with its array value.
 * 
 * @param  string $toot        The toot.
 * @param  string $placeholder The placeholder id.
 * @param  array  $value       The value to replace the placeholder with, each item separated with a space.
 * 
 * @return string              The input string with placeholder replaced with value.
 */
function replacePlaceholderArray($toot, $placeholder, $value){
    $items = '';

    for ($i = 0, $c = count($value); $i < $c; $i++) {
        $currentLength = getTootLength($toot);
        $space = $i == 0 ? '' : ' ';
        if ($currentLength + strlen($items) + strlen($value[$i] . $space) > TOOT_LENGTH) {
            break;
        }
        $items .= $space . $value[$i];
    }

    return str_replace('${'. $placeholder .'}', $items, $toot);
}

/**
 * Remove remaining placeholders from a string.
 * 
 * @param  string $toot The string.
 * 
 * @return string       The input string with placeholder removed.
 */
function removeRemainingPlaceholders($toot){
    return preg_replace('#\${(' . implode('|', ALLOWED_PLACEHOLDERS) . ')}#', '', $toot);
}

/**
 * Calculates the length of a toot respecting Mastodon rules.
 * For example, URL count as 23 characters.
 * 
 * @param  string $toot The toot.
 * 
 * @return int          The length of the toot.
 */
function getTootLength($toot){
    $urlMockup = '';
    for($i = 0; $i < URL_LENGTH ; $i++){
        $urlMockup .= 'a';
    }

    // URL detection regex taken from https://stackoverflow.com/a/16481681/2086437
    $toot = preg_replace('#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#si', $urlMockup, $toot);

    return strlen(removeRemainingPlaceholders($toot));
}

/**
 * Determines whether the configuration is valid or not.
 * 
 * @param  ConfigManager    $conf   Configuration instance.
 * 
 * @return boolean                  Whether the config is valid or not.
 */
function isConfigValid($conf){
    $mandatory = array(
        'MASTODON_INSTANCE',
        'MASTODON_APPID',
        'MASTODON_APPSECRET',
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
function isLinkNote($link){
    return $link['shorturl'] === substr($link['url'], 1);
}