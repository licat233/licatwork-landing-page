<?php
/**
 * OneExplorer New
 *
 * OneExplorer 是一个单文件的 php 文件浏览器，主要用于展示服务器目录/文件列表以方便访问和下载等。
 *
 * 项目地址： http://blog.vgot.net/one-explorer/
 * 更新日志： http://blog.vgot.net/archives/oneexplorer-update-log.html
 *
 * Copyright (c) 2008-2016 www.vgot.net
 *
 * @author Pader (ypnow@163.com)
 */

//默认目录列表类型 list|icon
define('DEFAULT_VIEWMODE', 'icon');

//是否显示隐藏文件，即开头有微信号 . 的文件
define('SHOW_HIDDEN', false);

/**
 * OneExplorer Core
 */
class OneExplorer {

	const VERSION = '1.5.4 (20190606)';

	//已知文件的类型对应的内嵌图标
	private $types = array(
		'apk' => 'apk.gif',  'asp' => 'code.gif',
		'bmp' => 'img.gif',  'bz2' => 'zip.gif',
		'cab' => 'rar.gif',  'chm' => 'manual.gif', 'css' => 'css.gif',
		'db'  => 'db.gif',   'dir' => 'dir.gif',  'doc' => 'word.gif',  'docx' => 'word.gif',
		'exe' => 'exe.gif',
		'gif' => 'img.gif',  'gz' => 'zip.gif',
		'htm' => 'html.gif',  'html' => 'html.gif',
		'img' => 'cd_page.gif', 'iso' => 'cd.gif',
		'js' => 'js.gif',    'jpg' => 'img.gif',
		'log' => 'txt.gif',
		'mdb' => 'mdb.gif',  'mp3' => 'mp3.gif',
		'php' => 'php.gif',  'png' => 'img.gif',  'ppt' => 'powerpoint.gif',  'pptx' => 'powerpoint.gif',  'py' => 'py.gif',
		'rar' => 'rar.gif',
		'sql' => 'txt.gif',  'swf' => 'swf.gif',
		'txt' => 'txt.gif',
		'wma' => 'mp3.gif',  'wmv' => 'mp3.gif',
		'xls' => 'excel.gif','xlsx' => 'excel.gif',  'xml' => 'xml.gif',
		'zip' => 'zip.gif'
	);

	//文件名URL是否编码
	private $encodeName;

	//检测的文件名编码列表
	private $nameEncodes;

	public function __construct() {
		$_ENV['time'] = time();

		$this->encodeName = PHP_OS != 'WINNT';
		$this->nameEncodes = ['ASCII', 'UTF-8', 'GBK', 'GB2312', 'CP936'];
	}

	/**
	 * 主页面
	 */
	public function index() {
		$viewMode = $this->fetchViewMode();
		list($sortField, $sortMethod) = $this->fetchSortOrder();

		//获取路径
		$dir = empty($_GET['dir']) ? '' : $this->filterDir($_GET['dir']);
		$path = realpath('./'.$dir);

		header('Content-Type: text/html; charset=utf-8');

		if (!is_dir($path)) {
			echo '<h1>目录不存在</h1><a href="javascript:history.go(-1);">点击返回</a> | <a href="?dir=.">到根目录</a>';
			footer();
			return;
		}

		$dirs = $files = array();

		//读取目录
		if (is_dir($path)) {
			$sortDir = $sortFile = array();
			$isSortDir = ($sortField == 'filename' || $sortField == 'filemtime');

			$handle = opendir($path);

			while (false !== ($filename = readdir($handle))) {
				if ($filename == '.' || $filename == '..') continue;

				if (!SHOW_HIDDEN && substr($filename, 0, 1) == '.') {
					continue;
				}

				/**
				 * $origName 原始编码文件名称，程序读取和web服务器传递需要
				 * $filename 页面可读编码，用于页面显示
				 * $uri URL可正确传递的编码，用于向程序传递名称
				 */
				$origName = $filename;
				list($filename, $uri) = $this->getDisplayUri($filename);

				$row = array(
					'origname' => $origName,
					'filename' => $filename,
					'uri' => $uri,
					'filemtime' => filemtime($path.'/'.$origName)
				);

				if (is_dir($path.'/'.$origName)) {
					$dirs[] = $row;
					$isSortDir && $sortDir[] = $row[$sortField];
				} else {
					$ext = ($sp = strrpos($filename, '.')) !== false ? strtolower(substr($filename, $sp + 1)) : '';

					$row['filesize'] = filesize($path.'/'.$origName);
					$row['type'] = $ext;
					$row['icon'] = isset($this->types[$ext]) ? $this->types[$ext] : 'unknow.gif';
					$files[] = $row;

					$sortField && $sortFile[] = $row[$sortField];
				}
			}

			closedir($handle);

			//对列表进行排序
			$sortField && $this->sortList($dirs, $files, $sortDir, $sortFile, $sortField, $sortMethod, $isSortDir);
		}

		$upDir = $this->encodeUrl(trim(dirname($dir),'./'));

		//标题
// 		$title = trim($dir,'./');
// 		if ($title) {
// 			$du = $this->getDisplayUri($title);
// 			$title = $du[0];
// 		} else {
// 			$title = $_SERVER['HTTP_HOST'];
// 		}
        $title = "落地頁資源";

		//统计
		$countDir = count($dirs);
		$countFile = count($files);
		$status = ($countDir == 0 && $countFile == 0) ? '[目录是空的]' : '['.$countDir.' 个目录, '.$countFile.' 个文件]';

		//生成导航
		$breadCrumb = $this->breadCrumb($dir, $countDir>0);

		if ($dir != '') {
			$dir = $this->encodeUrl($dir).'/';
		}

		$current = $dir;

		//输出视图
		view(compact('title', 'breadCrumb', 'current', 'viewMode', 'dir', 'upDir', 'status', 'countDir', 'countFile',
			'dirs', 'files', 'sortField', 'sortMethod'));
	}

	/**
	 * 获取子目录列表并改变前端页面 JS 值
	 */
	public function subdir() {
		$dir = empty($_GET['dir']) ? '' : $this->filterDir($_GET['dir']);
		$dir = realpath('./'.$dir);

		if (!is_dir($dir)) return;

		$ls = array();
		$handle = opendir($dir);
		while (false !== ($row = readdir($handle))) {
			if ($row == '.' || $row == '..' || !is_dir($dir.'/'.$row)) continue;
			$du = $this->getDisplayUri($row);
			$ls[] = '["'.addslashes($du[0]).'","'.addslashes($du[1]).'"]';
		}
		closedir($handle);
		sort($ls);

		header('Content-Type: text/javascript');
		echo 'subdirs=['.join(',', $ls).'];';
	}

	/**
	 * 输出图标图像
	 */
	public function image() {
		if (empty($_GET['file'])) {
			return;
		}

		//让图片缓存一天
		header('Cache-Control: public');
		header('Pragma: cache');
		header('Expires: '.gmdate('D, d M Y H:i:s', $_ENV['time'] + 86400).' GMT');

		$ns = explode('.', $_GET['file']);
		switch (end($ns)) {
			case 'png': header('Content-Type: image/png'); break;
			case 'jpg': header('Content-Type: image/jpeg'); break;
			case 'gif': header('Content-Type: image/gif'); break;
		}

		header('Content-Disposition: inline');
		echo getImageContent($_GET['file']);
	}

