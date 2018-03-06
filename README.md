# Mautic Contact Client [![Latest Stable Version](https://poser.pugx.org/thedmsgroup/mautic-contact-client-bundle/v/stable)](https://packagist.org/packages/thedmsgroup/mautic-contact-client-bundle) [![License](https://poser.pugx.org/thedmsgroup/mautic-contact-client-bundle/license)](https://packagist.org/packages/thedmsgroup/mautic-contact-client-bundle) [![Build Status](https://travis-ci.org/TheDMSGroup/mautic-contact-client.svg?branch=master)](https://travis-ci.org/TheDMSGroup/mautic-contact-client)
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

Standard formats:

| Format      | Example Token                                        | Result                                    |
| ----------- | ---------------------------------------------------- | ----------------------------------------- |
| atom        | {{#date.atom}}2018-03-05{{/date.atom}}               | 2018-03-05T00:00:00+00:00                 |
| cookie      | {{#date.cookie}}2018-03-05{{/date.cookie}}           | Monday, 05-Mar-18 00:00:00 UTC            |
| iso8601     | {{#date.iso8601}}2018-03-05{{/date.iso8601}}         | 2018-03-05T00:00:00+0000                  |
| rfc822      | {{#date.rfc822}}2018-03-05{{/date.rfc822}}           | Mon, 05 Mar 18 00:00:00 +0000             |
| rfc850      | {{#date.rfc850}}2018-03-05{{/date.rfc850}}           | Monday, 05-Mar-18 00:00:00 UTC            |
| rfc1036     | {{#date.rfc1036}}2018-03-05{{/date.rfc1036}}         | Mon, 05 Mar 18 00:00:00 +0000             |
| rfc1123     | {{#date.rfc1123}}2018-03-05{{/date.rfc1123}}         | Mon, 05 Mar 2018 00:00:00 +0000           |
| rfc2822     | {{#date.rfc2822}}2018-03-05{{/date.rfc2822}}         | Mon, 05 Mar 2018 00:00:00 +0000           |
| rfc3339     | {{#date.rfc3339}}2018-03-05{{/date.rfc3339}}         | 2018-03-05T00:00:00+00:00                 |
| rfc3339ext  | {{#date.rfc3339ext}}2018-03-05{{/date.rfc3339ext}}   | 2018-03-05T00:00:00.000+00:00             |
| rfc7231     | {{#date.rfc7231}}2018-03-05{{/date.rfc7231}}         | Mon, 05 Mar 2018 00:00:00 GMT             |
| rss         | {{#date.rss}}2018-03-05{{/date.rss}}                 | Mon, 05 Mar 2018 00:00:00 +0000           |
| w3c         | {{#date.w3c}}2018-03-05{{/date.w3c}}                 | 2018-03-05T00:00:00+00:00                 |

Extended formats:

| Format      | Example Token                                        | Result                                    |
| ----------- | ---------------------------------------------------- | ----------------------------------------- |
| short       | {{#date.short}}2018-03-05{{/date.short}}             | 05/03/2018                                |

Interval based formats:

| Format      | Example Token                                        | Result                                    |
| ----------- | ---------------------------------------------------- | ----------------------------------------- |
| yearsFrom   | {{#date.yearsFrom}}2018-03-06{{/date.yearsFrom}}     | years from the date (for Date of Birth)   |
| monthsFrom  | {{#date.monthsFrom}}2018-03-06{{/date.monthsFrom}}   | months from the date                      |
| daysFrom    | {{#date.daysFrom}}2018-03-06{{/date.daysFrom}}       | days from the date                        |
| hoursFrom   | {{#date.hoursFrom}}2018-03-06{{/date.hoursFrom}}     | hours from the date                       |
| minutesFrom | {{#date.minutesFrom}}2018-03-06{{/date.minutesFrom}} | minutes from the date                     |
| yearsTill   | {{#date.yearsTill}}2018-03-06{{/date.yearsTill}}     | years till the date                       |
| monthsTill  | {{#date.monthsTill}}2018-03-06{{/date.monthsTill}}   | months till the date                      |
| daysTill    | {{#date.daysTill}}2018-03-06{{/date.daysTill}}       | days till the date                        |
| hoursTill   | {{#date.hoursTill}}2018-03-06{{/date.hoursTill}}     | hours till the date                       |
| minutesTill | {{#date.minutesTill}}2018-03-06{{/date.minutesTill}} | minutes till the date                     |
| years       | {{#date.years}}2018-03-06{{/date.years}}             | absolute years between now and the date   |
| months      | {{#date.months}}2018-03-06{{/date.months}}           | absolute months between now and the date  |
| days        | {{#date.days}}2018-03-06{{/date.days}}               | absolute days between now and the date    |
| hours       | {{#date.hours}}2018-03-06{{/date.hours}}             | absolute hours between now and the date   |
| minutes     | {{#date.minutes}}2018-03-06{{/date.minutes}}         | absolute minutes between now and the date |

Custom formats: 
You can make your own formats by combining [date format characters](http://php.net/manual/en/function.date.php).
 
| Format      | Example Token                                        | Result                                    |
| ----------- | ---------------------------------------------------- | ----------------------------------------- |
| d           | {{#date.d}}2018-03-05{{/date.d}}                     | 05                                        |
| D           | {{#date.D}}2018-03-05{{/date.D}}                     | Mon                                       |
| j           | {{#date.j}}2018-03-05{{/date.j}}                     | 5                                         |
| l           | {{#date.l}}2018-03-05{{/date.l}}                     | Monday                                    |
| N           | {{#date.N}}2018-03-05{{/date.N}}                     | 1                                         |
| S           | {{#date.S}}2018-03-05{{/date.S}}                     | th                                        |
| w           | {{#date.w}}2018-03-05{{/date.w}}                     | 1                                         |
| z           | {{#date.z}}2018-03-05{{/date.z}}                     | 63                                        |
| W           | {{#date.W}}2018-03-05{{/date.W}}                     | 10                                        |
| F           | {{#date.F}}2018-03-05{{/date.F}}                     | March                                     |
| m           | {{#date.m}}2018-03-05{{/date.m}}                     | 03                                        |
| M           | {{#date.M}}2018-03-05{{/date.M}}                     | Mar                                       |
| n           | {{#date.n}}2018-03-05{{/date.n}}                     | 3                                         |
| t           | {{#date.t}}2018-03-05{{/date.t}}                     | 31                                        |
| L           | {{#date.L}}2018-03-05{{/date.L}}                     | 0                                         |
| o           | {{#date.o}}2018-03-05{{/date.o}}                     | 2018                                      |
| Y           | {{#date.Y}}2018-03-05{{/date.Y}}                     | 2018                                      |
| y           | {{#date.y}}2018-03-05{{/date.y}}                     | 18                                        |
| a           | {{#date.a}}2018-03-05{{/date.a}}                     | am                                        |
| A           | {{#date.A}}2018-03-05{{/date.A}}                     | AM                                        |
| B           | {{#date.B}}2018-03-05{{/date.B}}                     | 041                                       |
| g           | {{#date.g}}2018-03-05{{/date.g}}                     | 12                                        |
| G           | {{#date.G}}2018-03-05{{/date.G}}                     | 0                                         |
| h           | {{#date.h}}2018-03-05{{/date.h}}                     | 12                                        |
| H           | {{#date.H}}2018-03-05{{/date.H}}                     | 00                                        |
| i           | {{#date.i}}2018-03-05{{/date.i}}                     | 00                                        |
| s           | {{#date.s}}2018-03-05{{/date.s}}                     | 00                                        |
| u           | {{#date.u}}2018-03-05{{/date.u}}                     | 000000                                    |
| v           | {{#date.v}}2018-03-05{{/date.v}}                     | 000                                       |
| e           | {{#date.e}}2018-03-05{{/date.e}}                     | UTC                                       |
| I           | {{#date.I}}2018-03-05{{/date.I}}                     | 0                                         |
| O           | {{#date.O}}2018-03-05{{/date.O}}                     | +0000                                     |
| P           | {{#date.P}}2018-03-05{{/date.P}}                     | +00:00                                    |
| T           | {{#date.T}}2018-03-05{{/date.T}}                     | UTC                                       |
| Z           | {{#date.Z}}2018-03-05{{/date.Z}}                     | 0                                         |
| c           | {{#date.c}}2018-03-05{{/date.c}}                     | 2018-03-05T00:00:00+00:00                 |
| r           | {{#date.r}}2018-03-05{{/date.r}}                     | Mon, 05 Mar 2018 00:00:00 +0000           |
| U           | {{#date.U}}2018-03-05{{/date.U}}                     | 1520208000                                |

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
