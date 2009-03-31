<?PHP // $Id$
      // This function fetches user pictures from the data directory
      // Syntax:   pix.php/userid/f1.jpg or pix.php/userid/f2.jpg
      //     OR:   ?file=userid/f1.jpg or ?file=userid/f2.jpg

    $nomoodlecookie = true;     // Because it interferes with caching

    require_once('../config.php');
    require_once($CFG->libdir.'/filelib.php');

    // disable moodle specific debug messages
    disable_debugging();

    $relativepath = get_file_argument('pix.php');

    $args = explode('/', trim($relativepath, '/'));

    if (count($args) == 2) {
        $userid   = (integer)$args[0];
        $image    = $args[1];


	$userdir = make_user_directory($userid, false);

	if ($xoicon = get_user_preferences('xoicon', NULL, $userid)) {
	  if (preg_match('/#([A-F0-9]{1,6}),#([A-F0-9]{1,6})/', $xoicon, $matches)) {
	    $fillcolor   = $matches[2];
	    $strokecolor = $matches[1];
	    $imgid = "$fillcolor-$strokecolor";

	    // fixup the requested path from jpg to png
	    $image = preg_replace('/\.jpg$/', '.png', $image);
	    
	    $svg_fpath = "$userdir/xoicon-$imgid.svg";
	    if (!file_exists($svg_fpath)) {
	      // create appropriate svg file, and the f1 / f2 jpgs...
	      ob_start();
	      include(dirname(__FILE__) . '/xopix.php');
	      $svg_xml = ob_get_clean();

	      // should do this in $CFG->dataroot.'/temp/'
	      // but this is strangely racey with send_file below
	      $svg_fpath_tmp = tempnam($userdir, 'tmp-svg-');
	      $f1_fpath_tmp  = tempnam($userdir, 'tmp-f1-');
	      $f2_fpath_tmp  = tempnam($userdir, 'tmp-f2-');

	      // do it atomically
	      // note: We say (exec() || true) because we want to
	      //       avoid output to the html stream, and yet we want
	      //       the exit code. exec() will return the last line of
	      //       STDOUT, which is not very useful.
	      file_put_contents($svg_fpath_tmp, $svg_xml)
		&& ( (exec("/usr/bin/rsvg-convert -f png -w 100 -h 100 -o $f1_fpath_tmp $svg_fpath_tmp", $output, $exitval) || true)
		    && $exitval === 0 )
		&& ( (exec("/usr/bin/rsvg-convert -f png -w 35  -h 35  -o $f2_fpath_tmp $svg_fpath_tmp", $output, $exitval) || true)
		    && $exitval === 0)
		&& chmod($f1_fpath_tmp,  0755)
		&& chmod($f2_fpath_tmp,  0755)
		&& chmod($svg_fpath_tmp, 0755)
		&& rename($f1_fpath_tmp, "$userdir/f1.png")
		&& rename($f2_fpath_tmp, "$userdir/f2.png")
		&& rename($svg_fpath_tmp, $svg_fpath);
	      
	    }

	  }
	}

        $pathname = $userdir . "/$image";
        if (file_exists($pathname) and !is_dir($pathname)) {
            send_file($pathname, $image);
        }
    }

    // picture was deleted - use default instead
    redirect($CFG->pixpath.'/u/f1.png');
?>
