<?php

namespace App\Storage;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\FilesystemAdapter as LeagueFilesystemAdapter;
use League\Flysystem\Visibility;
use League\Flysystem\UnableToRetrieveMetadata;
use RuntimeException;

/** Azure Blob Storage over REST with App Service system-assigned managed identity. */
class AzureBlobAdapter implements LeagueFilesystemAdapter
{
    private ?string $token = null;
    private int $expiresAt = 0;

    public function __construct(private readonly string $account, private readonly string $container) {}

    private function token(): string
    {
        if ($this->token && time() < $this->expiresAt - 120) return $this->token;
        $endpoint = getenv('IDENTITY_ENDPOINT');
        $header = getenv('IDENTITY_HEADER');
        if (!$endpoint || !$header) throw new RuntimeException('Azure App Service managed identity is unavailable.');
        $url = $endpoint.'?api-version=2019-08-01&resource='.rawurlencode('https://storage.azure.com/');
        [$code, $body] = $this->http($url, 'GET', ['X-IDENTITY-HEADER: '.$header], null, false);
        $json = json_decode($body, true);
        if ($code !== 200 || empty($json['access_token'])) throw new RuntimeException('Could not obtain Azure Storage managed identity token (HTTP '.$code.').');
        $this->token = $json['access_token'];
        $this->expiresAt = (int) ($json['expires_on'] ?? (time() + 300));
        return $this->token;
    }

    private function endpoint(string $path = ''): string
    {
        $parts = array_map('rawurlencode', explode('/', ltrim($path, '/')));
        return 'https://'.$this->account.'.blob.core.windows.net/'.$this->container.($path !== '' ? '/'.implode('/', $parts) : '');
    }

    private function http(string $url, string $method, array $headers, ?string $body = null, bool $authenticate = true): array
    {
        if ($authenticate) $headers[] = 'Authorization: Bearer '.$this->token();
        $headers[] = 'x-ms-version: 2023-11-03';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_CUSTOMREQUEST => $method,
                    CURLOPT_NOBODY => $method === 'HEAD',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => true,
                    CURLOPT_TIMEOUT => 60,
                ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $response = curl_exec($ch);
        if ($response === false) throw new RuntimeException('Azure Storage HTTP failure: '.curl_error($ch));
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        return [$code, substr($response, $headerSize), substr($response, 0, $headerSize)];
    }

    public function fileExists(string $path): bool
    {
        [$code] = $this->http($this->endpoint($path), 'HEAD', []);
        if ($code === 404) return false;
        if ($code !== 200) throw new RuntimeException('Azure blob existence check failed: HTTP '.$code);
        return true;
    }

    public function directoryExists(string $path): bool
    {
        foreach ($this->listContents($path, false) as $_) return true;
        return false;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        [$code] = $this->http($this->endpoint($path), 'PUT', [
            'x-ms-blob-type: BlockBlob', 'Content-Type: '.($config->get('mimetype') ?: 'application/octet-stream'),
            'Content-Length: '.strlen($contents),
        ], $contents);
        if ($code !== 201) throw UnableToWriteFile::atLocation($path, 'Azure HTTP '.$code);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $temp = fopen('php://temp', 'w+b');
        stream_copy_to_stream($contents, $temp);
        rewind($temp);
        $data = stream_get_contents($temp);
        fclose($temp);
        $this->write($path, $data, $config);
    }

    public function read(string $path): string
    {
        [$code, $body] = $this->http($this->endpoint($path), 'GET', []);
        if ($code !== 200) throw UnableToReadFile::fromLocation($path, 'Azure HTTP '.$code);
        return $body;
    }

    public function readStream(string $path)
    {
        $handle = fopen('php://temp', 'w+b');
        fwrite($handle, $this->read($path));
        rewind($handle);
        return $handle;
    }

    public function delete(string $path): void
    {
        [$code] = $this->http($this->endpoint($path), 'DELETE', []);
        if (!in_array($code, [202, 404], true)) throw UnableToDeleteFile::atLocation($path, 'Azure HTTP '.$code);
    }

    public function deleteDirectory(string $path): void
    {
        foreach ($this->listContents($path, true) as $entry) {
            if ($entry->isFile()) $this->delete($entry->path());
        }
    }

    public function createDirectory(string $path, Config $config): void {}
    public function setVisibility(string $path, string $visibility): void {}
    public function visibility(string $path): FileAttributes { return new FileAttributes($path, null, Visibility::PRIVATE); }

    private function headers(string $path): string
    {
        [$code, , $headers] = $this->http($this->endpoint($path), 'HEAD', []);
        if ($code !== 200) throw UnableToRetrieveMetadata::create($path, 'HTTP '.$code);
        return $headers;
    }

    public function mimeType(string $path): FileAttributes
    {
        preg_match('/^Content-Type:\s*([^\r\n]+)/mi', $this->headers($path), $m);
        return new FileAttributes($path, null, null, null, $m[1] ?? 'application/octet-stream');
    }
    public function lastModified(string $path): FileAttributes
    {
        preg_match('/^Last-Modified:\s*([^\r\n]+)/mi', $this->headers($path), $m);
        return new FileAttributes($path, null, null, isset($m[1]) ? strtotime($m[1]) : null);
    }
    public function fileSize(string $path): FileAttributes
    {
        preg_match('/^Content-Length:\s*(\d+)/mi', $this->headers($path), $m);
        return new FileAttributes($path, isset($m[1]) ? (int) $m[1] : null);
    }
    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = trim($path, '/');
        $prefix = $prefix === '' ? '' : $prefix.'/';
        $marker = '';
        do {
            $query = '?restype=container&comp=list&prefix='.rawurlencode($prefix).'&maxresults=5000';
            if (!$deep) $query .= '&delimiter=%2F';
            if ($marker !== '') $query .= '&marker='.rawurlencode($marker);
            [$code, $xml] = $this->http($this->endpoint().$query, 'GET', []);
            if ($code !== 200) throw new RuntimeException('Azure list blobs failed: HTTP '.$code);
            $document = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
            if ($document === false) throw new RuntimeException('Invalid Azure listing response.');
            foreach ($document->Blobs->Blob ?? [] as $blob) {
                $name = (string) $blob->Name;
                yield new FileAttributes($name, (int) $blob->Properties->{'Content-Length'}, Visibility::PRIVATE,
                    strtotime((string) $blob->Properties->{'Last-Modified'}), (string) $blob->Properties->{'Content-Type'});
            }
            foreach ($document->Blobs->BlobPrefix ?? [] as $folder) yield new DirectoryAttributes(rtrim((string) $folder->Name, '/'));
            $marker = (string) $document->NextMarker;
        } while ($marker !== '');
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->copy($source, $destination, $config);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->write($destination, $this->read($source), $config);
    }
}
