[![ebay logo](docs/static/logo-vert.png)](http://www.ebayenterprise.com/)

# eBay Enterprise Mage Logger

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/eBayEnterprise/magento-log/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/eBayEnterprise/magento-log/?branch=master)

This module provides [PSR-3][0] compliant logging, rich logging context, JSON
formatting and querying, and notification levels for logs.

## PSR-3 Compliance

The `EbayEnterprise_MageLog_Helper_Data` implements `\Psr\Log\LoggerInterface`,
so it can be used in any context that requires PSR-3. For example, a PHP
library that is not Magento specific may allow you to supply a custom logger as
long as it is PSR-3 compliant. If you wish to use such a library in Magento,
you can use this extension to provide consistent and unified logging across the
extended application.

## Rich Logging Context

Logging is more useful when it provides more context. PSR-3 log methods allow
such context to be supplied in addition to the traditional timestamp, level and
message in the logs. That context can be interpolated into the message, or it
can be viewed in individual fields in JSON formatted output.

## JSON Formatting and Querying

`EbayEnterprise_MageLog` outputs messages as valid JSON. This enables Magento
logs to be parsed by powerful log analysis tools like [LogStash][1]. Even on
the commandline, the ability to query logs with [jq][2] is indispensable for
large logfiles.

### Log Reading Tips

System administrators are advised to set up a centralized logging solution with
full log parsing and analysis. For straightforward commandline usage, however,
the simplest way to read JSON logs is with `jq`, mentioned above. For example,
to tail the logs in a traditional way:

    tail system.log | jq -r '.timestamp + " " + .level + " " + .message'

For a quick peek, just do

    tail system.log | jq -r .message

Or for a full, but readable view of the last few log lines, simply use `.`.

    tail system.log | jq -r .

More sophisticated applications are possible as well. The JSON formatting
allows cleaner parsing and filtering than line-and-field based logging would.
For example, you can get only messages having to do with errors with the
following query:

     jq -r 'select(.level=="ERROR")|.message' system.log

## Notification Levels

1. Email notification
2. Log file messages
3. Ignored

For example, some serious problems may require human intervention. Thus, you
may wish to have `CRITICAL` logs sent directly to system administrators in
email. You might also want to avoid filling up your log file with `DEBUG`
messages, and simply log anything between `DEBUG` and `CRITICAL` to the
var/log/system.log file.

[Contributing to This Project](CONTRIBUTING.md)

## License

Licensed under the terms of the Open Software License v. 3.0 (OSL-3.0). See [LICENSE.md](LICENSE.md) or http://opensource.org/licenses/OSL-3.0 for the full text of the license.

- - -
Copyright © 2014 eBay Enterprise, Inc.

[0]: http://www.php-fig.org/psr/psr-3/
[1]: https://www.elastic.co/products/logstash
[2]: https://stedolan.github.io/jq/
