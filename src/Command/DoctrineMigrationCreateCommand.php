<?php /** @noinspection PhpInternalEntityUsedInspection */

declare(strict_types=1);

namespace Highcore\DoctrineMigrationBundle\Command;

use Doctrine\Migrations\Generator\Generator as OriginalGenerator;
use Doctrine\Migrations\Generator\SqlGenerator as OriginalSqlGenerator;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Highcore\DoctrineMigrationBundle\Migration\Generator\Generator;
use Highcore\DoctrineMigrationBundle\Migration\Generator\SqlGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Config\FileLocator;

final class DoctrineMigrationCreateCommand extends Command
{
    use DecoratesDoctrineCommandTrait;

    public function __construct(
        private readonly FileLocator $fileLocator,
        private readonly GenerateCommand $originalCommand,
    ) {
        parent::__construct();
    }

    protected function getOriginalCommand(): DoctrineCommand
    {
        return $this->originalCommand;
    }

    /**
     * @psalm-suppress InternalClass
     * @psalm-suppress InternalMethod
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dependencyFactory = $this->getDependencyFactory();
        $configuration = $dependencyFactory->getConfiguration();

        $this->resetFreeze($dependencyFactory);
        $dependencyFactory->setDefinition(OriginalGenerator::class,
            function () use ($dependencyFactory, $configuration): Generator {
                return new Generator($configuration);
            });

        $this->resetFreeze($dependencyFactory);
        $dependencyFactory->setDefinition(OriginalSqlGenerator::class,
            function () use ($dependencyFactory): SqlGenerator {
                return new SqlGenerator(
                    $configuration,
                    $dependencyFactory->getConnection()->getDatabasePlatform()
                );
            });

        $customMigrationTemplate = $this->fileLocator->locate(
            '@DoctrineMigrationBundle/Resources/tpl/migration.tpl');
        $configuration->setCustomTemplate($customMigrationTemplate);

        return $this->call(__FUNCTION__, $input, $output);
    }
}
