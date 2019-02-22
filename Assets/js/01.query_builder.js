// Establish default success definition settings.
// Note: Some operators are not going to be directly useful.
Mautic.contactclientQBDefaultOps = [
    'equal',
    'not_equal',
    // 'in',
    // 'not_in',
    'less',
    'less_or_equal',
    'greater',
    'greater_or_equal',
    // 'between',
    // 'not_between',
    'begins_with',
    'not_begins_with',
    'contains',
    'not_contains',
    'ends_with',
    'not_ends_with',
    'is_empty',
    'is_not_empty',
    // 'is_null'
    'regex'
];

// Used as the default filters (others are added on demand).
Mautic.contactclientQBDefaultFilters = [{
    id: 'status',
    label: 'Status Code',
    type: 'string',
    input: 'select',
    values: {
        '1xx': '1xx: Informational',
        '100': '100: Continue',
        '101': '101: Switching Protocols',
        '2xx': '2xx: Successful',
        '200': '200: OK',
        '201': '201: Created',
        '202': '202: Accepted',
        '203': '203: Non-Authoritative Information',
        '204': '204: No Content',
        '205': '205: Reset Content',
        '206': '206: Partial Content',
        '3xx': '3xx: Redirection',
        '300': '300: Multiple Choices',
        '301': '301: Moved Permanently',
        '302': '302: Found',
        '303': '303: See Other',
        '304': '304: Not Modified',
        '305': '305: Use Proxy',
        '307': '307: Temporary Redirect',
        '4xx': '4xx: Client Error',
        '400': '400: Bad Request',
        '401': '401: Unauthorized',
        '402': '402: Payment Required',
        '403': '403: Forbidden',
        '404': '404: Not Found',
        '405': '405: Method Not Allowed',
        '406': '406: Not Acceptable',
        '407': '407: Proxy Authentication Required',
        '408': '408: Request Timeout',
        '409': '409: Conflict',
        '410': '410: Gone',
        '411': '411: Length Required',
        '412': '412: Precondition Failed',
        '413': '413: Payload Too Large',
        '414': '414: URI Too Long',
        '415': '415: Unsupported Media Type',
        '416': '416: Range Not Satisfiable',
        '417': '417: Expectation Failed',
        '418': '418: I\'m a teapot',
        '426': '426: Upgrade Required',
        '5xx': '5xx: Server Error',
        '500': '500: Internal Server Error',
        '501': '501: Not Implemented',
        '502': '502: Bad Gateway',
        '503': '503: Service Unavailable',
        '504': '504: Gateway Time-out',
        '505': '505: HTTP Version Not Supported',
        '102': '102: Processing',
        '207': '207: Multi-Status',
        '226': '226: IM Used',
        '308': '308: Permanent Redirect',
        '422': '422: Unprocessable Entity',
        '423': '423: Locked',
        '424': '424: Failed Dependency',
        '428': '428: Precondition Required',
        '429': '429: Too Many Requests',
        '431': '431: Request Header Fields Too Large',
        '451': '451: Unavailable For Legal Reasons',
        '506': '506: Variant Also Negotiates',
        '507': '507: Insufficient Storage',
        '511': '511: Network Authentication Required',
        '7xx': '7xx: Developer Error'
    },
    operators: ['equal', 'not_equal']
}, {
    id: 'headersRaw',
    label: 'Header Text (raw)',
    type: 'string',
    operators: Mautic.contactclientQBDefaultOps
}, {
    id: 'bodyRaw',
    label: 'Body Text (raw)',
    type: 'string',
    operators: Mautic.contactclientQBDefaultOps
}, {
    id: 'bodySize',
    label: 'Body Size',
    type: 'integer',
    operators: [
        'equal',
        'not_equal',
        'less',
        'less_or_equal',
        'greater',
        'greater_or_equal'
    ]
}];

// Used whenever we use getRules for consistency.
Mautic.contactclientQBDefaultGet = {
    get_flags: false,
    skip_empty: true,
    allow_invalid: true,
};

// Used whenever we use setRules for consistency.
Mautic.contactclientQBDefaultSet = {
    allow_invalid: true,
};

// Used whenever instantiating.
Mautic.contactclientQBDefaultSettings = function () {
    var operators = Object.values(mQuery.fn.queryBuilder.defaults('operators'));
    operators.push({
        type: 'regex',
        nb_inputs: 1,
        multiple: false,
        apply_to: ['string']
    });
    return {
        allow_empty: true,
        allow_empty_value: true,
        select_placeholder: '-- Select a Field --',
        plugins: {
            'sortable': {
                icon: 'fa fa-sort'
            },
            'bt-tooltip-errors': null
        },
        filters: Mautic.contactclientQBDefaultFilters,
        icons: {
            add_group: 'fa fa-plus',
            add_rule: 'fa fa-plus',
            remove_group: 'fa fa-times',
            remove_rule: 'fa fa-times',
            sort: 'fa fa-sort',
            error: 'fa fa-exclamation-triangle'
        },
        operators: operators
        // Note: Providing rules upfront fails because the filters may not
        // include fields. Allow rules to be put in place on change when the
        // JSONSchema renders. rules: null
    };
};