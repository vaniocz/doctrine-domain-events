<?php

namespace Vanio\DoctrineDomainEvents;

final class EventCounter
{
    private static $count = 0;

    public static function next(): int
    {
        return ++self::$count;
    }
}
