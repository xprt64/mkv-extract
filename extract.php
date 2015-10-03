<?php
/**
 * @author Gica <xprt64@gmail.com>
 */

$file	=	$argv[1];

$file or die("Usage: php -f " .  __FILE__ . ' "path_to_file"' . "\n");

if(is_dir($file))
{
	parse_dir_recursive($file);
}
else
	extract_subtitle($file);

function parse_dir_recursive($dir)
{
	$files = scandir($dir);
	
	foreach($files as $file)
	{
		if('.' == $file || '..' == $file)
			continue;
		
		$path	=	$dir . DIRECTORY_SEPARATOR . $file;
		
		if(is_dir($path))
			parse_dir_recursive($path);
		else if(preg_match("#\.mkv$#ims", $file))
			extract_subtitle($path);
	}
}

function extract_subtitle($file)
{
	pecho("extract_subtitle($file)");
	
	$found_language	=	'';
	
	$track_id	=	find_track_id($file, $found_language);

	if(false === $track_id)
	{
		pecho("no subtitle found!\n");
		return;
	}

	pecho("Found track $track_id, language $found_language");

	extract_subtitle_track($file, $track_id);
}

die("\nOK.\n");

function find_track_id($file, &$found_language = null)
{
	$cmd	=	"mkvmerge -I $file";
	
	$output	=	array();
	
	$ret	=	0;
	
	exec($cmd, $output, $ret);
	
	if($ret)
		die("command $cmd failed: $ret (" . implode("\n", $output) . ")");
	
	$subtitle_tracks	=	array();
	
	foreach($output as $line)
	{
		if(preg_match("#Track ID (?P<id>\d+)\:\s*subtitles(:?.*?)language\:(?P<lang>[a-z]+)#ims", $line, $m))
		{
			$subtitle_tracks[$m['id']]	=	$m['lang'];
			
			//daca suntem norocosi si avem subtitrare in limba romana
			if($m['lang'] == 'rum' || $m['lang'] == 'rom')
			{
				$found_language	=	$m['lang'];
				return	$m['id'];
			}
		}
	}
	
	if(!$subtitle_tracks)
	{
		pecho("no subtitle tracks found!\n");
		return;
	}
	
	//cautam subtitrarea in limba engleza
	foreach($subtitle_tracks as $id => $lang)
	{
		if('eng' === $lang)
		{
			$found_language	=	$lang;
			return $id;
		}
	}
	
	//returnam prima subtitrare gasita
	$found_language	=	reset($subtitle_tracks);
	
	return reset(keys($subtitle_tracks));
}

function extract_subtitle_track($file, $track_id)
{
	$subtitle_path	=	preg_replace("#\.mkv#ims", '.srt', $file);
	
	if(is_file($subtitle_path))
	{
		unlink($subtitle_path);
		//pecho("Subtitle already exists.");
		//return;
	}
	
	$cmd	=	'mkvextract tracks "' . $file . '" ' . $track_id . ':"' . $subtitle_path . '"';
	
	pecho($cmd);
	
	exec($cmd, $output, $ret);
	
	if($ret)
		die("command $cmd failed: $ret (" . implode("\n", $output) . ")");
}

function pecho($s)
{
	print_r($s);
	echo "\n";
}