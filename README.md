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
Other contextual schema (such as previous headers/body fields) is also available (documentation incoming).

### Formatting Date and Time

You can wrap `date` tags around any string or custom field tag to format the date and/or time to your needs.
The Timezone of the Client will always be applied if provided.
Examples:
- `{{#date.atom}}2018-03-05{{/date.atom}}` - renders `2018-03-05T00:00:00+00:00`
- `{{#date.cookie}}2018-03-05{{/date.cookie}}` - renders `Monday, 05-Mar-18 00:00:00 UTC`
- `{{#date.iso8601}}2018-03-05{{/date.iso8601}}` - renders `2018-03-05T00:00:00+0000`
- `{{#date.rfc822}}2018-03-05{{/date.rfc822}}` - renders `Mon, 05 Mar 18 00:00:00 +0000`
- `{{#date.rfc850}}2018-03-05{{/date.rfc850}}` - renders `Monday, 05-Mar-18 00:00:00 UTC`
- `{{#date.rfc1036}}2018-03-05{{/date.rfc1036}}` - renders `Mon, 05 Mar 18 00:00:00 +0000`
- `{{#date.rfc1123}}2018-03-05{{/date.rfc1123}}` - renders `Mon, 05 Mar 2018 00:00:00 +0000`
- `{{#date.rfc2822}}2018-03-05{{/date.rfc2822}}` - renders `Mon, 05 Mar 2018 00:00:00 +0000`
- `{{#date.rfc3339}}2018-03-05{{/date.rfc3339}}` - renders `2018-03-05T00:00:00+00:00`
- `{{#date.rfc3339_extended}}2018-03-05{{/date.rfc3339_extended}}` - renders `2018-03-05T00:00:00.000+00:00`
- `{{#date.rfc7231}}2018-03-05{{/date.rfc7231}}` - renders `Mon, 05 Mar 2018 00:00:00 GMT`
- `{{#date.rss}}2018-03-05{{/date.rss}}` - renders `Mon, 05 Mar 2018 00:00:00 +0000`
- `{{#date.w3c}}2018-03-05{{/date.w3c}}` - renders `2018-03-05T00:00:00+00:00`
- `{{#date.short}}2018-03-05{{/date.short}}` - renders `05/03/2018`
- `{{#date.yearsFrom}}2018-03-06{{/date.yearsFrom}}` - renders the number of years from the date (good for Date of Birth) 
- `{{#date.monthsFrom}}2018-03-06{{/date.monthsFrom}}` - renders the number of months from the date. 
- `{{#date.daysFrom}}2018-03-06{{/date.daysFrom}}` - renders the number of days from the date. 
- `{{#date.hoursFrom}}2018-03-06{{/date.hoursFrom}}` - renders the number of hours from the date. 
- `{{#date.minutesFrom}}2018-03-06{{/date.minutesFrom}}` - renders the number of minutes from the date.
- `{{#date.yearsTill}}2018-03-06{{/date.yearsTill}}` - renders the number of years till the date (good for Date of Birth) 
- `{{#date.monthsTill}}2018-03-06{{/date.monthsTill}}` - renders the number of months till the date. 
- `{{#date.daysTill}}2018-03-06{{/date.daysTill}}` - renders the number of days till the date. 
- `{{#date.hoursTill}}2018-03-06{{/date.hoursTill}}` - renders the number of hours till the date. 
- `{{#date.minutesTill}}2018-03-06{{/date.minutesTill}}` - renders the number of minutes till the date.
- `{{#date.years}}2018-03-06{{/date.years}}` - renders the absolute number of years between now and the date.
- `{{#date.months}}2018-03-06{{/date.months}}` - renders the absolute number of months between now and the date.
- `{{#date.days}}2018-03-06{{/date.days}}` - renders the absolute number of days between now and the date.
- `{{#date.hours}}2018-03-06{{/date.hours}}` - renders the absolute number of hours between now and the date.
- `{{#date.minutes}}2018-03-06{{/date.minutes}}` - renders the absolute number of minutes between now and the date.
- `{{#date.X}}2018-03-06{{/date.X}}` - where `X` is any single-character [date format](http://php.net/manual/en/function.date.php) such as m, D, y etc. This can be used to create any format desired for situations where the list above is insufficient.

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
