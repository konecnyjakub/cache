Version 1.1.0+dev
- item keys in CacheItem::getKey() no longer contain namespace for apcu, redis and sqlite cache pools

Version 1.1.0
- added sqlite cache and journal
- dropped support for PHP 8.3
- added TomlFileJournal

Version 1.0.1
- fixed clearing non-namespaced redis or apcu cache also deleting items from namespaced caches

Version 1.0.0
- initial version
