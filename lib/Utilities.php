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
		return preg_replace_callback('/_(.?)/', function ($m) { return strtoupper($m[1]); },$string);
	}

    public static function dashesToMethodName ($string)
    {
        return self::toTableName(str_replace('-', '_', $string));
    }

    public static function dashesToClassName ($string)
    {
        return self::toClassName(str_replace('-', '_', $string));
    }

    public static function dashesToUnderscores ($string)
    {
        return str_replace('-', '_', $string);
    }
}
