<?php

namespace Webneft\Soap\Exception;

use Bitrix\Main\Loader;
use Exception;
use Throwable;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class AppException extends Exception
{
    protected $logger;
    protected $logMessage;
    protected $logUuid;
    protected $logFile;
    protected $logFileSaved;
    protected $logDataFile;

    public function __construct($message = '', $logName = 'base', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->logger = new Logger($logName);

        $this->setLogMessage($message);
        $this->setLogDataFile([
            'uuid' => $this->getLogUuid(),
            'line' => $this->getLine(),
            'file' => $this->getFile(),
            'trace' => $this->getTrace()
        ]);
    }

    protected function setLogMessage($message)
    {
        $this->logMessage = $message;
    }

    public function getLogMessage()
    {
        return $this->logMessage ? $this->logMessage : $this->getMessage();
    }

    protected function setLogUuid($uuid)
    {
        $this->logUuid = $uuid;
    }

    public function getLogUuid()
    {
        if (!$this->logUuid) {
            $this->setLogUuid(self::uuid());
        }

        return $this->logUuid;
    }

    protected function setLogFile($path)
    {
        $this->logFile = $path;
    }

    public function getLogFile()
    {
        if (!$this->logFile) {
            $config = \Bitrix\Main\Config\Configuration::getInstance();
            $file = $config->get("logs")['file'];
            $this->setLogFile($file);
        }
        return $this->logFile;
    }

    protected function setLogFileSaved($path)
    {
        $this->logFileSaved = $path;
    }

    public function getLogFileSaved()
    {
        return $this->logFileSaved;
    }

    protected function setLogDataFile($data)
    {
        if (isset($data['trace'])) {
            $data['trace'] = $this->clearTrace($data['trace']);
        }

        $data = $this->clearFilesContent($data);

        $this->logDataFile = $data;
    }

    public function getLogDataFile()
    {
        return $this->logDataFile;
    }

    public function log()
    {
        $message = "exception: {$this->getLogMessage()}";

        $this->logFile($message);
    }

    protected function logFile($message, $maxFiles = 30)
    {
        try {
            $fileHandler = new RotatingFileHandler($this->getLogFile(), $maxFiles);
            $this->logger->pushHandler(
                $fileHandler->setFormatter(new JsonFormatter())
            );

            $this->logger->alert($message, $this->getLogDataFile());

            $this->setLogFileSaved($fileHandler->getUrl());
        } catch (\Exception $e) {

        }
    }

    protected function clearTrace($trace)
    {
        if (is_array($trace)) {
            foreach ($trace as &$item) {
                $item['args'] = 'удалено из лога';
            }
        }

        return $trace;
    }

    public function clearFilesContent($data)
    {
        if (is_array($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                if (isset($value['Expansion']) && isset($value['Data'])) {
                    $value['Data'] = 'удалено из лога';
                }

                $result[$key] = $this->clearFilesContent($value);
            }
            return $result;
        }

        return $data;
    }

    public function sendAlert($title, $text = '')
    {
        $fields = [
            [
                'title' => 'Ошибка',
                'value' => $this->getLogMessage(),
            ]
        ];

        if ($this->getLogUuid()) {
            $fields[] = [
                'title' => 'Uuid ошибки',
                'value' => $this->getLogUuid(),
            ];
        }

        if ($this->getLogFileSaved()) {
            $fields[] = [
                'title' => 'Файл лога',
                'value' => $this->getLogFileSaved(),
            ];
        }

        // Отправка в slack
        if (Loader::includeModule('dk.bot')) {
            try {
                $bot = new \Dk\Bot\Controller();
                $bot->send([
                    'channel' => 'bear',
                    'title' => $title,
                    'text' => $text,
                    'url' => "",
                    'fields' => $fields
                ]);
            } catch (\Exception $e) {

                // todo: handle
            }
        }
    }

    public static function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}