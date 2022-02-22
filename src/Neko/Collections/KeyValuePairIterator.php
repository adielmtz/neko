<?php declare(strict_types=1);
namespace Neko\Collections;

use Iterator;
use Neko\InvalidOperationException;
use function current;
use function next;
use function reset;

final class KeyValuePairIterator implements Iterator
{
    /**
     * @var KeyValuePair[]
     */
    private array $entries;
    private int $map_version;
    private int $current_version;

    public function __construct(array &$entries, int &$version)
    {
        $this->entries = &$entries;
        $this->map_version = &$version;
        $this->current_version = $version;
    }

    public function current(): mixed
    {
        return current($this->entries)->getValue();
    }

    /**
     * @throws InvalidOperationException
     */
    public function next(): void
    {
        if ($this->current_version !== $this->map_version) {
            throw new InvalidOperationException('Collection was modified');
        }

        next($this->entries);
    }

    public function key(): mixed
    {
        return current($this->entries)->getKey();
    }

    public function valid(): bool
    {
        if ($this->current_version !== $this->map_version) {
            throw new InvalidOperationException('Collection was modified');
        }

        return current($this->entries) !== false;
    }

    /**
     * @throws InvalidOperationException
     */
    public function rewind(): void
    {
        if ($this->current_version !== $this->map_version) {
            throw new InvalidOperationException('Collection was modified');
        }
        reset($this->entries);
    }
}