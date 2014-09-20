<?php

namespace Seimas;
use \GuzzleHttp\Event\CompleteEvent;
use \GuzzleHttp\Event\ErrorEvent;
use \GuzzleHttp\Client as GuzzleClient;

class GuzzleHttpClient implements HttpClientInterface {
	protected $client;
	protected $async;
	protected $useCache;
	
	public function __construct(GuzzleClient $guzzle_client, $use_cache = true) {
		$this->client = $guzzle_client;
		$this->useCache = $use_cache;
	}
	
	public function request($url, \Closure $callback, \Closure $error_callback, $params = null) {
		if (!$this->retrieveCache($url, $callback, $params)) {
			$r = $this->client->createRequest('GET', $url);
			$this->client->sendAll(
				[$r],
				[
				'complete' => function (CompleteEvent $event) use ($callback, $url, $params) {
					$response = $event->getResponse();
					$this->saveToCache($url, $response);
					$callback(
						$response->getBody()->__toString(),
						$response->getEffectiveUrl(),
						$params
					);
				},
				'error' => function (ErrorEvent $event) use ($error_callback, $url, $params) {
					$error_callback($event->getException(), $url, $params);
				}
				]
			);
		}
	}
	
	public function startAsync() {
		$this->async['requests'] = new \SplObjectStorage();
		$this->async['complete_callback'] = null;
		$this->async['error_callback'] = null;
	}
	
	public function addAsync($url, $params) {
		$request = $this->client->createRequest('GET', $url);
		$this->async['requests']->attach($request, $params);
	}
	
	public function setAsyncCallbacks(\Closure $callback, \Closure $error_callback) {
		$this->async['complete_callback'] = $callback;
		$this->async['error_callback'] = $error_callback;
	}
	
	public function finishAsync() {
		$this->checkAsyncCache();
		$this->runRemainingAsync();
	}
	
	protected function getCurrentAsyncRequests() {
		return iterator_to_array($this->async['requests']);
	}
	
	protected function checkAsyncCache() {
		$callback = $this->async['complete_callback'];
		$detacheable = [];
		foreach($this->async['requests'] as $request) {
			$params = $this->async['requests'][$request];
			$url = $request->getUrl();
			if ($this->retrieveCache($url, $callback, $params)) {
				$detacheable[] = $request;
			}
		}
		foreach($detacheable as $request) {
			$this->async['requests']->detach($request);
		}
	}
	
	protected function runRemainingAsync() {
		$this->client->sendAll(
			$this->getCurrentAsyncRequests(),
			[	
				'complete' => function (CompleteEvent $event)  {
					$this->saveToCache($event->getRequest()->getUrl(), $event->getResponse());
					$callback = $this->async['complete_callback'];
					$callback(
						$event->getResponse()->getBody()->__toString(),
						$event->getResponse()->getEffectiveUrl(),
						$this->async['requests'][$event->getRequest()]
					);
				},
				'error' => function (ErrorEvent $event) {
					$error_callback = $this->async['error_callback'];
					$error_callback(
						$event->getException(),
						$event->getRequest()->getUrl(),
						$this->async['requests'][$event->getRequest()]
					);
				}
			]
		);
	}
	
	protected function retrieveCache($url, \Closure $callback, $callback_params = null) {
		$key = md5($url);
		if (\Cache::has($key) && $this->useCache) {
			$cached_data = \Cache::get($key);
			$callback($cached_data['body'], $cached_data['url'], $callback_params);
			return true;
		}
		return false;
	}
	
	protected function saveToCache($url, $response) {
		if ($this->useCache) {
			\Cache::forever(
				md5($url),
				['body' => $response->getBody()->__toString(),
				'url' => $response->getEffectiveUrl()]
			);
		}
	}

}
