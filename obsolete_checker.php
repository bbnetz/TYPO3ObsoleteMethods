#!/usr/bin/php
<?php
/**
 * TYPO3ObsoleteMethods Class
 * By Bastian Bringenberg <mail@bastian-bringenberg.de>
 *
 * #########
 * # USAGE #
 * #########
 *
 * See Readme File
 *
 * ###########
 * # Licence #
 * ###########
 *
 * See License File
 *
 * ##############
 * # Repository #
 * ##############
 *
 * Fork me on GitHub
 * https://github.com/bbnetz/TYPO3ObsoleteMethods
 *
 *
 */

/**
 * Class TYPO3ObsoleteMethods
 * @author Bastian Bringenberg <mail@bastian-bringenberg.de>
 * @link https://github.com/bbnetz/TYPO3ObsoleteMethods
 *
 */
class TYPO3ObsoleteMethods {

	/**
	 * @var string $obsoleteList Location in the Wiki where all deprecated methods are located
	 */
	protected $obsoleteList = 'http://wiki.typo3.org/TYPO3_6.0_Extension_Migration_Tips';

	/**
	 * @var string $html the html from the wiki
	 */
	protected $html = null;

	/**
	 * @var array contains all obsolete static methods from the core in form ( per array entry ): array('class', 'methodName', 'informationText')
	 */
	protected $obsoleteStaticMethods = array();

	/**
	 * @var array contains all obsolete non static methods from the core in form ( per array entry ): array('class', 'methodName', 'informationText')
	 */
	protected $obsoleteNonStaticMethods = array();

	/**
	 * @var array $ignoredFiles list of files to ignore
	 */
	protected $ignoredFiles = array('README', 'readme', 'Changelog', 'ChangeLog','changelog');

	/**
	 * @var array $checkableExtensions containing all pathes to possible Extensions
	 */
	protected $checkableExtensions = array();

	/**
	 * @var boolean $searchNonStatic if set will also check for non static methods. Warning: not yet stable could show wrong informations
	 */
	protected $searchNonStatic = false;

	/**
	 * function __construct
	 * Constructor for TYPO3ObsoleteMethods
	 *
	 * @return void
	 */
	public function __construct() {
		$ops = getopt('',
			array(
				'',
				'instancesPath::',
				'extensionPath::',
				'ignoredFiles::',
				'searchNonStatic::',
			)
		);

		if(isset($ops['instancesPath']))
			$this->checkableExtensions = array_merge($this->checkableExtensions, $this->getExtensionPathesByInstance($ops['instancesPath']));

		if(isset($ops['extensionPath']))
			$this->checkableExtensions = array_merge($this->checkableExtensions, $this->getExtensionPathesFromExtensionList($ops['extensionPath']));

		if(isset($ops['ignoredFiles']))
			$this->ignoredFiles = array_merge($this->ignoredFiles, $this->getIgnoredFiles($ops['ignoredFiles']));

		if(isset($ops['searchNonStatic']))
			$this->searchNonStatic = true;

		if(count($this->checkableExtensions) == 0)
			die('No Extension to Check.');
	}

	/**
	 * function start
	 * Doing all the work
	 *
	 * @return void
	 */
	public function start() {
		$this->html = $this->getCurrentHTML($this->obsoleteList);
		$this->obsoleteStaticMethods = $this->fetchStaticMethodFromHTML($this->html);
		$this->obsoleteNonStaticMethods = $this->fetchNonStaticMethodFromHTML($this->html);
		$this->checkExtensions($this->checkableExtensions);
		$output = '';
		foreach($this->obsoleteStaticMethods as $ext ) {
			if($ext['used'])
				$output .= 'Static Method: '.$ext['class'].'::'.$ext['methodname'].' - '.$ext['note'].PHP_EOL;
		}
		foreach($this->obsoleteNonStaticMethods as $ext ) {
			if($ext['used'])
				$output .= 'NonStatic Method: '.$ext['class'].'::'.$ext['methodname'].' - '.$ext['note'].PHP_EOL;
		}
		if($output != '')
			echo PHP_EOL.PHP_EOL.PHP_EOL.'Helping hands for your obsolete methods:'.PHP_EOL.'========================================'.PHP_EOL;
		echo $output;
	}

