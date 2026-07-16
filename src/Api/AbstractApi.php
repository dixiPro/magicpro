<?php

namespace MagicProSrc\Api;

class ApiException extends \Exception
{
    public function __construct(string $message, protected array $data = [])
    {
        parent::__construct($message);
    }

    public function getData(): array
    {
        return $this->data;
    }
}

abstract class AbstractApi
{
    protected array $map = [];

    public static function run(string $command, array $params = []): array
    {
        $service = new static();
        $params['command'] = $command;

        try {
            if (!isset($service->map[$command])) {
                throw new \Exception('Unknown command: ' . $command);
            }

            $method = $service->map[$command];

            if (!method_exists($service, $method)) {
                throw new \Exception('Handler not implemented: ' . $method);
            }

            $data = $service->$method($params);

            return [
                'status'   => true,
                'errorMsg' => '',
                'data'     => $data,
                'request'  => $params,
            ];
        } catch (\Throwable $e) {
            return [
                'status'   => false,
                'line'     => $e->getFile() . ' ' . $e->getLine(),
                'errorMsg' => $e->getMessage(),
                'data'     => $e instanceof ApiException ? $e->getData() : [],
                'request'  => $params,
            ];
        }
    }
}
