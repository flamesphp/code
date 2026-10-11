<?php

declare(strict_types=1);

if (! \function_exists('getallheaders')) {
    /**
     * @return array<string, string>
     */
    function getallheaders(): array
    {
        $headers = [];
        $copyServer = [
            'CONTENT_TYPE' => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5' => 'Content-Md5',
        ];

        foreach ($_SERVER as $key => $value) {
            if (\substr((string) $key, 0, 5) === 'HTTP_') {
                $key = \substr((string) $key, 5);
                if (! isset($copyServer[$key]) || ! isset($_SERVER[$key])) {
                    $key = \str_replace(' ', '-', \ucwords(\strtolower(\str_replace('_', ' ', $key))));
                    $headers[$key] = $value;
                }
            } elseif (isset($copyServer[$key]) && ! isset($_SERVER[$key])) {
                $headers[$copyServer[$key]] = $value;
            }
        }

        return $headers;
    }
}
