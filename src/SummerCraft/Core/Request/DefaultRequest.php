<?php

namespace SummerCraft\Core\Request;

use RuntimeException;
use SummerCraft\Core\ComponentManaging\LifeCycle\RequestScopeComponent;
use SummerCraft\Core\Routing\Exception\BadRequestException;

class DefaultRequest implements Request, RequestScopeComponent
{
    private const METHOD_MAP = [
        'cli' => self::METHOD_CLI,
        'get' => self::METHOD_GET,
        'post' => self::METHOD_POST,
        'put' => self::METHOD_PUT,
        'patch' => self::METHOD_PATCH,
        'delete' => self::METHOD_DELETE,
        'head' => self::METHOD_HEAD,
        'connect' => self::METHOD_CONNECT,
    ];

    /**
     * TODO PHP 7.2 (rewrite on sql_object_id)
     * @var int
     */
    private static int $autoIncrementValue = 0;

    private int $id;

    /**
     * @var array|null
     */
    private $source;

    /**
     * @var string[] $segments
     */
    private array $segments;

    private string $segmentsUri;

    private int $method;

    private string $domain;

    private string $uri;

    protected RequestConfig $config;

    /**
     * @param RequestConfig $config
     * @param array|null $source
     */
    public function __construct(RequestConfig $config, ?array $source = null)
    {
        $this->id = ++self::$autoIncrementValue;
        $this->config = $config;
        $this->source = $source;
        $this->initialize();
        $this->checkServer();

        $this->segments = $this->parseRequestUri();
        $this->segmentsUri = '/' . implode('/', $this->segments);

        $this->domain = $this->getGlobal(INPUT_SERVER, 'HTTP_HOST', 'cli');
        $this->uri = $this->getGlobal(INPUT_SERVER, 'REQUEST_URI', $this->segmentsUri);

        $methodName = strtolower($this->getGlobal(INPUT_SERVER, 'REQUEST_METHOD', 'cli'));
        if (!isset(self::METHOD_MAP[$methodName])) {
            throw new RuntimeException("Request method with name '$methodName' is not supported");
        }
        $this->method = self::METHOD_MAP[$methodName];
        //$body = file_get_contents('php://input');
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    public function getSegmentsUri(): string
    {
        return $this->segmentsUri;
    }

    public function getProtocol(): string
    {
        $protocol = $this->getGlobal(INPUT_SERVER, 'SERVER_PROTOCOL');
        return in_array($protocol, ['HTTP/1.0', 'HTTP/1.1', 'HTTP/2'], true) ? $protocol : 'HTTP/1.1';
    }

    public function getMethod(): int
    {
        return $this->method;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function isCli(): bool
    {
        return (PHP_SAPI === 'cli' OR defined('STDIN'));
    }

    public function isAJAX(): bool
    {
        $requestedWith = $this->getGlobal(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH', '');
        return strtolower($requestedWith) === 'xmlhttprequest';
    }

    public function isSecure(): bool
    {
        $httpsFlag = $this->getGlobal(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH', '');
        if ($httpsFlag && strtolower($httpsFlag) !== 'off') {
            return true;
        }
        $forwardedProto = $this->getGlobal(INPUT_SERVER, 'HTTP_X_FORWARDED_PROTO', '');
        if ($forwardedProto && $forwardedProto === 'https') {
            return true;
        }
        $frontEndHttps = $this->getGlobal(INPUT_SERVER, 'HTTP_FRONT_END_HTTPS', '');
        return $frontEndHttps && strtolower($frontEndHttps) !== 'off';
    }

    public function getAllPostAsArray(): array
    {
        return $this->getGlobalAsArray(INPUT_POST);
    }

    public function getAllGetAsArray(): array
    {
        return $this->getGlobalAsArray(INPUT_GET);
    }

    public function getGetAsString(string $key, string $default = ''): string
    {
        $value = $this->getGlobal(INPUT_GET, $key);
        if ($value === null) {
            return $default;
        }
        if (!is_string($value)) {
            throw new BadRequestException("GET key $key is not string it is: " . gettype($value));
        }
        return $value;
    }

    public function getPostAsString(string $key, string $default = ''): string
    {
        $value = $this->getGlobal(INPUT_POST, $key);
        if ($value === null) {
            return $default;
        }
        if (!is_string($value)) {
            throw new BadRequestException("POST key $key is not string it is: " . gettype($value));
        }
        return $value;
    }

    public function getGetAsArray(string $key): array
    {
        $value = $this->getGlobal(INPUT_GET, $key);
        if ($value === null) {
            return [];
        }
        if (!is_array($value)) {
            throw new BadRequestException("GET key $key is not array it is: " . gettype($value));
        }
        return $value;
    }

    public function getPostAsArray(string $key): array
    {
        $value = $this->getGlobal(INPUT_POST, $key);
        if ($value === null) {
            return [];
        }
        if (!is_array($value)) {
            throw new BadRequestException("POST key $key is not array it is: " . gettype($value));
        }
        return $value;
    }


    public function getHead(string $key, string $default = ''): string
    {
        $key = strtoupper( str_replace('-', '_', $key));
        $value = $this->getGlobal(INPUT_SERVER, 'HTTP_' . $key);
        if ($value === null) {
            return $default;
        }
        return $value;
    }

    public function getFile(string $key): RequestFile
    {
        return $this->parseFileData($_FILES[$key] ?? []);
    }

    public function getFiles(string $key): array
    {
        $result = [];
        $filesData = $_FILES[$key] ?? [];
        foreach ($filesData as $fileData) {
            if (!is_array($fileData)) {
                continue;
            }
            $result[] = $this->parseFileData($fileData);
        }
        return $result;
    }

    private function parseFileData(array $fileData): RequestFile
    {
        if (!$fileData) {
            return new RequestFile('', '', 'File not uploaded');
        }
        $errorCode = $fileData['error'] ?? null;
        $fileName = preg_replace('/[^A-Za-zА-Яа-я0-9_-]/ui', '', ($fileData['name'] ?? 'undefined'));
        $fileTemp = $fileData['tmp_name'] ?? '';
        if (($fileData['error'] ?? 100) !== 0 ) {
            return new RequestFile('', $fileName, "request:file_uploaded_with_error_$errorCode");
        }
        if (!$fileTemp) {
            return new RequestFile('', $fileName, 'request:file_uploaded_with_error_file_not_found');
        }
        return new RequestFile($fileTemp, $fileName, '');
    }

    public function getRequestValue(string $key, string $default = ''): string
    {
        $value = $this->getGlobal(INPUT_POST, $key);
        if ($value === null) {
            $value = $this->getGlobal(INPUT_GET, $key);
        }
        if ($value === null) {
            return $default;
        }
        return $value;
    }


    public function getCookie(string $key, string $default = ''): string
    {
        $value = $this->getGlobal(INPUT_COOKIE, $key);
        if ($value === null) {
            return $default;
        }
        return $value;
    }


    public function getClientIP(): string
    {
        return $this->getGlobal(INPUT_SERVER, 'REMOTE_ADDR', '');
    }

    protected function getGlobalAsArray($type): array
    {
        $source = $this->getGlobalSource($type);
        $result = [];
        foreach ($source as $key => $value) {
            $result[filter_var($key, FILTER_DEFAULT)] = $value !== null
                ? filter_var($value, FILTER_DEFAULT) : null;
        }
        return $result;
    }

    protected function getGlobal($type, $key, $default = null)
    {
        $source = $this->getGlobalSource($type);
        $value = $source[$key] ?? $default;
        return $value !== null ? filter_var($value, FILTER_DEFAULT) : $value;
    }

    protected function getGlobalSource($type): array
    {
        $source = $this->source;
        if (!isset($source[$type])) {
            switch ($type) {
                case INPUT_GET : $source = $_GET;
                    break;
                case INPUT_POST : $source = $_POST;
                    break;
                case INPUT_COOKIE : $source = $_COOKIE;
                    break;
                case INPUT_SERVER : $source = $_SERVER;
                    break;
                case INPUT_ENV : $source = $_ENV;
                    break;
            }
        } else {
            $source = $source[$type];
        }
        return $source;
    }

    /**
     * Will parse the REQUEST_URI and automatically detect the URI from it,
     * fixing the query string if necessary.
     *
     * @return array
     */
    protected function parseRequestUri(): array
    {
        $globalRequestUri = $this->getGlobal(INPUT_SERVER, 'REQUEST_URI');
        $globalScriptName = $this->getGlobal(INPUT_SERVER, 'SCRIPT_NAME');

        if ($globalRequestUri === null && $globalScriptName === null) {
            return [];
        }

        // parse_url() returns false if no host is present, but the path or query string
        // contains a colon followed by a number
        $parts = parse_url('http://dummy' . $globalRequestUri);
        $query = $parts['query'] ?? '';
        $uri = $parts['path'] ?? '';

        if (isset($globalScriptName[0])) {
            if (strpos($uri, $globalScriptName) === 0) {
                $uri = (string) substr($uri, strlen($globalScriptName));
            } elseif (strpos($uri, dirname($globalScriptName)) === 0) {
                $uri = (string) substr($uri, strlen(dirname($globalScriptName)));
            }
        }

        // This section ensures that even on servers that require the URI to be in the query string (Nginx) a correct
        // URI is found, and also fixes the QUERY_STRING getServer var and $_GET array.
        if (trim($uri, '/') === '' && strncmp($query, '/', 1) === 0) {
            $query = explode('?', $query, 2);
            $uri = $query[0];
            parse_str($query[1] ?? '', $_GET);
        } else {
            parse_str($query, $_GET);
        }

        if ($this->source !== null) {
            $this->source[INPUT_GET] = $_GET;
        }

        if ($uri === '/' || $uri === '') {
            return [];
        }

        return $this->createSegments($uri);
    }

    /**
     * @param string $uri
     * @return array
     */
    protected function createSegments($uri): array
    {
        $uris = [];
        $tok = strtok($uri, '/');
        while ($tok !== false) {
            if (( ! empty($tok) || $tok === '0') && $tok !== '..') {

                $uriSegment = trim($this->removeInvisibleCharacters($tok, FALSE));
                if (
                    !empty($uriSegment)
                    && !empty($this->config->permittedUriChars)
                    && !preg_match(
                        '/^['.$this->config->permittedUriChars.']+$/iu',
                        $uriSegment
                    )
                ) {
                    throw new BadRequestException('The URI you submitted has disallowed characters.');
                }
                if ($uriSegment !== '') {
                    $uris[] = $uriSegment;
                }
            }
            $tok = strtok('/');
        }
        return $uris;
    }

    protected $charset = 'UTF-8';
    protected $string_mb_enabled = false;
    protected $string_iconv_enabled = false;

    protected function initialize(): void
    {
        $charset = strtoupper($this->charset);
        ini_set('default_charset', $charset);

        if (extension_loaded('mbstring'))
        {
            $this->string_mb_enabled = true;
            // This is required for mb_convert_encoding() to strip invalid characters.
            // That's utilized by CI_Utf8, but it's also done for consistency with iconv.
            mb_substitute_character('none');
        }

        // There's an ICONV_IMPL constant, but the PHP manual says that using
        // iconv's predefined constants is "strongly discouraged".
        if (extension_loaded('iconv')) {
            $this->string_iconv_enabled = true;
        }
        ini_set('php.internal_encoding', $charset);
    }

    protected function checkServer(): void
    {
        // @todo Server min php8 require
        if (!$this->string_iconv_enabled && !$this->string_mb_enabled) {
            throw new RuntimeException('Server not support iconv or mbstring');
        }
    }

    /**
     * Remove Invisible Characters
     *
     * This prevents sandwiching null characters
     * between ascii characters, like Java\0script.
     *
     * @param	string
     * @param	bool
     * @return	string
     */
    private function removeInvisibleCharacters(string $str, bool $url_encoded = TRUE): string
    {
        $nonDisplayable = [];

        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($url_encoded) {
            $nonDisplayable[] = '/%0[0-8bcef]/i';	// url encoded 00-08, 11, 12, 14, 15
            $nonDisplayable[] = '/%1[0-9a-f]/i';	// url encoded 16-31
            $nonDisplayable[] = '/%7f/i';	// url encoded 127
        }
        $nonDisplayable[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

        do {
            $str = preg_replace($nonDisplayable, '', $str, -1, $count);
        } while ($count);
        return $str;
    }
}
