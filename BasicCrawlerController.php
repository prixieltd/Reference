<?php

abstract class BasicCrawlerController extends Controller {
	
	protected $emailRegex = '/[-0-9a-z.+_]+((\s*@\s*)|(([\s\[]+)at([\s\]]+)))[-0-9a-z.+_]+((\.)|(([\s\[]+)dot([\s\]]+)))[a-z]{2,4}/i';
	protected $emailReplaceSearch = array("/((\s*@\s*)|(([\s\[]+)at([\s\]]+)))/i", "/((\s*\.\s*)|(([\s\[]+)dot([\s\]]+)))/i");
	protected $emailReplaceReplace = array("@", ".");
	protected $nameRegex = '/((herr|frau|ansprechspartner) [a-z]+ [a-z]+)/i';
	protected $refNumRegex = '/Kennziffer:? ([a-z0-9_-]+)/i';
	
	abstract function index();
	
	abstract function doCrawler();
	
	abstract public function crawlerProcess($searched, $site_id, $searchId);
	
	abstract protected function getQueryString($linkstart, $searched, $counter);
	
	final protected function getLinkString($linkstart, $sublink) {
		return $linkstart . $sublink;
	}
	
	final protected function freeFromHtmlTags($str) {
		return preg_replace('/<\/?.*?>/', '', $str);
	}
	
	final protected function getSiteContent($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		$data = curl_exec($ch);
		curl_close($ch);
		return urldecode($data);
	}
	
	final protected function getBaseDomain($domain) {
		if (preg_match('/(^(https?:\/\/)?.+?)\//', $domain, $match)) return $match[1];
		return null;
	}
	
}
