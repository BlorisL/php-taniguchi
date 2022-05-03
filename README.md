# php-taniguchi
Wrapper for commonly used PHP IMAP functions.

## Description

I had to make a basic Imap client with attachments download, but I didn't find any working projects for my needs.
So i decided to learn how PHP Imap functions work and build an easy-to-use class around them.
The main issue with other project was about attachments download, I don't know why but every library i tried it was downloading only the first attachment.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [License](#license)

## Installation

To use this wrapper, just be sure to have [PHP Imap](https://www.php.net/manual/en/book.imap.php) enabled.

## Usage 

### An email will be represented like this:

| Property | Description |
| --- | --- |
| **uid** | Imap UID of the mail (`FT_UID` activated) |
| **seen** | Flag to check if this email is alreaby been opened |
| **from** | Sender's email |
| **subject** | Subject's email |
| **date** | Date's email |
| **messages** | It is an array composed by the main message and all the .eml messages |
| **attachemnts** | It is an array composed by all the attachments (.eml attachments too) |

### A Taniguchi\Imap object will be represented like this:

| Property | Type | Description |
| --- | --- | --- |
| **connection** | `IMAP\Connection\|false` | Connection to Imap's service |
| **user** | `string` | Email for the Imap's login |
| **password** | `string` | Password for the Imap's login |
| **url** | `string` | Url for the Imap's login |
| **port** | `int\|null` | Url for the Imap's login |
| **flags** | `array\|null` | Flags for the Imap's login |
| **total** | `string\|null` | Number of total mails in the Imap's account |
| **folders** | `string` | Array of folders of the Imap's account |
| **rejects** | `string` | Array of filenames to rejects when download an attachment |

### Examples

You can create the object in two different ways:
- using a full url
```php
$tmp = new \Taniguchi\Imap($account, $password, "{{$url}:{$port}/service=imap/ssl/novalidate-cert}");
```
- using separated values
```php
$tmp = new \Taniguchi\Imap($account, $password, $url, $port);
$tmp->setSsl()->setValidate(false);
```

A working example is given in [example.php](https://github.com/BlorisL/php-taniguchi/blob/main/example.php).

## License

Code licensed under the [MIT License](https://github.com/BlorisL/php-taniguchi/blob/main/LICENSE).

Do whatever you want with this code, but remember opensource projects work with the help of the community so would be really useful if any errors, updates, features or ideas were reported.

[Share with me a cup of tea](https://www.buymeacoffee.com/bloris) ☕

