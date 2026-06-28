<?php

namespace JooosiMailDeps\Tempest\Discovery;

final class ClearDiscoveryCache
{
    public function __invoke(DiscoveryCache $cache): void
    {
        $cache->clear();
    }
}
