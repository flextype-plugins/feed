<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$feed = flextype('registry')->get('plugins.feed.settings.feed');

if (isset($feed) and count($feed) > 0) {
    foreach (flextype('registry')->get('plugins.feed.settings.feed') as $item) {

        $cacheID = strings('feed-collection-' . $item['id'])->hash()->toString();

        flextype('emitter')->addListener('onEntriesCreate', function () use ($cacheID) {
            flextype('cache')->delete($cacheID);
        });

        flextype('emitter')->addListener('onEntriesDelete', function () use ($cacheID) {
            flextype('cache')->delete($cacheID);
        });

        flextype('emitter')->addListener('onEntriesMove', function () use ($cacheID) {
            flextype('cache')->delete($cacheID);
        });

        flextype('emitter')->addListener('onEntriesCopy', function () use ($cacheID) {
            flextype('cache')->delete($cacheID);
        });

        flextype('emitter')->addListener('onEntriesUpdate', function () use ($cacheID) {
            flextype('cache')->delete($cacheID);
        });

        flextype()->get($item['options']['route'], function (Request $request, Response $response, array $args) use ($item, $cacheID) {

            if (flextype('cache')->has($cacheID)) {
                $entries = flextype('cache')->get($cacheID);
            } else {
                $entries = flextype('entries')
                                ->fetch($item['id'], $item['options'])
                                ->sortBy('published_at', 'DESC');

                flextype('cache')->set($cacheID, $entries);
            }

            switch ($item['options']['format']) {
                case 'rss':
                    $response = $response->withHeader('Content-Type', 'application/rss+xml');
                    $template = 'plugins/feed/templates/feed.rss.html';
                    break;
                case 'atom':
                    $response = $response->withHeader('Content-Type', 'application/atom+xml');
                    $template = 'plugins/feed/templates/feed.atom.html';
                    break;
                case 'json':
                default:
                    $response = $response->withHeader('Content-Type', 'application/json');
                    $template = 'plugins/feed/templates/feed.json.html';
                    break;
            }

            return flextype('twig')->render($response, $template, ['entries' => $entries, 'item' => $item]);
        });
    }
}
