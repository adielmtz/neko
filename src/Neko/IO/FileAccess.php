<?php declare(strict_types=1);
namespace Neko\IO;

/**
 * Specifies the level of access for IO operations.
 */
enum FileAccess: int
{
    case Read = 1;
    case Write = 2;
    case ReadWrite = 3;

    /**
     * Returns true if the access level allows read operations.
     *
     * @return bool
     */
    public function canRead(): bool
    {
        return (bool) ($this->value & FileAccess::Read->value);
    }

    /**
     * Returns true if the access level allows write operations.
     *
     * @return bool
     */
    public function canWrite(): bool
    {
        return (bool) ($this->value & FileAccess::Write->value);
    }
}
