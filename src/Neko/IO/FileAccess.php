<?php declare(strict_types=1);
namespace Neko\IO;

enum FileAccess: int
{
    case Read = 1;
    case Write = 2;
    case ReadWrite = 3;

    public function canRead(): bool
    {
        return (bool) ($this->value & FileAccess::Read->value);
    }

    public function canWrite(): bool
    {
        return (bool) ($this->value & FileAccess::Write->value);
    }
}
