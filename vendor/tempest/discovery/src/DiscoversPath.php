<?php

declare (strict_types=1);
namespace OmniMailDeps\Tempest\Discovery;

interface DiscoversPath
{
    public function discoverPath(DiscoveryLocation $location, string $path): void;
}
