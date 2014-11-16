<?php

namespace Seimas\http;

interface HttpClientInterface {
	public function request($url, \Closure $callback, \Closure $error_callback, $params);
	public function startAsync();
	public function addAsync($url, $params);
	public function setAsyncCallbacks(\Closure $callback, \Closure $error_callback);
	public function finishAsync();
}

