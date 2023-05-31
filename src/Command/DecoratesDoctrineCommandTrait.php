<?php

declare(strict_types=1);

namespace Highcore\DoctrineMigrationBundle\Command;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait DecoratesDoctrineCommandTrait
{
    private ?DependencyFactory $dependencyFactory = null;

    abstract protected function getOriginalCommand(): DoctrineCommand;

    public function getDependencyFactory(): DependencyFactory
    {
        /** @var DependencyFactory */
        return $this->dependencyFactory ?? ($this->dependencyFactory = $this->call('getDependencyFactory'));
    }

    public function getConfiguration(): Configuration
    {
        return $this->getDependencyFactory()->getConfiguration();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->call(__FUNCTION__, $input, $output);
    }
    protected function configure(): void
    {
        $this->setDefinition($this->getOriginalCommand()->getNativeDefinition());

        $this->call(__FUNCTION__);
    }

    private function resetFreeze(DependencyFactory $dependencyFactory): void
    {
        $reflection = new \ReflectionObject($dependencyFactory);
        $property = $reflection->getProperty('frozen');
        $property->setValue($dependencyFactory, false);
    }

    /**
     * @param mixed ...$args
     *
     * @return mixed
     *
     * @throws \ReflectionException
     */
    private function call(string $methodName, ...$args): mixed
    {
        $reflection = new \ReflectionObject($this->getOriginalCommand());
        $method = $reflection->getMethod($methodName);

        /** @noinspection PhpExpressionResultUnusedInspection */
        $method->setAccessible(true);

        return $method->invoke($this->getOriginalCommand(), ...$args);
    }
}