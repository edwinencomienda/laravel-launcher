<?php

namespace App\Services;

use Exception;
use phpseclib3\Net\SSH2;

class SshService
{
    protected SSH2 $ssh;

    public function __construct(protected string $host, protected string $user, protected string $password) {}

    public function connect()
    {
        $this->ssh = new SSH2($this->host);

        if (! $this->ssh->login($this->user, $this->password)) {
            throw new Exception('Server connection failed');
        }
    }

    public function runCommand(string $command, int $timeout = 10): string
    {
        $output = '';
        $buffer = '';

        $this->ssh->setTimeout($timeout);

        $this->ssh->exec($command, function ($chunk) use (&$output, &$buffer) {
            $buffer .= $chunk;
            $lines = explode("\n", $buffer);

            // Keep the last incomplete line in the buffer
            $buffer = array_pop($lines);

            // Process complete lines
            foreach ($lines as $line) {
                $output .= $line."\n";
                echo $line."\n";
            }
        });

        // Handle any remaining content in the buffer
        if (! empty($buffer)) {
            $output .= $buffer;
            echo $buffer;
        }

        if ($this->ssh->getExitStatus() !== 0) {
            $output .= "\nExit code: ".$this->ssh->getExitStatus();
            $output .= "\nError: ".$this->ssh->getStdError();
            throw new Exception("error while running command: {$output}");
        }

        $output .= "\nExit code: ".$this->ssh->getExitStatus();

        return $output;
    }

    public function getExitStatus()
    {
        return $this->ssh->getExitStatus();
    }
}