	/**
	 * 过滤传入的目录参数中不合法的字符
	 *
	 * @param string $dir
	 * @return string
	 */
	private function filterDir($dir) {
		return trim(preg_replace('#/{1,}#', '/', $dir), './');
	}

	/**
	 * 生成 Win7 式的面包屑导航
	 *
	 * @param string $dir
	 * @param bool $hasSubDirs
	 * @return string
	 */
	private function breadCrumb($dir, $hasSubDirs) {
		if ($dir == '') {
			return '';
		}

		$bc = $breadCrumb = '';

		if ($dir) {
			foreach (explode('/', $dir) as $row) {
				$d = $bc;
				list($filename, $uri) = $this->getDisplayUri($row);
				$bc .= $bc ? '/'.$uri : $uri;
				$link = "<a href=\"?dir=$bc\">$filename</a>";
				$breadCrumb .= $breadCrumb ? "<em data-addr=\"$d\"></em>".$link : $link;
			}

			if ($hasSubDirs) {
				$breadCrumb .= "<em data-addr=\"$bc\"></em>";
			}
		}

		return $breadCrumb;
	}

	/**
	 * 获取当前的浏览模式
	 *
	 * 根据用户输入或默认设定返回当前的浏览模式
	 *
	 * @return string
	 */
	private function fetchViewMode() {
		if (!empty($_GET['viewmode'])) {
			$viewMode = $_GET['viewmode'] == 'icon' ? 'icon' : 'list';
			if ($viewMode != DEFAULT_VIEWMODE) {
				setcookie('OEviewmode', $viewMode, $_ENV['time'] + 2592000);
			} else {
				setcookie('OEviewmode', false, $_ENV['time'] - 3600);
			}
		} else {
			$viewMode = isset($_COOKIE['OEviewmode']) ? $_COOKIE['OEviewmode'] : DEFAULT_VIEWMODE;
			if ($viewMode != 'list' && $viewMode != 'icon') {
				$viewMode = DEFAULT_VIEWMODE;
			}
		}

		return $viewMode;
	}

	/**
	 * 获取当前的排序方式
	 *
	 * @return array
	 */
	private function fetchSortOrder() {
		$limit = '/^(filename|filemtime|type|filesize),(asc|desc)$/';

		$sortField = $sortMethod = '';

		if (isset($_GET['sort'])) {
			if (preg_match($limit, $_GET['sort'])) {
				list($sortField, $sortMethod) = explode(',', $_GET['sort']);
				setcookie('OEsort', $_GET['sort'], $_ENV['time'] + 2592000);
			} else {
				setcookie('OEsort', false, $_ENV['time'] - 3600);
			}
		} elseif (isset($_COOKIE['OEsort']) && preg_match($limit, $_COOKIE['OEsort'])) {
			list($sortField, $sortMethod) = explode(',', $_COOKIE['OEsort']);
		}

		return array($sortField, $sortMethod);
	}

	/**
	 * 对目录/文件列表进行排序
	 *
	 * @param array $dirs 目录列表
	 * @param array $files 文件列表
	 * @param array $sortDir 目录排序依据
	 * @param array $sortFile 文件排序依据
	 * @param string $sortField 排序字段
	 * @param string $sortMethod 排序方式
	 * @param bool $isSortDir 是否对目录进行排序
	 */
	private function sortList(&$dirs, &$files, $sortDir, $sortFile, $sortField, $sortMethod, $isSortDir) {
		$sortFlag = $sortMethod == 'asc' ? SORT_ASC : SORT_DESC;
		$sortType =  ($sortField == 'filename' || $sortField == 'type') ? SORT_STRING : SORT_NUMERIC;

		if ($sortType == SORT_STRING) {
			$isSortDir && $sortDir = array_map('strtolower', $sortDir);
			$sortFile = array_map('strtolower', $sortFile);
		}

		$isSortDir && array_multisort($sortDir, $sortFlag, $sortType, $dirs);
		array_multisort($sortFile, $sortFlag, $sortType, $files);
	}

	/**
	 * 将路径中的每一层目录编码
	 *
	 * @param string $uri
	 * @return string
	 */
	private function encodeUrl($uri) {
		return $this->encodeName ? join('/', array_map('rawurlencode', explode('/', $uri))) : $uri;
	}

	/**
	 * 获取可阅读，URL编码格式的文件名
	 *
	 * @param string $filename
	 * @return array
	 */
	private function getDisplayUri($filename) {
		//对非全角字符和URL涉及字符（#%&=?)进行转码
		$uri = $this->encodeName ?
			preg_replace_callback('/([^\x20-\x22\x24\x27-\x3c\x3e\x40-\x7e]+)/', function ($m) { return rawurlencode($m[0]); }, $filename)
			: $filename;

		$code = mb_detect_encoding($filename, $this->nameEncodes);
		
		//Windows 下访问 URL 必须以 UTF-8 编码
		//$uri = ($code != 'UTF-8' && PHP_OS == 'WINNT') ? iconv($code, 'UTF-8', $filename) : $filename;
		//$uri = rawurlencode($uri);
		
		//因 iconv() 函数存在某些环境下转码出现进程崩溃的问题，所以这里改用 mb_convert_encoding()
		//文件名必须以 UTF-8（页面编码） 显示
		if ($code && $code != 'UTF-8') {
			$filename = mb_convert_encoding($filename, 'UTF-8', $code);
		}
		
		return array($filename, $uri);
	}

}

//调用动作
$instance = new OneExplorer();

$action = isset($_GET['action']) ? $_GET['action'] : 'index';
$call = array($instance, $action);

if (is_callable($call)) {
	call_user_func($call);
} else {
	header('HTTP/1.1 404 Not Found');
	echo '404 Page Not Found.';
}

//辅助函数

/**
 * 转换文件大小为可读格式
 *
 * @param int $size
 * @return string
 */
function convertFileSize($size) {
	if ($size > 0) {
		$units = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		return number_format($size/pow(1024, ($i = floor(log($size, 1024)))), 2, '.', '').' '.$units[$i];
	} elseif ($size == 0) {
		return '0 Bytes';
	} else {
		return '&gt;2 GB';
	}
}

/**
 * 截取字符串
 *
 * @param string $str
 * @param int $cutlen
 * @return string
 */
function cutstr($str, $cutlen) {
	$strlen = strlen($str);

	if ($cutlen > $strlen) return $str;

	$notChineseNum = 0;

	for ($i=0; $i<$cutlen; $i++) {
		ord(substr($str,$i,1)) <= 128 && ++$notChineseNum;
	}

	($cutlen % 2 == 1 && $notChineseNum % 2 == 0) && ++$cutlen;
	($cutlen % 2 == 0 && $notChineseNum % 2 == 1) && ++$cutlen;

	return substr($str,0,$cutlen).'...';
}

/**
 * 获取图标图像文件的内容
 *
 * 图像内容为 base64 格式保存
 *
 * @param string $filename
 * @return string
 */
