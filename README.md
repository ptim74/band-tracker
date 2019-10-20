# band-tracker
Clash of Clans donation tracker for BAND

## Requirements

- PHP with openssl extension enabled
- Clash of Clans API key from https://developer.clashofclans.com
- BAND Access Token from https://developers.band.us/

**Note:** Both CoC API and BAND API requires static IP address.

## Install PHP

### Install PHP on Windows

- Download PHP from https://windows.php.net/download/.
- If unsure which file to download choose latest Non Thread Safe zip file.
- Unzip file to c:\php.
- Copy c:\php\php.ini-development to c:\windows\php.ini.
- Uncomment following line **;extension=openssl** by removing the **;**-character.
- Save modified c:\windows\php.ini.

### Install PHP on Linux

- You can install PHP with package management tools.

## Install Tracker

```
git clone https://github.com/ptim74/band-tracker
```

## Configure Tracker

Copy config.sample.php to config.php.

### Getting Band API key

- Go to https://developers.band.us/develop/myapps/list
- Click Register

Service Name can be anything and Category should be Games.
Band API requires Redirect URL and if you have an URL for your computer you can use that.

If you don't have URL for your computer you can use IP-address (Google: what is my ip),
and then you can put the IP-address as your Redirect URL, for example: http://192.168.43.54

- Agree the terms and click OK.

Now your App should be listed in My Apps.

- Click name of the app you created.

To get Access Token you need to click Connect BAND Account -button.

You can either allow the app to use all of your Bands or you can select which Bands the app can access. 

- Click Agree after you have decided which Bands to allow.

Now you can copy the Access Token value and put it as the value of band_access_token in your config.php file (replacing text PUT_YOUR_BAND_ACCESS_TOKEN_HERE).

### Getting Clash of Clans API key

- Go to https://developer.clashofclans.com
- Register or sign in depending on whether you already have an accound or not.

- Click My Account.
- Click Create New Key.

Key name and description can be whatever you want.

Allowed IP Adresses should contain your public IP (Google: what is my ip).

- Click Create Key.
- Click the key you created from My Keys list.

Now you can copy the Token value and put it as the value of clash_api_token in your config.php file (replacing text PUT_YOUR_CLASH_API_TOKEN_HERE).

### Configuring Other Settings

If you want to run the tracker continuously every minute, then set run_continuously to 1 in config.php. If you want to run the tracker only once, then set run_continuously to 0.

If you want to see the list of available band keys, then set the value of list_bands_on_startup to 1. If you don't want to see the list anymore, then set the value of list_bands_on_startup to 0.

Sample config of global settings:

```php
$config = (object) [
    'clash_api_token' => 'eyJ0eXAi ... KV1zBi7j',
    'band_access_token' => 'ZQAAAX ... Fo6j2teB',
    'data_dir' => 'data',
    'run_continuously' => 1,
    'list_bands_on_startup' => 1
];
```

### Configuring Clans

Sample config contains settings for two clans, but you can track only one clan or as many clans as you want.

Put your clan's tag in the tag value of the clan settings (replacing text #PUT_CLANTAG_OF_FIRST_CLAN_HERE). Clan tag should start with the #-sign, for example: #LGQJYLPY.

For the band_key you need first to run the tracker once with the list_bands_on_startup value set to 1 in config.php.
After you have the band key list you can copy the value of the key to the band_key value of your clan.

You can either have a separate post for every tracking message (use_comments = 0), 
or you can have all tracking messages sent as comments under one single post (use_comments = 1).

Sample config for clan:

```php
$config->clans[] = (object) [
    'tag' => '#LGQJYLPY',
    'band_key' => 'AAB0fUBqx94pzPffkOnvr3qP',
    'use_comments' => 1
];
```

## Running Tracker

### Running the Tracker in Command Line

```
cd band-tracker
c:\php\php.exe tracker.php
```

### Running the Tracker in VS Code (for Development)

Install PHP Debug (by Felix Becker) extension.
- View > Extensions
- Search: PHP Debug
- Find PHP Debug made by Felix Becker
- Click Install

Configure PHP Executable Path
- File > Preferences > Settings
- User > Extensions > PHP
- Executable Path: edit setting in settings.json
- Add "php.validate.executablePath": "c:/php/php.exe" to settings.json

Now you can run the Tracker by clicking F5 in VS Code.





