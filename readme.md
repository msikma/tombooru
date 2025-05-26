[![MIT license](https://img.shields.io/badge/license-MIT-brightgreen.svg)](https://opensource.org/licenses/MIT)

# Tombooru

A Booru style imageboard extension for MediaWiki, originally developed for the [Tomba Club Wiki](https://tomba.club/wiki/).

This extension is tightly coupled with that wiki's custom visual theme and does not work properly without it.

## Installation

Download the **Tombooru** directory to your extensions directory, and enable it via your **LocalSettings.php**:

```php
wfLoadExtension('Tombooru');
```

To finalize the installation and insert the database tables, run the maintenance script from inside your MediaWiki directory:

```bash
php maintenance/run.php update
```

### Settings

Tombooru uses the special `tombooru-admin` permission to do admin work. To give the permission to all sysops:

```php
$wgGroupPermissions['sysop']['tombooru-admin'] = true;
```

The imageboard includes optional settings to moderate explicit and AI generated content.

```php
$wgTombooruGenAIPolicy = 0;
$wgTombooruEnableExplicitContent = false;
```

See the [config.md](/config.md) file for more information.

### Features

After installation, the extension does the following:

* adds a new [special page](https://www.mediawiki.org/wiki/Manual:Special_pages) at `Special:Tombooru`;
* adds a new [namespace](https://www.mediawiki.org/wiki/Help:Namespaces) called `Tombooru_data` (ID 7500) plus its talk page.

The special page is used to access every part of the imageboard.

### URL rewrites

To enable cleaner URLs for the imageboard, it's recommended to add the following rewrite rules to your .htaccess file:

```
RewriteEngine On

RewriteRule ^imageboard/?$ /tomba.club/w/index.php?title=Special:Tombooru [L,QSA]
RewriteRule ^imageboard/(.+)$ /tomba.club/w/index.php?title=Special:Tombooru/$1 [L,QSA]
```

This ensures the imageboard is available at **/imageboard** instead of **/wiki/Special:Tombooru**.

### Requirements

This extension requires MediaWiki v1.43.0 or above. Tested with PHP 8.3.20.

The [installation SQL](/Tombooru/sql/install.sql) file is written for MySQL.

## Description

Tombooru is built on top of MediaWiki's file upload system. When the user uploads a file via the imageboard's upload page, it's added to the wiki just like a regular image, but additional rows are created in the database marking it as an imageboard image and keeping track of its tags and other metadata.

Both tags and images can have a description, which is stored as a regular wiki page in the `Tombooru_data` namespace, so that edits appear in **Recent Changes** like any other page. These pages are created only as needed.

## License

MIT license.
