# Mautic Contact Client [![Latest Stable Version](https://poser.pugx.org/thedmsgroup/mautic-contact-client-bundle/v/stable)](https://packagist.org/packages/thedmsgroup/mautic-contact-client-bundle) [![License](https://poser.pugx.org/thedmsgroup/mautic-contact-client-bundle/license)](https://packagist.org/packages/thedmsgroup/mautic-contact-client-bundle) [![Build Status](https://travis-ci.com/TheDMSGroup/mautic-contact-client.svg?branch=master)](https://travis-ci.com/TheDMSGroup/mautic-contact-client)
![](./Assets/img/client.png)

Create integrations/enhancers without touching code. Allows you to send contacts to third parties, supporting virtually any API. Unlike webhooks the result can be used to enhance/modify the contact and virtually any API/Client is supported by chaining API operations as needed.

Designed for use by performance marketers who enhance/exchange contacts in mass quantities.
Can optionally be used in tandem with it's sibling [Mautic Contact Source](https://github.com/TheDMSGroup/mautic-contact-source).

## Features
- [x] Campaign: Queue a contact to be sent within a Campaign as you would any integration.
- [x] Campaign: Allow fields to be overridden within a campaign workflow for specific use cases.
- [x] Duplicates: Rules to detect limited duplicates prior to send.
- [x] Exclusivity: Rules to allow a client limited exclusivity prior to send.
- [x] API: Rules to define the measurement of a successful send based on status/headers/body.
- [x] API: Supports any Auth types, ping/post, and more by chaining API operations.
- [x] API: Map fields from an API to update or enhance contacts on success.
- [x] Finances: Track cost/revenue in the attribution field.
- [x] Schedule: Choose to send based on days/hours/exclusions including multiple timezone support.
- [x] Command line: Method provided to pipe a contact through any published client.
- [x] Budgets: Rules to limit the quantity of successful contacts sent to a client.
- [X] Files: Allow a file payload to send for clients that have no API. Contacts will queued and added to a file to be delivered to clients by FTP/SFTP/S3/Email by a schedule.
- [x] Logging: Logs the complete transaction, revenue, audit trail and integration (on contacts). Searchable.

## Installation & Usage

Currently being used with Mautic `2.14.x`.
If you have success/issues with other versions please report.

1. Install by running `composer require thedmsgroup/mautic-contact-client-bundle`
2. Go to `/s/plugins/reload`
3. Click "Clients" and "Publish" the plugin.
4. You'll find "Clients" in the main menu and can dive in to create your first one.

## Payloads

You can use [Mustache](http://mustache.github.io) to format outgoing field values with any client. 
Just use the field alias, like so "{{ firstname }} {{ lastname }}" to send the full name, or "{{ email }}" to just send the email.
Other contextual schema (such as previous headers/body fields) is also available (just start typing "payload" for suggestions).

## Uses these fine libraries:

* [Bootstrap Datepicker](https://github.com/uxsolutions/bootstrap-datepicker)
* [Bootstrap Slider](https://github.com/seiyria/bootstrap-slider)
* [Caret](https://github.com/accursoft/caret)
* [CodeMirror](https://github.com/codemirror/CodeMirror)
* [date.format](https://github.com/jacwright/date.format)
* [HTMLHint](https://github.com/yaniswang/HTMLHint)
* [Interact.js](https://github.com/taye/interact.js)
* [jQuery QueryBuilder](https://github.com/mistic100/jQuery-QueryBuilder)
* [jQuery Timepicker](https://github.com/jonthornton/jquery-timepicker)
* [jQuery BusinessHours](https://github.com/gEndelf/jquery.businessHours)
* [js-yaml](https://github.com/nodeca/js-yaml)
* [JSON Editor](https://github.com/json-editor/json-editor)
* [JSON Lint](https://github.com/zaach/jsonlint)
* [Mustache.php](https://github.com/bobthecow/mustache.php)
* [font-awesome-animation](https://github.com/l-lin/font-awesome-animation)

## Todo
- [ ] Filtering: Rules to globally exclude contacts from sending to a client based on field values.
- [ ] Finance: Dynamic attribution on a per-campaign basis.
- [ ] Campaign: Provide a better widget for including clients in campaigns (using the integration screen is a bit tedious, and there's not an easy way to divert success/failure).
