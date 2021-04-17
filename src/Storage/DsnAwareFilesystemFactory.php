<?php

namespace App\Storage;

use Aws\S3\S3Client;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

final class DsnAwareFilesystemFactory {
    public static function createFilesystem(string $dsn, array $options = []): Filesystem {
        $parts = parse_url($dsn) ?: ['scheme' => preg_replace('/\W.*/', '', $dsn)];
        $parts += [
            'scheme' => null,
            'user' => null,
            'pass' => null,
            'path' => null,
            'query' => null,
        ];

        foreach (['user', 'pass'] as $key) {
            $parts[$key] = isset($parts[$key]) ? urldecode($parts[$key]) : null;
        }

        parse_str($parts['query'], $query);

        switch ($parts['scheme']) {
        case 'local':
        case 'file':
            $path = rtrim($parts['path'], '/');

            if (!empty($options['prefix'])) {
                $path .= '/'.$options['prefix'];
            }

            $adapter = new Local($path);

            break;

        case 'null':
            $adapter = new NullAdapter();
            break;

        case 's3':
            $client = new S3Client([
                'credentials' => [
                    'key' => $parts['user'],
                    'secret' => $parts['pass'],
                ],
                'endpoint' => $query['endpoint'] ?? null,
                'region' => $parts['host'],
                'version' => $query['version'] ?? 'latest',
                'bucket_endpoint' => (bool) ($query['bucket_endpoint'] ?? true),
                'use_path_style_endpoint' => (bool) ($query['use_path_style_endpoint'] ?? false),
            ]);

            $bucket = ltrim($parts['path'], '/');
            $prefix = $options['prefix'] ?? '';

            $adapter = new AwsS3Adapter($client, $bucket, $prefix, []);

            break;

        default:
            throw new \InvalidArgumentException(
                "Unknown filesystem '{$parts['scheme']}'"
            );
        }

        return new Filesystem($adapter, $options);
    }
}
