<?php
/*
Copyright (c) 2013 Alexander Kirk
http://alexander.kirk.at/

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/
/* Abby Chau 2015 */
class RateExceededException extends Exception {}

class RateLimiter {
	private $prefix, $redis;
	//global $redisHdl = new Redis();
	//$redisHdl->connect('127.0.0.1', 6379);
	public function __construct($redisHdl, $ip, $prefix = "rate") {
		$this->redis  = $redisHdl;
		$this->prefix = $prefix . $ip;
	}

	public function limitRequestsInMinutes($allowedRequests, $minutes) {
		$requests = 0;

		foreach ($this->getKeys($minutes) as $key) {
			$requestsInCurrentMinute = $this->redis->get($key);
			if (false !== $requestsInCurrentMinute) $requests += $requestsInCurrentMinute;
		}

		if (false === $requestsInCurrentMinute) {
			$this->redis->set($key, 1);
			$this->redis->setTimeout($key, $minutes * 60 + 1);
		} else {
			$this->redis->incr($key);
		}

		if ($requests > $allowedRequests) throw new RateExceededException;
	}

	private function getKeys($minutes) {
		$keys = array();
		$now = time();
		for ($time = $now - $minutes * 60; $time <= $now; $time += 60) {
			$keys[] = $this->prefix . date("dHi", $time);
		}

		return $keys;
	}
}
