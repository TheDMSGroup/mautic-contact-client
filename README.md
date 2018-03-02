# Mautic Contact Client [![Build Status](https://travis-ci.org/TheDMSGroup/mautic-contact-client.svg?branch=master)](https://travis-ci.org/TheDMSGroup/mautic-contact-client)
![](./Assets/img/client.png)

Create integrations/enhancers without touching code.

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
- [x] Logging: Logs the complete transaction, revenue, audit trail and integration (on contacts).
- [x] Command line: Method provided to pipe a contact through any published client.

## Installation & Usage

Currently being tested with Mautic `2.12.x`.
If you have success/issues with other versions please report.

1. Install by running `composer require thedmsgroup/mautic-contact-client-bundle`
2. Go to `/s/plugins/reload`
3. Click "Clients" and "Publish" the plugin.
4. You'll find "Clients" in the main menu and can dive in to create your first one.

## Payloads

You can use [Mustache](http://mustache.github.io) to format outgoing field values with any client. 
Just use the field alias, like so "{{firstname}} {{lastname}}" to send the full name, or "{{email}}" to just send the email.
Date formatting and other contextual schema is also available (documentation incoming).

## Uses these fine libraries:

* [Bootstrap Datepicker](https://github.com/uxsolutions/bootstrap-datepicker)
* [Bootstrap Slider](https://github.com/seiyria/bootstrap-slider)
* [Caret](https://github.com/accursoft/caret)
* [CodeMirror](https://github.com/codemirror/CodeMirror)
* [Interact.js](https://github.com/taye/interact.js)
* [jQuery QueryBuilder](https://github.com/mistic100/jQuery-QueryBuilder)
* [jQuery TagEditor](https://github.com/heathdutton/jQuery-tagEditor)
* [jQuery Timepicker](https://github.com/jonthornton/jquery-timepicker)
* [jQuery BusinessHours](https://github.com/gEndelf/jquery.businessHours)
* [JSON Editor](https://github.com/json-editor/json-editor)
* [JSON Lint](https://github.com/zaach/jsonlint)
* [Mustache.php](https://github.com/bobthecow/mustache.php)

## Todo
- [ ] Filtering: Rules to globally exclude contacts from sending to a client based on fields.
- [ ] Budgets: Rules to limit the quantity of successful contacts sent to a client.
- [ ] Files: Allow a file payload to send for clients that have no API. Contacts will queued and added to a CSV/XSL batch to be delivered to clients by FTP or email within the given schedule.
- [ ] Logging: Make the logging screen sortable/searchable. Currently this is pretty minimal (and buggy).
- [ ] Finance: Store the most recent attribution on a separate field (to be used in Campaign workflows).
- [ ] Campaign: Provide a better widget for including clients in campaigns (using the integration screen is a bit tedious, and there's not an easy way to divert success/failure).
