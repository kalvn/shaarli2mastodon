# Shaarli2Mastodon

This plugin allows you to automatically publish links you post on your Mastodon timeline.

It is largely inspired by [ArthurHoaro's shaarli2twitter](https://github.com/ArthurHoaro/shaarli2twitter) and uses an adapted version of [TootoPHP](https://framagit.org/MaxKoder/TootoPHP).

## Requirements

- PHP 5.3
- PHP cURL extension
- Shaarli >= v0.8.1

## Installation
### 1. Create the application in Mastodon
On your Mastodon instance, go to *Preferences > Development > Your applications* and create a new one.

You can use whatever you want as *name* and *website* but if you have no idea, I suggest *shaarli2mastodon* and *https://github.com/kalvn/shaarli2mastodon*. You need to remember those parameters for next step.

In *Scopes*, chose only *write* permission and validate.

Your new application should appear in the list. Click on it and keep the first 3 settings visible, you'll need them for next step.

### 2. Install the plugin
You must download and copy the files under `/plugins/shaarli2mastodon` directory of your Shaarli installation. There are several ways to do so. Here, I'll be using Git.

Run the following command from within the `/plugins` directory:

```bash
$ git clone https://github.com/kalvn/shaarli2mastodon
```

Then, on your Shaarli instance, go to *Plugin administration* page and activate the plugin.

### 3. Configure the plugin
Your parameters from step 1 will be used here. After plugin activation, you'll see 5 parameters.

- **MASTODON_INSTANCE**: Your Mastodon instance, example: *mastodon.xyz*
- **MASTODON_APPID**: Mastodon application ID
- **MASTODON_APPSECRET**: Mastodon application secret
- **MASTODON_APPTOKEN**: Mastodon application token
- **MASTODON_TOOT_FORMAT**: The format of your toots. Available placeholders:
    + *${url}*: URL of link shared
    + *${permalink}*: permalink of the share
    + *${title}*: title of the share
    + *${description}*: description of the share
    + *${tags}*: tags of the share, prefixed with # to be valid Mastodon tags



## Tests
After you installed PHPUnit, you can run unit tests with:

```bash
$ phpunit tests
```

