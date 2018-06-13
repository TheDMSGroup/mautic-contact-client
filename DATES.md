# Formatting Date and Time

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


Using filters pragma:
We also support filters pragma for all the above, and a few extra padding helpers:

| Format      | Example Token                                        | Result                                    |
| ----------- | ---------------------------------------------------- | ----------------------------------------- |
| lpad.2      | {{% FILTERS }}{{ dob_month | lpad.2 }}               | 02                                        |
| lpad.4      | {{% FILTERS }}{{ dob_year | lpad.4 }}                | 0002                                      |
| rpad.2      | {{% FILTERS }}{{ dob_month | rpad.2 }}               | 20                                        |
| rpad.4      | {{% FILTERS }}{{ dob_year | rpad.4 }}                | 2000                                      |
