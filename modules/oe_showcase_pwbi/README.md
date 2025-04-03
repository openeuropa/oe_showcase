## INTRODUCTION

This module creates a media and paragraph bundles to be used with the module
https://www.drupal.org/project/pwbi to manage report embedding from Power Bi.

This module creates a media using pwbi_embed_visual media type provided by pwbi.

  * Label: Power BI report media
    * Machine name: oe_media_pwbi
  * Media type: pwbi_embed_visual
  * Field type: pwbi_embed

It also creates a paragraph that will, reference the media created in oe_media_pwbi.

  * Label: Power BI
    * Machine name: oe_parapraph_pwbi
  * Reference field to Power BI report media
    * Label: Power BI media
    * Machine name: field_oe_power_bi_report

## INSTALLATION

Install https://www.drupal.org/project/pwbi:

```bash
composer require 'drupal/pwbi:^2.0'
```


https://www.drupal.org/project/pwbi needs the PowerBi client from
https://github.com/microsoft/PowerBI-JavaScript.

There are three options to install it:

### Preferred method:
PowerBi Embed provides a package.json to include the
powerbi-client library. Make sure you have NPM of Node.js
installed in your system and from the module folder, run `npm install`.

Alternatively, in your composer add the following script in your
post-install-cmd scripts in order to install the
dependencies with each `composer install`.
```json
  "scripts": {
    "post-install-cmd": [
      "npm install -C [path to module]"
    ]
  }
```
In the script above, it is assumed that the `pwbi`
module is installed in the `web/modules/contrib` directory as
a relative path from the project root or where your `composer.json`
is located. Adapt the path according to your installation.

#### Composer
You can use composer to download the powerbi js client by taking these steps:

1. Run the following command to ensure that you have the "composer/installers"
   package installed:

```
        composer require --prefer-dist composer/installers

```
2. Add the following to the "installer-paths" section of "composer.json":

```
        "libraries/{$name}": ["type:drupal-library"],
```
3. Add the following to the "repositories" section of "composer.json":
```
   {
       "type": "package",
       "package": {
           "name": "microsoft/powerbi",
           "version": "2.1.19",
           "type": "drupal-library",
           "dist": {
               "url": "https://github.com/microsoft/PowerBI-JavaScript/archive/refs/tags/v2.19.1.zip",
               "type": "zip"
           }
       }
   }
```
4. Run the following command; you should find that new directories have been
   created under "/libraries".
```
        composer require --prefer-dist microsoft/powerbi
```

## CONFIGURATION

1. Check https://www.drupal.org/project/pwbi to configure access to Power Bi
