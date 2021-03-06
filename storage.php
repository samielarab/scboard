<?

/*no action*/ if (!$_GET['action']) {
	die("no action specified");
/*text recognition*/ }elseif (($_GET['action'] == "recognise") && ($_POST['img'])) {
	//echo shell_exec("bash -c ".escapeshellarg("echo -n ".escapeshellarg($_POST['img'])." | base64 -d | convert -compress none png:- pnm:- | gocr -i -"));
	echo shell_exec("bash -c ".escapeshellarg("echo -n ".escapeshellarg($_POST['img'])." | base64 -d | convert -compress none png:- pnm:- | ocrad"));
/*save*/}elseif ($_GET['action'] == "save") {
	if (($_POST['file']) && ($_POST['name']) && ($_POST['class'])) {
		$name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['name']);
		$class = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['class']);
		mkdir("./stored/$class/$name-".time(), 0777, false);
		$pngstrings = explode(" ", $_POST['file']);
		foreach ($pngstrings as $i => $pngfile){
			file_put_contents("./stored/$class/$name-".time()."/$name-".time()."-$i.png", base64_decode($pngfile));
		}
		echo "Datei $name der Klasse $class gespeichert.";
	} else {
		echo "Bitte Dateinamen angeben.";
	}
	
/*list*/} elseif ($_GET['action'] == "list") {
	if ($_POST['class']) {
		$dirEscaped = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['class']);
		if (file_exists("./stored/".$dirEscaped)) {
			$files = array_slice(scandir("./stored/".$dirEscaped), 2);
		} else {
			die("nothing");
		}
	} else {
		$dirs = scandir("./stored/");
		foreach ($dirs as $dir) {
			if (($dir!=".") && ($dir!="..")) {
				foreach (scandir("./stored/".$dir) as $flipchart) {
					if (($flipchart!=".") && ($flipchart!="..")) {
						$files[]=$dir."#".$flipchart;
					}
				}
			}
		}
	}
	if (count($files)==0) {
		echo "nothing";
	} else {
		echo implode(" ", $files);
	}
/*load*/} elseif (($_GET['action']=='getfiles') && ($_POST['class']) && ($_POST['name']) && ($_POST['date']) && ($_POST['width']) && ($_POST['height'])) {
	$class = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['class']);
	$name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['name']);
	$date = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['date']);
	$width = preg_replace('/[^0-9]/', '', $_POST['width']);
	$height = preg_replace('/[^0-9]/', '', $_POST['height']);
	if (file_exists("./stored/$class/$name-$date")) {
		$images = array_slice(scandir("./stored/$class/$name-$date"), 2);
		foreach ($images as $image) {
			//echo base64_encode(file_get_contents("./stored/$class/$name-$date/$image"))." ";
			echo shell_exec("bash -c \"convert -resize $width".'x'."$height ./stored/$class/$name-$date/$image - | base64 \"")." ";
		}
	} else {
		echo "no ./stored/$class/$name-$date";
	}
/*list of classes*/} elseif ($_GET['action'] == "classes") {
	echo implode(" ",array_slice(scandir("./stored"), 2));
/*pdf import*/} elseif (($_GET['action'] == "pdfimport") && ($_FILES['importFile']) && ($_POST['width'])) {
	$width = preg_replace('/[^0-9]/', '', $_POST['width']);
	function sendthrough ($command, $input) {
		$descriptorspec = array(0 => array("pipe", "r"),1 => array("pipe", "w"),2 => array("pipe", "w"));
		$process = proc_open($command, $descriptorspec, $pipes);
		fwrite($pipes[0], $input);
		fclose($pipes[0]);
		$returnValue = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$closing = proc_close($process);
		return $returnValue;
	}
	$file = file_get_contents($_FILES['importFile']['tmp_name']);
	$numberOfPages = sendthrough ('pdf2ps - - | grep showpage | wc -l' , $file); // $numberOfPages = sendthrough ('pdfinfo - | grep -x Pages:.* | tail -c +17 | tr -d "\n"' , $file); <-- this seems smarter, but it makes everything hang when you feed a non-pdf-file into it
	if ($numberOfPages==0) {
		die("<html><head></head><body><img src=\"./emblem-unreadable.png\" alt=\"\" /></body></html>");
	}
	$output="<html><head></head>";
	for ($i=0;$i<$numberOfPages;$i++) {
		//$output .= sendthrough("convert -density 200x200 -resize ".$width."x pdf:-[$i] png:- | base64 -w 0",$file)." ";
		$output .= "<img src=\"data:image/png;base64,".sendthrough("convert -density 200x200 -resize ".$width."x pdf:-[$i] png:- | base64 -w 0",$file)."\" alt=\"\" />";
	}
	echo $output."</body></html>";
/*load in viewer*/} elseif (($_GET['action']=='view') && ($_POST['class']) && ($_POST['name']) && ($_POST['date'])) {
	$class = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['class']);
	$name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['name']);
	$date = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['date']);
	if (file_exists("./stored/$class/$name-$date")) {
		$images = array_slice(scandir("./stored/$class/$name-$date"), 2);
		foreach ($images as $image) {
			echo "./stored/$class/$name-$date/$image ";
		}
	} else {
		echo "./emblem-unreadable.png";
	}
/*list of classes*/}
?>
