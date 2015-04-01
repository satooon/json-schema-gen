json-schema-gen
====

[satooon/json-schema-gen](https://github.com/satooon/json-schema-gen) - Response data convert to JSON Schema

## Install

Install Composer

```
$ curl -sS https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
```

Add the following to your require block in composer.json config

```
"satooon/json-schema-gen": ">=0.0.1"
```

## Configuration

Add to your app/config/app.php the service provider and aliases:

```
// Provider
'providers' => array(
    'Satooon\JsonSchemaGen\JsonSchemaGenServiceProvider',
)

// Aliases
'aliases' => array(
    'JsonSchemaGen'   => 'Satooon\JsonSchemaGen\Facades\JsonSchemaGen',
)
```

## Usage

GET request

```
php artisan command:JsonSchemaGen https://search.twitter.com/search.json
```

POST request and add heade paramater

```
php artisan command:JsonSchemaGen https://api.twitter.com/1.1/statuses/update.json -X POST -d '{"status":"text"}' -H "Authorization: OAuth  ...."
```
## Licence

MIT