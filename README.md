# Highcore Doctrine Migrations Bundle

## Installation
```shell
composer require highcore/doctrine-migrations-bundle
```

## Usage

To create SQL files with migration, use:
```shell
./bin/console doctrine:migrations:generate 
```

To create a diff SQL migration with your actual database:
```shell
./bin/console doctrine:migrations:diff
```

## Example 
```
$ tree
.
├── sql
│   ├── Version20220708173033_up.sql
│   ├── Version20220708173033_down.sql
│   ├── Version20220710115549_up.sql
│   ├── Version20220710115549_down.sql
├── Version20220708173033.php
├── Version20220710115549.php
```

> You can delete `_down.sql` migration if you don't need it. 