	/**
	 * function getCurrentHTML
	 * Gets $url and creates HTMLString from it
	 *
	 * @param string $url to fetch the HTML from
	 * @return string the HTML from $url
	 */
	protected function getCurrentHTML($url) {
		return file_get_contents($url);
	}

	/**
	 * function fetchStaticMethodFromHTML
	 * Itterates trough the Tables of $this->obsoleteList and collects informations from the first Table
	 *
	 * @param string $html the html to check in
	 * @return array see $this->obsoleteStaticMethods for more informations
	 */
	protected function fetchStaticMethodFromHTML($html) {
		$array = array();
		preg_match_all('/<table class="usertable sortable">(.*?)<\/table>/sm', $html, $tables);
		preg_match_all('/<tr>(.*?)<\/tr>/s', $tables[0][0], $rows);
		foreach($rows[1] as $number => $row) {
			if($number == 0 ) continue;
			$array[] = $this->buildSingleMethodEntryFromTableRow($row);
		}
		return $array;
	}

	/**
	 * function fetchNonStaticMethodFromHTML
	 * Itterates trough the Tables of $this->obsoleteList and collects informations from the second Table
	 *
	 * @param string $html the html to check in
	 * @return array see $this->obsoleteNonStaticMethods for more informations
	 */
	protected function fetchNonStaticMethodFromHTML($html) {
		$array = array();
		preg_match_all('/<table class="usertable sortable">(.*?)<\/table>/sm', $html, $tables);
		preg_match_all('/<tr>(.*?)<\/tr>/s', $tables[0][1], $rows);
		foreach($rows[1] as $number => $row) {
			if($number == 0 ) continue;
			$array[] = $this->buildSingleMethodEntryFromTableRow($row);
		}
		return $array;
	}

	/**
	 * function buildSingleMethodEntryFromTableRow
	 * Gets a TD Row and returns as method struct
	 *
	 * @param string $row the table data row
	 * @return array $method a struct for the methods
	 */
	protected function buildSingleMethodEntryFromTableRow($row) {
		$method = array(
			'class' => '',
			'methodname' => '',
			'note' => '',
			'used' => false
			);
		$row = str_replace(array('&gt;', '&lt;'), array('>', '<'), $row);
		$tbs = explode('</td>', $row);
		$tbs[0] = str_replace(array('<td>', '</td>'), array('', ''), $tbs[0]);
		$tbs[1] = str_replace(array('<td>', '</td>'), array('', ''), $tbs[1]);
		$splitted_field = explode('::', $tbs[0]);
		$method['class'] = trim($splitted_field[0]);
		if(isset($splitted_field[1]))
			$method['methodname'] = trim($splitted_field[1]);
		$method['note'] = trim($tbs[1]);
		return $method;
	}

	/**
	 * function checkExtensions
	 * itterates trough $extensionList 
	 * 
	 * @param array $extensionList containing all pathes
	 * @return void
	 */
	protected function checkExtensions($extensionList) {
		foreach($extensionList as $extensionPath )
			$this->checkExtension($extensionPath);
	}

