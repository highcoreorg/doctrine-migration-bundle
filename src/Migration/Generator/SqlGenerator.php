<?php

declare(strict_types=1);

namespace Highcore\DoctrineMigrationBundle\Migration\Generator;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;

/**
 * @noinspection PhpInternalEntityUsedInspection
 */
final class SqlGenerator extends \Doctrine\Migrations\Generator\SqlGenerator
{
    public const CODE_TEMPLATE = <<<'PHP'
$this->abortIf(
    !$this->connection->getDatabasePlatform() instanceof %s,
    "Migration can only be executed safely on '%s'."
);
PHP;
    private Configuration $configuration;

    private AbstractPlatform $platform;

    /**
     * @noinspection PhpInternalEntityUsedInspection
     */
    public function __construct(Configuration $configuration, AbstractPlatform $platform)
    {
        $this->configuration = $configuration;
        $this->platform      = $platform;

        parent::__construct($configuration, $platform);
    }

    /** @param string[] $sql */
    public function generate(
        array $sql,
        bool $formatted = false,
        int $lineLength = 120,
        bool $checkDbPlatform = true
    ): string {
        $code = [];

        $storageConfiguration = $this->configuration->getMetadataStorageConfiguration();
        foreach ($sql as $query) {
            if (
                $storageConfiguration instanceof TableMetadataStorageConfiguration
                && \mb_stripos($query, $storageConfiguration->getTableName()) !== false
            ) {
                continue;
            }

            if ($formatted) {
                $maxLength = $lineLength - 18 - 8; // max - php code length - indentation

                if (\mb_strlen($query) > $maxLength) {
                    $query = (new SqlFormatter(new NullHighlighter()))->format($query);
                }
            }

            $code[] = \sprintf('%s;', $query);
        }

        if ($checkDbPlatform && [] !== $code && $this->configuration->isDatabasePlatformChecked()) {
            $currentPlatform = '\\' . \get_class($this->platform);
            $checkPlatformCodeString = \sprintf(
                self::CODE_TEMPLATE,
                $currentPlatform, $currentPlatform
            );
            \array_unshift($code, $checkPlatformCodeString, '');
        }

        return \implode("\n", $code);
    }
}