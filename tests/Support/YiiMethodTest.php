<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Support;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Vix\PhpstanRules\Support\YiiMethod;

final class YiiMethodTest extends TestCase
{
    public function testDetectsMatchingParentCall(): void
    {
        $method = $this->parseMethod(<<<'PHP'
<?php

final class Example
{
    public function beforeSave(bool $insert): bool
    {
        return parent::beforeSave($insert);
    }
}
PHP, 'beforeSave');

        self::assertTrue($method->hasParentCall());
    }

    public function testIgnoresDifferentParentCall(): void
    {
        $method = $this->parseMethod(<<<'PHP'
<?php

final class Example
{
    public function afterSave(bool $insert, array $changedAttributes): void
    {
        parent::afterDelete();
    }
}
PHP, 'afterSave');

        self::assertFalse($method->hasParentCall());
    }

    public function testDetectsMutationCallOnThis(): void
    {
        $method = $this->parseMethod(<<<'PHP'
<?php

final class Example
{
    public function afterFind(): void
    {
        $this->save(false);
    }
}
PHP, 'afterFind');

        self::assertTrue($method->callsAnyThisMethod(['save', 'update', 'delete']));
    }

    public function testIgnoresMutationCallOnOtherVariable(): void
    {
        $method = $this->parseMethod(<<<'PHP'
<?php

final class Example
{
    public function afterFind(): void
    {
        $model = new OtherModel();
        $model->save(false);
    }
}
PHP, 'afterFind');

        self::assertFalse($method->callsAnyThisMethod(['save', 'update', 'delete']));
    }

    private function parseMethod(string $code, string $methodName): YiiMethod
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        self::assertNotNull($ast);

        $method = (new NodeFinder())->findFirst(
            $ast,
            static fn(mixed $node): bool => $node instanceof ClassMethod && $node->name->toString() === $methodName,
        );

        self::assertInstanceOf(ClassMethod::class, $method);

        return new YiiMethod($method);
    }
}
