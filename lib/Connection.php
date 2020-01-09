<?php

namespace Webneft\Soap;

use mysql_xdevapi\DatabaseObject;
use Webneft\Soap\Module;
use Webneft\Soap\Exception\SoapClientException;
use Bitrix\Main\Config\Configuration as BitrixConfig;

class Connection
{
    private static $instance;
    private $lastError;
    private $connectionIsFail;
    private $queryFault;
    private $soapClient;
    private $url;
    private $login;
    private $password;
    private $result;

    public static function instance(): Connection
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __clone()
    {
    }

    private function __construct()
    {
        $conf = BitrixConfig::getInstance()->get(Module::SETTINGS_NAME);
        $this->url = $conf['url'];
        $this->login = $conf['login'];
        $this->password = $conf['password'];

        if (!strlen($this->url) || !strlen($this->login) || !strlen($this->password)) {
            throw new SoapClientException('Не указан URL, логин или пароль.');
        }

        try {
            $context = stream_context_create([
                'ssl' => [
                    // set some SSL/TLS specific options
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            $this->soapClient = new \SoapClient($this->url,
                array(
                    'soap_version' => SOAP_1_2,
                    'trace' => true,
                    'exceptions' => true,
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'login' => $this->login,
                    'password' => $this->password,
                    'stream_context' => $context
                )
            );

        } catch (\SoapFault $soapFault) {
            $this->lastError = $soapFault->getMessage();
            $this->connectionIsFail = true;

            throw new SoapClientException($this->lastError, $soapFault);
        }
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function query($method, $params): Connection
    {
        $method = trim($method);
        $result = null;

        if (!strlen($method)) {
            throw new SoapClientException('Не указан метод.');
        }

        if ($this->soapClient !== null) {
            try {
                $result = $this->soapClient->$method($params);
            } catch (\SoapFault $soapFault) {
                $this->lastError = $soapFault->getMessage();
                $this->queryFault = true;

                throw new SoapClientException($this->lastError, $soapFault);
            }
        }

        $this->result = $result;

        return $this;
    }

    public function toArray($data)
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = self::toArray($value);
            }
            return $result;
        }

        return $data;
    }

    public function checkConnection(): bool
    {
        if ($this->connectionIsFail)
            return false;

        return true;
    }

    public function item(): ?array
    {
        $item = $this->items();
        return isset($item[0]) ? $item[0] : null;
    }

    public function items(): array
    {
        $result = $this->toArray($this->result);
        
        $items = [];
        if (isset($result['return']['Elem'])) {
            $items = $result['return']['Elem'];

            if (!isset($items[0])) {
                $items = [$items];
            }
        } elseif (isset($result['return'])) {
            $items = [$result['return']];
        }

        return $items;
    }

    public function result(): ?object
    {
        return $this->result;
    }
}