<?php

namespace JooosiMail\Tests\Rector\Rule\DowngradeCloneWithRector\Fixture;

final class Cache
{
    public function __construct(
        private Strategy $strategy,
        private ?Pool $pool = null,
    ) {
    }

    public function withStrategy(Strategy $strategy): self
    {
        return clone($this, [
            'strategy' => $strategy,
        ]);
    }
}

final class Strategy
{
}

final class Pool
{
}

?>
-----
<?php

namespace JooosiMail\Tests\Rector\Rule\DowngradeCloneWithRector\Fixture;

final class Cache
{
    public function __construct(
        private Strategy $strategy,
        private ?Pool $pool = null,
    ) {
    }

    public function withStrategy(Strategy $strategy): self
    {
        return new self(strategy: $strategy, pool: $this->pool);
    }
}

final class Strategy
{
}

final class Pool
{
}

?>
