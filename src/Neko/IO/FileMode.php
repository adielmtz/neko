<?php declare(strict_types=1);
namespace Neko\IO;

use InvalidArgumentException;

enum FileMode: string
{
    case Append = 'a';       // Opens or creates
    case Create = 'x';       // Opens. Throws if file exist
    case Open = 'r';         // Opens. Throws if file does not exist
    case OpenOrCreate = 'c'; // Opens or creates.
    case Truncate = 'w';     // Opens and truncates or creates empty file

    public function getOpenMode(FileAccess $access): string
    {
        switch ($this) {
            case FileMode::Append:
            case FileMode::Create:
            case FileMode::OpenOrCreate:
            case FileMode::Truncate:
                if (!$access->canWrite()) {
                    throw new InvalidArgumentException(
                        sprintf(
                            '%s::%s requires %s::%s permission',
                            FileMode::class,
                            $this->name,
                            FileAccess::class,
                            FileAccess::Write->name
                        )
                    );
                }

                return $this->value . ($access->canRead() ? '+' : '');

            case FileMode::Open:
                if (!$access->canRead()) {
                    throw new InvalidArgumentException(
                        sprintf(
                            '%s::%s requires %s::%s permission',
                            FileMode::class,
                            $this->name,
                            FileAccess::class,
                            FileAccess::Read->name
                        )
                    );
                }

                return $this->value . ($access->canWrite() ? '+' : '');
        }
    }
}
