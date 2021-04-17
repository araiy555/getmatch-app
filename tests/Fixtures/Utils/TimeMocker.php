<?php

namespace App\Tests\Fixtures\Utils;

/**
 * Lets us override time() and microtime() when loading database fixtures.
 */
class TimeMocker {
    public static $times = [];

    public static function mock(string $className, \DateTimeInterface $dateTime): void {
        self::$times[$className] = $dateTime->getTimestamp();

        $namespace = substr($className, 0, strrpos($className, '\\'));

        if ($namespace === '') {
            throw new \InvalidArgumentException('Cannot mock functions for non-namespaced classes');
        }

        if (\function_exists("$namespace\\time") || \function_exists("$namespace\\microtime")) {
            return;
        }

        $self = __CLASS__;

        eval(<<<EOPHP
namespace $namespace;

function microtime(\$get_as_float) {
    \$caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'];

    if (!isset(\\$self::\$times[\$caller])) {
        return \\microtime(\$get_as_float);
    }

    if (!\$get_as_float) {
        throw new \BadMethodCallException('Mocked microtime() must be called with true');
    }

    return (float) \\$self::\$times[\$caller];
}

function time() {
    \$caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'];

    if (!isset(\\$self::\$times[\$caller])) {
        return \\time();
    }

    return \\$self::\$times[\$caller];
}
EOPHP
        );
    }
}
