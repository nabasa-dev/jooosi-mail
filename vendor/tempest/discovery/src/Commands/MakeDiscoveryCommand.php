<?php

declare (strict_types=1);
namespace JooosiMailDeps\Tempest\Discovery\Commands;

use JooosiMailDeps\Tempest\Console\ConsoleArgument;
use JooosiMailDeps\Tempest\Console\ConsoleCommand;
use JooosiMailDeps\Tempest\Core\PublishesFiles;
use JooosiMailDeps\Tempest\Discovery\SkipDiscovery;
use JooosiMailDeps\Tempest\Discovery\Stubs\DiscoveryStub;
use JooosiMailDeps\Tempest\Generation\Php\ClassManipulator;
use JooosiMailDeps\Tempest\Generation\Php\DataObjects\StubFile;
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
