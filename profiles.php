<?php

// Convert profiles to N-Triples

//----------------------------------------------------------------------------------------
// Make a URI play nice with triple store
function nice_uri($uri)
{
	// known errors 
	
	// <https://doi.org/10.3398/064.079.0101> <http://schema.org/citation> <https://doi.org/10.1899/0887-3593(2005)024\%5B0508:biomeu\%5D2.0.co;2> .
	// 10.1899/0887-3593(2005)024\[0508:BIOMEU\]2.0.CO;2
	$uri = str_replace('\[', '[', $uri);
	$uri = str_replace('\]', ']', $uri);
	
	// <https://doi.org/10.1139/gen-2015-0168> <http://schema.org/citation> <https://doi.org/10.1111/j.1471-8286.2007.01678.x. pmid:> .
	$uri = preg_replace('/\.\s+pmid:/', '', $uri);	
	
	// <https://doi.org/10.1007/s10531-023-02686-9> <http://schema.org/citation> <https://doi.org/10.1139/gen-2019-0226%m32502367> .
	$uri = preg_replace('/%m\d+/', '', $uri);
	
	// <https://doi.org/10.1071/is24059> <http://schema.org/citation> <https://doi.org/10.11646/zoosymposia. 2.1.21> .
	$uri = preg_replace('/\s+/', '', $uri);


	$uri = str_replace('[', urlencode('['), $uri);
	$uri = str_replace(']', urlencode(']'), $uri);
	$uri = str_replace('<', urlencode('<'), $uri);
	$uri = str_replace('>', urlencode('>'), $uri);
	
	$uri = str_replace('|', urlencode('|'), $uri);
	
	// cray cray
	// 3234R
	// http://https://www.indexfungorum.org/Names/NamesRecord.asp?RecordID=560001
	$uri = str_replace('http://https://', 'http://', $uri);

	return $uri;
}

//----------------------------------------------------------------------------------------
// Clean up text to play nice with triple stire
function nice_literal($text)
{
	// escape backslashes 
	$text = str_replace('\\', '\\\\', $text);
	
	// remove HTML/XML tags
	$text = strip_tags($text);
	
	// replace newlines
	$text = preg_replace('/\R/u', ' ', $text);	
	
	// clean up spaces
	$text = preg_replace('/\s\s+/', ' ', $text);	
	
	
	// escape double quotes
	$text = str_replace('"', '\"', $text);
	
	return $text;
}

//----------------------------------------------------------------------------------------
// Dump array of triples (each triple is itself an array)
function dump_triples($triples)
{
	$output = '';

	foreach ($triples as $t)
	{	
		$row = array();
		foreach ($t as $element)
		{
			// Is this a URI?
			//
			// geo: is here for the geotag annotations, whose body is an RFC 5870 URI rather
			// than a minted http one. Without it the body is emitted bare, which is not a
			// valid N-Triples object at all and takes the rest of the file down with it.
			if (preg_match('/^(https?|urn|geo):/', $element))
			{
				$element = '<' . $element . '>';
			}
		
			$row[] = $element;
		}
	
		$output .= join(" ", $row) . " .\n";
	}		
	
	return $output;
}

//----------------------------------------------------------------------------------------
// http://stackoverflow.com/a/5996888/9684
function translate_quoted($string) {
  $search  = array("\\t", "\\n", "\\r");
  $replace = array( "\t",  "\n",  "\r");
  return str_replace($search, $replace, $string);
}

//----------------------------------------------------------------------------------------

$triples = [];

$version = 21734026;

$filename = dirname(__FILE__) . '/data/' . $version . '/bionomia-public-profiles.csv';

$headings = array();

$row_count = 0;

$file = @fopen($filename, "r") or die("couldn't open $filename");
		
$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$row = fgetcsv(
		$file_handle, 
		0, 
		translate_quoted(','),
		translate_quoted('"') 
		);
		
	$go = is_array($row);
	
	if ($go)
	{
		if ($row_count == 0)
		{
			$headings = $row;		
		}
		else
		{
			$obj = new stdclass;
		
			foreach ($row as $k => $v)
			{
				if ($v != '')
				{
					$obj->{$headings[$k]} = $v;
				}
			}
			
			//print_r($obj);
			
			$profile = new stdclass;
			$profile->id = $obj->URL;
			$profile->sameAs = [];
			
			$name_parts = [];
			
			if (isset($obj->Given))
			{
				$profile->givenName = $obj->Given;
				$name_parts[] = $obj->Given;
			}

			if (isset($obj->Family))
			{
				$profile->familyName = $obj->Family;
				$name_parts[] = $obj->Family;
			}
			
			if (isset($obj->LabelName))
			{
				$profile->name = $obj->LabelName;
			}
			else
			{				
				if (count($name_parts) == 2)
				{
					$profile->name = join(" ", $name_parts);
				}
			}
			
			if (isset($obj->OtherNames))
			{
				$profile->alternateName = explode("|", $obj->OtherNames);
			}

			if (isset($obj->Keywords))
			{
				$profile->keywords = explode("|", $obj->Keywords);
			}

			if (isset($obj->wikidata))
			{
				$profile->sameAs[] = 'http://www.wikidata.org/entity/' . $obj->wikidata;
			}

			if (isset($obj->ORCID))
			{
				$profile->sameAs[] = 'https://orcid.org/' . $obj->ORCID;
			}

			//print_r($profile);
			
			// type
			$s = $profile->id;
			$p = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
			$o = 'https://schema.org/Person';		
			$triples[] = [$s, $p, $o];	

			if (isset($profile->name))
			{
				$s = $profile->id;
				$p = 'https://schema.org/name';		
				$o = '"' . nice_literal($profile->name)	. '"';
				$triples[] = [$s, $p, $o];	
			}

			if (isset($profile->givenName))
			{
				$s = $profile->id;
				$p = 'https://schema.org/givenName';		
				$o = '"' . nice_literal($profile->givenName)	. '"';
				$triples[] = [$s, $p, $o];	
			}

			if (isset($profile->familyName))
			{
				$s = $profile->id;
				$p = 'https://schema.org/familyName';		
				$o = '"' . nice_literal($profile->familyName)	. '"';
				$triples[] = [$s, $p, $o];	
			}

			if (isset($profile->alternateName))
			{			
				foreach ($profile->alternateName as $name)
				{
					$s = $profile->id;
					$p = 'https://schema.org/alternateName';		
					$o = '"' . nice_literal($name) . '"';
					$triples[] = [$s, $p, $o];	
				}
			}

			if (isset($profile->keywords))
			{			
				foreach ($profile->keywords as $keyword)
				{
					$s = $profile->id;
					$p = 'https://schema.org/keywords';		
					$o = '"' . nice_literal($keyword) . '"';
					$triples[] = [$s, $p, $o];	
				}
			}
				
			// external identifiers
			foreach ($profile->sameAs as $sameAs)
			{
				$s = $profile->id;
				$p = 'https://schema.org/sameAs';		
				$o = nice_uri($sameAs);
				$triples[] = [$s, $p, $o];	
			}

		
			
		}
	}	
	$row_count++;
}

$output = dump_triples($triples);			
echo $output . "\n";


?>