function getImageContent($filename) {
	switch ($filename) {
		case 'alert.gif': $img = <<<EOF
R0lGODlhEAAQALMPAP/yl//riv/lUv/3q//fTP/rZf/mfP/ka//hW//ldP/jY+m8ZNJ2Hf/eReCfIf///yH5BAEAAA8ALAAAAAAQABAAAARl8Mm5nFsz57XGOJiWOd4AOOJEmgEQoCkZME
zyxiydFAU8DoEZ46DoaRwAl4GmQBQEvgcyYEjQEAQCVJWsEhFY7VYK8Cpo2UZj6wgmvmj1Guowg9Ny9WXxxeblHA8VFoSFFiEpiREAOw==
EOF;
			break;
		case 'apk.gif': $img = <<<EOF
R0lGODlhEAAQALMPAGylAK3LUNnmrrnTaJ/CNPv898vdj5W9IOTsxuzx1I25DIKyAcPYfJnAJpi/JP///yH5BAEAAA8ALAAAAAAQABAAAARy8MlXxHyo3J1ICASybcewLMOhjQ+gmKgCsA
9j3ILNbELzBIOgMPBoWH4AxELBbC4QAOLDlFA0rleHIoGSMBZVh3i87SLDY7ECKhUQHgtxtrF4EI4TwKHBbBxmLAFaHwoHUiwDgAADNBIEim+NCVIBCRsRADs=
EOF;
			break;
		case 'cd.gif': $img = <<<EOF
R0lGODlhEAAQALMPAGKOvHyhx9Xo+/L9/7bQ7ef1/qnN8sfl/cPc99Xj8dzv/ZrA6qW/2Z241f///////yH5BAEAAA8ALAAAAAAQABAAAASL8MmXWgAgtDR7A0SiFAoBNN0TME5hGMLgOE
wwNYw8LLAzyAxUAqBYKBwE46CgA1QIyYVAgGAyfwRLYsElMDA5UiJx2XIRAAmR8Bo6uYuhOvGCZcywL4BxYIc0BAgHBSQKAgd1CFlDSwoHB4p1dhw4BYeRdQdBEysFkIkCNSkfBAeGiicp
EhUXGRsdEQA7
EOF;
			break;
		case 'cd_page.gif': $img = <<<EOF
R0lGODlhEAAQALMPAHiZzZSv1dfm98HW8FqAveTk5O/y9qrG6/j4+Pn5+fr6+vv7+8nJyfz8/MzMzP///yH5BAEAAA8ALAAAAAAQABAAAASH8L3imL22yO3QamCIMNrGLAaSGkbRFKQ5HL
TAumosHIItBAdWqMHYpQaGRgKALLSIA0GUlWj8VEIG74AIBAw9AAJL3BqAhihAkRiXe1jpALBItIm/RG+AJAwUCm4MDwcAPVIEAQmAgEQSXgAAAQMLlZaODwxDmyGDDw5VnCEJDhIwGKgM
DgURADs=
EOF;
			break;
		case 'code.gif': $img = <<<EOF
R0lGODlhEAAQANUAAB8fH+bm5ra2toKCgnyRFV5vEszMzP///3Z2djpDDpauJaOjo/f390FBQcXFxZmZmeDg4HKEFKjAMUBKD4ifG+/v7729vZSUlNXV1aysrE1ZD2x+E7HKOI6lII2NjY
OZGHiLFCYmJp+3K4ylFmV3E63GND1HDoSUGVFeEHmMFQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAAHAP8A
LAAAAAAQABAAAAaewIMQkikaiwGh8uBgVJ4BiACzgAgpo+yHAIpsSBcG1fopcSQiReeTKiAY4kVl06l3KNsIyoNADC4YGhMTJoUJhwkBURYDDkpNcE5Pkw4Wj5FQBgGTApcMEAZiGBYYcJ
1CjhUCTwIQFQsBDKdMBxahDHIMFguyjwcBvAwPcB6ls45MGQEPBwuWB8dKGFQHGErRS9kHGY8NId/g3wANFkEAOw==
EOF;
			break;
		case 'css.gif': $img = <<<EOF
R0lGODlhEAAQALMPAMzMzMTExP///52tzvf399/f36W77I2y6O/v72iV4MnX8Nzn+O/x+YCo5oGg1P///yH5BAEAAA8ALAAAAAAQABAAAARm8L0CqrVFakCE/wIRZFtInAhSCMVYnnBquo
xzHIYSg8ByG7eDKaUSAG4Oj6HBGMoAjcPgY2iaYNDGFOSBnbJJj0Px8QKWCQWjke6aH4aEPGFwvx8ABZnLkwD4fAB+HYBdghMXiRgRADs=
EOF;
			break;
		case 'db.gif': $img = <<<EOF
R0lGODlhEAAQALMPAPv7+8LCwtPT09ra2svLy7KyspiYmOLi4vX19fHx8enp6aioqLq6ut7e3v///////yH5BAEAAA8ALAAAAAAQABAAAAR/8MlJKwVKBMYCUU6FBIKCONgRBAhFHE4sow
JBBcAsYwXFIIoEAgBINAKKhQ/YEDg3SOXklzgcFAfC6iCV/LCNQ4PQ4foSisHgMNgwzJNCdSBQBwpvA0XerGsKBQ16cQ0IdHQeCgGDElwlCJBVDAYHFQkBC5kLBgYsFhMYCgAWEQA7
EOF;
		   break;
		case 'dir.gif': $img = <<<EOF
R0lGODlhEAAQAOYAANmLM/bpyem5hfDLJeaxdvbhq9umOv788Pbge+PDYuC/Vfzz0PHScfLUfvPTQ/XgnufPfNqOOPTYW/767/Xek//9+OrLa+7Jou/ThPLkuvv26NutO/rx3vLQONuUPu
XIbvrsq+/Uk/fjhP////vyzPbca/nom/TXU9u0O9ucO97FY+7PdfHOL+/QefXbZPjmlPvvuPbecfzyyPPYjfPUSNuWOvHQa/Tdp/fWhPnpo/fhgOC8V9ytP9yzQPz22Pjkje/OKenAcujQ
g/fmvd6USvrtsu7Oh////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEHAEcALAAAAAAQABAAAAeYgEeCg4SFhoeI
iYQKCY2NChOIKCOUlBofmB8QQhmCPRU/FA8FEwemphU7gjwTFBQNFhYYGC21Gj2CGxohRkEBp6cjG4IGIxXHFT4vIjEuEj4GxBUyMCAmPwglEicOC9FHKQdFOcvNJzQdLCQpgjUTOT865u
gsAyQ1gkQcNzcUMzMNGjCwYWOIB0EXCHgAwLBhwwgCFEmcGAgAOw==
EOF;
			break;
		case 'excel.gif': $img = <<<EOF
R0lGODlhEAAQALMPAPDw8OHk4GuqTzxxKIOuclOOPKLHkr3Guvj4+Pv7+/r6+snJyfz8/MzMzP///////yH5BAEAAA8ALAAAAAAQABAAAAR+8L3Qlr02yN1QYmCIHNq2JACSAkDABOQjzH
TtIsgSCE7v9wVAiLGYEQSEQaGgVAVcRcPBYCAQCoGCikXkEajJgCO4JWKV1SNioMAhzEomOJlwm6mGxlHAVLTfCwU/P2x+CkQDiYqLCY0fCxILQ5MhkA8dlEMIDRIUGJ8LDQERADs=
EOF;
			break;
		case 'exe.gif': $img = <<<EOF
R0lGODlhEAAQALMPAPX19e3t7fDw8GCqe0J2m+jo6FOFklWbiPn5+fz8/P7+/tPZ4MrMzf///4+Sl////yH5BAEAAA8ALAAAAAAQABAAAARe8MlJq73U6c27XmAYGgtpaEyqrqvWvLCiJA
kCAIJL7IQh0zZCztHYGQyHxAog1B0PSYT0dgi4kNDkSjCwErODge0mEAQKLmhYvAqcXY3ZlOwuoInx2th8tnv+HBgYEQA7
EOF;
			break;
		case 'external.gif': $img = 'R0lGODlhDAAMAIABAHOfqv///yH5BAEAAAEALAAAAAAMAAwAAAIXjI+pCu2QnlvhLclahWjurhmhyFHmeRQAOw=='; break;
		case 'folder.gif': $img = <<<EOF
R0lGODlhMAAwALMPANKLFOCvSf/NBf/SGf/YLf/eRenFaP/kWf/nZP/sc//tff/yjfnxmf/+qf/+tf///yH5BAEAAA8ALAAAAAAwADAAAAT/8MlJq7046827/2AojlUAnGiqAqQWBE4szz
TDthdA77th4Bad40AoGo/HwA9I0SUE0Kh0qmQ2HYipdrrqdjO6w3Ys4JlnhlsQS94e3vC4/M2IVdfitn4f8+WGe4FkCw5+eIKIUwqFSxVhXF6Rkip/eQIASj6am5ydnpwmjoCXMGempzMv
V3kAdag0DbGysQwNtQy1MmoPj5evDQ6zwrcMC8XGxjG7vUJmwrPEuLjIC9XVyldZvrDPtLbSyMfWCwrkhA7LWAMDzd223+Hj1uUK9fbn6QjrQtDv4OLj6NkbmCABvisJivCLZgzgPHID7R
WciGAROoQKYzSIJ89cRAUJ/0BOTICAJIKK2CY4KVBAiEOPH0GKLHiyps0EKSXoUMDSZTWBEUOOLGnT5gEEbxDk5LXxjRCIQYXSNFkU6ZyjS3UsOPpU4kyqVa1eLXCgAAJgyzaefDoUrFGx
csiynHu2QboFBYUoIBr26hu5cwOzPBArLYN6eose9QtYcGAjhO02sVVNCMnFY8s6nkugAJLOhSdTtjyn8WaWn5EUCK2Slo0YVk1vTp163WrJrWUBAIbgNGfaRwYQEL5uwO1ltdDCdgz8c/
HnxQmwlvDCnhDNnYt4bg69uwDh0yWkQRGLSPPh3Z9/1yIdd45YC2inVz9g/ZYE4UsYCNYgbF+rcM2hQB0sCuwShAHuJKjgMwVyYMIkEEpixYQUVmjhhRhGAAA7
EOF;
			break;
		case 'html.gif': $img = <<<EOF
R0lGODlhEAAQALMPAGhrmXaCri6c6i6Mzn6tyczm/iBimZ+/3lmWvISp3MTV/bPO9VdYiPn5+pOTk////yH5BAEAAA8ALAAAAAAQABAAAASK8MlEKw1K6pea/01AHBv3KQSxBAtCagkiy4
0A3IEjHchSCAJE41BoKHIP3m8wAAo9K0dBSDAcGs1BoLgAOKiCZtjp6zoaBKBhzWaUvYeDmlioF4gLxncssDIHbgV5XyMGQAJxVgoKgwcEDQgGDGsMKouDDwsECQpxC4ugmBIMpKWmpDoa
DqusrasRADs=
EOF;
			break;
		case 'img.gif': $img = <<<EOF
R0lGODlhEAAQALMPAIGITsnT5Ka304i8Zpq/96jQg4C1pNKfVLbV/6+GP+7z92memmeaQJmv0cLcv////yH5BAEAAA8ALAAAAAAQABAAAARd8MlJqwyi6c1bkIIijqTYSI2CIEbhFgtBKO
eTsu+gxzSqtrogr3YDBgdDH+6InBFViMUx5lSuAFgAYdWzKRgDRuJwSDDO3ZRj7Sgc2I60Ak6P10KlfA3T6X8sgBMRADs=
EOF;
			break;
		case 'js.gif': $img = <<<EOF
R0lGODlhEAAQALMPAO/v74CJO295It/f3+Dj5uns7+/y9cTExObm5oyUTr7DnPf391pmAMzMzP///////yH5BAEAAA8ALAAAAAAQABAAAARo8JFGax0v57ac/85yYFoTLigAIA4ylie6qO
f7mDJqFGCzKQkBgyFIKAoFls8gZDiGHkaAAHD4AkOnNhvw+LLbZxZ1zYq3XeuDGXWKA4WO7wZsFhUd7wbE/8xNfXx/eYEhcxMWiRQEDxEAOw==
EOF;
			break;
		case 'manual.gif': $img = <<<EOF
R0lGODlhEgASALMIAMzMzN3d3aqqqnd3d7u7u4iIiP///+7u7v///wAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAAgALAAAAAASABIAAARgEMlJKw0kay0sIoZxiEd5FIEFmuzZVUTLFo
UqCgAulMWgyqbeD3jywYgB4VEWKA5bzQLhNYlBD5gQ9WNqekEhQ01i9WIDgrB4IqC532mDUYJT22ltSm/A7/NfeR6CCG0RADs=
EOF;
		   break;
		case 'mdb.gif': $img = <<<EOF
R0lGODlhEgASAKIAAISEhIQAhIQAAMbGxgAAAP///9TQyAAAACwAAAAAEgASAAADVFi63P4wNsJEsPgGBQpV2yMMHmEqwgJcXUAqwxcWV5GOS4wuGf0WOhpK0xkNSMEba+E6AmU0QYcHe+
4eIaQsw60GHcew8yvJfRji9PG8MLnf7jIkAQA7
EOF;
			break;
		case 'mp3.gif': $img = <<<EOF
R0lGODlhEAAQAOYAAAAPZNvb0EZnyZqGM02rVsTExHdjMKiuuaW05PD//yBBq7GMjGCflu3q5ouj1zJ/XbmiKH17Z42OgTVHiqFhfcfa/rOorm5lcomWxeyVjI6Rl1xqqmiIbtldXO2yr+
Lj5JCOPXWBm6aTXZaw//f390F0OczMzHA+RrTE9RI0jYWJi4yt40x/7mm2gcu0S97m7pG2nJV8gUFco7e7vTpneJZ3M3GMr6XEuf///1CA2V+2ZEBPc751cNLm//+noJKbrLnX/5ym9evr
59na3PH1/83P1rLB3djb84t/YVx0vGqfcsaNitbO1vTn673K8+Pq7ZChzRcuhLzCwavBsrrT89/f3+bm5u/v7/T2/6uvuevq68zf/52q+7HQ89DT1Orv8f///wAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEHAGAALAAAAAAQABAAAAezgGAfJoSFhVVg
iYkmJCQ4j5AkBYiKjI2NV1daOFaTlTiXmJuNnmAmoE8WMRwwXpA4Jos4Q0sZHjctOlNaVrCLXzw+TVhYTgwEUle+pgsdR10rVD1BD0rKsaYnFEAOKVEbXDQlrtgmOzJbUAATGCMXBjPLJj
8KKC9GCQkINUhF8kJJWFTAgSWAiwEaGpXD8cFGDgEiIIBQMUShLBwNDoSIICFLr0flHL0aSQLbIEMoCX0AEwgAOw==
EOF;
			break;
		case 'php.gif': $img = <<<EOF
R0lGODlhEAAQALMPAF5goaGixubm69/f331+s8TExODg6cjJ36+w0O/v79PU5vf391FTmczMzPr6/P///yH5BAEAAA8ALAAAAAAQABAAAAR58D3TqrVD6rac/16Rbc5iLkkiOIJInmZaLu
5TIADBMEBggI0HYsdQEHcBwaoxPCgQgqdzGEg4GgCGAXAQAAzbAwFgbeyMB0MRres0AoyFIjBEyOEBd4JgPPYJAm4PCQE6RwQIVh5BDw0gjx+MjpCPkpSVEhQXmxUGEQA7
EOF;
			break;
		case 'powerpoint.gif': $img = <<<EOF
R0lGODlhEAAQALMPAPTz8+Xl5eVHNOphR+53WMPDw/KOb/3s6fj4+Pv7+/r6+snJyfz8/MzMzP///////yH5BAEAAA8ALAAAAAAQABAAAAR98L3Qlr02yN1QYmCIFNq2JAACrEDABORjzA
ZRE7irkobj/76BK7ToIWY2hGOwCrgWBIfhgMD1mCwGVKpYDgYOgSK7JXzPYcUYoY0azoKoIIGoa8HVL+GQVtgXYEA/YmoKd18CiYpzCY1aEgshkpMLEh2TkwgNEgEYnhYNAREAOw==
EOF;
			break;
		case 'py.gif': $img = <<<EOF
R0lGODlhEAAQALMPAENtjv/RQqWoq0eArv/spf/sYu3t7bOzs7q6usLCwt7e3svLy/f39+Xk5P7+/v///yH5BAEAAA8ALAAAAAAQABAAAASE8KCJkl0rqccfWg3jjOPiLBuHKKLSGA3pGF
0COu+YDAOA1IqRaDRQAI4dTCwoSyA5GhFj1zseHQGCLebgVY8CRiAQFXkNJEZh/Al1AQZCYT5ms0QIgGO+rhMOIAZoI3MEHRwHLA0vaHQGAR0CCQcHAoAMBH4dgB0KlVyHoR0NCAICC4cR
ADs=
EOF;
			break;
		case 'rar.gif': $img = <<<EOF
R0lGODlhEAAQALMPAP/NBcvLy/X19WCv//93YEu5NS2X/+Hh4UGU5pubm9nZ2TmyMOZYQbCXKnR0dP///yH5BAEAAA8ALAAAAAAQABAAAAR88MnXqp2TadaAB83GHA9DnN3XnASTHKIjz2
KibI6g785mI8DcTAhE/AYDx8GBVDIHRgXCYHAoHFQr1hAtamfXom1BdgTOaAd5MU4UhrOCm61YFO6NA0AAuhfoawt5fAINgTYHCYoWFg6KiiQHCgoBj48BkyQYD5KTCpoTEQA7
EOF;
			break;
		case 'round.gif': $img = <<<EOF
R0lGODlhDQAQAOZhAAB3v2uwKHy9KAB2vgButgBttQBvt/7//tLr+NDr+dztvczju+z12AeDyvH442q96QB9xgCAyXm7IwBhq/P5526yLLfJtPv99gB/yI3FGmqwJ/j7/IXBHqnK4nu8Jk
KIw73c7+XyxeHx+e322svl9Ah1vHGzMYC/GABwtwB4v5fLRtDp9vD3+9Dj8fn6+Hu8Jfj87rfYoOfy3OLv2GmrPrTT6KXST7TXkLfb8O323/7+/8Ha7PX68pnF4iqBuwN7w6DPRebyzW2x
KpvNXgR6wna6IHiw1rveY0Ch2K3WeIvFL4rFL8/moo/HOQBrtO31+q/P5sTd7obCIXq8JVyjLQ2N0/b77bLP6Ljd8giFy1qVSenx5o/HOCSX1u/33PLz8jmIv////wAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAGEALAAAAAANABAAAAeFgGGCggmDhoYI
D4eHK1VdWCKLYThZEBgRDUgkhixEAykAoQM/IIM6PQUoBgUEBE4lT4Y1Ez47LVFQYEaHVx+HHWEhXoMbF0cwhiNATAwOFEEZNlaGCidKXE1LUhwqOYZJRVMvHgICEkOGBzcmAQEVQhoyiz
wLMVQ0M5KCLlpb+oJfLBwKBAA7
EOF;
			break;
		case 'sarrow.gif': $img = <<<EOF
R0lGODlhCAAeALMNAHV1dnNzdHh4ecPDw3l5esLCwsDAwHh4eM3NzXNzc3Z2d3V1de/v7////wAAAAAAACH5BAEAAA0ALAAAAAAIAB4AAAQzsLUhq1TFSgCMXguBWIsQMNZxagmquVosz3
Rt33iu77zFJAcQQEFhBAQLQEZiJHh8I0kEADs=
EOF;
			break;
		case 'sort.gif': $img = <<<EOF
R0lGODlhCwAlAJECAPX19f///////wAAACH5BAEAAAIALAAAAAALACUAAAI8lA2nCLnSoDuR0Wnr0s++yEnXqIDYdZbdyrbuCz8BG9SdbSu5sO8GPpMFY8Si8TfUzXy8YS/ZjAqhukUBADs=
EOF;
			break;
		case 'swf.gif': $img = <<<EOF
R0lGODlhEAAQALMPAPnKy/zn5/l2c/iKi/VlY/I7QPNXWfRITPaAgubm5t/f3+/v78zMzP////f39////yH5BAEAAA8ALAAAAAAQABAAAARr8ElGa1UyM+ea/w6DaVy5LEmTiFrDAQNyuu
HIuACBHPPHTI2AgWAQOE6oxu9xQwgEAdNsyQAYDoAArRSaDJ4FhIfbZRYEhfCYuywWAFkyhyEwFHRrOeNwP2jzbGkGAB+FhVQdhoVlChaOFxEAOw==
EOF;
			break;
		case 'th.gif': $img = <<<EOF
R0lGODlhBgAaAMQAAOeKGP/kjf/ef//ac//Wav/UZP/TYf/TYv/SX//PXf/NWv/LWv/KV//HVv/GU//DT//ATv+/S/+8R/+6Rv+5Q/+3Qv+1P/+yPP+wOf+tN/+uOf+xQP+4Tv+2S/++Wv
/GbCwAAAAABgAaAAAFSiAgjkFpCmg6rCzhvkQhz0ZdH0ieJ3yfKEDggkEkNhxI5GPJhESeT4l0OqFYrRWLdnvpejHgcGY8xmjO5416zem43Z64/EOvj0YhADs=
EOF;
			break;
		case 'txt.gif': $img = <<<EOF
R0lGODlhEAAQALMAAKSkpPf398zMzN3d3cXFxf///+bm5r29vdbW1re3t+/v762trf///wAAAAAAAAAAACH5BAEHAAwALAAAAAAQABAAAARekBlBax0sZxFC+WBAYBrXdYpiFMZYFieqwC
IpwMeRLAsAFoLN75RSAYUFAkG3482CjNuPFoNKKcpcrmOdfmKBbgGBqCgJXOQXnI5+BgOyHNGWen9Wz/0LnVj+FAYMEQA7
EOF;
			break;
		case 'up.gif': $img = <<<EOF
R0lGODlhDAAOAOZIAKPsJ4C5M87itPD16uz7yLjch6XMcG2zDIXIKmuxC7jbh2+2DbDSgX6/Jnq5Is34QLndiOX5r2muCunp6cv3Pu743pHQI7jlHdTvd5HOK5DPIsPpUavuKpLQLOD4Zd
T0f9LumLzqItz2hNb4Xtf2hpLMLInIII7NIbnnGuz6v/D6zPD80LjsKcr0Qdvyl+z7xsDyNb/1OMj3PKzuK+T1wdT4U9T5VNT4XLf0M7z1P9XxduP0yNb5X9fyepPQLfL5547WFdPtp6Dc
O5XeGbjkb4/YE5zlILfah////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAEgALAAAAAAMAA4AAAeFgEiCSBAICBCD
iQU+KykWBYlICh0EPDYRGgqDRxkEIw8PNREnR0hHJS83FDIxODkkJkcNKh4wHAC4My0iDQwOCx9GwkYsCw4MSBMHPUPNQyEHE4MJOkXWRSgJiRIYQN5AFxKJAS5C5kIbAYkGNETuRCAGiQ
IVQfZBOwKJA0g//j9I+CEJBAA7
EOF;
			break;
		case 'viewmode.gif': $img = 'R0lGODlhGwAIAIABAJmZmf///yH5BAEAAAEALAAAAAAbAAgAAAIhRICZwYxn3mu0NhmzVtP6D2IQtHGVKIKqVypnNnarA2MFADs='; break;
		case 'word.gif': $img = <<<EOF
R0lGODlhEAAQALMPAPT09FhvyuLj5piq3nCV1WGC0LzCzuzu9fj4+Pv7+/r6+vv8/snJyfz8/MzMzP///yH5BAEAAA8ALAAAAAAQABAAAASB8D3hmL1WyO1QamCIGNrGJEAKHIDQCORDzD
RR3EeDAIxAKIug8FDIhRiExWywLARWAhdSSTAUmE5Vbkor2AaBXarBvTkLgoACsSMXFk7s4BBIsBHuhldhO9AVa3gMRX6FB2mAgGQBjI2OCZAfDBIMIZaXkw8dl5cIDhIUGKIMDgIRADs=
EOF;
			break;
		case 'xml.gif': $img = <<<EOF
R0lGODlhEAAQAMQfAFe68v/QCNfr/f+qAMHwqbS10uX3/0KxUPT9///DPLCmoKmPadnb+ubo98LC4bXE8//ZXv/mffzvvsrY+rni7Onk08LB1ZzQ0o7I9f71ypukvoTXnfP/0sDP8P////
///yH5BAEAAB8ALAAAAAAQABAAAAWU4Nc5TGmaxqd+jue+r/A0awvDzYTR7A0jjMms90JQKBeDB2EQaIgNI4dwACAmCE+h14gCNtWDwNDxPFtdBABwAFMQDsTT4opiMGAAWfks3DoXGAwG
hHIffj8GTISEfRkcHBkZEhkVEhWYFU8KAZ0QnwkQCQkDpQsfnJ4REBISo6UDp6kBEasSEQkRsKcWC76/wL8KIQA7
EOF;
			break;
		case 'zip.gif': $img = <<<EOF
R0lGODlhEAAQANU6AJKSjI2NjOfp7MTExJ2dlPXuR5WVkvP0Y+fmMezrQWSW5IuRl/X1XOjv+Xui33ym55ubd6mbWO/myOfoOtrGe7GrfcyzO8fFv8LIzWZjT8GoN2+f6Gl2f+/pQ76+uu
3sN9HQyvj3bO7oNPbuP+vrVPf3SoKs6t/k69nZ1bi5tHGk8urrR9jUSMWsP9DQGo6MhMStR6Ojm+bm5s62QO/v797f3vf395mZmf///8zMzP///wAAAAAAAAAAAAAAAAAAACH5BAEAADoA
LAAAAAAQABAAAAaeQJ1OlisajTWhMmfDOZ842yC5jNquNJoAJ5vqJK3K7RYrEzzb6xTGgrzer4BcBs3NDodQqQCTjbMCdHYkLoUdfTEGWVk4dgwJCR98MolXlnYrhS4iMDUEAJZXjhMICC
Odn6E2ORQzrhYaESkxAao5Qhc1CwsAL70nDZdCOQIqDjkAGRwbJk+3OjkNCg41KCAYCg/Ow01Q3qtCNUfjSEEAOw==
EOF;
			break;
		default: $img = <<<EOF
R0lGODlhEAAQALMPAPLy8u/v7+rq6uDg4O3t7efn58PDw/b29vj4+Pr6+vn5+fv7+8nJyczMzPz8/P///yH5BAEAAA8ALAAAAAAQABAAAARq8I3Gqq3Dvf0aWk4oIkbGMQtwrEAgOEWpPY
xzKKxrA+VWK0DWS8TwOUBHxAFAEBQcRZootFC2CFDjNLkEZKVbRwJ3+NbCCyDCPF24EwnEOnqmLuB4dtvN14enUQ0KfyIKDRsTF4oMDQMPEQA7
EOF;
	}

	return base64_decode($img);
}

