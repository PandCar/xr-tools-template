<?php

/**
 * @author  Dmitriy Lukin <lukin.d87@gmail.com>
 */

namespace XrTools;

/**
 * Custom template parts builder
 */
class Template
{
	/**
	 * @var array
	 */
	private $parts = [];
	/**
	 * @var array
	 */
	private $conf = [];
	/**
	 * @var Locale
	 */
	private $locale;
	/**
	 * @var Router
	 */
	private $router;

	/**
	 * Template constructor.
	 * @param Locale $locale
	 * @param Router $router
	 */
	public function __construct(
		Locale $locale, 
		Router $router
	){
		$this->locale = $locale;
		$this->router = $router;
	}

	public function set($part, $value){
		if(is_array($part)){
			$this->setBatch($part, $value);
		}
		else {
			$this->parts[$part] = $value;
		}

		return $value;
	}

	public function pushInit($part, $first_value = null){
		$this->parts[$part] = [];

		if(!empty($first_value)){
			$this->parts[$part][] = $first_value;
		}
	}

	public function push($part, $value){

		if(empty($value)){
			return;
		}

		$this->parts[$part][] = $value;
	}

	public function append($part, string $value){

		if(!isset($this->parts[$part])){
			$this->parts[$part] = '';
		}
		elseif(!is_string($this->parts[$part])){
			throw new \Exception('Appending is supported only against strings, ('.gettype($this->parts[$part]).' given)');			
		}

		$this->parts[$part] .= $value;

		return $this->parts[$part];		
	}

	public function setBatch(array $parts, $value){
		foreach ($parts as $part) {
			$this->parts[$part] = $value;
		}
	}
	
	public function get($parts = null){
		if(!isset($parts)){
			return $this->parts;
		}
		elseif(is_array($parts)){
			$result = [];

			foreach ($parts as $key) {
				$result[$key] = $this->parts[$key] ?? null;
			}

			return $result;
		}
		else {
			return $this->parts[$parts] ?? null;
		}
	}

	/**
	 * @param $key
	 * @param null $value
	 * @return mixed|void|null
	 */
	public function config($key, $value = null)
	{
		// write config
		if (isset($value)) {
			$this->conf[ $key ] = $value;
			return;
		}
		// read config
		else {
			return $this->conf[ $key ] ?? null;
		}
	}

	/**
	 * Proxy to Locale Service
	 * @return \XrTools\Locale
	 */
	public function locale(){
		return $this->locale;
	}

	/**
	 * Формирует канонический адрес для мета тега rel=canonical в шаблонах вывода страниц
	 * @param  array  $ruleset Set of rules to generate a canonical URL. Processed in this order:
	 *                         	<ul>
	 *                         		<li> <strong> url_go_max </strong> int
	 *                         			- last index of an array $urlParts which canonical URL is generated from (indexes greater than url_go_max are ignored)
	 *                         		<li> <strong> query_count_max </strong> int
	 *                         			- ignore the whole query string in canonical URL if there are more _GET parameters than this number
	 *                         	</ul>
	 * @param  array  $sys     Settings:
	 *                         	<ul>
	 *                         		<li> <strong> return_only </strong> bool (off)
	 *                         			- do not save result as $this->set('rel_canonical', $result)
	 *                         		<li> <strong> rewrite </strong> bool (off)
	 *                         			- by default result is saved only if $this->get('rel_canonical') is empty.
	 *                         			  This option forces to overwrite the existing canonical URL settings (for subpage to override inherited parent settings)
	 *                         	</ul>
	 * @return string          Generated canonical URL
	 */
	function relCanonicalRules($ruleset = [], $sys = []){
		// результат
		$rel_canonical = false;
		
		// проверяем максимальный уровень arr_url_go
		if(isset($ruleset['url_go_max'])){
			$url_go_max = (int) $ruleset['url_go_max'];

			if($this->router->getUrlPart($url_go_max) !== false){
				$rel_canonical = '';
				for($i=0; $i<=$url_go_max; $i++){
					$rel_canonical .= '/'.$this->router->getUrlPart($i);
				}
			}
		}
		
		// проверяем максимальное кол-во аргументов в запросе (если уже не настроено)
		if($rel_canonical === false && isset($ruleset['query_count_max'])){
			$query_count_max = (int) $ruleset['query_count_max'];
			
			// учитываем rewrite параметр (напр. $_GET['go'])
			if(count($this->router->getUrlQuery()) > $query_count_max){
				$rel_canonical = $this->router->getUrl();
			}
		}
		
		// если нужно только вернуть результат и не нужна авто-настройка параметра в шаблоне
		// авто-настройка записывается только если атрибут пуст или присутствует настройка 'rewrite'=>true
		if(empty($sys['return_only']) && (empty($this->get('rel_canonical')) || !empty($sys['rewrite']))){
			$this->set('rel_canonical', $rel_canonical);
		}
		
		return $rel_canonical;
	}
}

