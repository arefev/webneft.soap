<?php
use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('webneft.soap',
    array(
        'Webneft\Soap\Module' => 'lib/Module.php',
        'Webneft\Soap\Connection' => 'lib/Connection.php',
        'Webneft\Soap\Exception\AppException' => 'lib/Exception/AppException.php',
        'Webneft\Soap\Exception\SoapClientException' => 'lib/Exception/WebsocketClientException.php'
    )
);