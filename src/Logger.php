<?php
/**
 * Logger
 *
 * @author sskaje
 *
 */

namespace sskaje\mitm;


class Logger
{
    /**
     * Use php syslog constants as log levels
     *
     * @var int
     */
    static public $log_level = LOG_INFO;

    /**
     * Dump Plain text or Hex string
     *
     * @var string
     */
    static public $dump = 'plain';

    /**
     * Send output to
     *
     * @var resource
     */
    static public $output = STDERR;

    /**
     * Log String
     *
     * @param string $msg
     * @param mixed  $data
     */
    static public function Log($msg, $data = null)
    {
        self::Out(rtrim($msg) . "\n");
        if (self::$log_level >= LOG_DEBUG && $data !== null) {
            self::Dump($data);
        }
    }

    /**
     * Write Data
     *
     * @param string $data
     */
    static protected function Out($data)
    {
        if (is_array($data)) {
            $data = implode('', $data);
        }
        fwrite(self::$output, $data);
    }

    /**
     * Dump data
     *
     * @param string $data
     */
    static public function Dump($data)
    {
        if (self::$dump == 'plain') {
            self::DumpPlain($data);
        } else {
            self::DumpHex($data);
        }
    }

    /**
     * Dump Hex
     *
     * @param string $data
     * @return string
     */
    static public function DumpHex($data)
    {
        self::Out("\n======== DUMP DATA ========\n\n");

        static $from = '';
        static $to = '';
        if ($from === '') {
            for ($i = 0; $i < 0x21; $i++) {
                $from .= chr($i);
                $to .= '.';
            }
            for ($i = 0x7E; $i <= 0xFF; $i++) {
                $from .= chr($i);
                $to .= '.';
            }
        }

        if (isset($data[0])) {

            $linedelim = PHP_EOL;
            $ncols     = 16;
            $c         = 1;
            $offset    = 0;
            $len       = strlen($data);

            self::Out(sprintf('%08x: ', $offset));
            for ($i = 0; $i < $len; $i++, $c++) {
                self::Out(bin2hex($data[$i]) . ' ');    # write hex dump

                # write ascii at end of line.
                if ($c === $ncols) {
                    self::Out(
                        [
                            '|',
                            strtr(substr($data, $i - $ncols + 1, $ncols), $from, $to),
                            '|',
                            $linedelim,
                        ]
                    );
                    $c = 0;
                    $offset += $ncols;

                    # next line address
                    self::Out(sprintf('%08x: ', $offset));
                }
            }

            $remains = $ncols - ($i % $ncols);
            if ($remains !== $ncols) {
                self::Out(
                    [
                        str_repeat('   ', $remains),
                        '|',
                        strtr(substr($data, $i - ($i % $ncols)), $from, $to),
                        '|',
                        $linedelim,
                    ]
                );
            }
        }

        self::Out("\n======== DUMP DATA ========\n\n");

        return '';
    }

    /**
     * Dump as Plain text
     *
     * @param string $data
     */
    static public function DumpPlain($data)
    {
        self::Out("\n======== DUMP DATA ========\n\n");
        self::Out($data);
        self::Out("\n======== DUMP DATA ========\n\n");
        self::Out("\n");
    }
}

# EOF