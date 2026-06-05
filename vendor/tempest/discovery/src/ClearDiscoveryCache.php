<?php

namespace OmniMailDeps\Tempest\Discovery;

final class ClearDiscoveryCache
{
    public function __invoke(DiscoveryCache $cache): void
    {
        $cache->clear();
    }
}