/**
 * 展示主示图
 *
 * @param array $__vars 视图变量
 */
function view($__vars) {
	extract($__vars);
?>
<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta charset="utf-8" />
<title><?=$title?></title>
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<link type="favicon" rel="shortcut icon" href="/favicon.png" />
<style type="text/css">
html{margin:0;background-color: #fff;}
body {margin:0; background-color:#F7F7F7;}
body{max-width: 800px;width: 100%;margin: 0 auto;min-height: 90vh;}
body,td,th {font-size:14px; font-family:Verdana, Arial, 微软雅黑, 宋体;}
a {text-decoration:none; color:#0000FF;}
table {border-collapse:collapse; border-spacing:0; empty-cells:show; clear:both;}
table,ul,li {padding:0; margin:0;}
img {border:none;}
.button {
	color:#CB760D; background-color:#FFD361; border:1px solid #E78A18; border-radius:3px; text-decoration:none;
	box-shadow:0 1px 2px rgba(0,0,0,.2);
	background: -webkit-gradient(linear, left top, left bottom, from(#FFDA73), to(#FFB039));
	background: -moz-linear-gradient(top, #FFDA73, #FFB039);
	background: -o-linear-gradient(top, #FFDA73, #FFB039);
	background: gradient(top, #FFDA73, #FFB039);
	filter:progid:DXImageTransform.Microsoft.Gradient(GradientType=0, StartColorStr='#FFDA73', EndColorStr='#FFB039');
}
a.button {color:#CB760D;}
.button:active {
	background-color:#FFB742;
	background: -webkit-gradient(linear, left top, left bottom, from(#F2A531), to(#FFC66C));
	background: -moz-linear-gradient(top, #F2A531, #FFC66C);
	background: -o-linear-gradient(top, #F2A531, #FFC66C);
	background: gradient(top, #F2A531, #FFC66C);
	filter:progid:DXImageTransform.Microsoft.Gradient(GradientType=0, StartColorStr='#F2A531', EndColorStr='#FFC66C');
}
.cc:after {content:"."; display:block; height:0; clear:both; visibility:hidden;}
.cc {zoom:1;}

.addressBar {border-bottom:1px solid #D6E579; background-color:#F2FFA5;}
.topbox{position:fixed; top:0;z-index:1;width:100%;max-width:800px;}
.top {background-color:#F7FFC9; border-bottom:2px solid #D6E579; width:100%;}
.top .search {float:right; margin-right:5px;}
.top .toolbar {padding:3px 10px 3px 6px;}
.top .nav {line-height:14px;}
.top .nav a {margin-left:10px; padding:2px 5px; display:block; height:16px; float:left;}
.top .nav img {vertical-align:top;}
.top .nav span {line-height:20px;}
#address {padding-left:6px;}
#address a, #address em {float:left; display:block; border:1px solid #F2FFA5; border-top:none; border-bottom:none; height:26px; line-height:26px;}
#address a:hover, #address em:hover {color:#000; border-color:#D6E579;}
#address a {text-decoration:none; color:#666; float:left; padding:0 3px;}
#address em {width:14px; background:url(?action=image&file=sarrow.gif) center 9px no-repeat; position:relative;}
#address em.current {background-position:center -15px; border-color:#D6E579;}
#subdir {position:absolute; z-index:2; border:1px solid #CEDE6F; width:auto; display:inline-block; background-color:#F2FFA5; left:-1px; top:26px; padding:5px; list-style:none; overflow-y:auto; box-shadow:0 3px 3px rgba(0,0,0,.3);}
#subdir li {background:url(?action=image&file=dir.gif) left center no-repeat; text-indent:23px; font-style:normal; border-bottom:1px solid #D8E87E;  padding-right:5px;}
#subdir li.current {font-weight:bold;}
#subdir li a {float:none; border:none; white-space:nowrap; padding:0;}
.viewmode {float:right; border:1px solid #CCC; height:14px; background-color:#EAFF6A; font-size:1px; margin-top:3px;}
.viewmode a {display:block; position:relative; overflow:hidden; background-image:url(?action=image&file=viewmode.gif); background-repeat:no-repeat; width:15px; height:14px; float:left;}
.viewmode-icon {background-position:-14px 3px; border-right:1px solid #CCC;}
.viewmode-list {background-position:2px 3px;}
.viewmode .current {background-color:#FFF;}
address {text-align:right;}
.container {padding:10px; margin-top:55px;}
.list table tr {height:22px;}
.list table th a {color:#FFF; display:inline-block; height:17px; line-height:17px;}
.sort, .sort-asc, .sort-desc {background:url(?action=image&file=sort.gif) no-repeat right 2px; padding-right:17px;}
.sort-asc {background-position:right -14px;}
.sort-desc {background-position:right -25px;}
.folder span a, #filelist a {text-decoration:underline;}
.list .folder a:visited, .list tbody a:visited {color:#810081;}
.folder {width:100px; height:90px; text-align:center; float:left; margin:5px;}
.folder-icon {border-radius:5px; width:48px; height:48px; display:block; padding:3px 5px; margin:0 auto 2px;}
.folder-name {display:inline-block; word-break:break-all; padding:1px 3px; border-radius:3px;}
.folder:hover .folder-icon {background-color:#DDD;}
.folder:hover .folder-name {background-color:#5151d4;}
.folder:hover .folder-name, .folder:hover .folder-name:visited {color:#FFF;}
#filelist a {text-decoration:underline;}
#filelist em a {text-decoration:none;}
#filelist a:visited {color:#810081;}
#filelist td {padding:5px;}

@media (max-width: 750px) {
	.colType {display:none;}
}
</style>
<script type="text/javascript">
Array.prototype.each = function(callback) {
	for(var i=0,o;o=this[i]; i++) {
		if (callback.call(o,(this),i) === false) {
			break;
		}
	}
};

var is_ie = (navigator.userAgent.indexOf('MSIE') != -1);
var is_gecko = (navigator.product == "Gecko");
var is_ns = (document.layers);
var is_w3 = (document.getElementById && !is_ie);
var searchIndex = 0;

var $ = function(id){ return document.getElementById(id); };

function ToFindInPage() {
	if ($('schstring').value!='') {
		FindInPage($('schstring').value);
		$('schbt').disabled = false;
	} else {
		$('schbt').disabled = true;
	}
}

function FindInPage(str) {
	if(!str) {
		alert('未找到指定内容');
		return;
	}
	if (is_w3 || is_ns) {
		if (!window.find(str)) {
			alert('到达页尾，从页首继续');
			while (1) {
				if (window.find(str,false,true)) break;
			}
		}
	} else if (is_ie) {
		var found;
		var txt = document.body.createTextRange();
		for (var i = 0; i <= searchIndex && (found = txt.findText(str)) != false; i++) {
			txt.moveStart('character',1);
			txt.moveEnd('textedit');
		}
		if (found) {
			searchIndex++;
			txt.moveStart('character',-1);
			txt.findText(str);
			try {
				txt.select();
				txt.scrollIntoView();
			} catch(e) { FindInPage(str); }
		} else {
			if (searchIndex > 0) {
				searchIndex = 0;
				alert('到达页尾，从页首继续');
				FindInPage(str);
			} else {
				alert('未找到指定内容');
			}
		}
	}
}

function setBackgroundColorHover(id,tag,color) {
	var elements = document.getElementById(id).getElementsByTagName(tag);
	for (var i=0,row; row=elements[i]; i++) {
		if (typeof arguments[3] != 'undefined' && i % 2 == 1) {
			row.style.backgroundColor = arguments[3];
		}
		if (color) {
			row.onmouseover = function(){
				if (!this.origColor) this.origColor = this.style.backgroundColor;
				this.style.backgroundColor = color;
			};
			row.onmouseout = function(){ this.style.backgroundColor = this.origColor; }
		}
	}
}

function pLoadScripts(scripts,callback) {
	if(typeof(scripts)!="object"){scripts=[scripts];}var HEAD=document.getElementsByTagName("head").item(0)||document.documentElement,
		s=[],loaded=0;for(var i=0;i<scripts.length;i++){s[i]=document.createElement("script");s[i].setAttribute("type","text/javascript");
		s[i].onload=s[i].onreadystatechange=function(){if(!this.readyState||/loaded|complete/.test(this.readyState)){loaded++;
			this.onload=this.onreadystatechange=null;this.parentNode.removeChild(this);if(loaded==scripts.length&&typeof(callback)=="function")
			{callback();}}};s[i].setAttribute("src",scripts[i]);HEAD.appendChild(s[i]);}
}

function removeElement(element) {
	if (element) element.parentNode.removeChild(element);
}

function getElementsByClass(searchClass,node,tag) {
	var classElements = [];
	if (node == null) node = document;
	if (tag == null) tag = '*';
	var els = node.getElementsByTagName(tag);
	var elsLen = els.length;
	var pattern = new RegExp("(^|\\s)" + searchClass + "(\\s|$)");
	for (var i=0,j=0; i<elsLen; i++) {
		if (pattern.test(els[i].className)) {
			classElements[j] = els[i];
			j++;
		}
	}
	return classElements;
}

function each(elements,callback) {
	for (var i=0,a; a=elements[i]; i++) {
		callback.call(a,i);
	}
}

if (is_ie) {
	document.execCommand("BackgroundImageCache", false, true);
}
</script>
</head>
<body>
<a name="Top"></a>
<div class="topbox">
<div class="top">
	<div class="addressBar cc">
		<div class="search"><input type="text" name="schstring" id="schstring" style="width:150px;" onfocus="document.getElementById('schbt').disabled = false;" onclick="this.select();"  onkeydown="if(event.keyCode==13) { ToFindInPage(); }" /> <input type="button" id="schbt" value="查找" onclick="ToFindInPage();" /></div>
		<div id="address" class="cc"><a href="?dir=."><b>根目录</b></a><em></em><?=$breadCrumb?></div>
	</div>
	<div class="toolbar cc">
		<div class="viewmode cc">
			<a href="?dir=<?=$current?>&viewmode=icon" class="viewmode-icon<?php if ($viewMode == 'icon') { ?> current<?php } ?>" title="图标模式">&nbsp;</a>
			<a href="?dir=<?=$current?>&viewmode=list" class="viewmode-list<?php if ($viewMode == 'list') { ?> current<?php } ?>" title="列表模式">&nbsp;</a>
		</div>
		<div class="nav">
			<?php if ($dir != '') { ?>
				<a href="?dir=." class="button"><img src="?action=image&file=dir.gif" /> ROOT</a>
				<a href="?dir=<?=$upDir?>" class="button"><img src="?action=image&file=up.gif" /> 上一层</a>
			<?php } ?>
			<a href="javascript:location.reload();" class="button"><img src="?action=image&file=round.gif" /> 刷新</a>
			<span>&nbsp;　&nbsp;<?=$status?></span>
		</div>
	</div>
</div>
</div>
<div class="container">
	<div class="list">
		<?php if ($viewMode == 'icon' && $countDir > 0) { foreach($dirs as $row) { ?>
			<div class="folder">
				<a class="folder-icon" href="?dir=<?=$dir.$row['uri']?>" title="浏览此目录"><img src="?action=image&file=folder.gif" /></a>
				<a class="folder-name" href="?dir=<?=$dir.$row['uri']?>" target="_self" title="访问 <?=$row['filename']?> 目录"><?=$row['filename']?></a>
			</div>
		<?php } }
		if (($viewMode == 'icon' && $countFile > 0) || ($viewMode == 'list' && $countFile + $countDir > 0)) {
			$sortNames = array('filename', 'filemtime', 'type', 'filesize');
			$sorts = array();
			if ($sortField == '') {
				foreach ($sortNames as $row) {
					$sorts[$row] = array("$row,asc", 'sort');
				}
			} else {
				foreach ($sortNames as $row) {
					if ($sortField == $row) {
						$orderBy = $sortMethod == 'asc' ? "$row,desc" : ($sortMethod == 'desc' ? 'no' : "$row,asc");
						$sorts[$row] = array($orderBy, "sort-$sortMethod");
					} else {
						$sorts[$row] = array("$row,asc", '');
					}
				}
			}
			?>
			<table width="100%" cellpadding="1" cellspacing="0" border="0" <?php if(empty($_GET['dir'])&&$viewMode=='icon') echo 'style="display:none"';?>">
				<thead>
				<tr style="background:#E8E8A0 url('?action=image&file=th.gif') repeat-x;height:26px;">
					<th width="24">&nbsp;</th>
					<th align="left">&nbsp;<a href="?dir=<?=$current?>&sort=<?=$sorts['filename'][0]?>" <?php if ($sorts['filename'][1]) { ?>class="<?=$sorts['filename'][1]?>" <?php } ?>title="按名称排序"><?=($viewMode == 'list' ? '名称' : '文件名')?></a></th>
					<th width="200" align="center"><a href="?dir=<?=$current?>&sort=<?=$sorts['filemtime'][0]?>" <?php if ($sorts['filemtime'][1]) { ?>class="<?=$sorts['filemtime'][1]?>" <?php } ?>title="按最后修改时间排序">最后修改时间</a></th>
					<th width="110" align="left" class="colType">&nbsp;<a href="?dir=<?=$current?>&sort=<?=$sorts['type'][0]?>" <?php if ($sorts['type'][1]) { ?>class="<?=$sorts['type'][1]?>" <?php } ?>title="按文件扩展名排序">类型</a></th>
					<th width="120" align="right"><a href="?dir=<?=$current?>&sort=<?=$sorts['filesize'][0]?>" <?php if ($sorts['filesize'][1]) { ?>class="<?=$sorts['filesize'][1]?>" <?php } ?>title="按文件大小排序">文件大小</a>　</th>
				</tr>
				</thead>
				<tbody id="filelist">
				<?php if ($viewMode == 'list') { foreach ($dirs as $row) { ?>
					<tr align="center">
						<td><img src="?action=image&file=dir.gif" border="0" /></td>
						<td align="left"><a href="?dir=<?=$dir.$row['uri']?>" title="浏览 <?=$row['filename']?> 目录"><?=$row['filename']?></a> <em><a href="<?=$dir.$row['uri'].'/'?>" target="_blank" title="访问 <?=$row['filename']?> 目录"><img src="?action=image&file=external.gif" /></a></em></td>
						<td><?=date('Y-m-d H:i:s',$row['filemtime'])?></td>
						<td align="left">文件夹</td>
						<td>&nbsp;</td>
					</tr>
				<?php } } if (!empty($_GET['dir'])) foreach($files as $row) { ?>
					<tr align="center">
						<td><img src="?action=image&file=<?=$row['icon']?>" border="0" /></td>
						<td align="left"><a href="<?=$dir.$row['uri']?>" target="_blank" title="访问 <?=$row['filename']?>"><?=$row['filename']?></a></td>
						<td><?=date('Y-m-d H:i:s',$row['filemtime'])?></td>
						<td align="left" class="colType"><?=strtoupper($row['type'])?> 文件</td>
						<td align="right"><?=convertFileSize($row['filesize'])?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		<?php } ?>
	</div>
	<!--<div class="cc">-->
	<!--	<a href="javascript:;" onclick="window.scrollTo(0,0);" style="float:right; margin-right:60px;">Go Top</a>-->
	<!--</div>-->
	 <!--<?php footer(); ?> -->
</div>

</body>
<script type="text/javascript">
//$("schstring").focus();

if (document.getElementById("filelist")) { setBackgroundColorHover("filelist","tr","#BFFAFF","#F5F0F0"); }

var symbol = $("address").getElementsByTagName("em"), subdirs = [], listSubDirectorys = function() {
	if (document.getElementById("subdir")) { removeElement($("subdir")); }

	var dir = this.getAttribute("data-addr");
	dir = dir ? dir + "/" : "";

	var nextDir = this.nextSibling == null ? "" : this.nextSibling.innerHTML;

	var sub = document.createElement("ul");
	sub.setAttribute("id","subdir");

	pLoadScripts("?action=subdir&dir=" + dir, function(){
		for (var i=0,row; row=subdirs[i]; i++) {
			var li = document.createElement("li");
			if (row[0] == nextDir) { li.className = "current"; }
			li.innerHTML = '<a href="?dir=' + dir + row[1] + '">' + row[0] + '</a>';
			sub.appendChild(li);
		}
		var adjust = document.documentElement.clientHeight - 50;
		if (sub.offsetHeight > adjust) {
			sub.style.height = adjust + "px";
			sub.style.width = (sub.offsetWidth + 18) + "px";
		}
	});

	this.appendChild(sub);
	this.className = "current";

	document.body.onclick = function(e) {
		var target = (is_ie ? event.srcElement : e.target), outEm = 1;
		getElementsByClass("current",$("address"),"em").each(function(){
			target != this ? this.className = "" : outEm = 0;
		});
		if (outEm && sub != target) {
			removeElement(sub);
			document.body.onclick = null;
		}
	};
};

each(symbol,function(){
	this.onclick = listSubDirectorys;
});
</script>
</html>
<?php
}

/**
 * 展示视角
 */
function footer() {
	// $SoftWare = explode(' ',$_SERVER['SERVER_SOFTWARE']);
	//php_uname().PHP_SAPI
	// echo '<hr /><address>One Explorer V'.OneExplorer::VERSION.' & '.PHP_OS.' '.$SoftWare[0].' PHP/'.PHP_VERSION.'<br /><small>&copy; Copyrights <a href="http://www.vgot.net/" target="_blank">VGOT.NET</a> 2008-2020</small></address>';
}
