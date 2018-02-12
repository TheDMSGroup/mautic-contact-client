![](./Assets/img/client.png)
# Mautic Contact Client 

You need to send contacts to third parties (Clients),
with complex requirements that shift on a daily basis.
Webhooks are one-way, and you need to enhance/modify the contact, log results, etc.
You can write integrations for each third party, but that comes at a cost and adds risk.
This plugin will allow you to create an integration (or enhancer) without touching code.

Features:
- [x] Campaign: Queue a contact to be sent within a Campaign as you would any integration.
- [x] Campaign: Allow fields to be overriden within a campaign workflow for specific use cases.
- [x] Duplicates: Rules to detect limited duplicates prior to send.
- [x] Exclusivity: Rules to allow a client limited exclusivity prior to send.
- [x] API: Rules to define the measurement of a succesful send based on status/headers/body.
- [x] API: Supports any Auth types, ping/post, and more by chaining API operations.
- [x] API: Map fields from an API to update or enhance contacts on success.
- [x] Finances: Track cost/revenue in the attribution field.
- [x] Schedule: Choose to send based on days/hours/exclusions including multiple timezone support.
- [x] Logging: Logs the complete transaction, revenue, audit trail and integration (on contacts).
- [x] Command line: Method provided to pipe a contact through any published client.

Todo:
- [ ] Filtering: Rules to globally exclude contacts from sending to a client based on fields.
- [ ] Limits: Rules to limit the quantity of succesful contact.
- [ ] Files: Allow a file payload to send for clients that have no API. Contacts will queued and added to a CSV/XSL batch to be delivered to clients by FTP or email within the given schedule.
- [ ] Logging: Make the logging screen sortable/searchable. Currently this is pretty minimal (and buggy).
- [ ] Finance: Store the most recent attribution on a sepperate field (to be used in Campaign workflows).
- [ ] Campaign: Provide a better widget for including clients in campaigns (using the integration screen is a bit tedous, and there's not an easy way to divert success/failure).

Uses these fine libraries:

* [Bootstrap Datepicker](https://github.com/uxsolutions/bootstrap-datepicker)
* [Bootstrap Slider](https://github.com/seiyria/bootstrap-slider)
* [CodeMirror](https://github.com/codemirror/CodeMirror)
* [Interact.js](https://github.com/taye/interact.js)
* [jQuery QueryBuilder](https://github.com/mistic100/jQuery-QueryBuilder)
* [jQuery Timepicker](https://github.com/jonthornton/jquery-timepicker)
* [jQuery BusinessHours](https://github.com/gEndelf/jquery.businessHours)
* [JSON Editor](https://github.com/json-editor/json-editor)
* [JSON Lint](https://github.com/zaach/jsonlint)
* [Selectize.js](https://github.com/selectize/selectize.js)
