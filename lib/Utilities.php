<?php

namespace Pails;

class Utilities
{
	public static function toClassName ($string)
	{
		// underscored to upper-camelcase
		// e.g. "this_method_name" -> "ThisMethodName"
		return str_replace('_', '', preg_replace_callback ('/(?:^|_|\\\)(.?)/', function ($m) { return strtoupper($m[0]); }, $string));
	}

	public static function toTableName ($string)
	{
		// underscored to lower-camelcase
		// e.g. "this_method_name" -> "thisMethodName"
		return preg_replace('/_(.?)/e',"strtoupper('$1')",$string);
	}
}