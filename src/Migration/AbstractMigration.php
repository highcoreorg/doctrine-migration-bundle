<?php

declare(strict_types=1);

namespace Highcore\DoctrineMigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Psr\Log\LoggerInterface;

abstract class AbstractMigration extends \Doctrine\Migrations\AbstractMigration
{
    private string $fileName;
    private string $rootDir;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        parent::__construct($connection, $logger);

        $reflection = new \ReflectionClass(static::class);
        $this->fileName = $reflection->getShortName();
        $this->rootDir = \dirname($reflection->getFileName());
    }

    final public function up(Schema $schema): void
    {
        $this->beforeUp();
        $this->exec($this->rootDir . '/sql/' . $this->fileName . '_up.sql');
        $this->afterUp();
    }

    final public function down(Schema $schema): void
    {
        $this->beforeDown();
        $this->exec($this->rootDir . '/sql/' . $this->fileName . '_down.sql');
        $this->afterDown();
    }

    private function exec(string $filePath): void
    {
        if (!\file_exists($filePath)) {
            return;
        }

        $file = \fopen($filePath, 'rb');

        $buffer = '';
        while (false !== ($line = \fgets($file))) {
            $buffer .= $line = \trim($line);
            if (';' === \mb_substr($line, -1)) {
                $this->addSql($buffer);
                $buffer = '';
            }
        }

        \fclose($file);
    }

    public function beforeUp(): void
    {
    }

    public function beforeDown(): void
    {
    }

    public function afterUp(): void
    {
    }

    public function afterDown(): void
    {
    }
}
