<?php

namespace JooosiMail\Tests\Rector\Rule\DowngradeAsymmetricVisibilityRector\Fixture;

final class Location
{
    public function __construct(
        public readonly string $path,
        private(set) array $ignore = [],
    ) {
    }
}

trait ManipulatesString
{
    private(set) string $value;

    public function __construct(string $value = '')
    {
        $this->value = $value;
    }
}

?>
-----
<?php

namespace JooosiMail\Tests\Rector\Rule\DowngradeAsymmetricVisibilityRector\Fixture;

final class Location
{
    public function __construct(
        public readonly string $path,
        public array $ignore = [],
    ) {
    }
}

trait ManipulatesString
{
    public string $value;

    public function __construct(string $value = '')
    {
        $this->value = $value;
    }
}

?>
