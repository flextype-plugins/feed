<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$feed = registry()->get('plugins.feed.settings.feed');

if (isset($feed) and count($feed) > 0) {
    foreach ($feed as $item) {

        $cacheID = strings('feed-collection-' . $item['id'])->hash()->toString();

        emitter()->addListener('onEntriesCreate', fn () => cache()->delete($cacheID));
        emitter()->addListener('onEntriesDelete', fn () => cache()->delete($cacheID));
        emitter()->addListener('onEntriesMove', fn () => cache()->delete($cacheID));
        emitter()->addListener('onEntriesCopy', fn () => cache()->delete($cacheID));
        emitter()->addListener('onEntriesUpdate', fn () => cache()->delete($cacheID));

        app()->get($item['options']['route'], function (Request $request, Response $response, array $args) use ($item, $cacheID) {

            if (cache()->has($cacheID)) {
                $entries = cache()->get($cacheID);
            } else {
                $entries = entries()
                                ->fetch($item['id'], $item['options'])
                                ->sortBy('published_at', 'DESC');

                cache()->set($cacheID, $entries);
            }

            switch ($item['options']['format']) {
                case 'rss':
                    $response = $response->withHeader('Content-Type', 'application/rss+xml; charset=utf-8');
                    $template = 'plugins/feed/templates/feed.rss.html';
                    break;
                case 'atom':
                    $response = $response->withHeader('Content-Type', 'application/atom+xml; charset=utf-8');
                    $template = 'plugins/feed/templates/feed.atom.html';
                    break;
                case 'json':
                default:
                    $response = $response->withHeader('Content-Type', 'application/json; charset=utf-8');
                    $template = 'plugins/feed/templates/feed.json.html';
                    break;
            }

            return twig()->render($response, $template, ['entries' => $entries, 'item' => $item]);
        });
    }
}
