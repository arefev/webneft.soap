<?php

namespace Webneft\Soap\Exception;

use Throwable;

class SoapClientException extends AppException
{
    protected $defaultError = 'Нет связи с системой, ведутся технические работы.';

    public function __construct($message, \SoapFault $soapException = null, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, 'soapClient', $code, $previous);

        $this->setUpdateError();

        if ($soapException) {
            $this->setLogDataFile([
                'uuid' => $this->getLogUuid(),
                'line' => $soapException->getLine(),
                'file' => $soapException->getFile(),
                'trace' => $soapException->getTrace()
            ]);
        }

        $this->log();
        $this->sendAlert('ALERT: Soap client exception');
    }

    protected function clearTrace($trace)
    {
        if (is_array($trace)) {
            foreach ($trace as &$item) {
                if ($item['class'] != 'SoapClient') {
                    $item['args'] = 'удалено из лога';
                } else {
                    foreach ($item['args'] as $arg) {
                        if (isset($arg['stream_context']) || isset($arg['password'])) {
                            $item['args'] = 'удалено из лога';
                        }
                    }
                }
            }
        }

        return $trace;
    }

    public function isUpdateTime()
    {
        if (preg_match_all("/([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?/", $this->getMessage(), $time)) {
            return true;
        }

        return false;
    }

    public function getUpdateTime()
    {
        $updateTime = '';
        if (preg_match_all("/([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?/", $this->getMessage(), $time)) {

            $date = date('d-m-Y');

            if (preg_match_all("/(0[1-9]|[1-2][0-9]|3[0-1]).(0[1-9]|1[0-2]).[0-9]{4}/", $this->getMessage(), $date)) {
                $date = $date[0][0];
            }

            $updateTime = "{$date} с {$time[0][0]} по {$time[0][1]} (по Московскому времени)";
        }

        return $updateTime;
    }

    public function setUpdateError()
    {
        if ($this->isUpdateTime()) {
            $this->defaultError = "Обновление системы. Плановое время обновления {$this->getUpdateTime()}";
        }
    }

    public function getDefaultError()
    {
        return $this->defaultError;
    }
}