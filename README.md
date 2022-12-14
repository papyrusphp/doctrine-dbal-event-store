# 📜 Papyrus Event Store: Doctrine DBAL implementation
[![Build Status](https://scrutinizer-ci.com/g/papyrusphp/doctrine-dbal-event-store/badges/build.png?b=main)](https://github.com/papyrusphp/doctrine-dbal-event-store/actions)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/papyrusphp/doctrine-dbal-event-store.svg?style=flat)](https://scrutinizer-ci.com/g/papyrusphp/doctrine-dbal-event-store/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/papyrusphp/doctrine-dbal-event-store.svg?style=flat)](https://scrutinizer-ci.com/g/papyrusphp/doctrine-dbal-event-store)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/papyrus/doctrine-dbal-event-store.svg?style=flat&include_prereleases)](https://packagist.org/packages/papyrus/doctrine-dbal-event-store)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-8892BF.svg?style=flat)](http://www.php.net)

Implementation of [papyrus/event-store](https://github.com/papyrusphp/event-store), based on [doctrine/dbal](https://github.com/doctrine/dbal).

### Installation
Install via composer:
```bash
composer require papyrus/doctrine-dbal-event-store
```

In order to use `doctrine-dbal-event-store`, you need to install implementations of
[papyrus/domain-event-registry](https://github.com/papyrusphp/domain-event-registry) and [papyrus/serializer](https://github.com/papyrusphp/serializer) as well.

See these two libraries for installation instructions.

## Configuration
Bind this implementation to the interface `EventStore` in your service definitions, e.g.:

A plain PHP PSR-11 Container definition:

```php
use Doctrine\DBAL\Connection;
use Papyrus\DoctrineDbalEventStore\DoctrineDbalEventStore;
use Papyrus\DoctrineDbalEventStore\TableSchemaFactory;
use Papyrus\DomainEventRegistry\DomainEventRegistry;
use Papyrus\EventStore\EventStore\EventStore;
use Papyrus\Serializer\Serializer;
use Psr\Container\ContainerInterface;

return [
    // Other definitions
    // ...

    EventStore::class => static function (ContainerInterface $container): EventStore {
        return new DoctrineDbalEventStore(
            $container->get(Connection::class),
            TableSchemaFactory::create(/* use you custom field names */),
            // See papyrus/domain-event-registry for more details
            $container->get(DomainEventRegistry::class),
            // See papyrus/serializer for more details
            $container->get(Serializer::class),
        ); 
    },
];
```
A Symfony YAML-file definition:
```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  # Other definitions
  # ...

  Papyrus\EventStore\EventStore\EventStore:
    class: Papyrus\DoctrineDbalEventStore\DoctrineDbalEventStore
    
  Papyrus\DoctrineDbalEventStore\TableSchema:
    factory: [Papyrus\DoctrineDbalEventStore\TableSchemaFactory, 'create']
```

### Database schema
In `./resources` there are migrations available to create your database table.