	/**
	 * function checkExtension
	 * checks the extension for obsolete static and non-static methods
	 * Echos on Error
	 *
	 * @param string $extensionPath The path to the extension 
	 * @return void
	 */
	protected function checkExtension($extensionPath) {
		for($i = 0; $i < count($this->obsoleteStaticMethods) -1; $i++) {
			$filename = $this->php_grep($this->obsoleteStaticMethods[$i]['class'].'::'.$this->obsoleteStaticMethods[$i]['methodname'], $extensionPath);
			if($filename) {
				$filenames = explode(PHP_EOL, $filename);
					foreach($filenames as $filename) {
						if($filename !== '')
							echo 'StaticMethod: '.$this->obsoleteStaticMethods[$i]['class'].'::'.$this->obsoleteStaticMethods[$i]['methodname'].' in '.$filename.PHP_EOL;
					}
				$this->obsoleteStaticMethods[$i]['used'] = true;
			}
		}

		if($this->searchNonStatic)
			for($i = 0; $i < count($this->obsoleteStaticMethods) -1; $i++) {
				$error = $this->php_grep($this->obsoleteNonStaticMethods[$i]['class'], $extensionPath);
				if($filename = $this->php_grep($this->obsoleteNonStaticMethods[$i]['class'], $extensionPath)) {
					$filenames = explode(PHP_EOL, $filename);
					foreach($filenames as $filename)
						if(trim($filename) !== '')
							if(strpos(file_get_contents($filename), $this->obsoleteStaticMethods[$i]['methodname']) !== FALSE) {
								echo 'NonStaticMethod: '.$this->obsoleteStaticMethods[$i]['class'].'::'.$this->obsoleteStaticMethods[$i]['methodname'].' in '.$filename.PHP_EOL;
								$this->obsoleteNonStaticMethods[$i]['used'] = true;
							}
				}
			}
	}

	/**
	 * function php_grep
	 * works like grep -R
	 *
	 * @see http://www.cafewebmaster.com/search-text-files-recursively-php-grep
	 * @param string $q the search string 
	 * @param string $path the path to check
	 * @return string the method and the path to the error
	 */
	protected function php_grep($q, $path){
		$fp = opendir($path);
		$ret = '';
		while($f = readdir($fp)){
			if($this->ignoreFile($f)) continue;
			if( preg_match('#^\.+$#', $f) ) continue; // ignore symbolic links
			$file_full_path = $path.DIRECTORY_SEPARATOR.$f;
			if(is_dir($file_full_path)) {
				$ret .= $this->php_grep($q, $file_full_path);
			} else if( stristr(file_get_contents($file_full_path), $q) ) {
				$ret .= $file_full_path.PHP_EOL;
			}
		}
		return $ret;
	}

	/**
	 * function ignoreFile
	 * Checks if $file is ignored
	 *
	 * @param string $file the file name to check
	 * @return boolean true if $file is ignored
	 */
	protected function ignoreFile($file) {
		return in_array($file, $this->ignoredFiles);
	}

	/**
	 * function getIgnoredFiles
	 * Separates the comma list and trims each entry
	 * 
	 * @param string $ignoredFiles comma separated list of files
	 * @return array of trimmed files
	 */
	public function getIgnoredFiles($ignoredFiles) {
		$ignoredFiles = explode(',', $ignoredFiles);
		$files = array();
		foreach($ignoredFiles as $file) {
			$files[] = trim($file);
		}
		return $files;	
	}

	/**
	 * function getExtensionPathesFromExtensionList
	 * Separates the comma list and trims each entry
	 * 
	 * @param string $pathesList comma separated list of directories
	 * @return array of trimmed extensionPathes
	 */
	public function getExtensionPathesFromExtensionList($pathesList) {
		$pathesList = explode(',', $pathesList);
		$files = array();
		foreach($pathesList as $file) {
			$files[] = realpath(trim($file));
		}
		return $files;	
	}

	/**
	 * function getExtensionPathesByInstance
	 * Gets all Extensions from $instancesList
	 *
	 * @param string $instancesList comma separated list of instances
	 * @return array of Extensions
	 */
	public function getExtensionPathesByInstance($instancesList) {
		$instancesList = explode(',', $instancesList);
		$files = array();
		foreach($instancesList as $file) {
			$files = array_merge($files, $this->getExtensionsFromSingleInstance(realpath(trim($file))));
		}
		return $files;
	}

	/**
	 * function getExtensionsFromSingleInstance
	 * Checks TYPO3 Instances to get all Extensions from it
	 *
	 * @param string $file a path to the TYPO3 instance
	 * @return array of Extensions
	 */
	public function getExtensionsFromSingleInstance($file) {
		if($file{strlen($file)-1} != DIRECTORY_SEPARATOR ) $file .= DIRECTORY_SEPARATOR;
		return glob($file.'typo3conf'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);
	}

}

$typo3obsoletemethods = new TYPO3ObsoleteMethods();
$typo3obsoletemethods -> start();