# 📜 Papyrus Event Store: Doctrine DBAL implementation
Implementation of [papyrus/event-store](https://github.com/papyrusphp/event-store), based on [doctrine/dbal](https://github.com/doctrine/dbal).

### Installation
Install via composer:
```bash
$ composer install papyrus/doctrine-dbal-event-store
```

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
            $container->get(DomainEventRegistry::class),
            $container->get(Serializer::class),
        ); 
    },
];
```
A Symfony YAML-file definition:
```yaml
services:
    Papyrus\EventStore\EventStore\EventStore:
        class: Papyrus\DoctrineDbalEventStore\DoctrineDbalEventStore
```

### Database schema
In `./resources` there are migrations available to create your database table.
