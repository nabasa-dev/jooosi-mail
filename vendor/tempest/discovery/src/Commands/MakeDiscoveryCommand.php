<?php

declare (strict_types=1);
namespace OmniMailDeps\Tempest\Discovery\Commands;

use OmniMailDeps\Tempest\Console\ConsoleArgument;
use OmniMailDeps\Tempest\Console\ConsoleCommand;
use OmniMailDeps\Tempest\Core\PublishesFiles;
use OmniMailDeps\Tempest\Discovery\SkipDiscovery;
use OmniMailDeps\Tempest\Discovery\Stubs\DiscoveryStub;
use OmniMailDeps\Tempest\Generation\Php\ClassManipulator;
use OmniMailDeps\Tempest\Generation\Php\DataObjects\StubFile;
if (class_exists(ConsoleCommand::class)) {
    final class MakeDiscoveryCommand
    {
        use PublishesFiles;
        #[ConsoleCommand(name: 'make:discovery', description: 'Creates a new discovery class', aliases: ['discovery:make', 'discovery:create', 'create:discovery'])]
        public function __invoke(
            #[ConsoleArgument(description: 'The name of the discovery class to create')]
            string $className
        ): void
        {
            $suggestedPath = $this->getSuggestedPath($className);
            $targetPath = $this->promptTargetPath($suggestedPath);
            $shouldOverride = $this->askForOverride($targetPath);
            $this->stubFileGenerator->generateClassFile(stubFile: StubFile::from(DiscoveryStub::class), targetPath: $targetPath, shouldOverride: $shouldOverride, manipulations: [fn(ClassManipulator $class) => $class->removeClassAttribute(SkipDiscovery::class)]);
            $this->console->writeln();
            $this->console->success(sprintf('File successfully created at <file="%s"/>.', $targetPath));
        }
    }
}
