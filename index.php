<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>grep #sailfishos-porters archive</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="./bootstrap/css/bootstrap.css" rel="stylesheet">
    <style>
    body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
    }
    </style>
    <link href="./bootstrap/css/bootstrap-responsive.css" rel="stylesheet">

</head>

<body>

    <div class="container">

    <h1>#sailfishos-porters archive</h1>
    <p>Use this to search the IRC logs of #sailfishos-porters</p>

    <form action="index.php" method="post">
        <div class="form-group">
            <input type="text" class="form-control" id="inputSearchText" name="inputSearchText" placeholder="Enter search text">
        </div>
        <button type="submit" class="btn btn-primary" name="submit" value="submit">Search</button>
    </form>
    </div> <!-- /container -->

    
    <!--Main content-->
    <div class="container">
<?php

    if (isset($_GET['log'])) {
        //Load a file and parse for display
        $logfile = file_get_contents("./sailfishIRC-localArchiver/archive/".$_GET['log']);
        
        $output = "";
        $count = 1;
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $logfile) as $line){
            // Add bookmarks to each line
            $line = '<span id="line'.$count.'">'.htmlspecialchars($line).'</span>';
            $output = $output.$line.'<br>';
            $count = $count + 1;
        }
        echo "<pre>".$output."</pre>";
    } else if(isset($_POST['submit'])){
        //Show the search results
        $inputSearchText=trim($_POST["inputSearchText"]);
        
        $instance = new Grep();
        $params = array();
        $params["recursive"] = false;
        $params["include"] = array("*.txt");
        $params["exclude"] = array();
        $params["pattern"] = $inputSearchText;
        echo $instance($params);
    }


?>
    </div>
    
    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="./bootstrap/js/jquery.js"></script>
    <script src="./bootstrap/js/bootstrap-transition.js"></script>
    <script src="./bootstrap/js/bootstrap-alert.js"></script>
    <script src="./bootstrap/js/bootstrap-modal.js"></script>
    <script src="./bootstrap/js/bootstrap-dropdown.js"></script>
    <script src="./bootstrap/js/bootstrap-scrollspy.js"></script>
    <script src="./bootstrap/js/bootstrap-tab.js"></script>
    <script src="./bootstrap/js/bootstrap-tooltip.js"></script>
    <script src="./bootstrap/js/bootstrap-popover.js"></script>
    <script src="./bootstrap/js/bootstrap-button.js"></script>
    <script src="./bootstrap/js/bootstrap-collapse.js"></script>
    <script src="./bootstrap/js/bootstrap-carousel.js"></script>
    <script src="./bootstrap/js/bootstrap-typeahead.js"></script>

</body>
</html>

<!-- Grep script -->

<?php
/**
* Grep (UNIX command) all files for search text recursively in current directory
*
* Usage:
*     $instance = new Grep();
*     $params = array();
*     echo $instance($params);
*
* @author  Zion Ng <zion@intzone.com>
* @link    [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/Grep
* @since   2012-11-23T21:30+08:00
*/
class Grep
{
    /**
    * __invoke
    *
    * @params array $params Key-value pairs with the following keys:
    *         'exclude'   array   Files to exclude. Can include wildcard, eg. *.jpg
    *         'include'   array   Files to include. Can include wildcard, eg. *.ph*
    *         'pattern'   string  Regular expression pattern to use for search
    *         'recursive' boolean DEFAULT=true. Whether to search thru subdirectories
    *                             recursively
    * @return string Output from 'grep' shell command sorted in ascending order
    * @throws RuntimeException         If 'grep' command is not available as a shell command
    * @throws InvalidArgumentException If $pattern is empty, or $exclude/$include are not arrays
    */
    public function __invoke(array $params = array())
    {
        // Check if 'grep' is available as a shell command
        $output = array();
        $returnValue = null;
        exec('grep', $output, $returnValue);
        if ($returnValue < 0) {
            throw new RuntimeException("'grep' is not a valid shell command");
        }

        // Ensure all keys are set before extracting to prevent notices
        $params = array_merge(
            array(
                'exclude' => array(),
                'include' => array(),
                'pattern' => '',
                'recursive' => true,
            ),
            $params
        );
        extract($params);

        // Check parameters
        if (empty($pattern)) {
            throw new InvalidArgumentException("Parameter 'pattern' must be a non-empty string");
        }
        if (!is_array($exclude)) {
            throw new InvalidArgumentException("Parameter 'exclude' must be an array");
        }
        if (!is_array($include)) {
            throw new InvalidArgumentException("Parameter 'include' must be an array");
        }

        // Collate arguments
        $recursiveArg = ($recursive ? '-r' : '');

        $excludeArgs = '';
        foreach ($exclude as $file) {
            $excludeArgs .= '--exclude=' . $file . ' ';
        }

        $includeArgs = '';
        foreach ($include as $file) {
            $includeArgs .= '--include=' . $file . ' ';
        }

        // Run shell command
        $command = "grep -n {$recursiveArg} {$excludeArgs} {$includeArgs} ".escapeshellarg($pattern)." ./sailfishIRC-localArchiver/archive/* | sort";
        //echo $command;
        $result = shell_exec($command);

        $output = "";
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $result) as $line){
            // do stuff with $line
            $line = str_replace("./sailfishIRC-localArchiver/archive/", "", $line);
            $line2 = '<a href="index.php?log='.substr($line, 0, 14).'#line'.get_string_between($line,":", ":").'">'.substr($line, 0, strpos($line, " "))."</a>"." ".htmlspecialchars(substr($line, strpos($line, " ") + 1));
            $output = $line2."<br>".$output;
        } 

        // Process results
        if (empty($output)) {
            $output = 'No matches found';
        } else {
            $output = '<pre>'.$output.'</pre>';
        }

        return $output;
    } // end function __invoke

}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

?>

