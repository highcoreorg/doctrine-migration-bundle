<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(Highcore\DoctrineMigrationBundle\Command\DoctrineMigrationCreateCommand::class)
        ->decorate('doctrine_migrations.generate_command')
        ->args([service('file_locator'), service('.inner')])
    ;

    $services->set(Highcore\DoctrineMigrationBundle\Command\DoctrineMigrationDiffGeneratorCommand::class)
        ->decorate('doctrine_migrations.diff_command')
        ->args([service('file_locator'), service('.inner')])
    ;
};
