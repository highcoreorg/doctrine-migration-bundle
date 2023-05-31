<?php /** @noinspection PhpInternalEntityUsedInspection */

declare(strict_types=1);

namespace Highcore\DoctrineMigrationBundle\Migration\Generator;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Generator\Exception\InvalidTemplateSpecified;
use Doctrine\Migrations\Tools\Console\Helper\MigrationDirectoryHelper;
use InvalidArgumentException;

final class Generator extends \Doctrine\Migrations\Generator\Generator
{
    private const MIGRATION_TEMPLATE = <<<'TEMPLATE'
<?php
declare(strict_types = 1);

namespace <namespace>;

/*
* This file is part of the Highcore group.
*
* (c) Roman Cherniakhovsky bizrenay@gmail.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

use Doctrine\DBAL\Schema\Schema;
use Highcore\DoctrineMigrationBundle\Migration\AbstractMigration;

/**
* Auto-generated Migration: Please modify to your needs!
*/
class <className> extends AbstractMigration {<override>}
TEMPLATE;
    public const SQL_MIGRATION_FILE_PATH_TEMPLATE = '%s/sql/%s_%s.sql';

    private Configuration $configuration;

    private ?string $template = null;

    /**
     * @noinspection PhpInternalEntityUsedInspection
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;

        parent::__construct($configuration);
    }

    public function generateMigration(
        string $fqcn,
        ?string $up = null,
        ?string $down = null
    ): string {
        $mch = [];
        if (preg_match('~(.*)\\\\([^\\\\]+)~', $fqcn, $mch) === 0) {
            throw new InvalidArgumentException(sprintf('Invalid FQCN'));
        }

        [, $namespace, $className] = $mch;

        $dirs = $this->configuration->getMigrationDirectories();
        if (! isset($dirs[$namespace])) {
            throw new InvalidArgumentException(sprintf('Path not defined for the namespace "%s"', $namespace));
        }

        $dir = $dirs[$namespace];

        $replacements = [
            '<namespace>' => $namespace,
            '<className>' => $className,
            '<override>' => $this->configuration->isTransactional() ? '' : <<<'METHOD'


    public function isTransactional(): bool
    {
        return false;
    }
METHOD
            ,
        ];

        $code = strtr($this->getTemplate(), $replacements);
        $code = preg_replace('/^ +$/m', '', $code);

        $directoryHelper = new MigrationDirectoryHelper();
        $dir             = $directoryHelper->getMigrationDirectory($this->configuration, $dir);
        $path            = $dir . '/' . $className . '.php';

        file_put_contents($path, $code);

        file_put_contents($this->getSqlMigrationPathname($dir, $className, 'up'), $up);
        if (null !== $down && '' !== trim($down)) {
            file_put_contents($this->getSqlMigrationPathname($dir, $className, 'down'), $down);
        }


        return $path;
    }

    /**
     * @noinspection PhpInternalEntityUsedInspection
     */
    public function getSqlMigrationPathname(string $migrationsDir, string $filename, string $type): string
    {
        assert(in_array($type, ['up', 'down'], true), 'Type of migration should be up or down.');

        $directoryHelper = new MigrationDirectoryHelper();
        $dir             = $directoryHelper->getMigrationDirectory($this->configuration, $migrationsDir);
        $migrationFile   = \sprintf(self::SQL_MIGRATION_FILE_PATH_TEMPLATE, $dir, $filename, $type);

        $sqlDir = \dirname($migrationFile);
        if (!\is_dir($sqlDir) && !mkdir($sqlDir, 0755, true) && !is_dir($sqlDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $sqlDir));
        }
        \touch($migrationFile);

        return $migrationFile;
    }

    private function getTemplate(): string
    {
        if ($this->template === null) {
            $this->template = $this->loadCustomTemplate();

            if ($this->template === null) {
                $this->template = self::MIGRATION_TEMPLATE;
            }
        }

        return $this->template;
    }

    /**
     * @throws InvalidTemplateSpecified
     */
    private function loadCustomTemplate(): ?string
    {
        $customTemplate = $this->configuration->getCustomTemplate();

        if ($customTemplate === null) {
            return null;
        }

        if (! is_file($customTemplate) || ! is_readable($customTemplate)) {
            throw InvalidTemplateSpecified::notFoundOrNotReadable($customTemplate);
        }

        $content = file_get_contents($customTemplate);

        if ($content === false) {
            throw InvalidTemplateSpecified::notReadable($customTemplate);
        }

        if (trim($content) === '') {
            throw InvalidTemplateSpecified::empty($customTemplate);
        }

        return $content;
    }
}