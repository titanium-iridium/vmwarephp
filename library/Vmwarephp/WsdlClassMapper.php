<?php
namespace Vmwarephp;

class WsdlClassMapper {
	private $classDefinitionsFilePath;
	private $useClassMapCaching = true;
	private $classMapCacheFile;

	protected static $cacheDirectory = null;

	function __construct($classDefinitionsFilePath = null) {
		$this->classDefinitionsFilePath = $classDefinitionsFilePath ? : dirname(__FILE__) . '/TypeDefinitions.inc';
	}

	function getClassMap() {
		$classMap = $this->readClassMapFromCache();
		if ($classMap) {
			return $classMap;
		}
		$classMap = $this->generateClassMap();
		$this->cacheClassMap($classMap);
		return $classMap;
	}

	function configureClassMapCaching($useCaching = true) {
		$this->useClassMapCaching = $useCaching;
	}

	private function readClassMapFromCache() {
		$cacheFilePath = $this->getClassMapCacheFile();
		if (!file_exists($cacheFilePath) || !$this->useClassMapCaching) return;
		return unserialize(file_get_contents($cacheFilePath));
	}

	private function generateClassMap() {
		$classMap = array();
		$allTokens = token_get_all($this->readClassDefinitions());
		foreach ($allTokens as $key => $token) {
			if ($this->tokenRepresentsClassDefinition($token)) {
				$className = $allTokens[$key + 2][1];
				$classMap[$className] = $className;
			}
		}
		return array_merge($classMap, $this->getExtendedClasses());
	}

	private function getExtendedClasses() {
		$classes = array();
		foreach (scandir(__DIR__ . '/Extensions/') as $fileName) {
			if (in_array($fileName, array('.', '..'))) continue;
			$classNameComponents = explode('.', $fileName);
			$className = $classNameComponents[0];
			$classes[$className] = '\\Vmwarephp\\Extensions\\' . $className;
		}
		return $classes;
	}

	private function tokenRepresentsClassDefinition($token) {
		return is_array($token) && $token[0] == T_CLASS;
	}

	private function readClassDefinitions() {
		if (!file_exists($this->classDefinitionsFilePath)) return '';
		return file_get_contents($this->classDefinitionsFilePath);
	}

	private function cacheClassMap($classMap) {
		if (!$this->useClassMapCaching) return;
		if (!file_put_contents($this->getClassMapCacheFile(), serialize($classMap))) {
			throw new \Exception('\\Vmwarephp\\WsdlClassMapper is configured to cache the class map but was not able to. Check the permissions on the cache directory.');
		}
	}

	private function getClassMapCacheFile() {
		if (!$this->classMapCacheFile) {
			// TODO
//			$this->classMapCacheFile = self::$cacheDirectory . '/.wsdl_class_map.cache';
			$this->classMapCacheFile = __DIR__ . '/' . '.wsdl_class_map.cache';
		}
		return $this->classMapCacheFile;
	}

	public static function setCacheDirectory($path) {
		if (!file_exists($path)) {
			mkdir($path, 0777, true);
		}
		if (is_readable($path)) {
			throw new \Exception('WsdlClassMapper cache directory is not accessible: ' . $path);
		}
		self::$cacheDirectory = realpath($path);
	}
}
