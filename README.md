# Shaarli2Mastodon

This plugin allows you to automatically publish links you post on your Mastodon timeline.

It is largely inspired by [ArthurHoaro's shaarli2twitter](https://github.com/ArthurHoaro/shaarli2twitter) and uses an adapted version of [TootoPHP](https://framagit.org/MaxKoder/TootoPHP).

## Requirements

- PHP 5.3
- PHP cURL extension
- Shaarli >= v0.8.1 in public mode (which is the default mode)


## Installation
### 1. Create the application in Mastodon
On your Mastodon instance, go to *Preferences > Development > Your applications* and create a new one.

You can use whatever you want as *name* and *website* but if you have no idea, I suggest *shaarli2mastodon* and *https://github.com/kalvn/shaarli2mastodon*.

In *Scopes*, chose only *write* permission and validate.

Your new application should appear in the list. Click on it and copy the app token (the third entry) to your clipboard (CTRL+C).

### 2. Install the plugin
You must download and copy the files under `/plugins/shaarli2mastodon` directory of your Shaarli installation. There are several ways to do so. Here, I'll be using Git.

Run the following command from within the `/plugins` directory:

```bash
$ git clone https://github.com/kalvn/shaarli2mastodon
```

Make sure these new files are readable by your web server (Apache, Nginx, etc.).

Then, on your Shaarli instance, go to *Plugin administration* page and activate the plugin.

### 3. Configure the plugin
Your parameters from step 1 will be used here. After plugin activation, you'll see 5 parameters.

- **MASTODON_INSTANCE**: Your Mastodon instance, example: *mastodon.xyz*
- **MASTODON_APPTOKEN**: Mastodon application token, example: *rODeyYKXVXDq91ecGwTG6BI0yU5mLTSiPjFMv6uJ50I*
- **MASTODON_TOOT_MAX_LENGTH**: Defines the toots max length. By default it is 500 since it's the max length on most Mastodon instances.
- **MASTODON_TOOT_FORMAT**: The format of your toots. Available placeholders:
    + *${url}*: URL of link shared
    + *${permalink}*: permalink of the share
    + *${title}*: title of the share
    + *${description}*: description of the share
    + *${tags}*: tags of the share, prefixed with # to be valid Mastodon tags
    + *${cw}*: content warning. Everything which is before this placeholder will appear before the content warning (visible). Everything which is after this placeholder will appear after the content warning (hidden - you must unfold to see it).


## Tests

```bash
composer install
composer test
# or
./vendor/bin/phpunit tests
```

