<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Output\Status;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function explode;
use function implode;
use function is_string;
use function sprintf;

use const PHP_EOL;

final class OverWritingOutPut implements OutputInterface
{
    private int $previousLinecount = 0;

    public function __construct(
        private readonly OutputInterface $output,
    ) {
    }

    /**
     * @param iterable<string>|string $messages
     *
     * @phpstan-ignore-next-line
     */
    public function write(iterable|string $messages, bool $newline = false, int $options = 0): void
    {
        $this->output->write($messages, $newline, $options);
    }

    /**
     * @param iterable<string>|string $messages
     *
     * @phpstan-ignore-next-line
     */
    public function writeln(iterable|string $messages, int $options = 0): void
    {
        if (! is_string($messages)) {
            $messages = implode(PHP_EOL, [...$messages]);
        }

        if ($this->previousLinecount > 0) {
            $this->output->write(sprintf("\x1b[%dA", $this->previousLinecount));
            $this->output->write("\x1b[0J");
        }

        $this->previousLinecount = count(explode(PHP_EOL, $messages));

        $this->output->writeln($messages, $options);
    }

    public function setVerbosity(int $level): void
    {
        $this->output->setVerbosity($level);
    }

    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    public function isVeryVerbose(): bool
    {
        return $this->output->isVeryVerbose();
    }

    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    public function setDecorated(bool $decorated): void
    {
        $this->output->setDecorated($decorated);
    }

    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        $this->output->setFormatter($formatter);
    }

    public function getFormatter(): OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }
}
