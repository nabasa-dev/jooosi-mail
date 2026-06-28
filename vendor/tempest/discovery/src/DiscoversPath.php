<?php

declare (strict_types=1);
namespace JooosiMailDeps\Tempest\Discovery;

interface DiscoversPath
{
    public function discoverPath(DiscoveryLocation $location, string $path): void;
}
